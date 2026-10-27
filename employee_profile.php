<?php
session_start();

if (!isset($_SESSION["employee_id"])) {
    header("Location: employee_login.php");
    exit();
}

require_once __DIR__ . "/config/database.php";

/* EMPLOYEE DETAILS */
$employee_id = (string) $_SESSION["employee_id"];

/* FETCH EMPLOYEE FROM DATABASE */
$stmt = $conn->prepare("
    SELECT
        employee_id,
        name,
        email,
        department,
        created_at
    FROM employees
    WHERE employee_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to load employee profile.");
}

$stmt->bind_param("s", $employee_id);
$stmt->execute();

$result = $stmt->get_result();
$employee = $result->fetch_assoc();

$stmt->close();

/* INVALID EMPLOYEE SESSION */
if (!$employee) {
    session_destroy();
    header("Location: employee_login.php");
    exit();
}

/* ESCAPE HTML */
function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* PROFILE INFORMATION */
$employee_name = $employee["name"];
$employee_email = $employee["email"];
$employee_department = $employee["department"];

$initial = strtoupper(
    substr(trim($employee_name), 0, 1)
);

if ($initial === "") {
    $initial = "E";
}

/* FORMAT ACCOUNT DATE */
$created_at = "Not Available";

if (!empty($employee["created_at"])) {
    $timestamp = strtotime($employee["created_at"]);

    if ($timestamp !== false) {
        $created_at = date("d M Y", $timestamp);
    }
}

/* KEEP SESSION DETAILS UPDATED */
$_SESSION["employee_name"] = $employee_name;
$_SESSION["employee_email"] = $employee_email;
$_SESSION["employee_department"] = $employee_department;
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Profile | AssetTrack</title>

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

/* COMMON EMPLOYEE SIDEBAR */
.sidebar {
    width: 255px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
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
    flex-wrap: wrap;
    gap: 18px;
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

.top-avatar {
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

/* PROFILE LAYOUT */
.content-grid {
    display: grid;
    grid-template-columns: minmax(0,1fr) 270px;
    gap: 22px;
    align-items: start;
}

/* PROFILE CARD */
.profile-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.profile-cover {
    height: 115px;
    border-radius: 13px;
    background: linear-gradient(
        110deg,
        #172d52,
        #2563eb,
        #0891b2
    );
}

.profile-identity {
    display: flex;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 18px;
    margin: -37px 18px 28px;
    position: relative;
}

.large-avatar {
    width: 90px;
    height: 90px;
    background: linear-gradient(
        135deg,
        #2563eb,
        #0891b2
    );
    color: white;
    border: 5px solid white;
    border-radius: 22px;
    display: grid;
    place-items: center;
    font-size: 35px;
    font-weight: 800;
    box-shadow: 0 5px 15px rgba(37,99,235,0.15);
    flex-shrink: 0;
}

.profile-identity h2 {
    color: #172641;
    font-size: 23px;
    margin-bottom: 7px;
}

.profile-identity p {
    color: #64748b;
    font-size: 12px;
}

.section-heading {
    padding-bottom: 18px;
    border-bottom: 1px solid #edf1f7;
    margin-bottom: 22px;
}

.section-heading h3 {
    font-size: 18px;
    color: #172641;
    margin-bottom: 7px;
}

.section-heading p {
    font-size: 12px;
    color: #64748b;
    line-height: 1.7;
}

/* INFORMATION GRID */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 17px;
}

.info-item {
    padding: 20px;
    border: 1px solid #e8edf5;
    background: #f8faff;
    border-radius: 11px;
}

.info-item label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 10px;
}

.info-item strong {
    display: block;
    font-size: 13px;
    color: #172641;
    overflow-wrap: anywhere;
}

.info-icon {
    font-size: 20px;
    margin-bottom: 12px;
}

/* PROFILE ACTIONS */
.profile-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 27px;
}

.primary-btn {
    display: inline-block;
    padding: 13px 20px;
    background: linear-gradient(
        90deg,
        #2563eb,
        #0891b2
    );
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
}

.primary-btn:hover {
    opacity: 0.9;
}

.secondary-btn {
    display: inline-block;
    padding: 13px 20px;
    background: #f1f5f9;
    color: #475569;
    text-decoration: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

/* RIGHT PANEL */
.side-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 15px;
    padding: 23px;
    margin-bottom: 18px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.side-card h3 {
    font-size: 15px;
    color: #172641;
    margin-bottom: 15px;
}

.side-card p {
    font-size: 12px;
    color: #64748b;
    line-height: 1.8;
}

.status-pill {
    display: inline-block;
    padding: 8px 13px;
    background: #dcfce7;
    color: #166534;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    margin-bottom: 13px;
}

.quick-link {
    display: block;
    padding: 13px 14px;
    background: #f8faff;
    border: 1px solid #e8edf5;
    border-radius: 9px;
    margin-bottom: 10px;
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.quick-link:hover {
    background: #eff6ff;
}

.notice-card {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    padding: 20px;
}

.notice-card h3 {
    color: #1d4ed8;
    font-size: 13px;
    margin-bottom: 10px;
}

.notice-card p {
    color: #475569;
    font-size: 12px;
    line-height: 1.8;
}

.footer {
    margin-top: 30px;
    padding: 18px 0;
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
}

/* RESPONSIVE */
@media(max-width:1100px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .side-column {
        display: grid;
        grid-template-columns: repeat(2,minmax(0,1fr));
        gap: 16px;
    }

    .side-card {
        margin-bottom: 0;
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

    .profile-card {
        padding: 17px;
    }

    .info-grid,
    .side-column {
        grid-template-columns: 1fr;
    }

    .profile-identity {
        margin-left: 10px;
        margin-right: 10px;
    }
}
</style>

</head>

<body>

<!-- COMMON EMPLOYEE SIDEBAR -->
<?php require __DIR__ . "/employee/sidebar.php"; ?>

<!-- MAIN CONTENT -->
<main class="main-content">

    <!-- HEADER -->
    <header class="topbar">

        <div class="welcome">

            <div class="page-label">
                EMPLOYEE PORTAL / MY PROFILE
            </div>

            <h1>My Profile</h1>

            <p>
                View your personal information and account details.
            </p>

        </div>

        <div class="profile-badge">

            <div class="top-avatar">
                <?= e($initial) ?>
            </div>

            <div>
                <strong><?= e($employee_name) ?></strong>
                <small><?= e($employee_department) ?></small>
            </div>

        </div>

    </header>

    <!-- BANNER -->
    
    <div class="content-grid">

        <!-- PROFILE DETAILS -->
        <section class="profile-card">

            <div class="profile-cover"></div>

            <div class="profile-identity">

                <div class="large-avatar">
                    <?= e($initial) ?>
                </div>

                <div>
                    <h2><?= e($employee_name) ?></h2>
                    <p><?= e($employee_department) ?></p>
                </div>

            </div>

            <div class="section-heading">

                <h3>Personal Information</h3>

                <p>
                    Your registered employee account details.
                </p>

            </div>

            <div class="info-grid">

                <div class="info-item">

                    <div class="info-icon">🪪</div>

                    <label>Employee ID</label>

                    <strong>
                        <?= e($employee["employee_id"]) ?>
                    </strong>

                </div>

                <div class="info-item">

                    <div class="info-icon">👤</div>

                    <label>Full Name</label>

                    <strong>
                        <?= e($employee_name) ?>
                    </strong>

                </div>

                <div class="info-item">

                    <div class="info-icon">✉️</div>

                    <label>Email Address</label>

                    <strong>
                        <?= e($employee_email) ?>
                    </strong>

                </div>

                <div class="info-item">

                    <div class="info-icon">🏢</div>

                    <label>Department</label>

                    <strong>
                        <?= e($employee_department) ?>
                    </strong>

                </div>

                <div class="info-item">

                    <div class="info-icon">📅</div>

                    <label>Account Created</label>

                    <strong>
                        <?= e($created_at) ?>
                    </strong>

                </div>

            </div>

            <div class="profile-actions">

                <a href="employee_dashboard.php"
                   class="primary-btn">
                    Back to Dashboard
                </a>

                <a href="my_requests.php"
                   class="secondary-btn">
                    View My Requests
                </a>

            </div>

        </section>

        <!-- RIGHT PANEL -->
        <aside class="side-column">

            <div class="side-card">

                <h3>Account Information</h3>

                <span class="status-pill">
                    ✓ Profile Available
                </span>

                <p>
                    Your employee account is registered
                    in the AssetTrack system. You can
                    view your assigned assets and submit
                    asset-related requests.
                </p>

            </div>

            <div class="side-card">

                <h3>Quick Navigation</h3>

                <a href="employee_dashboard.php"
                   class="quick-link">
                    Dashboard
                </a>

                <a href="request_asset.php"
                   class="quick-link">
                    Request New Asset
                </a>

                <a href="my_requests.php"
                   class="quick-link">
                    My Requests
                </a>

                <a href="request_return.php"
                   class="quick-link">
                    Request Asset Return
                </a>

                <a href="employee_notifications.php"
                   class="quick-link">
                    My Notifications
                </a>

            </div>

            <div class="notice-card">

                <h3>Profile Information</h3>

                <p>
                    Your employee information is maintained
                    in the AssetTrack database. Contact
                    the administrator if any details
                    need to be updated.
                </p>

            </div>

        </aside>

    </div>

    <footer class="footer">
        © <?= date("Y") ?> AssetTrack |
        IT Asset Tracking System
    </footer>

</main>

</body>
</html>