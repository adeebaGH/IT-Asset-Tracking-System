<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

/* SEARCH AND FILTER */
$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");

$sql = "SELECT * FROM assets WHERE 1=1";

if ($search !== "") {
    $safe_search = $conn->real_escape_string($search);

    $sql .= " AND (
        asset_code LIKE '%$safe_search%'
        OR asset_name LIKE '%$safe_search%'
        OR category LIKE '%$safe_search%'
        OR brand LIKE '%$safe_search%'
        OR model LIKE '%$safe_search%'
        OR assigned_to LIKE '%$safe_search%'
        OR location LIKE '%$safe_search%'
    )";
}

if ($status_filter !== "") {
    $safe_status = $conn->real_escape_string($status_filter);
    $sql .= " AND status = '$safe_status'";
}

$sql .= " ORDER BY id ASC";
$result = $conn->query($sql);

/* SUCCESS MESSAGES */
$updated = ($_GET["updated"] ?? "") === "1";
$deleted = ($_GET["deleted"] ?? "") === "1";
$added = ($_GET["added"] ?? "") === "1";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Assets | AssetTrack</title>

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
    padding: 30px 34px;
    min-height: 100vh;
}

/* HEADER */
.top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.top h1 {
    font-size: 29px;
    color: #14213c;
    margin-bottom: 7px;
}

.top p {
    color: #64748b;
    font-size: 13px;
}

.add-btn {
    display: inline-block;
    background: linear-gradient(135deg, #2563eb, #0891b2);
    color: white;
    text-decoration: none;
    padding: 12px 19px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    transition: .25s;
}

.add-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(37,99,235,.2);
}

/* SUCCESS MESSAGE */
.success-message {
    background: #dcfce7;
    color: #166534;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 13px;
    font-weight: 600;
    border-left: 4px solid #22c55e;
}

/* SEARCH AND FILTER */
.filter-box {
    background: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 5px 22px rgba(27,48,85,.06);
    border: 1px solid #e8edf5;
    margin-bottom: 23px;
}

.filter-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.search-input {
    flex: 1;
    min-width: 220px;
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 13px;
    outline: none;
}

.search-input:focus,
.status-select:focus {
    border-color: #2563eb;
}

.status-select {
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    background: white;
    font-size: 13px;
    outline: none;
}

.search-btn {
    background: #14213c;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 9px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}

.search-btn:hover {
    background: #2563eb;
}

.clear-btn {
    background: #e2e8f0;
    color: #334155;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 9px;
    font-size: 13px;
}

.clear-btn:hover {
    background: #cbd5e1;
}

/* TABLE */
.table-box {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    box-shadow: 0 5px 22px rgba(27,48,85,.06);
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

th {
    background: #f6f9fd;
    color: #64748b;
    text-align: left;
    padding: 15px 13px;
    font-size: 11px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

td {
    padding: 15px 13px;
    border-bottom: 1px solid #edf1f7;
    font-size: 12px;
    white-space: nowrap;
}

tbody tr:hover {
    background: #f6faff;
}

.serial {
    color: #64748b;
    font-weight: 600;
}

.asset-code {
    color: #2563eb;
    font-weight: 700;
}

.asset-name {
    color: #14213c;
    font-weight: 600;
}

/* STATUS */
.status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
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

.other-status {
    background: #f1f5f9;
    color: #475569;
}

/* ACTION BUTTONS */
.actions {
    display: flex;
    gap: 8px;
}

.edit-btn,
.delete-btn {
    color: white;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 600;
}

.edit-btn {
    background: #2563eb;
}

.edit-btn:hover {
    background: #1d4ed8;
}

.delete-btn {
    background: #dc2626;
}

.delete-btn:hover {
    background: #b91c1c;
}

.empty {
    text-align: center;
    padding: 45px;
    color: #64748b;
}

.footer {
    text-align: center;
    margin-top: 25px;
    color: #94a3b8;
    font-size: 11px;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .main {
        padding: 25px 20px;
    }

    .top {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 700px) {
    .main {
        margin-left: 0;
        padding: 20px 14px;
    }

    .top h1 {
        font-size: 25px;
    }

    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .search-input {
        min-width: 0;
        width: 100%;
        flex: auto;
    }

    .status-select,
    .search-btn,
    .clear-btn {
        width: 100%;
    }

    .clear-btn {
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

    <div class="top">
        <div>
            <h1>Manage Assets</h1>
            <p>Manage, search and track all IT assets</p>
        </div>

        <a href="add_asset.php" class="add-btn">
            ＋ Add New Asset
        </a>
    </div>

    <!-- SUCCESS MESSAGES -->
    <?php if ($updated): ?>
        <div class="success-message" id="successMessage">
            ✅ Asset updated successfully!
        </div>
    <?php elseif ($deleted): ?>
        <div class="success-message" id="successMessage">
            🗑️ Asset deleted successfully!
        </div>
    <?php elseif ($added): ?>
        <div class="success-message" id="successMessage">
            ✅ Asset added successfully!
        </div>
    <?php endif; ?>

    <!-- SEARCH AND FILTER -->
    <div class="filter-box">
        <form method="GET" class="filter-form">

            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="🔎 Search by asset, code, brand, employee..."
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
            >

            <select name="status" class="status-select">
                <option value="">All Status</option>

                <option value="Available"
                    <?php echo $status_filter === "Available" ? "selected" : ""; ?>>
                    Available
                </option>

                <option value="Assigned"
                    <?php echo $status_filter === "Assigned" ? "selected" : ""; ?>>
                    Assigned
                </option>

                <option value="Maintenance"
                    <?php echo $status_filter === "Maintenance" ? "selected" : ""; ?>>
                    Maintenance
                </option>
            </select>

            <button type="submit" class="search-btn">
                Search
            </button>

            <a href="assets.php" class="clear-btn">
                Clear
            </a>

        </form>
    </div>

    <!-- ASSETS TABLE -->
    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Asset Code</th>
                    <th>Asset Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Purchase Date</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php
            $serial = 1;

            if ($result && $result->num_rows > 0):
                while ($asset = $result->fetch_assoc()):

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
                    <td class="serial">
                        <?php echo $serial++; ?>
                    </td>

                    <td class="asset-code">
                        <?php echo htmlspecialchars($asset["asset_code"] ?? ""); ?>
                    </td>

                    <td class="asset-name">
                        <?php echo htmlspecialchars($asset["asset_name"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($asset["category"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($asset["brand"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($asset["model"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo !empty($asset["purchase_date"])
                            ? htmlspecialchars($asset["purchase_date"])
                            : "—"; ?>
                    </td>

                    <td>
                        ₹<?php echo number_format((float)($asset["price"] ?? 0), 2); ?>
                    </td>

                    <td>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status_text); ?>
                        </span>
                    </td>

                    <td>
                        <?php echo !empty($asset["assigned_to"])
                            ? htmlspecialchars($asset["assigned_to"])
                            : "—"; ?>
                    </td>

                    <td>
                        <?php echo !empty($asset["location"])
                            ? htmlspecialchars($asset["location"])
                            : "—"; ?>
                    </td>

                    <td>
                        <div class="actions">

                            <a
                                href="edit_asset.php?id=<?php echo (int)$asset['id']; ?>"
                                class="edit-btn"
                            >
                                ✏️ Edit
                            </a>

                            <a
                                href="delete_asset.php?id=<?php echo (int)$asset['id']; ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this asset?');"
                            >
                                🗑️ Delete
                            </a>

                        </div>
                    </td>
                </tr>

            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="12" class="empty">
                        🔍 No assets found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer class="footer">
        AssetTrack · IT Asset Tracking System · Admin Panel
    </footer>

</main>

<!-- SUCCESS MESSAGE: AUTO HIDE AFTER 2 SECONDS -->
<script>
const message = document.getElementById("successMessage");

if (message) {
    setTimeout(function () {
        message.style.transition = "opacity 0.5s ease";
        message.style.opacity = "0";

        setTimeout(function () {
            message.remove();
        }, 500);

        const url = new URL(window.location.href);
        url.searchParams.delete("updated");
        url.searchParams.delete("deleted");
        url.searchParams.delete("added");

        window.history.replaceState(
            {},
            document.title,
            url.pathname + url.search + url.hash
        );
    }, 2000);
}
</script>

</body>
</html>