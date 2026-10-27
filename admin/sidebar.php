<?php
// Reusable Admin Panel Sidebar
$current_page = basename($_SERVER["PHP_SELF"]);
$admin_name = $_SESSION["name"] ?? "Admin";

$menu_items = [
    ["dashboard.php", "▦", "Dashboard"],
    ["assets.php", "💻", "Manage Assets"],
    ["add_asset.php", "＋", "Add Asset"],
    ["employees.php", "👥", "Employees"],
    ["add_employee.php", "＋", "Add Employee"],
    ["asset_requests.php", "📋", "Asset Requests"],
    ["asset_returns.php", "↩", "Asset Returns"],
    ["messages.php", "✉", "Messages"],
    ["reports.php", "📊", "Reports"]
];
?>

<style>
.admin-sidebar {
    width: 255px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, #101a35, #152849);
    color: white;
    padding: 25px 17px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    z-index: 1000;
    font-family: "Segoe UI", Arial, sans-serif;
}

.admin-brand {
    padding: 10px 12px 25px;
    border-bottom: 1px solid rgba(255,255,255,.12);
    margin-bottom: 22px;
}

.admin-brand h2 {
    font-size: 23px;
    color: white;
}

.admin-brand p {
    font-size: 11px;
    letter-spacing: 1.5px;
    color: #9fb4d0;
    margin-top: 6px;
}

.admin-menu-label {
    font-size: 10px;
    color: #8ea3c3;
    letter-spacing: 1.5px;
    margin: 15px 12px;
    font-weight: bold;
}

.admin-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px;
    margin-bottom: 5px;
    border-radius: 10px;
    color: #c0cee2;
    text-decoration: none;
    font-size: 13px;
    transition: .25s;
}

.admin-menu a:hover,
.admin-menu a.active {
    background: rgba(34,211,238,.16);
    color: white;
}

.admin-menu a.active {
    border-left: 3px solid #22d3ee;
}

.admin-bottom {
    margin-top: auto;
    padding-top: 20px;
}

.admin-profile {
    padding: 14px;
    border-radius: 12px;
    background: rgba(255,255,255,.07);
    margin-bottom: 12px;
}

.admin-profile strong {
    display: block;
    font-size: 13px;
}

.admin-profile small {
    display: block;
    color: #a6bad5;
    margin-top: 5px;
}

.admin-logout {
    display: block;
    text-align: center;
    background: rgba(239,68,68,.12);
    border-radius: 10px;
    padding: 12px;
    color: #fecaca;
    text-decoration: none;
    font-size: 13px;
}

.admin-logout:hover {
    background: #dc2626;
    color: white;
}

@media(max-width:700px) {
    .admin-sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }
}
</style>

<aside class="admin-sidebar">
    <div class="admin-brand">
        <h2>▦ AssetTrack</h2>
        <p>ADMIN CONTROL PANEL</p>
    </div>

    <div class="admin-menu-label">MAIN NAVIGATION</div>

    <nav class="admin-menu">
        <?php foreach ($menu_items as $item): ?>
            <a href="<?php echo htmlspecialchars($item[0]); ?>"
               class="<?php echo $current_page === $item[0] ? 'active' : ''; ?>">
                <span><?php echo $item[1]; ?></span>
                <span><?php echo htmlspecialchars($item[2]); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-bottom">
        <div class="admin-profile">
            <strong>
                <?php echo htmlspecialchars($admin_name); ?>
            </strong>
            <small>System Administrator</small>
        </div>

        <a href="logout.php"
           class="admin-logout"
           onclick="return confirm('Are you sure you want to logout?');">
            ↪ Logout Securely
        </a>
    </div>
</aside>