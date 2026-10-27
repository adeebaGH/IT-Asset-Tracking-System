<?php
session_start();
require_once __DIR__ . "/config/database.php";

/* ADMIN AUTHENTICATION */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";

function escapeHtml($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* UPDATE REQUEST STATUS */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["request_id"])
) {

    $request_id = (int) $_POST["request_id"];
    $status = $_POST["status"] ?? "";

    $allowed_statuses = [
        "Pending",
        "Approved",
        "Rejected"
    ];

    if (
        $request_id <= 0 ||
        !in_array($status, $allowed_statuses, true)
    ) {
        $_SESSION["request_flash"] = [
            "type" => "error",
            "message" => "Invalid request or status."
        ];

        header("Location: asset_requests.php");
        exit();
    }

    /* FETCH CURRENT REQUEST */
    $stmt = $conn->prepare("
        SELECT
            employee_id,
            employee_name,
            asset_type,
            status
        FROM asset_requests
        WHERE id = ?
    ");

    if (!$stmt) {
        $_SESSION["request_flash"] = [
            "type" => "error",
            "message" => "Unable to prepare request query."
        ];

        header("Location: asset_requests.php");
        exit();
    }

    $stmt->bind_param("i", $request_id);
    $stmt->execute();

    $request_result = $stmt->get_result();
    $request = $request_result->fetch_assoc();

    $stmt->close();

    if (!$request) {
        $_SESSION["request_flash"] = [
            "type" => "error",
            "message" => "Asset request not found."
        ];

        header("Location: asset_requests.php");
        exit();
    }

    $old_status = $request["status"];

    if ($old_status === $status) {
        $_SESSION["request_flash"] = [
            "type" => "success",
            "message" => "Request status is already " . $status . "."
        ];

        header("Location: asset_requests.php");
        exit();
    }

    /* UPDATE STATUS */
    $update = $conn->prepare("
        UPDATE asset_requests
        SET status = ?
        WHERE id = ?
    ");

    if (!$update) {
        $_SESSION["request_flash"] = [
            "type" => "error",
            "message" => "Unable to prepare status update."
        ];

        header("Location: asset_requests.php");
        exit();
    }

    $update->bind_param(
        "si",
        $status,
        $request_id
    );

    if (!$update->execute()) {
        $update->close();

        $_SESSION["request_flash"] = [
            "type" => "error",
            "message" => "Unable to update request status."
        ];

        header("Location: asset_requests.php");
        exit();
    }

    $update->close();

    /* CREATE EMPLOYEE NOTIFICATION */
    $notification_created = false;

    $title = "Asset Request " . $status;

    if ($status === "Approved") {

        $notification_message =
            "Hello " . $request["employee_name"] .
            ", your request for " . $request["asset_type"] .
            " has been approved. Please check your dashboard for updates.";

    } elseif ($status === "Rejected") {

        $notification_message =
            "Hello " . $request["employee_name"] .
            ", your request for " . $request["asset_type"] .
            " has been rejected. Please contact the admin if you need more information.";

    } else {

        $notification_message =
            "Hello " . $request["employee_name"] .
            ", your request for " . $request["asset_type"] .
            " is now pending review.";
    }

    $notification = $conn->prepare("
        INSERT INTO notifications
        (employee_id, title, message)
        VALUES (?, ?, ?)
    ");

    if ($notification) {

        $notification->bind_param(
            "sss",
            $request["employee_id"],
            $title,
            $notification_message
        );

        if ($notification->execute()) {
            $notification_created = true;
        }

        $notification->close();
    }

    if ($notification_created) {

        $_SESSION["request_flash"] = [
            "type" => "success",
            "message" => "Request status updated and employee notified successfully."
        ];

    } else {

        $_SESSION["request_flash"] = [
            "type" => "error",
            "message" => "Request status updated, but notification could not be saved."
        ];
    }

    header("Location: asset_requests.php");
    exit();
}

/* ONE-TIME MESSAGE */
if (isset($_SESSION["request_flash"])) {

    $flash = $_SESSION["request_flash"];

    if ($flash["type"] === "success") {
        $success_message = $flash["message"];
    } else {
        $error_message = $flash["message"];
    }

    unset($_SESSION["request_flash"]);
}

/* FETCH ALL REQUESTS */
$result = $conn->query("
    SELECT
        id,
        employee_name,
        employee_id,
        email,
        department,
        asset_type,
        reason,
        status,
        created_at
    FROM asset_requests
    ORDER BY id DESC
");

if (!$result) {
    die("Unable to load asset requests.");
}

/* REQUEST STATISTICS */
$stats = [
    "total" => 0,
    "Pending" => 0,
    "Approved" => 0,
    "Rejected" => 0
];

$stats_result = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM asset_requests
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

<title>Asset Requests | AssetTrack Admin Panel</title>

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
    margin-bottom: 27px;
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
    line-height: 1.6;
}

/* STATISTICS */
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

.stat-icon {
    font-size: 23px;
    margin-bottom: 13px;
}

.stat-label {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 29px;
    font-weight: 800;
    color: #172641;
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
    padding: 24px;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    box-shadow: 0 8px 28px rgba(27,48,85,.06);
}

.panel-heading {
    margin-bottom: 20px;
}

.panel-heading h2 {
    color: #172641;
    font-size: 18px;
    margin-bottom: 6px;
}

.panel-heading p {
    color: #8491a5;
    font-size: 12px;
}

/* SEARCH AND FILTER */
.toolbar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.toolbar input,
.toolbar select {
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 12px;
    background: white;
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
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1250px;
    border-collapse: collapse;
}

th {
    background: #f6f9fd;
    padding: 15px 13px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    color: #64748b;
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
    font-weight: 700;
    color: #172641;
}

.employee-id {
    color: #2563eb;
    font-size: 11px;
    margin-top: 5px;
    font-weight: 700;
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

.pending {
    background: #fef3c7;
    color: #92400e;
}

.approved {
    background: #dcfce7;
    color: #166534;
}

.rejected {
    background: #fee2e2;
    color: #991b1b;
}

/* ACTIONS */
.action-form {
    display: flex;
    gap: 8px;
    align-items: center;
}

.action-form select {
    padding: 9px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 12px;
    background: white;
}

.update-btn {
    background: linear-gradient(135deg,#2563eb,#0891b2);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 13px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.update-btn:hover {
    opacity: .9;
}

.assign-btn {
    display: inline-block;
    padding: 10px 13px;
    background: #16a34a;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.assign-btn:hover {
    background: #15803d;
}

.no-data {
    text-align: center;
    padding: 45px;
    color: #94a3b8;
    font-size: 13px;
}

.footer {
    margin-top: 26px;
    text-align: center;
    font-size: 11px;
    color: #94a3b8;
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
            ADMIN PANEL / REQUEST MANAGEMENT
        </div>

        <h1>Asset Requests</h1>

        <p>
            Review, approve and manage employee asset requests.
        </p>
    </header>

    <!-- STATISTICS -->
    <section class="stats-grid">

        <div class="stat-card total">
            <div class="stat-icon">📋</div>
            <div class="stat-label">Total Requests</div>
            <div class="stat-number">
                <?php echo $stats["total"]; ?>
            </div>
        </div>

        <div class="stat-card pending-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending Requests</div>
            <div class="stat-number">
                <?php echo $stats["Pending"]; ?>
            </div>
        </div>

        <div class="stat-card approved-card">
            <div class="stat-icon">✅</div>
            <div class="stat-label">Approved Requests</div>
            <div class="stat-number">
                <?php echo $stats["Approved"]; ?>
            </div>
        </div>

        <div class="stat-card rejected-card">
            <div class="stat-icon">❌</div>
            <div class="stat-label">Rejected Requests</div>
            <div class="stat-number">
                <?php echo $stats["Rejected"]; ?>
            </div>
        </div>

    </section>

    <!-- ALERTS -->
    <?php if ($success_message !== ""): ?>
        <div class="alert alert-success">
            <?php echo escapeHtml($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ""): ?>
        <div class="alert alert-error">
            <?php echo escapeHtml($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- REQUEST TABLE -->
    <section class="table-panel">

        <div class="panel-heading">
            <h2>Employee Asset Requests</h2>
            <p>Manage submitted requests and their approval status.</p>
        </div>

        <!-- FILTERS -->
        <div class="toolbar">

            <input
                type="text"
                id="searchInput"
                placeholder="Search employee, asset or department..."
                oninput="filterRequests()"
            >

            <select
                id="statusFilter"
                onchange="filterRequests()"
            >
                <option value="all">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
            </select>

        </div>

        <div class="table-wrap">

            <?php if ($result->num_rows > 0): ?>

            <table id="requestsTable">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Asset Requested</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested On</th>
                        <th>Update</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $serial = 1;

                while ($request = $result->fetch_assoc()):
                    $status_class = strtolower($request["status"]);
                ?>

                <tr data-status="<?php echo escapeHtml($request["status"]); ?>">

                    <td><?php echo $serial++; ?></td>

                    <td>
                        <div class="employee-name">
                            <?php echo escapeHtml($request["employee_name"]); ?>
                        </div>

                        <div class="employee-id">
                            <?php echo escapeHtml($request["employee_id"]); ?>
                        </div>
                    </td>

                    <td>
                        <?php echo escapeHtml($request["email"]); ?>
                    </td>

                    <td>
                        <?php echo escapeHtml($request["department"]); ?>
                    </td>

                    <td>
                        <strong>
                            <?php echo escapeHtml($request["asset_type"]); ?>
                        </strong>
                    </td>

                    <td class="reason">
                        <?php echo escapeHtml($request["reason"]); ?>
                    </td>

                    <td>
                        <span class="status <?php echo escapeHtml($status_class); ?>">
                            <?php echo escapeHtml($request["status"]); ?>
                        </span>
                    </td>

                    <td>
                        <?php
                        echo escapeHtml(
                            date(
                                "d M Y, h:i A",
                                strtotime($request["created_at"])
                            )
                        );
                        ?>
                    </td>

                    <td>
                        <form method="POST" class="action-form">

                            <input
                                type="hidden"
                                name="request_id"
                                value="<?php echo (int)$request["id"]; ?>"
                            >

                            <select
                                name="status"
                                aria-label="Request status"
                            >
                                <?php
                                foreach (
                                    ["Pending", "Approved", "Rejected"]
                                    as $option
                                ):
                                ?>
                                    <option
                                        value="<?php echo $option; ?>"
                                        <?php echo $request["status"] === $option
                                            ? "selected" : ""; ?>
                                    >
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button
                                type="submit"
                                class="update-btn"
                            >
                                Update
                            </button>

                        </form>
                    </td>

                    <td>
                        <?php if ($request["status"] === "Approved"): ?>

                            <a
                                href="assign_asset.php?request_id=<?php echo (int)$request["id"]; ?>"
                                class="assign-btn"
                            >
                                Assign Asset
                            </a>

                        <?php else: ?>

                            <span style="color:#94a3b8;">—</span>

                        <?php endif; ?>
                    </td>

                </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

            <?php else: ?>

                <div class="no-data">
                    No asset requests have been submitted yet.
                </div>

            <?php endif; ?>

        </div>

        <div
            id="noResults"
            class="no-data"
            style="display:none;"
        >
            No matching asset requests found.
        </div>

    </section>

    <footer class="footer">
        AssetTrack · IT Asset Tracking System · Admin Panel
    </footer>

</main>

<script>
function filterRequests() {

    const search = document
        .getElementById("searchInput")
        .value.toLowerCase().trim();

    const status = document
        .getElementById("statusFilter")
        .value;

    const table = document.getElementById("requestsTable");
    const empty = document.getElementById("noResults");

    if (!table) {
        return;
    }

    const rows = table.querySelectorAll("tbody tr");

    let visible = 0;

    rows.forEach(function(row) {

        const text = Array.from(
            row.querySelectorAll("td")
        ).slice(1, 8)
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