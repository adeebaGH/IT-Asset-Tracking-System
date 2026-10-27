<?php
session_start();

require_once __DIR__ . "/config/database.php";

/* ADMIN AUTHENTICATION */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

/* ASSET REPORT STATISTICS */
$report = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) AS available,
        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) AS assigned,
        SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) AS maintenance,
        COALESCE(SUM(price), 0) AS total_value
    FROM assets
");

if (!$report) {
    die("Unable to load asset report.");
}

$data = $report->fetch_assoc();

$total = (int)$data["total"];
$available = (int)$data["available"];
$assigned = (int)$data["assigned"];
$maintenance = (int)$data["maintenance"];
$total_value = (float)$data["total_value"];

/* CALCULATE PERCENTAGES */
$available_percent = $total > 0
    ? round(($available / $total) * 100, 1)
    : 0;

$assigned_percent = $total > 0
    ? round(($assigned / $total) * 100, 1)
    : 0;

$maintenance_percent = $total > 0
    ? round(($maintenance / $total) * 100, 1)
    : 0;

$utilization = $assigned_percent;

/* REPORT DATE */
$report_date = date("d M Y");
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Asset Reports | AssetTrack Admin Panel</title>

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

/* HEADER */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
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
    line-height: 1.6;
}

/* BUTTONS */
.report-buttons {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.print-btn,
.back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 19px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: .2s;
}

.print-btn {
    background: linear-gradient(135deg,#2563eb,#0891b2);
    color: white;
    border: none;
}

.print-btn:hover {
    box-shadow: 0 7px 18px rgba(37,99,235,.2);
    transform: translateY(-2px);
}

.back-btn {
    background: white;
    color: #2563eb;
    border: 1px solid #cbd5e1;
}

.back-btn:hover {
    background: #eff6ff;
}

/* SUMMARY CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(5,minmax(0,1fr));
    gap: 17px;
    margin-bottom: 26px;
}

.card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 8px 25px rgba(27,48,85,.05);
}

.card:nth-child(1) {
    border-top: 3px solid #2563eb;
}

.card:nth-child(2) {
    border-top: 3px solid #22c55e;
}

.card:nth-child(3) {
    border-top: 3px solid #3b82f6;
}

.card:nth-child(4) {
    border-top: 3px solid #f59e0b;
}

.card:nth-child(5) {
    border-top: 3px solid #0891b2;
}

.card-icon {
    font-size: 24px;
    margin-bottom: 14px;
}

.card-title {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 9px;
}

.card-value {
    font-size: 27px;
    font-weight: 800;
    color: #172641;
    overflow-wrap: anywhere;
}

.card-value.money {
    font-size: 23px;
}

/* REPORT SECTION */
.report-section {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 8px 28px rgba(27,48,85,.06);
}

.section-title {
    font-size: 18px;
    font-weight: 750;
    color: #172641;
    margin-bottom: 7px;
}

.section-subtitle {
    font-size: 12px;
    color: #8491a5;
    margin-bottom: 28px;
}

/* PROGRESS BARS */
.status-row {
    margin-bottom: 25px;
}

.status-row:last-child {
    margin-bottom: 0;
}

.status-info {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    align-items: center;
    margin-bottom: 10px;
}

.status-name {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.status-count {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
}

.progress {
    width: 100%;
    height: 11px;
    background: #e9eef6;
    border-radius: 20px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    border-radius: 20px;
}

.available-bar {
    background: linear-gradient(90deg,#16a34a,#4ade80);
}

.assigned-bar {
    background: linear-gradient(90deg,#2563eb,#38bdf8);
}

.maintenance-bar {
    background: linear-gradient(90deg,#f59e0b,#fbbf24);
}

/* ADDITIONAL INFORMATION */
.report-info {
    display: grid;
    grid-template-columns: repeat(3,minmax(0,1fr));
    gap: 18px;
}

.info-box {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    padding: 23px;
    box-shadow: 0 6px 20px rgba(27,48,85,.04);
}

.info-icon {
    font-size: 23px;
    margin-bottom: 12px;
}

.info-box h3 {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 10px;
}

.info-box .info-value {
    color: #172641;
    font-size: 26px;
    font-weight: 800;
}

.info-note {
    margin-top: 8px;
    font-size: 11px;
    color: #94a3b8;
}

/* REPORT FOOTER */
.report-footer {
    margin-top: 25px;
    padding: 16px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    color: #94a3b8;
    font-size: 11px;
}

.print-heading {
    display: none;
}

/* PRINT DESIGN */
@media print {

    @page {
        size: A4;
        margin: 12mm;
    }

    body {
        background: white !important;
        color: #111827;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .admin-sidebar,
    .sidebar,
    .report-buttons {
        display: none !important;
    }

    .main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100%;
        min-height: auto;
    }

    .print-heading {
        display: block;
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #1e40af;
    }

    .print-heading h2 {
        font-size: 21px;
        color: #172641;
        margin-bottom: 5px;
    }

    .print-heading p {
        font-size: 11px;
        color: #64748b;
    }

    .header {
        margin-bottom: 18px;
    }

    .header h1 {
        font-size: 20px;
    }

    .cards {
        grid-template-columns: repeat(5,minmax(0,1fr));
        gap: 8px;
        margin-bottom: 16px;
    }

    .card {
        padding: 11px;
    }

    .card-icon {
        font-size: 16px;
        margin-bottom: 7px;
    }

    .card-title {
        font-size: 9px;
    }

    .card-value,
    .card-value.money {
        font-size: 15px;
    }

    .card,
    .report-section,
    .info-box {
        box-shadow: none !important;
        border: 1px solid #d1d5db;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .report-section {
        padding: 17px;
        margin-bottom: 16px;
    }

    .report-info {
        gap: 9px;
    }

    .info-box {
        padding: 13px;
    }

    .info-box .info-value {
        font-size: 19px;
    }

    .report-footer {
        margin-top: 16px;
    }
}

/* RESPONSIVE */
@media screen and (max-width:1200px) {
    .cards {
        grid-template-columns: repeat(3,minmax(0,1fr));
    }
}

@media screen and (max-width:900px) {
    .cards {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .report-info {
        grid-template-columns: 1fr;
    }
}

@media screen and (max-width:700px) {
    .main {
        margin-left: 0;
        padding: 20px 14px;
    }

    .header h1 {
        font-size: 25px;
    }

    .report-section {
        padding: 18px;
    }
}

@media screen and (max-width:450px) {
    .cards {
        grid-template-columns: 1fr;
    }

    .report-buttons {
        width: 100%;
    }

    .print-btn,
    .back-btn {
        flex: 1;
    }
}
</style>
</head>

<body>

<!-- SHARED ADMIN SIDEBAR -->
<?php require_once __DIR__ . "/admin/sidebar.php"; ?>

<main class="main">

    <!-- PRINT HEADER -->
    <div class="print-heading">
        <h2>AssetTrack</h2>
        <p>
            IT Asset Tracking System | Inventory Report
        </p>
        <p>
            Generated on: <?php echo htmlspecialchars($report_date); ?>
        </p>
    </div>

    <!-- PAGE HEADER -->
    <header class="header">

        <div>
            <div class="page-label">
                ADMIN PANEL / INVENTORY ANALYTICS
            </div>

            <h1>Asset Reports</h1>

            <p>
                Overview and analysis of IT asset inventory.
            </p>
        </div>

        <div class="report-buttons">

            <button
                type="button"
                class="print-btn"
                onclick="window.print()"
            >
                🖨️ Print Report
            </button>

            <a
                href="dashboard.php"
                class="back-btn"
            >
                Dashboard
            </a>

        </div>

    </header>

    <!-- SUMMARY CARDS -->
    <section class="cards">

        <div class="card">
            <div class="card-icon">💻</div>
            <div class="card-title">Total Assets</div>
            <div class="card-value">
                <?php echo number_format($total); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">🟢</div>
            <div class="card-title">Available Assets</div>
            <div class="card-value">
                <?php echo number_format($available); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">👤</div>
            <div class="card-title">Assigned Assets</div>
            <div class="card-value">
                <?php echo number_format($assigned); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">🛠️</div>
            <div class="card-title">Maintenance Assets</div>
            <div class="card-value">
                <?php echo number_format($maintenance); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">💰</div>
            <div class="card-title">Total Asset Value</div>
            <div class="card-value money">
                ₹<?php echo number_format($total_value, 0); ?>
            </div>
        </div>

    </section>

    <!-- STATUS DISTRIBUTION -->
    <section class="report-section">

        <h2 class="section-title">
            Asset Status Overview
        </h2>

        <p class="section-subtitle">
            Distribution of assets based on their current status.
        </p>

        <!-- AVAILABLE -->
        <div class="status-row">

            <div class="status-info">
                <span class="status-name">Available</span>

                <span class="status-count">
                    <?php echo $available; ?>
                    (<?php echo $available_percent; ?>%)
                </span>
            </div>

            <div class="progress">
                <div
                    class="progress-bar available-bar"
                    style="width:<?php echo $available_percent; ?>%;"
                ></div>
            </div>

        </div>

        <!-- ASSIGNED -->
        <div class="status-row">

            <div class="status-info">
                <span class="status-name">Assigned</span>

                <span class="status-count">
                    <?php echo $assigned; ?>
                    (<?php echo $assigned_percent; ?>%)
                </span>
            </div>

            <div class="progress">
                <div
                    class="progress-bar assigned-bar"
                    style="width:<?php echo $assigned_percent; ?>%;"
                ></div>
            </div>

        </div>

        <!-- MAINTENANCE -->
        <div class="status-row">

            <div class="status-info">
                <span class="status-name">Maintenance</span>

                <span class="status-count">
                    <?php echo $maintenance; ?>
                    (<?php echo $maintenance_percent; ?>%)
                </span>
            </div>

            <div class="progress">
                <div
                    class="progress-bar maintenance-bar"
                    style="width:<?php echo $maintenance_percent; ?>%;"
                ></div>
            </div>

        </div>

    </section>

    <!-- ADDITIONAL REPORT INFORMATION -->
    <section class="report-info">

        <div class="info-box">
            <div class="info-icon">📊</div>

            <h3>Asset Utilization</h3>

            <div class="info-value">
                <?php echo $utilization; ?>%
            </div>

            <p class="info-note">
                Percentage of assets currently assigned
            </p>
        </div>

        <div class="info-box">
            <div class="info-icon">📦</div>

            <h3>Available Assets</h3>

            <div class="info-value">
                <?php echo $available; ?>
            </div>

            <p class="info-note">
                Assets ready for allocation
            </p>
        </div>

        <div class="info-box">
            <div class="info-icon">🔧</div>

            <h3>Maintenance Assets</h3>

            <div class="info-value">
                <?php echo $maintenance; ?>
            </div>

            <p class="info-note">
                Assets currently under maintenance
            </p>
        </div>

    </section>

    <!-- FOOTER -->
    <footer class="report-footer">

        <span>
            AssetTrack · IT Asset Tracking System
        </span>

        <span>
            Report Generated: <?php echo htmlspecialchars($report_date); ?>
        </span>

    </footer>

</main>

</body>
</html>