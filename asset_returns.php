<?php
session_start();
require_once __DIR__ . "/config/database.php";

/* ADMIN AUTHENTICATION */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";

function escapeHtml($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* PROCESS ADMIN DECISION */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $return_id = filter_input(
        INPUT_POST,
        "return_id",
        FILTER_VALIDATE_INT
    );

    $decision = $_POST["decision"] ?? "";

    if (
        !$return_id ||
        !in_array($decision, ["Approved", "Rejected"], true)
    ) {
        $_SESSION["return_flash"] = [
            "type" => "error",
            "message" => "Invalid return request."
        ];

    } else {

        try {
            $conn->begin_transaction();

            /* LOCK PENDING RETURN REQUEST */
            $stmt = $conn->prepare("
                SELECT id, asset_id, employee_id
                FROM asset_returns
                WHERE id = ? AND status = 'Pending'
                FOR UPDATE
            ");

            $stmt->bind_param("i", $return_id);
            $stmt->execute();

            $request = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$request) {
                throw new Exception(
                    "This request has already been processed or does not exist."
                );
            }

            /* APPROVE RETURN */
            if ($decision === "Approved") {

                /* CHECK ASSET STATUS */
                $asset_stmt = $conn->prepare("
                    SELECT id
                    FROM assets
                    WHERE id = ? AND status = 'Assigned'
                    FOR UPDATE
                ");

                $asset_stmt->bind_param(
                    "i",
                    $request["asset_id"]
                );

                $asset_stmt->execute();

                $asset = $asset_stmt
                    ->get_result()
                    ->fetch_assoc();

                $asset_stmt->close();

                if (!$asset) {
                    throw new Exception(
                        "The asset is no longer marked as Assigned. Please check the asset record."
                    );
                }

                /* RELEASE ASSET */
                $update_asset = $conn->prepare("
                    UPDATE assets
                    SET
                        status = 'Available',
                        assigned_to = NULL
                    WHERE id = ?
                    AND status = 'Assigned'
                ");

                $update_asset->bind_param(
                    "i",
                    $request["asset_id"]
                );

                $update_asset->execute();

                if ($update_asset->affected_rows !== 1) {
                    throw new Exception(
                        "Could not update the asset status."
                    );
                }

                $update_asset->close();
            }

            /* UPDATE RETURN REQUEST */
            $update_request = $conn->prepare("
                UPDATE asset_returns
                SET
                    status = ?,
                    processed_date = NOW()
                WHERE id = ?
                AND status = 'Pending'
            ");

            $update_request->bind_param(
                "si",
                $decision,
                $return_id
            );

            $update_request->execute();

            if ($update_request->affected_rows !== 1) {
                throw new Exception(
                    "Could not update the return request."
                );
            }

            $update_request->close();

            $conn->commit();

            $_SESSION["return_flash"] = [
                "type" => "success",
                "message" => "Return request " .
                    strtolower($decision) .
                    " successfully."
            ];

        } catch (Throwable $e) {

            $conn->rollback();

            $_SESSION["return_flash"] = [
                "type" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    header("Location: asset_returns.php");
    exit();
}

/* DISPLAY ONE-TIME MESSAGE */
if (isset($_SESSION["return_flash"])) {

    $flash = $_SESSION["return_flash"];

    if ($flash["type"] === "success") {
        $message = $flash["message"];
    } else {
        $error = $flash["message"];
    }

    unset($_SESSION["return_flash"]);
}

/* FETCH RETURN REQUESTS */
$result = $conn->query("
    SELECT
        r.id,
        r.asset_id,
        r.employee_id,
        r.employee_name,
        r.reason,
        r.status,
        r.request_date,
        a.asset_code,
        a.asset_name
    FROM asset_returns r
    LEFT JOIN assets a
        ON a.id = r.asset_id
    ORDER BY
        CASE
            WHEN r.status = 'Pending' THEN 0
            ELSE 1
        END,
        r.request_date DESC
");

/* RETURN STATISTICS */
$stats = [
    "total" => 0,
    "Pending" => 0,
    "Approved" => 0,
    "Rejected" => 0
];

$stats_result = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM asset_returns
    GROUP BY status
");

if ($stats_result) {

    while ($row = $stats_result->fetch_assoc()) {

        $count = (int)$row["total"];

        $stats["total"] += $count;

        if (isset($stats[$row["status"]])) {
            $stats[$row["status"]] = $count;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Asset Returns | AssetTrack Admin Panel</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    background: #f3f6fc;
    color: #1e293b;
}

/* MAIN CONTENT */
.main {
    margin-left: 255px;
    min-height: 100vh;
    padding: 30px 34px;
}

/* PAGE HEADER */
.header {
    margin-bottom: 28px;
}

.page-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: #2563eb;
    margin-bottom: 10px;
}

.header h1 {
    font-size: 30px;
    color: #14213c;
    margin-bottom: 8px;
}

.header p {
    color: #64748b;
    font-size: 13px;
    line-height: 1.7;
}

/* SUMMARY CARDS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 17px;
    margin-bottom: 26px;
}

.stat-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 8px 25px rgba(27,48,85,.05);
}

.stat-card.total {
    border-top: 3px solid #2563eb;
}

.stat-card.pending-card {
    border-top: 3px solid #f59e0b;
}

.stat-card.approved-card {
    border-top: 3px solid #22c55e;
}

.stat-card.rejected-card {
    border-top: 3px solid #ef4444;
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 13px;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 29px;
    color: #172641;
    font-weight: 800;
}

/* ALERTS */
.alert {
    padding: 15px 18px;
    border-radius: 10px;
    margin-bottom: 22px;
    font-size: 13px;
    font-weight: 600;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border-left: 4px solid #22c55e;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

/* TABLE PANEL */
.table-panel {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 8px 28px rgba(27,48,85,.06);
}

.panel-heading {
    margin-bottom: 22px;
}

.panel-heading h2 {
    font-size: 18px;
    color: #172641;
    margin-bottom: 7px;
}

.panel-heading p {
    font-size: 12px;
    color: #8491a5;
}

/* FILTER TOOLBAR */
.toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.toolbar input,
.toolbar select {
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    background: white;
    font-size: 12px;
    outline: none;
}

.toolbar input {
    flex: 1;
    min-width: 200px;
}

.toolbar select {
    min-width: 160px;
}

.toolbar input:focus,
.toolbar select:focus {
    border-color: #2563eb;
}

/* TABLE */
.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

th {
    background: #f6f9fd;
    padding: 15px 13px;
    text-align: left;
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    white-space: nowrap;
    border-bottom: 1px solid #e2e8f0;
}

td {
    padding: 15px 13px;
    font-size: 12px;
    color: #475569;
    vertical-align: middle;
    border-bottom: 1px solid #edf1f7;
}

tbody tr:hover {
    background: #f6faff;
}

.employee-name {
    color: #172641;
    font-weight: 700;
}

.employee-id {
    color: #2563eb;
    font-weight: 700;
    font-size: 11px;
}

.asset-code {
    color: #2563eb;
    font-weight: 700;
}

.asset-name {
    color: #172641;
    font-weight: 600;
}

.reason {
    max-width: 240px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

/* STATUS BADGES */
.status {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.Pending {
    background: #fef3c7;
    color: #92400e;
}

.Approved {
    background: #dcfce7;
    color: #166534;
}

.Rejected {
    background: #fee2e2;
    color: #991b1b;
}

/* ACTION BUTTONS */
.action-form {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    padding: 10px 13px;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.approve {
    background: #16a34a;
}

.approve:hover {
    background: #15803d;
}

.reject {
    background: #dc2626;
}

.reject:hover {
    background: #b91c1c;
}

.completed {
    color: #94a3b8;
    font-size: 12px;
    font-weight: 600;
}

/* EMPTY STATE */
.no-data {
    padding: 45px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
}

.footer {
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 26px;
}

/* RESPONSIVE */
@media(max-width:1100px) {
    .stats-grid {
        grid-template-columns: repeat(2,1fr);
    }
}

@media(max-width:850px) {
    .main {
        padding: 25px 20px;
    }
}

@media(max-width:700px) {
    .main {
        margin-left: 0;
        padding: 20px 14px;
    }

    .header h1 {
        font-size: 25px;
    }

    .table-panel {
        padding: 15px;
    }
}

@media(max-width:450px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .action-form {
        flex-wrap: wrap;
    }
}
</style>
</head>

<body>

<!-- SHARED ADMIN SIDEBAR -->
<?php require_once __DIR__ . "/admin/sidebar.php"; ?>

<main class="main">

    <!-- PAGE HEADER -->
    <header class="header">

        <div class="page-label">
            ADMIN PANEL / RETURN MANAGEMENT
        </div>

        <h1>Asset Return Requests</h1>

        <p>
            Review employee return requests and approve or reject
            each return. Approved returns automatically make the
            asset available again.
        </p>

    </header>

    <!-- SUMMARY CARDS -->
    <section class="stats-grid">

        <div class="stat-card total">
            <div class="stat-icon">🔄</div>
            <div class="stat-label">Total Returns</div>
            <div class="stat-number">
                <?php echo $stats["total"]; ?>
            </div>
        </div>

        <div class="stat-card pending-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending Returns</div>
            <div class="stat-number">
                <?php echo $stats["Pending"]; ?>
            </div>
        </div>

        <div class="stat-card approved-card">
            <div class="stat-icon">✅</div>
            <div class="stat-label">Approved Returns</div>
            <div class="stat-number">
                <?php echo $stats["Approved"]; ?>
            </div>
        </div>

        <div class="stat-card rejected-card">
            <div class="stat-icon">❌</div>
            <div class="stat-label">Rejected Returns</div>
            <div class="stat-number">
                <?php echo $stats["Rejected"]; ?>
            </div>
        </div>

    </section>

    <!-- MESSAGES -->
    <?php if ($message !== ""): ?>
        <div class="alert alert-success">
            <?php echo escapeHtml($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error">
            <?php echo escapeHtml($error); ?>
        </div>
    <?php endif; ?>

    <!-- RETURN REQUEST TABLE -->
    <section class="table-panel">

        <div class="panel-heading">
            <h2>Employee Return Requests</h2>
            <p>Review submitted returns and manage approval decisions.</p>
        </div>

        <!-- SEARCH AND FILTER -->
        <div class="toolbar">

            <input
                type="text"
                id="searchInput"
                placeholder="Search employee, asset or reason..."
                oninput="filterReturns()"
            >

            <select
                id="statusFilter"
                onchange="filterReturns()"
            >
                <option value="all">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
            </select>

        </div>

        <div class="table-wrap">

            <?php if ($result && $result->num_rows > 0): ?>

            <table id="returnsTable">

                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Asset Code</th>
                        <th>Asset Name</th>
                        <th>Reason</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($row = $result->fetch_assoc()): ?>

                <tr data-status="<?php echo escapeHtml($row["status"]); ?>">

                    <td class="employee-name">
                        <?php echo escapeHtml($row["employee_name"]); ?>
                    </td>

                    <td class="employee-id">
                        <?php echo escapeHtml($row["employee_id"]); ?>
                    </td>

                    <td class="asset-code">
                        <?php echo escapeHtml(
                            $row["asset_code"] ?? "Unavailable"
                        ); ?>
                    </td>

                    <td class="asset-name">
                        <?php echo escapeHtml(
                            $row["asset_name"] ?? "Unavailable"
                        ); ?>
                    </td>

                    <td class="reason">
                        <?php echo escapeHtml($row["reason"]); ?>
                    </td>

                    <td>
                        <?php
                        if (!empty($row["request_date"])) {
                            echo escapeHtml(
                                date(
                                    "d M Y, h:i A",
                                    strtotime($row["request_date"])
                                )
                            );
                        } else {
                            echo "—";
                        }
                        ?>
                    </td>

                    <td>
                        <span class="status <?php echo escapeHtml($row["status"]); ?>">
                            <?php echo escapeHtml($row["status"]); ?>
                        </span>
                    </td>

                    <td>

                        <?php if ($row["status"] === "Pending"): ?>

                        <form method="POST" class="action-form">

                            <input
                                type="hidden"
                                name="return_id"
                                value="<?php echo (int)$row["id"]; ?>"
                            >

                            <button
                                type="submit"
                                name="decision"
                                value="Approved"
                                class="action-btn approve"
                                onclick="return confirm('Approve this return? The asset will become Available.');"
                            >
                                Approve
                            </button>

                            <button
                                type="submit"
                                name="decision"
                                value="Rejected"
                                class="action-btn reject"
                                onclick="return confirm('Reject this return request?');"
                            >
                                Reject
                            </button>

                        </form>

                        <?php else: ?>

                            <span class="completed">
                                Processed
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

            <?php else: ?>

                <div class="no-data">
                    No asset return requests found.
                </div>

            <?php endif; ?>

        </div>

        <div
            id="noResults"
            class="no-data"
            style="display:none;"
        >
            No matching return requests found.
        </div>

    </section>

    <footer class="footer">
        AssetTrack · IT Asset Tracking System · Admin Panel
    </footer>

</main>

<script>
function filterReturns() {

    const search = document
        .getElementById("searchInput")
        .value.toLowerCase().trim();

    const status = document
        .getElementById("statusFilter")
        .value;

    const table = document.getElementById("returnsTable");
    const empty = document.getElementById("noResults");

    if (!table) {
        return;
    }

    const rows = table.querySelectorAll("tbody tr");

    let visible = 0;

    rows.forEach(function(row) {

        const text = Array.from(
            row.querySelectorAll("td")
        ).slice(0, 6)
         .map(cell => cell.textContent.toLowerCase())
         .join(" ");

        const rowStatus = row.dataset.status;

        const matchesSearch = text.includes(search);

        const matchesStatus =
            status === "all" || rowStatus === status;

        const show = matchesSearch && matchesStatus;

        row.style.display = show ? "" : "none";

        if (show) {
            visible++;
        }
    });

    empty.style.display =
        visible === 0 ? "block" : "none";
}
</script>

</body>
</html>