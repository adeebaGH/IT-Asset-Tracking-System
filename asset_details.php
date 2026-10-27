<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: assets.php");
    exit();
}

$id = intval($_GET["id"]);

$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: assets.php");
    exit();
}

$asset = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Asset Details - IT Asset Tracking</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    color: #1f2937;
}

.sidebar {
    width: 240px;
    height: 100vh;
    background: #111827;
    position: fixed;
    left: 0;
    top: 0;
    padding: 25px 15px;
}

.sidebar h2 {
    color: white;
    text-align: center;
    margin-bottom: 35px;
}

.sidebar a {
    display: block;
    color: #d1d5db;
    text-decoration: none;
    padding: 13px 15px;
    margin-bottom: 8px;
    border-radius: 8px;
}

.sidebar a:hover {
    background: #2563eb;
    color: white;
}

.main {
    margin-left: 240px;
    padding: 35px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.topbar h1 {
    margin: 0;
}

.back-btn {
    background: #6b7280;
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 7px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    max-width: 900px;
}

.asset-title {
    font-size: 24px;
    font-weight: bold;
    color: #2563eb;
    margin-bottom: 25px;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.detail-box {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #2563eb;
}

.detail-box strong {
    display: block;
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 6px;
}

.detail-box span {
    font-size: 16px;
    font-weight: 600;
}

.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
}

.available {
    background: #dcfce7;
    color: #166534;
}

.assigned {
    background: #dbeafe;
    color: #1d4ed8;
}

.maintenance {
    background: #fef3c7;
    color: #92400e;
}

.actions {
    margin-top: 30px;
}

.edit-btn {
    background: #2563eb;
    color: white;
    padding: 11px 20px;
    border-radius: 7px;
    text-decoration: none;
}

</style>
</head>

<body>

<div class="sidebar">

    <h2>IT Asset<br>Tracking</h2>

    <a href="dashboard.php">📊 Dashboard</a>
    <a href="assets.php">💻 Assets</a>
    <a href="add_asset.php">➕ Add Asset</a>
    <a href="#">👥 Employees</a>
    <a href="#">📈 Reports</a>
    <a href="logout.php">🚪 Logout</a>

</div>

<div class="main">

    <div class="topbar">

        <h1>Asset Details</h1>

        <a href="assets.php" class="back-btn">
            ← Back to Assets
        </a>

    </div>

    <div class="card">

        <div class="asset-title">
            <?php echo htmlspecialchars($asset["asset_name"]); ?>
        </div>

        <div class="details-grid">

            <div class="detail-box">
                <strong>Asset Code</strong>
                <span><?php echo htmlspecialchars($asset["asset_code"]); ?></span>
            </div>

            <div class="detail-box">
                <strong>Category</strong>
                <span><?php echo htmlspecialchars($asset["category"]); ?></span>
            </div>

            <div class="detail-box">
                <strong>Brand</strong>
                <span><?php echo htmlspecialchars($asset["brand"]); ?></span>
            </div>

            <div class="detail-box">
                <strong>Model</strong>
                <span><?php echo htmlspecialchars($asset["model"]); ?></span>
            </div>

            <div class="detail-box">
                <strong>Purchase Date</strong>
                <span><?php echo htmlspecialchars($asset["purchase_date"]); ?></span>
            </div>

            <div class="detail-box">
                <strong>Price</strong>
                <span>₹<?php echo number_format($asset["price"], 2); ?></span>
            </div>

            <div class="detail-box">
                <strong>Status</strong>

                <?php
                $statusClass = strtolower($asset["status"]);
                ?>

                <span class="status <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($asset["status"]); ?>
                </span>

            </div>

            <div class="detail-box">
                <strong>Assigned To</strong>
                <span>
                    <?php
                    echo $asset["assigned_to"]
                        ? htmlspecialchars($asset["assigned_to"])
                        : "Not Assigned";
                    ?>
                </span>
            </div>

            <div class="detail-box">
                <strong>Location</strong>
                <span><?php echo htmlspecialchars($asset["location"]); ?></span>
            </div>

            <div class="detail-box">
                <strong>Added On</strong>
                <span><?php echo htmlspecialchars($asset["created_at"]); ?></span>
            </div>

        </div>

        <div class="actions">

        <td>
    <a href="edit_asset.php?id=<?php echo $asset['id']; ?>"
       style="background:#2563eb;color:white;padding:7px 12px;border-radius:5px;text-decoration:none;">
        ✏️ Edit
    </a>

    <a href="delete_asset.php?id=<?php echo $asset['id']; ?>"
       style="background:#dc2626;color:white;padding:7px 12px;border-radius:5px;text-decoration:none;"
       onclick="return confirm('Are you sure you want to delete this asset?');">
        🗑️ Delete
    </a>
    </td>

        </div>

    </div>

</div>

</body>
</html>