<?php
session_start();

if (!isset($_SESSION["employee_id"])) {
    header("Location: employee_login.php");
    exit();
}

require_once __DIR__ . "/config/database.php";

/* EMPLOYEE DETAILS */
$employee_id = (string) $_SESSION["employee_id"];
$employee_name = $_SESSION["employee_name"] ?? "Employee";
$employee_department = $_SESSION["employee_department"] ?? "Employee";

$message = "";
$error = "";
$notifications = [];

$total_count = 0;
$unread_count = 0;
$read_count = 0;

/* ESCAPE HTML */
function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* EMPLOYEE INITIAL */
$initial = strtoupper(substr(trim($employee_name), 0, 1));

if ($initial === "") {
    $initial = "E";
}

/* MARK NOTIFICATION AS READ */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["mark_read"])
) {

    $notification_id = filter_input(
        INPUT_POST,
        "notification_id",
        FILTER_VALIDATE_INT
    );

    if (!$notification_id || $notification_id < 1) {

        $error = "Invalid notification selected.";

    } else {

        $update = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND employee_id = ?
              AND is_read = 0
        ");

        if ($update) {

            $update->bind_param(
                "is",
                $notification_id,
                $employee_id
            );

            if ($update->execute()) {

                if ($update->affected_rows > 0) {
                    $message = "Notification marked as read successfully.";
                } else {
                    $error = "Notification was already read or could not be found.";
                }

            } else {

                $error = "Unable to update notification.";
            }

            $update->close();

        } else {

            $error = "Unable to prepare notification update.";
        }
    }
}

/* NOTIFICATION STATISTICS */
$count_stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END),
            0
        ) AS unread,
        COALESCE(
            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END),
            0
        ) AS read_total
    FROM notifications
    WHERE employee_id = ?
");

if ($count_stmt) {

    $count_stmt->bind_param("s", $employee_id);
    $count_stmt->execute();

    $counts = $count_stmt->get_result()->fetch_assoc();

    $total_count = (int) ($counts["total"] ?? 0);
    $unread_count = (int) ($counts["unread"] ?? 0);
    $read_count = (int) ($counts["read_total"] ?? 0);

    $count_stmt->close();

} else {

    $error = "Unable to load notification statistics.";
}

/* FETCH EMPLOYEE NOTIFICATIONS */
$stmt = $conn->prepare("
    SELECT
        id,
        title,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE employee_id = ?
    ORDER BY created_at DESC, id DESC
");

if ($stmt) {

    $stmt->bind_param("s", $employee_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();

} else {

    $error = "Unable to load your notifications.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Notifications | AssetTrack</title>

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

/* COMMON SIDEBAR STYLING */
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

/* TOP BAR */
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

/* BANNER */
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
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 17px;
    margin-bottom: 27px;
}

.stat-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    padding: 23px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.stat-card:nth-child(1) {
    border-top: 3px solid #2563eb;
}

.stat-card:nth-child(2) {
    border-top: 3px solid #f59e0b;
}

.stat-card:nth-child(3) {
    border-top: 3px solid #10b981;
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

/* NOTIFICATIONS SECTION */
.notifications-section {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    padding: 26px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 23px;
    padding-bottom: 18px;
    border-bottom: 1px solid #edf1f7;
}

.section-header h3 {
    font-size: 19px;
    color: #172641;
    margin-bottom: 7px;
}

.section-header p {
    font-size: 12px;
    color: #64748b;
}

.view-btn {
    display: inline-block;
    padding: 11px 15px;
    background: #eff6ff;
    color: #2563eb;
    text-decoration: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

/* NOTIFICATION CARD */
.notification-card {
    display: flex;
    align-items: flex-start;
    gap: 17px;
    background: #fff;
    border: 1px solid #e8edf5;
    border-left: 4px solid #cbd5e1;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 14px;
    transition: 0.2s;
}

.notification-card.unread {
    background: #f0f7ff;
    border-left-color: #2563eb;
}

.notification-card:hover {
    box-shadow: 0 5px 20px rgba(37,99,235,0.07);
}

.notification-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #eff6ff;
    color: #2563eb;
    font-size: 21px;
    flex-shrink: 0;
}

.notification-body {
    flex: 1;
    min-width: 0;
}

.notification-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.notification-top h4 {
    color: #172641;
    font-size: 14px;
}

.notification-body p {
    color: #64748b;
    font-size: 12px;
    line-height: 1.9;
    overflow-wrap: anywhere;
    margin-bottom: 13px;
}

.notification-date {
    font-size: 11px;
    color: #94a3b8;
}

.status-badge {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.status-badge.unread {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-badge.read {
    background: #dcfce7;
    color: #15803d;
}

.notification-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 13px;
}

.read-btn {
    background: linear-gradient(
        90deg,
        #2563eb,
        #0891b2
    );
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.read-btn:hover {
    opacity: 0.9;
}

/* MESSAGES */
.alert {
    padding: 15px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 12px;
    font-weight: 600;
}

.alert.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 55px 20px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
}

.empty-icon {
    font-size: 43px;
    margin-bottom: 15px;
}

.empty-state h3 {
    color: #172641;
    font-size: 18px;
    margin-bottom: 10px;
}

.empty-state p {
    color: #64748b;
    font-size: 12px;
    line-height: 1.8;
}

.footer {
    text-align: center;
    margin-top: 30px;
    padding: 18px 0;
    font-size: 11px;
    color: #94a3b8;
}

/* RESPONSIVE */
@media(max-width:900px) {
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

    .notifications-section {
        padding: 18px;
    }

    .notification-card {
        padding: 15px;
        gap: 11px;
    }

    .notification-icon {
        width: 36px;
        height: 36px;
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
                EMPLOYEE PORTAL / NOTIFICATIONS
            </div>

            <h1>My Notifications</h1>

            <p>
                Stay updated with your asset requests and activities.
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

        <h2>Stay Informed & Updated</h2>

        <p>
            View important notifications regarding
            asset requests, approvals, assignments,
            and other account activities.
        </p>

    </section>

    <!-- STATISTICS -->
    <section class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">🔔</div>

            <h3><?= $total_count ?></h3>

            <p>Total Notifications</p>

        </div>

        <div class="stat-card">

            <div class="stat-icon">⏳</div>

            <h3><?= $unread_count ?></h3>

            <p>Unread Notifications</p>

        </div>

        <div class="stat-card">

            <div class="stat-icon">✓</div>

            <h3><?= $read_count ?></h3>

            <p>Read Notifications</p>

        </div>

    </section>

    <!-- NOTIFICATIONS LIST -->
    <section class="notifications-section">

        <div class="section-header">

            <div>
                <h3>Recent Notifications</h3>

                <p>
                    Your latest updates and system messages.
                </p>
            </div>

            <a href="employee_dashboard.php"
               class="view-btn">
                Back to Dashboard
            </a>

        </div>

        <?php if ($message !== ""): ?>

            <div class="alert success" role="alert">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <div class="alert error" role="alert">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <?php if (count($notifications) > 0): ?>

            <?php foreach ($notifications as $notification): ?>

                <?php
                $is_read = (int) $notification["is_read"] === 1;

                $formatted_date = $notification["created_at"];

                $timestamp = strtotime(
                    $notification["created_at"]
                );

                if ($timestamp !== false) {
                    $formatted_date = date(
                        "d M Y, h:i A",
                        $timestamp
                    );
                }
                ?>

                <article class="notification-card <?= $is_read ? "" : "unread" ?>">

                    <div class="notification-icon">
                        🔔
                    </div>

                    <div class="notification-body">

                        <div class="notification-top">

                            <h4>
                                <?= e($notification["title"]) ?>
                            </h4>

                            <?php if ($is_read): ?>

                                <span class="status-badge read">
                                    Read
                                </span>

                            <?php else: ?>

                                <span class="status-badge unread">
                                    Unread
                                </span>

                            <?php endif; ?>

                        </div>

                        <p>
                            <?= nl2br(e($notification["message"])) ?>
                        </p>

                        <div class="notification-actions">

                            <span class="notification-date">
                                <?= e($formatted_date) ?>
                            </span>

                            <?php if (!$is_read): ?>

                                <form method="POST"
                                      action="employee_notifications.php">

                                    <input
                                        type="hidden"
                                        name="notification_id"
                                        value="<?= (int) $notification["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="mark_read"
                                        value="1"
                                        class="read-btn"
                                    >
                                        Mark as Read
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">🔔</div>

                <h3>You're All Caught Up!</h3>

                <p>
                    You don't have any notifications yet.
                    New updates will appear here when
                    notifications are created for your account.
                </p>

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