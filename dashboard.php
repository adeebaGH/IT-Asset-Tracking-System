<?php
session_start();
require_once __DIR__ . "/config/database.php";

// Admin authentication
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Dashboard statistics
function getCount($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    return (int)($row["count"] ?? 0);
}

$total = getCount($conn,
    "SELECT COUNT(*) AS count FROM assets");

$available = getCount($conn,
    "SELECT COUNT(*) AS count FROM assets
     WHERE status = 'Available'");

$assigned = getCount($conn,
    "SELECT COUNT(*) AS count FROM assets
     WHERE status = 'Assigned'");

$maintenance = getCount($conn,
    "SELECT COUNT(*) AS count FROM assets
     WHERE status = 'Maintenance'");

$pending_requests = getCount($conn,
    "SELECT COUNT(*) AS count FROM asset_requests
     WHERE status = 'Pending'");

$total_employees = getCount($conn,
    "SELECT COUNT(*) AS count FROM employees");

$value_result = $conn->query(
    "SELECT COALESCE(SUM(price), 0) AS total
     FROM assets"
);
$total_value = $value_result
    ? (float)($value_result->fetch_assoc()["total"] ?? 0)
    : 0;

$recent_assets = $conn->query(
    "SELECT asset_code, asset_name, category,
            status, assigned_to, location
     FROM assets
     ORDER BY id DESC
     LIMIT 5"
);

$admin_name = $_SESSION["name"] ?? "Admin";
$today = date("d M Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Admin Panel | AssetTrack</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --navy: #101a35;
    --cyan: #22d3ee;
    --blue: #2563eb;
    --bg: #f3f6fc;
    --text: #18243b;
    --muted: #78869d;
    --border: #e8edf5;
    --shadow: 0 10px 30px rgba(27,48,85,.07);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    background: var(--bg);
    color: var(--text);
}

a {
    text-decoration: none;
}

/* MAIN ADMIN PANEL */
.main {
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

.eyebrow {
    color: #3183d9;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    margin-bottom: 9px;
}

.topbar h1 {
    font-size: 31px;
    color: #14213c;
    margin-bottom: 7px;
}

.topbar p {
    color: var(--muted);
    font-size: 13px;
}

.top-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.date-box {
    background: white;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 11px;
    color: #586981;
    font-size: 12px;
}

.primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg,#2563eb,#0891b2);
    color: white;
    padding: 12px 17px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    transition: .25s;
}

.primary-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(37,99,235,.2);
}

/* WELCOME BANNER */
.welcome {
    position: relative;
    overflow: hidden;
    background: linear-gradient(
        115deg,#14213f,#173e68 70%,#087e9d
    );
    color: white;
    border-radius: 18px;
    padding: 29px 31px;
    margin-bottom: 29px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    box-shadow: 0 13px 32px rgba(16,42,78,.14);
}

.welcome::after {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 50%;
    right: 40px;
    top: -120px;
    box-shadow:
        0 0 0 30px rgba(255,255,255,.025),
        0 0 0 60px rgba(255,255,255,.025);
}

.welcome h2 {
    font-size: 23px;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.welcome p {
    color: #c7d9ee;
    font-size: 13px;
    line-height: 1.8;
    max-width: 550px;
    position: relative;
    z-index: 1;
}

.welcome-tag {
    position: relative;
    z-index: 1;
    color: #8ff3ff;
    border: 1px solid rgba(143,243,255,.25);
    background: rgba(34,211,238,.13);
    padding: 12px 15px;
    border-radius: 11px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

/* SECTION HEADINGS */
.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 17px;
}

.section-heading h2 {
    font-size: 18px;
    color: #172641;
}

.section-heading p {
    font-size: 11px;
    color: var(--muted);
    margin-top: 5px;
}

/* STATISTICS */
.cards {
    display: grid;
    grid-template-columns: repeat(3,minmax(0,1fr));
    gap: 17px;
    margin-bottom: 29px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 15px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: .25s;
    min-width: 0;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(27,48,85,.12);
}

.card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 17px;
    gap: 10px;
}

.card-label {
    font-size: 12px;
    color: var(--muted);
    font-weight: 600;
}

.card-icon {
    width: 45px;
    height: 45px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    font-size: 22px;
}

.card-number {
    font-size: 30px;
    font-weight: 800;
    color: #172641;
    overflow-wrap: anywhere;
}

.card-note {
    color: #99a4b6;
    font-size: 10px;
    margin-top: 8px;
}

.blue .card-icon { background:#eaf2ff; }
.green .card-icon { background:#e5faf2; }
.purple .card-icon { background:#f1eaff; }
.orange .card-icon { background:#fff5df; }
.cyan .card-icon { background:#e0faff; }
.pink .card-icon { background:#ffe8f3; }

/* DASHBOARD PANELS */
.dashboard-grid {
    display: grid;
    grid-template-columns:
        minmax(270px,.85fr) minmax(0,1.65fr);
    gap: 20px;
    margin-bottom: 25px;
}

.panel {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 23px;
    box-shadow: var(--shadow);
    min-width: 0;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 22px;
}

.panel-header h2 {
    font-size: 16px;
    color: #172641;
    margin-bottom: 6px;
}

.panel-header p {
    font-size: 11px;
    color: var(--muted);
    line-height: 1.5;
}

.panel-pill {
    font-size: 10px;
    color: #1675bd;
    background: #eaf6ff;
    padding: 7px 10px;
    border-radius: 8px;
    font-weight: 700;
}

.chart-container {
    height: 270px;
    position: relative;
}

/* ASSET TABLE */
.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 620px;
}

th {
    background: #f6f9fd;
    color: #728098;
    text-align: left;
    padding: 13px 11px;
    font-size: 10px;
    text-transform: uppercase;
    border-bottom: 1px solid var(--border);
}

td {
    padding: 14px 11px;
    font-size: 11px;
    color: #43516a;
    border-bottom: 1px solid #edf1f7;
}

tbody tr:hover {
    background: #f6faff;
}

.asset-code {
    color: #2477d4;
    font-weight: 800;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.available {
    background: #dcfce7;
    color: #167443;
}

.assigned {
    background: #dbeafe;
    color: #1d4ed8;
}

.maintenance {
    background: #fef3c7;
    color: #a16207;
}

.other-status {
    background: #f1f5f9;
    color: #475569;
}

.empty-state {
    text-align: center;
    padding: 28px;
    color: #8794a8;
}

.view-all {
    color: #2477d4;
    font-size: 11px;
    font-weight: 800;
}

/* QUICK ACTIONS */
.quick-actions {
    background: white;
    padding: 23px;
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(4,minmax(0,1fr));
    gap: 13px;
}

.action-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 13px;
    border: 1px solid var(--border);
    border-radius: 12px;
    color: #263650;
    background: white;
    transition: .22s;
    min-width: 0;
}

.action-card:hover {
    border-color: #a5dff0;
    background: #f3fbff;
    transform: translateY(-3px);
}

.action-icon {
    flex-shrink: 0;
    width: 39px;
    height: 39px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: #eaf5ff;
    font-size: 18px;
}

.action-card strong {
    display: block;
    font-size: 11px;
}

.action-card small {
    display: block;
    color: #8b98ab;
    font-size: 9px;
    margin-top: 5px;
}

.footer {
    text-align: center;
    color: #9aa6b7;
    font-size: 11px;
    margin-top: 26px;
}

/* RESPONSIVE */
@media(max-width:1200px) {
    .cards {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }
}

@media(max-width:700px) {
    .main {
        margin-left: 0;
        padding: 20px 14px;
    }

    .welcome {
        flex-direction: column;
        align-items: flex-start;
        padding: 23px;
    }

    .topbar h1 {
        font-size: 26px;
    }

    .cards {
        gap: 11px;
    }

    .card {
        padding: 16px;
    }

    .card-number {
        font-size: 25px;
    }

    .actions-grid {
        grid-template-columns: 1fr;
    }

    .panel,
    .quick-actions {
        padding: 17px;
    }
}

@media(max-width:380px) {
    .cards {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- REUSABLE ADMIN PANEL SIDEBAR -->
<?php include __DIR__ . "/admin/sidebar.php"; ?>

<main class="main">

<header class="topbar">
    <div>
        <div class="eyebrow">ADMIN CONTROL CENTER</div>
        <h1>Dashboard Overview</h1>
        <p>
            Monitor assets, employee requests
            and inventory performance.
        </p>
    </div>

    <div class="top-actions">
        <div class="date-box">
            📅 <?php echo htmlspecialchars($today); ?>
        </div>

        <a href="add_asset.php" class="primary-btn">
            ＋ Add New Asset
        </a>
    </div>
</header>

<section class="welcome">
    <div>
        <h2>
            Welcome back,
            <?php echo htmlspecialchars($admin_name); ?>! 👋
        </h2>

        <p>
            Here's your IT asset management summary.
            Review your inventory, track assigned
            equipment, and manage pending requests
            from one place.
        </p>
    </div>

    <div class="welcome-tag">
        ● Admin Panel
    </div>
</section>

<div class="section-heading">
    <div>
        <h2>Inventory at a Glance</h2>
        <p>Key figures from your asset database</p>
    </div>
</div>

<section class="cards">

    <div class="card blue">
        <div class="card-top">
            <span class="card-label">Total Assets</span>
            <span class="card-icon">💻</span>
        </div>
        <div class="card-number">
            <?php echo number_format($total); ?>
        </div>
        <div class="card-note">All registered assets</div>
    </div>

    <div class="card green">
        <div class="card-top">
            <span class="card-label">Available Assets</span>
            <span class="card-icon">✓</span>
        </div>
        <div class="card-number">
            <?php echo number_format($available); ?>
        </div>
        <div class="card-note">Ready to be assigned</div>
    </div>

    <div class="card purple">
        <div class="card-top">
            <span class="card-label">Assigned Assets</span>
            <span class="card-icon">👤</span>
        </div>
        <div class="card-number">
            <?php echo number_format($assigned); ?>
        </div>
        <div class="card-note">Currently allocated</div>
    </div>

    <div class="card orange">
        <div class="card-top">
            <span class="card-label">Under Maintenance</span>
            <span class="card-icon">🛠️</span>
        </div>
        <div class="card-number">
            <?php echo number_format($maintenance); ?>
        </div>
        <div class="card-note">Assets needing attention</div>
    </div>

    <div class="card cyan">
        <div class="card-top">
            <span class="card-label">Total Asset Value</span>
            <span class="card-icon">₹</span>
        </div>
        <div class="card-number">
            ₹<?php echo number_format($total_value, 0); ?>
        </div>
        <div class="card-note">Combined purchase value</div>
    </div>

    <div class="card pink">
        <div class="card-top">
            <span class="card-label">Pending Requests</span>
            <span class="card-icon">📋</span>
        </div>
        <div class="card-number">
            <?php echo number_format($pending_requests); ?>
        </div>
        <div class="card-note">Awaiting admin review</div>
    </div>

</section>

<section class="dashboard-grid">

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Asset Status Overview</h2>
                <p>Distribution of registered inventory</p>
            </div>
            <span class="panel-pill">LIVE DATA</span>
        </div>

        <div class="chart-container">
            <canvas id="assetChart"></canvas>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Recently Added Assets</h2>
                <p>Five latest assets in your inventory</p>
            </div>

            <a href="assets.php" class="view-all">
                View all →
            </a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Location</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($recent_assets &&
                          $recent_assets->num_rows > 0): ?>

                    <?php while (
                        $asset = $recent_assets->fetch_assoc()
                    ): ?>

                    <?php
                    $status_text = $asset["status"] ?? "";
                    $status_key = strtolower(trim($status_text));

                    if ($status_key === "available") {
                        $status_class = "available";
                    } elseif ($status_key === "assigned") {
                        $status_class = "assigned";
                    } elseif ($status_key === "maintenance") {
                        $status_class = "maintenance";
                    } else {
                        $status_class = "other-status";
                    }
                    ?>

                    <tr>
                        <td class="asset-code">
                            <?php echo htmlspecialchars(
                                $asset["asset_code"] ?? "—"
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $asset["asset_name"] ?? "—"
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $asset["category"] ?? "—"
                            ); ?>
                        </td>

                        <td>
                            <span class="status <?php
                                echo $status_class;
                            ?>">
                                <?php echo htmlspecialchars(
                                    $status_text ?: "Unknown"
                                ); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo !empty($asset["assigned_to"])
                                ? htmlspecialchars($asset["assigned_to"])
                                : "—"; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $asset["location"] ?? "—"
                            ); ?>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty-state">
                            No assets found.
                            Add your first asset to get started.
                        </td>
                    </tr>

                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</section>

<section class="quick-actions">

    <div class="section-heading">
        <div>
            <h2>Quick Actions</h2>
            <p>Access frequently used admin features</p>
        </div>
    </div>

    <div class="actions-grid">

        <a href="add_asset.php" class="action-card">
            <span class="action-icon">➕</span>
            <span>
                <strong>Add New Asset</strong>
                <small>Register equipment</small>
            </span>
        </a>

        <a href="assets.php" class="action-card">
            <span class="action-icon">💻</span>
            <span>
                <strong>Manage Assets</strong>
                <small>View and update inventory</small>
            </span>
        </a>

        <a href="employees.php" class="action-card">
            <span class="action-icon">👥</span>
            <span>
                <strong>Employees</strong>
                <small>View employee records</small>
            </span>
        </a>

        <a href="add_employee.php" class="action-card">
            <span class="action-icon">🧑‍💼</span>
            <span>
                <strong>Add Employee</strong>
                <small>Create employee record</small>
            </span>
        </a>

        <a href="asset_requests.php" class="action-card">
            <span class="action-icon">📋</span>
            <span>
                <strong>Asset Requests</strong>
                <small>Review pending requests</small>
            </span>
        </a>

        <a href="asset_returns.php" class="action-card">
            <span class="action-icon">↩️</span>
            <span>
                <strong>Asset Returns</strong>
                <small>Process return requests</small>
            </span>
        </a>

        <a href="messages.php" class="action-card">
            <span class="action-icon">✉️</span>
            <span>
                <strong>Messages</strong>
                <small>Review contact messages</small>
            </span>
        </a>

        <a href="reports.php" class="action-card">
            <span class="action-icon">📊</span>
            <span>
                <strong>Reports</strong>
                <small>Review asset information</small>
            </span>
        </a>

    </div>
</section>

<footer class="footer">
    AssetTrack · IT Asset Tracking System · Admin Panel
</footer>

</main>

<script>
const chartCanvas = document.getElementById("assetChart");

if (chartCanvas && typeof Chart !== "undefined") {
    new Chart(chartCanvas, {
        type: "doughnut",
        data: {
            labels: [
                "Available",
                "Assigned",
                "Maintenance"
            ],
            datasets: [{
                data: [
                    <?php echo $available; ?>,
                    <?php echo $assigned; ?>,
                    <?php echo $maintenance; ?>
                ],
                backgroundColor: [
                    "#10b981",
                    "#3b82f6",
                    "#f59e0b"
                ],
                borderColor: "#ffffff",
                borderWidth: 4,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "70%",
            animation: {
                animateRotate: true,
                duration: 900
            },
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        usePointStyle: true,
                        pointStyle: "circle",
                        padding: 19,
                        color: "#64748b",
                        font: {
                            size: 11,
                            family: "Segoe UI"
                        }
                    }
                },
                tooltip: {
                    backgroundColor: "#14213f",
                    padding: 12,
                    cornerRadius: 8
                }
            }
        }
    });
}
</script>

</body>
</html>