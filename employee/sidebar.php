<?php
$current_page = basename($_SERVER["SCRIPT_NAME"]);

$employee_name = $_SESSION["employee_name"] ?? "Employee";
$employee_id = $_SESSION["employee_id"] ?? "";

$initial = strtoupper(substr(trim($employee_name), 0, 1));

if ($initial === "") {
    $initial = "E";
}
if (!function_exists("employeeSidebarEscape")) {
    function employeeSidebarEscape($value) {
        return htmlspecialchars(
            (string) ($value ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );
    }
}
?>

<aside class="sidebar">

    <div class="brand">
        Asset<span>Track</span>
    </div>

    <div class="brand-caption">
        EMPLOYEE PORTAL
    </div>

    <div class="menu-title">
        EMPLOYEE MENU
    </div>

    <nav class="nav-links">

        <a href="employee_dashboard.php"
           class="<?= $current_page === 'employee_dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">⌂</span>
            Dashboard
        </a>

        <a href="request_asset.php"
           class="<?= $current_page === 'request_asset.php' ? 'active' : '' ?>">
            <span class="nav-icon">＋</span>
            Request New Asset
        </a>

        <a href="my_requests.php"
           class="<?= $current_page === 'my_requests.php' ? 'active' : '' ?>">
            <span class="nav-icon">▤</span>
            My Requests
        </a>

        <a href="request_return.php"
           class="<?= $current_page === 'request_return.php' ? 'active' : '' ?>">
            <span class="nav-icon">↩</span>
            Request Asset Return
        </a>

        <a href="employee_notifications.php"
           class="<?= $current_page === 'employee_notifications.php' ? 'active' : '' ?>">
            <span class="nav-icon">🔔</span>
            Notifications
        </a>

        <a href="employee_profile.php"
           class="<?= $current_page === 'employee_profile.php' ? 'active' : '' ?>">
            <span class="nav-icon">♙</span>
            My Profile
        </a>

        <a href="home.php"
           class="<?= $current_page === 'home.php' ? 'active' : '' ?>">
            <span class="nav-icon">🌐</span>
            Main Website
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="sidebar-user">

            <div class="sidebar-avatar">
                <?= employeeSidebarEscape($initial) ?>
            </div>

            <div>
                <strong>
                    <?= employeeSidebarEscape($employee_name) ?>
                </strong>

                <small>
                    ID: <?= employeeSidebarEscape($employee_id) ?>
                </small>
            </div>

        </div>

        <a href="employee_logout.php"
           class="logout-btn"
           onclick="return confirm('Are you sure you want to logout?');">
            ⏻ Logout Securely
        </a>

    </div>

</aside>