<?php
session_start();

/* EMPLOYEE LOGIN CHECK */
if (!isset($_SESSION["employee_id"])) {
    header("Location: employee_login.php");
    exit();
}

require_once __DIR__ . "/config/database.php";

/* EMPLOYEE DETAILS */
$employee_id = (string) $_SESSION["employee_id"];
$employee_name = $_SESSION["employee_name"] ?? "Employee";
$employee_email = $_SESSION["employee_email"] ?? "";
$employee_department = $_SESSION["employee_department"] ?? "Not specified";

/* DASHBOARD VARIABLES */
$assigned_assets = [];
$asset_count = 0;
$total_requests = 0;
$pending_requests = 0;

/* FETCH ASSIGNED ASSETS */
$stmt = $conn->prepare("
    SELECT
        asset_code,
        asset_name,
        category,
        brand,
        model,
        status,
        location
    FROM assets
    WHERE assigned_to = ?
      AND status = 'Assigned'
    ORDER BY id DESC
");

if ($stmt) {
    $stmt->bind_param("s", $employee_name);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $assigned_assets[] = $row;
    }

    $asset_count = count($assigned_assets);
    $stmt->close();
}

/* FETCH EMPLOYEE REQUEST STATISTICS */
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_requests,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending' THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending_requests
    FROM asset_requests
    WHERE employee_id = ?
");

if ($stmt) {
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    $total_requests = (int) ($stats["total_requests"] ?? 0);
    $pending_requests = (int) ($stats["pending_requests"] ?? 0);

    $stmt->close();
}

/* ESCAPE HTML OUTPUT */
function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* PROFILE INITIAL */
$initial = strtoupper(
    substr(trim($employee_name), 0, 1)
);

if ($initial === "") {
    $initial = "E";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Employee Dashboard | AssetTrack</title>

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
    overflow-wrap: anywhere;
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
    transition: 0.2s;
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

/* WELCOME BANNER */
.welcome-banner {
    background: linear-gradient(
        115deg,
        #172d52,
        #1d4ed8,
        #0891b2
    );
    border-radius: 18px;
    padding: 34px;
    color: white;
    margin-bottom: 29px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(37,99,235,0.12);
}

.welcome-banner::after {
    content: "";
    position: absolute;
    width: 230px;
    height: 230px;
    right: -65px;
    top: -95px;
    border: 35px solid rgba(255,255,255,0.08);
    border-radius: 50%;
    pointer-events: none;
}

.banner-tag {
    display: inline-block;
    padding: 7px 12px;
    background: rgba(255,255,255,0.13);
    border: 1px solid rgba(255,255,255,0.17);
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    margin-bottom: 17px;
}

.welcome-banner h2 {
    font-size: 25px;
    margin-bottom: 12px;
    position: relative;
    z-index: 1;
}

.welcome-banner p {
    max-width: 620px;
    font-size: 13px;
    line-height: 1.8;
    color: #e0f2fe;
    position: relative;
    z-index: 1;
}

/* SECTION HEADINGS */
.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin: 30px 0 18px;
}

.section-heading h2 {
    font-size: 19px;
    color: #172641;
    margin-bottom: 6px;
}

.section-heading p {
    color: #8491a5;
    font-size: 12px;
}

.view-link {
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

/* STATISTICS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 18px;
}

.stat-card {
    background: white;
    padding: 24px;
    border: 1px solid #e8edf5;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
    transition: 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-card:nth-child(1) {
    border-top: 3px solid #2563eb;
}

.stat-card:nth-child(2) {
    border-top: 3px solid #0891b2;
}

.stat-card:nth-child(3) {
    border-top: 3px solid #f59e0b;
}

.stat-icon {
    display: grid;
    place-items: center;
    width: 49px;
    height: 49px;
    border-radius: 12px;
    background: #eaf2ff;
    font-size: 23px;
    margin-bottom: 17px;
}

.stat-card h3 {
    color: #172641;
    font-size: 30px;
    margin-bottom: 7px;
}

.stat-card p {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
}

/* QUICK ACTIONS */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 17px;
}

.action-card {
    display: block;
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    padding: 23px;
    text-decoration: none;
    color: #1e293b;
    transition: 0.2s;
}

.action-card:hover {
    border-color: #93c5fd;
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(37,99,235,0.08);
}

.action-icon {
    display: grid;
    place-items: center;
    width: 47px;
    height: 47px;
    border-radius: 12px;
    background: #eff6ff;
    font-size: 23px;
    margin-bottom: 17px;
}

.action-card h3 {
    font-size: 14px;
    color: #172641;
    margin-bottom: 9px;
}

.action-card p {
    color: #64748b;
    font-size: 12px;
    line-height: 1.7;
}

/* ASSET TABLE */
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
    min-width: 700px;
    border-collapse: collapse;
}

th {
    padding: 16px;
    background: #f6f9fd;
    color: #64748b;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}

td {
    padding: 16px;
    border-top: 1px solid #edf1f7;
    color: #475569;
    font-size: 12px;
}

tbody tr:hover {
    background: #f7faff;
}

.asset-name {
    color: #172641;
    font-weight: 700;
}

.asset-code {
    color: #2563eb;
    font-weight: 700;
}

.status-badge {
    display: inline-block;
    padding: 7px 11px;
    background: #dcfce7;
    color: #166534;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

/* EMPTY STATE */
.empty-state {
    padding: 48px 20px;
    text-align: center;
}

.empty-icon {
    font-size: 35px;
    margin-bottom: 15px;
}

.empty-state h3 {
    font-size: 17px;
    color: #334155;
    margin-bottom: 9px;
}

.empty-state p {
    font-size: 12px;
    color: #94a3b8;
    margin-bottom: 19px;
}

.empty-link {
    display: inline-block;
    padding: 11px 18px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

.footer {
    margin-top: 30px;
    padding: 18px 0;
    text-align: center;
    font-size: 11px;
    color: #94a3b8;
}

/* RESPONSIVE */
@media(max-width:1100px) {
    .stats-grid,
    .actions-grid {
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

    .welcome-banner {
        padding: 24px;
    }

    .welcome-banner h2 {
        font-size: 21px;
    }

    .stats-grid,
    .actions-grid {
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

    <!-- HEADER -->
    <header class="topbar">

        <div class="welcome">

            <div class="page-label">
                EMPLOYEE PORTAL / DASHBOARD
            </div>

            <h1>Employee Dashboard</h1>

            <p>
                Manage your assets and requests in one place.
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

    <!-- WELCOME BANNER -->
    <section class="welcome-banner">

        <div class="banner-tag">
            ASSETTRACK EMPLOYEE PORTAL
        </div>

        <h2>
            Welcome back, <?= e($employee_name) ?>! 👋
        </h2>

        <p>
            Stay connected with your workplace assets.
            View assigned equipment, submit new requests,
            track approvals, and manage asset returns
            through your employee dashboard.
        </p>

    </section>

    <!-- STATISTICS -->
    <div class="section-heading">
        <div>
            <h2>Dashboard Overview</h2>
            <p>A summary of your assets and requests.</p>
        </div>
    </div>

    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">💻</div>
            <h3><?= number_format($asset_count) ?></h3>
            <p>Assigned Assets</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <h3><?= number_format($total_requests) ?></h3>
            <p>Total Asset Requests</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <h3><?= number_format($pending_requests) ?></h3>
            <p>Pending Requests</p>
        </div>

    </section>

    <!-- QUICK ACTIONS -->
    <div class="section-heading">
        <div>
            <h2>Quick Actions</h2>
            <p>Access important employee services.</p>
        </div>
    </div>

    <section class="actions-grid">

        <a href="request_asset.php" class="action-card">
            <span class="action-icon">🖥️</span>
            <h3>Request New Asset</h3>
            <p>Submit a request for workplace equipment.</p>
        </a>

        <a href="my_requests.php" class="action-card">
            <span class="action-icon">📑</span>
            <h3>Track My Requests</h3>
            <p>Check the status of submitted requests.</p>
        </a>

        <a href="request_return.php" class="action-card">
            <span class="action-icon">↩️</span>
            <h3>Request Asset Return</h3>
            <p>Request the return of assigned equipment.</p>
        </a>

        <a href="employee_notifications.php" class="action-card">
            <span class="action-icon">🔔</span>
            <h3>My Notifications</h3>
            <p>View important asset and request updates.</p>
        </a>

        <a href="employee_profile.php" class="action-card">
            <span class="action-icon">👤</span>
            <h3>My Profile</h3>
            <p>View your employee account information.</p>
        </a>

        <a href="home.php" class="action-card">
            <span class="action-icon">🌐</span>
            <h3>Main Website</h3>
            <p>Return to the AssetTrack homepage.</p>
        </a>

    </section>

    <!-- ASSIGNED ASSETS -->
    <div class="section-heading">

        <div>
            <h2>My Assigned Assets</h2>
            <p>Equipment currently assigned to you.</p>
        </div>

        <a href="request_return.php" class="view-link">
            Request Return →
        </a>

    </div>

    <section class="table-card">

        <?php if (!empty($assigned_assets)): ?>

            <div class="table-responsive">

                <table>
                    <thead>
                        <tr>
                            <th>Asset Code</th>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Location</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($assigned_assets as $asset): ?>

                        <tr>
                            <td class="asset-code">
                                <?= e($asset["asset_code"]) ?>
                            </td>

                            <td class="asset-name">
                                <?= e($asset["asset_name"]) ?>
                            </td>

                            <td><?= e($asset["category"]) ?></td>
                            <td><?= e($asset["brand"]) ?></td>
                            <td><?= e($asset["model"]) ?></td>

                            <td>
                                <span class="status-badge">
                                    <?= e($asset["status"]) ?>
                                </span>
                            </td>

                            <td><?= e($asset["location"]) ?></td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">💻</div>

                <h3>No Assets Assigned Yet</h3>

                <p>
                    Your assigned equipment will appear here
                    once approved and allocated.
                </p>

                <a href="request_asset.php" class="empty-link">
                    Request an Asset
                </a>

            </div>

        <?php endif; ?>

    </section>

    <footer class="footer">
        © <?= date("Y") ?> AssetTrack | IT Asset Tracking System
    </footer>

</main>

</body>
</html>