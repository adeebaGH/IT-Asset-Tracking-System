<?php
session_start();
require_once __DIR__ . "/config/database.php";

/* ADMIN AUTHENTICATION */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

/* FETCH EMPLOYEE MESSAGES */
$result = $conn->query("
    SELECT *
    FROM contact_messages
    ORDER BY id DESC
");

$total_messages = $result ? $result->num_rows : 0;

function escapeHtml($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Messages | AssetTrack Admin Panel</title>

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
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
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

.back-btn {
    display: inline-block;
    background: linear-gradient(135deg,#2563eb,#0891b2);
    color: white;
    padding: 12px 20px;
    border-radius: 9px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    transition: .2s;
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(37,99,235,.2);
}

/* MESSAGE SUMMARY */
.summary {
    display: flex;
    align-items: center;
    gap: 17px;
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 15px;
    padding: 23px;
    margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(27,48,85,.05);
}

.summary-icon {
    display: grid;
    place-items: center;
    width: 55px;
    height: 55px;
    background: #eaf2ff;
    border-radius: 14px;
    font-size: 25px;
}

.summary-label {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 6px;
}

.summary-number {
    color: #172641;
    font-size: 29px;
    font-weight: 800;
}

.summary-note {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 5px;
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
    margin-bottom: 7px;
}

.panel-heading p {
    color: #8491a5;
    font-size: 12px;
}

/* SEARCH AND FILTER */
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

/* MESSAGE TABLE */
.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1150px;
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
    vertical-align: top;
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
    font-size: 11px;
    color: #2563eb;
    font-weight: 700;
}

.email {
    color: #2563eb;
    text-decoration: none;
}

.email:hover {
    text-decoration: underline;
}

.department {
    display: inline-block;
    background: #eaf2ff;
    color: #2563eb;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.subject {
    font-weight: 700;
    color: #172641;
}

.message-text {
    max-width: 300px;
    min-width: 180px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

/* STATUS BADGES */
.status {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
}

.status.pending {
    background: #fef3c7;
    color: #92400e;
}

.status.read {
    background: #dbeafe;
    color: #1d4ed8;
}

.status.resolved,
.status.replied {
    background: #dcfce7;
    color: #166534;
}

/* EMPTY STATE */
.no-data {
    text-align: center;
    padding: 45px;
    color: #94a3b8;
    font-size: 13px;
}

.no-data h3 {
    color: #334155;
    margin-bottom: 8px;
}

.footer {
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 26px;
}

/* RESPONSIVE */
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

    .back-btn {
        width: 100%;
        text-align: center;
    }
}
</style>
</head>

<body>

<!-- SHARED ADMIN SIDEBAR -->
<?php require_once __DIR__ . "/admin/sidebar.php"; ?>

<!-- MAIN CONTENT -->
<main class="main">

    <!-- PAGE HEADER -->
    <header class="header">

        <div>
            <div class="page-label">
                ADMIN PANEL / COMMUNICATION
            </div>

            <h1>Employee Messages</h1>

            <p>
                View and manage messages submitted by employees.
            </p>
        </div>

        <a href="dashboard.php" class="back-btn">
            Dashboard
        </a>

    </header>

    <!-- MESSAGE SUMMARY -->
    <section class="summary">

        <div class="summary-icon">
            ✉️
        </div>

        <div>
            <div class="summary-label">
                Total Employee Messages
            </div>

            <div class="summary-number">
                <?php echo number_format($total_messages); ?>
            </div>

            <div class="summary-note">
                Messages received through the employee contact form
            </div>
        </div>

    </section>

    <!-- MESSAGES TABLE -->
    <section class="table-panel">

        <div class="panel-heading">
            <h2>Message Inbox</h2>
            <p>Browse employee inquiries, feedback and messages.</p>
        </div>

        <!-- SEARCH AND FILTER -->
        <div class="toolbar">

            <input
                type="text"
                id="searchInput"
                placeholder="Search employee, subject, message or department..."
                oninput="filterMessages()"
            >

            <select
                id="statusFilter"
                onchange="filterMessages()"
            >
                <option value="all">All Statuses</option>
                <?php
                $statuses = [];

                if ($result) {
                    foreach ($result as $messageRow) {
                        $statusValue = trim((string)($messageRow["status"] ?? ""));

                        if ($statusValue !== "") {
                            $statuses[$statusValue] = true;
                        }
                    }

                    $result->data_seek(0);
                }

                foreach (array_keys($statuses) as $statusOption):
                ?>
                    <option value="<?php echo escapeHtml($statusOption); ?>">
                        <?php echo escapeHtml($statusOption); ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </div>

        <div class="table-wrap">

            <?php if ($result && $total_messages > 0): ?>

            <table id="messagesTable">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $serial = 1;

                while ($row = $result->fetch_assoc()):
                    $statusValue = (string)($row["status"] ?? "");
                    $statusClass = strtolower($statusValue);
                ?>

                    <tr data-status="<?php echo escapeHtml($statusValue); ?>">

                        <td>
                            <?php echo $serial++; ?>
                        </td>

                        <td class="employee-name">
                            <?php echo escapeHtml($row["name"]); ?>
                        </td>

                        <td class="employee-id">
                            <?php echo escapeHtml(
                                $row["employee_id"] ?: "-"
                            ); ?>
                        </td>

                        <td>
                            <a
                                class="email"
                                href="mailto:<?php echo escapeHtml($row["email"]); ?>"
                            >
                                <?php echo escapeHtml($row["email"]); ?>
                            </a>
                        </td>

                        <td>
                            <span class="department">
                                <?php echo escapeHtml($row["department"]); ?>
                            </span>
                        </td>

                        <td class="subject">
                            <?php echo escapeHtml($row["subject"]); ?>
                        </td>

                        <td class="message-text">
                            <?php echo nl2br(
                                escapeHtml($row["message"])
                            ); ?>
                        </td>

                        <td>
                            <span class="status <?php echo escapeHtml($statusClass); ?>">
                                <?php echo escapeHtml($statusValue); ?>
                            </span>
                        </td>

                        <td>
                            <?php
                            if (!empty($row["created_at"])) {
                                echo escapeHtml(
                                    date(
                                        "d M Y, h:i A",
                                        strtotime($row["created_at"])
                                    )
                                );
                            } else {
                                echo "—";
                            }
                            ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

            <?php else: ?>

                <div class="no-data">
                    <h3>No Messages Yet</h3>
                    <p>Employee messages will appear here when submitted.</p>
                </div>

            <?php endif; ?>

        </div>

        <div
            id="noResults"
            class="no-data"
            style="display:none;"
        >
            No matching messages found.
        </div>

    </section>

    <footer class="footer">
        AssetTrack · IT Asset Tracking System · Admin Panel
    </footer>

</main>

<script>
function filterMessages() {

    const search = document
        .getElementById("searchInput")
        .value.toLowerCase().trim();

    const status = document
        .getElementById("statusFilter")
        .value;

    const table = document.getElementById("messagesTable");
    const empty = document.getElementById("noResults");

    if (!table) {
        return;
    }

    const rows = table.querySelectorAll("tbody tr");

    let visible = 0;

    rows.forEach(function(row) {

        const text = row.textContent.toLowerCase();
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