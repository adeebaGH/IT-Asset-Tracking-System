<?php
session_start();

if (!isset($_SESSION["employee_id"])) {
    header("Location: employee_login.php");
    exit();
}

require_once __DIR__ . "/config/database.php";

$employee_id = (string) $_SESSION["employee_id"];
$employee_name = $_SESSION["employee_name"] ?? "Employee";
$employee_department = $_SESSION["employee_department"] ?? "Employee";

$requests = [];
$total_requests = 0;
$pending_requests = 0;
$approved_requests = 0;
$rejected_requests = 0;

/* FETCH EMPLOYEE REQUESTS */
$stmt = $conn->prepare("
    SELECT id, asset_type, reason, status, created_at
    FROM asset_requests
    WHERE employee_id = ?
    ORDER BY id DESC
");

if ($stmt) {
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;

        $status = strtolower(trim($row["status"]));

        if ($status === "pending") {
            $pending_requests++;
        } elseif ($status === "approved") {
            $approved_requests++;
        } elseif ($status === "rejected") {
            $rejected_requests++;
        }
    }

    $stmt->close();
}

$total_requests = count($requests);

function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$initial = strtoupper(substr(trim($employee_name), 0, 1));

if ($initial === "") {
    $initial = "E";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Requests | AssetTrack</title>

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

/* SIDEBAR */
.sidebar {
    width: 255px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: linear-gradient(
        180deg,
        #101e38,
        #142c4f 65%,
        #0c203c
    );
    padding: 27px 16px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    z-index: 100;
}

.brand {
    font-size: 25px;
    font-weight: 800;
    color: white;
    padding: 0 13px 9px;
}

.brand span {
    color: #38bdf8;
}

.brand-caption {
    padding: 0 13px;
    font-size: 10px;
    color: #94a3b8;
    letter-spacing: 1px;
    margin-bottom: 38px;
}

.menu-title {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.6px;
    color: #94a3b8;
    padding: 0 13px;
    margin-bottom: 15px;
}

.nav-links {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.nav-links a {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #cbd5e1;
    text-decoration: none;
    padding: 13px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    transition: 0.2s;
}

.nav-links a:hover,
.nav-links a.active {
    background: linear-gradient(
        90deg,
        #2563eb,
        #0891b2
    );
    color: white;
}

.nav-icon {
    width: 23px;
    text-align: center;
    font-size: 18px;
    flex-shrink: 0;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 22px;
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 16px 8px;
    border-top: 1px solid rgba(255,255,255,0.12);
    color: #cbd5e1;
}

.sidebar-avatar {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    background: #2563eb;
    color: white;
    border-radius: 11px;
    font-weight: 800;
    flex-shrink: 0;
}

.sidebar-user strong {
    display: block;
    font-size: 12px;
    color: white;
    overflow-wrap: anywhere;
}

.sidebar-user small {
    display: block;
    font-size: 10px;
    color: #94a3b8;
    margin-top: 5px;
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 13px;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 10px;
    background: rgba(239,68,68,0.12);
    color: #fecaca;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.logout-btn:hover {
    background: #dc2626;
    color: white;
}

/* MAIN CONTENT */
.main-content {
    margin-left: 255px;
    min-height: 100vh;
    padding: 30px 34px;
}

/* HEADER */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 27px;
}

.page-label {
    color: #2563eb;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.5px;
    margin-bottom: 9px;
}

.welcome h1 {
    font-size: 29px;
    color: #14213c;
    margin-bottom: 7px;
}

.welcome p {
    font-size: 13px;
    color: #64748b;
}

.profile-badge {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border: 1px solid #e8edf5;
    padding: 10px 16px;
    border-radius: 13px;
}

.avatar {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    background: linear-gradient(
        135deg,
        #2563eb,
        #0891b2
    );
    color: white;
    font-size: 18px;
    font-weight: 800;
    border-radius: 11px;
}

.profile-badge strong {
    display: block;
    font-size: 12px;
    color: #172641;
    margin-bottom: 5px;
}

.profile-badge small {
    color: #64748b;
    font-size: 11px;
}

/* PAGE BANNER */
.page-banner {
    background: linear-gradient(
        115deg,
        #172d52,
        #1d4ed8,
        #0891b2
    );
    border-radius: 18px;
    padding: 30px 34px;
    color: white;
    margin-bottom: 27px;
    box-shadow: 0 10px 25px rgba(37,99,235,0.12);
}

.banner-tag {
    display: inline-block;
    padding: 7px 12px;
    background: rgba(255,255,255,0.13);
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    margin-bottom: 15px;
}

.page-banner h2 {
    font-size: 23px;
    margin-bottom: 10px;
}

.page-banner p {
    font-size: 12px;
    color: #e0f2fe;
    line-height: 1.8;
}

/* STATS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 22px;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.stat-card:nth-child(1) {
    border-top: 3px solid #2563eb;
}

.stat-card:nth-child(2) {
    border-top: 3px solid #f59e0b;
}

.stat-card:nth-child(3) {
    border-top: 3px solid #16a34a;
}

.stat-card:nth-child(4) {
    border-top: 3px solid #dc2626;
}

.stat-icon {
    font-size: 23px;
    margin-bottom: 12px;
}

.stat-card h3 {
    font-size: 29px;
    color: #172641;
    margin-bottom: 7px;
}

.stat-card p {
    font-size: 12px;
    color: #64748b;
}

/* SECTION */
.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 18px;
}

.section-heading h2 {
    font-size: 19px;
    color: #172641;
    margin-bottom: 6px;
}

.section-heading p {
    font-size: 12px;
    color: #8491a5;
}

.new-request-btn {
    display: inline-block;
    padding: 12px 18px;
    background: linear-gradient(
        90deg,
        #2563eb,
        #0891b2
    );
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

.new-request-btn:hover {
    opacity: 0.9;
}

/* TABLE */
.table-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
}

th {
    background: #f6f9fd;
    color: #64748b;
    padding: 17px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 17px;
    border-top: 1px solid #edf1f7;
    font-size: 12px;
    color: #475569;
    vertical-align: middle;
}

tbody tr:hover {
    background: #f7faff;
}

.asset-type {
    color: #172641;
    font-weight: 700;
}

.reason {
    max-width: 300px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.status {
    display: inline-block;
    padding: 7px 12px;
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

.unknown {
    background: #e2e8f0;
    color: #334155;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 55px 20px;
}

.empty-icon {
    font-size: 38px;
    margin-bottom: 15px;
}

.empty-state h3 {
    font-size: 17px;
    color: #334155;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 12px;
    color: #94a3b8;
    margin-bottom: 20px;
}

.footer {
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 30px;
}

/* RESPONSIVE */
@media(max-width:1100px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0,1fr));
    }
}

@media(max-width:700px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .sidebar-bottom {
        margin-top: 22px;
    }

    .main-content {
        margin-left: 0;
        padding: 20px 14px;
    }

    .welcome h1 {
        font-size: 24px;
    }

    .page-banner {
        padding: 24px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- EMPLOYEE SIDEBAR -->
 
<?php require __DIR__ . "/employee/sidebar.php"; ?>

<!-- MAIN CONTENT -->
<main class="main-content">

    <header class="topbar">

        <div class="welcome">

            <div class="page-label">
                EMPLOYEE PORTAL / MY REQUESTS
            </div>

            <h1>My Asset Requests</h1>

            <p>
                Track and monitor your submitted asset requests.
            </p>

        </div>

        <div class="profile-badge">

            <div class="avatar">
                <?= e($initial) ?>
            </div>

            <div>
                <strong><?= e($employee_name) ?></strong>
                <small><?= e($employee_department) ?></small>
            </div>

        </div>

    </header>

    <!-- BANNER -->
    <section class="page-banner">

        <div class="banner-tag">
            ASSETTRACK EMPLOYEE PORTAL
        </div>

        <h2>Manage Your Asset Requests</h2>

        <p>
            View your submitted requests and stay updated
            on their approval status.
        </p>

    </section>

    <!-- STATISTICS -->
    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <h3><?= $total_requests ?></h3>
            <p>Total Requests</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <h3><?= $pending_requests ?></h3>
            <p>Pending Requests</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <h3><?= $approved_requests ?></h3>
            <p>Approved Requests</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">❌</div>
            <h3><?= $rejected_requests ?></h3>
            <p>Rejected Requests</p>
        </div>

    </section>

    <!-- REQUESTS TABLE -->
    <div class="section-heading">

        <div>
            <h2>Request History</h2>
            <p>All your submitted asset requests.</p>
        </div>

        <a href="request_asset.php" class="new-request-btn">
            + New Asset Request
        </a>

    </div>

    <section class="table-card">

        <?php if (!empty($requests)): ?>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Asset Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested On</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($requests as $index => $request): ?>

                    <?php
                    $status = strtolower(
                        trim($request["status"] ?? "")
                    );

                    if ($status === "approved") {
                        $status_class = "approved";
                    } elseif ($status === "rejected") {
                        $status_class = "rejected";
                    } elseif ($status === "pending") {
                        $status_class = "pending";
                    } else {
                        $status_class = "unknown";
                    }

                    $timestamp = strtotime(
                        $request["created_at"] ?? ""
                    );

                    $date = $timestamp
                        ? date("d M Y, h:i A", $timestamp)
                        : "N/A";
                    ?>

                    <tr>

                        <td>
                            <?= $index + 1 ?>
                        </td>

                        <td class="asset-type">
                            <?= e($request["asset_type"]) ?>
                        </td>

                        <td class="reason">
                            <?= e($request["reason"]) ?>
                        </td>

                        <td>
                            <span class="status <?= e($status_class) ?>">
                                <?= e($request["status"]) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($date) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <?php else: ?>

        <div class="empty-state">

            <div class="empty-icon">📋</div>

            <h3>No Asset Requests Yet</h3>

            <p>
                You haven't submitted any asset requests.
            </p>

            <a href="request_asset.php" class="new-request-btn">
                + Request an Asset
            </a>

        </div>

        <?php endif; ?>

    </section>

    <footer class="footer">
        © <?= date("Y") ?> AssetTrack |
        IT Asset Tracking System
    </footer>

</main>

</body>
</html>