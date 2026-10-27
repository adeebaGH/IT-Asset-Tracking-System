<?php
session_start();
require_once __DIR__ . "/config/database.php";

/* ADMIN AUTHENTICATION */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

/* VALIDATE ASSET ID */
if (!isset($_GET["id"]) || !ctype_digit((string)$_GET["id"])
    || (int)$_GET["id"] <= 0) {
    header("Location: assets.php");
    exit();
}

$id = (int)$_GET["id"];
$error = "";

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* GET EXISTING ASSET */
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: assets.php");
    exit();
}

$asset = $result->fetch_assoc();
$stmt->close();

/* LOAD EMPLOYEES */
$employees = [];

$employeeResult = $conn->query("
    SELECT employee_id, name
    FROM employees
    ORDER BY name ASC
");

if ($employeeResult) {
    while ($employee = $employeeResult->fetch_assoc()) {
        $employees[] = $employee;
    }
}

/* CATEGORY OPTIONS */
$categories = [
    "Laptop",
    "Desktop",
    "Printer",
    "Monitor",
    "Projector",
    "Networking",
    "Power Backup",
    "Accessories",
    "Tablet",
    "Mobile",
    "Storage",
    "Audio",
    "Other"
];

$statuses = [
    "Available",
    "Assigned",
    "Maintenance"
];

/* UPDATE ASSET */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $asset_code = trim($_POST["asset_code"] ?? "");
    $asset_name = trim($_POST["asset_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $brand = trim($_POST["brand"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $purchase_date = trim($_POST["purchase_date"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $status = trim($_POST["status"] ?? "");
    $assigned_to = trim($_POST["assigned_to"] ?? "");
    $location = trim($_POST["location"] ?? "");

    /* KEEP ENTERED VALUES IF VALIDATION FAILS */
    $asset = array_merge($asset, [
        "asset_code" => $asset_code,
        "asset_name" => $asset_name,
        "category" => $category,
        "brand" => $brand,
        "model" => $model,
        "purchase_date" => $purchase_date,
        "price" => $price,
        "status" => $status,
        "assigned_to" => $assigned_to,
        "location" => $location
    ]);

    if ($asset_code === "" || $asset_name === "") {
        $error = "Asset code and asset name are required.";
    } elseif (!in_array($category, $categories, true)) {
        $error = "Please select a valid category.";
    } elseif (!in_array($status, $statuses, true)) {
        $error = "Please select a valid asset status.";
    } elseif ($price !== "" &&
        (!is_numeric($price) || (float)$price < 0)) {
        $error = "Please enter a valid asset price.";
    } elseif ($purchase_date !== "" &&
        (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchase_date)
        || !checkdate(
            (int)substr($purchase_date, 5, 2),
            (int)substr($purchase_date, 8, 2),
            (int)substr($purchase_date, 0, 4)
        ))) {
        $error = "Please enter a valid purchase date.";
    } else {

        /* VERIFY SELECTED EMPLOYEE */
        $validEmployee = $assigned_to === "";

        foreach ($employees as $employee) {
            if ($employee["name"] === $assigned_to) {
                $validEmployee = true;
                break;
            }
        }

        if (!$validEmployee) {
            $error = "Please select a valid employee.";
        } else {

            /* KEEP ASSIGNMENT AND STATUS CONSISTENT */
            if ($status !== "Assigned") {
                $assigned_to = "";
            } elseif ($assigned_to === "") {
                $error = "Select an employee for an assigned asset.";
            }

            if ($error === "") {

                $purchaseValue = $purchase_date !== ""
                    ? $purchase_date : null;

                $priceValue = $price !== ""
                    ? (float)$price : null;

                $assignedValue = $assigned_to !== ""
                    ? $assigned_to : null;

                $sql = "
                    UPDATE assets SET
                        asset_code = ?,
                        asset_name = ?,
                        category = ?,
                        brand = ?,
                        model = ?,
                        purchase_date = ?,
                        price = ?,
                        status = ?,
                        assigned_to = ?,
                        location = ?
                    WHERE id = ?
                ";

                $update = $conn->prepare($sql);

                $update->bind_param(
                    "ssssssdsssi",
                    $asset_code,
                    $asset_name,
                    $category,
                    $brand,
                    $model,
                    $purchaseValue,
                    $priceValue,
                    $status,
                    $assignedValue,
                    $location,
                    $id
                );

                if ($update->execute()) {
                    $update->close();

                    header("Location: assets.php?updated=1");
                    exit();
                } else {
                    $error = "Unable to update asset. Please try again.";
                }

                $update->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Asset | AssetTrack</title>

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
    gap: 15px;
    margin-bottom: 27px;
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

.back-top {
    background: white;
    border: 1px solid #dbe3ef;
    padding: 12px 18px;
    border-radius: 9px;
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.back-top:hover {
    background: #eff6ff;
}

/* FORM PANEL */
.form-box {
    background: white;
    max-width: 1050px;
    padding: 30px;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    box-shadow: 0 8px 28px rgba(27,48,85,.06);
}

.form-heading {
    padding-bottom: 22px;
    margin-bottom: 25px;
    border-bottom: 1px solid #e8edf5;
}

.form-heading h2 {
    font-size: 19px;
    color: #172641;
    margin-bottom: 7px;
}

.form-heading p {
    color: #8491a5;
    font-size: 12px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2,minmax(0,1fr));
    gap: 23px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 9px;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #d7dfeb;
    border-radius: 9px;
    font-size: 13px;
    color: #334155;
    background: #fff;
    outline: none;
    transition: .2s;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}

.form-group input::placeholder {
    color: #94a3b8;
}

.required {
    color: #ef4444;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 30px;
    padding-top: 23px;
    border-top: 1px solid #e8edf5;
}

.save-btn {
    border: none;
    background: linear-gradient(135deg,#2563eb,#0891b2);
    color: white;
    padding: 13px 23px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.save-btn:hover {
    box-shadow: 0 7px 18px rgba(37,99,235,.2);
}

.cancel-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: #475569;
    padding: 13px 23px;
    border-radius: 9px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.cancel-btn:hover {
    background: #e2e8f0;
}

.error {
    padding: 14px 17px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    border-radius: 9px;
    font-size: 13px;
    margin-bottom: 22px;
}

.footer {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 25px;
}

/* RESPONSIVE */
@media(max-width:850px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width:700px) {
    .main {
        margin-left: 0;
        padding: 20px 14px;
    }

    .header h1 {
        font-size: 25px;
    }

    .form-box {
        padding: 20px;
    }
}
</style>

</head>

<body>

<!-- SHARED ADMIN SIDEBAR -->
<?php require_once __DIR__ . "/admin/sidebar.php"; ?>

<main class="main">

    <header class="header">

        <div>
            <div class="page-label">
                ADMIN PANEL / ASSET MANAGEMENT
            </div>

            <h1>Edit Asset</h1>

            <p>
                Update the details of your existing IT asset.
            </p>
        </div>

        <a href="assets.php" class="back-top">
            ← Back to Assets
        </a>

    </header>

    <section class="form-box">

        <div class="form-heading">
            <h2>Asset Information</h2>

            <p>
                Modify the fields below and save your changes.
            </p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-grid">

                <!-- ASSET CODE -->
                <div class="form-group">
                    <label>
                        Asset Code <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="asset_code"
                        value="<?php echo e($asset["asset_code"]); ?>"
                        required
                    >
                </div>

                <!-- ASSET NAME -->
                <div class="form-group">
                    <label>
                        Asset Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="asset_name"
                        value="<?php echo e($asset["asset_name"]); ?>"
                        required
                    >
                </div>

                <!-- CATEGORY -->
                <div class="form-group">
                    <label>
                        Category <span class="required">*</span>
                    </label>

                    <select name="category" required>

                        <?php foreach ($categories as $option): ?>

                            <option
                                value="<?php echo e($option); ?>"
                                <?php echo
                                    $asset["category"] === $option
                                    ? "selected" : "";
                                ?>
                            >
                                <?php echo e($option); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- BRAND -->
                <div class="form-group">
                    <label>Brand</label>

                    <input
                        type="text"
                        name="brand"
                        value="<?php echo e($asset["brand"]); ?>"
                        placeholder="Enter brand"
                    >
                </div>

                <!-- MODEL -->
                <div class="form-group">
                    <label>Model</label>

                    <input
                        type="text"
                        name="model"
                        value="<?php echo e($asset["model"]); ?>"
                        placeholder="Enter model"
                    >
                </div>

                <!-- PURCHASE DATE -->
                <div class="form-group">
                    <label>Purchase Date</label>

                    <input
                        type="date"
                        name="purchase_date"
                        value="<?php echo e($asset["purchase_date"]); ?>"
                    >
                </div>

                <!-- PRICE -->
                <div class="form-group">
                    <label>Price (₹)</label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?php echo e($asset["price"]); ?>"
                        placeholder="Enter asset price"
                    >
                </div>

                <!-- STATUS -->
                <div class="form-group">
                    <label>Asset Status</label>

                    <select
                        name="status"
                        id="assetStatus"
                        onchange="updateAssignment()"
                    >

                        <?php foreach ($statuses as $option): ?>

                            <option
                                value="<?php echo e($option); ?>"
                                <?php echo
                                    $asset["status"] === $option
                                    ? "selected" : "";
                                ?>
                            >
                                <?php echo e($option); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- ASSIGNED TO -->
                <div class="form-group">
                    <label>Assigned To</label>

                    <select
                        name="assigned_to"
                        id="assignedTo"
                    >

                        <option value="">Unassigned</option>

                        <?php
                        $currentAssigned = trim(
                            (string)($asset["assigned_to"] ?? "")
                        );

                        $employeeMatched = false;

                        foreach ($employees as $employee):
                            if ($currentAssigned === $employee["name"]) {
                                $employeeMatched = true;
                            }
                        endforeach;

                        if ($currentAssigned !== "" && !$employeeMatched):
                        ?>
                            <option
                                value="<?php echo e($currentAssigned); ?>"
                                selected
                            >
                                <?php echo e($currentAssigned); ?>
                                (Previously assigned)
                            </option>
                        <?php endif; ?>

                        <?php foreach ($employees as $employee): ?>

                            <option
                                value="<?php echo e($employee["name"]); ?>"
                                <?php echo
                                    $currentAssigned === $employee["name"]
                                    ? "selected" : "";
                                ?>
                            >
                                <?php echo e($employee["name"]); ?>
                                (<?php echo e($employee["employee_id"]); ?>)
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- LOCATION -->
                <div class="form-group">
                    <label>Location</label>

                    <input
                        type="text"
                        name="location"
                        value="<?php echo e($asset["location"]); ?>"
                        placeholder="Enter asset location"
                    >
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="save-btn">
                    💾 Save Changes
                </button>

                <a href="assets.php" class="cancel-btn">
                    Cancel
                </a>

            </div>

        </form>

    </section>

    <footer class="footer">
        AssetTrack · IT Asset Tracking System · Admin Panel
    </footer>

</main>

<script>
function updateAssignment() {
    const status = document.getElementById("assetStatus");
    const assigned = document.getElementById("assignedTo");

    if (status.value === "Assigned") {
        assigned.disabled = false;
        assigned.required = true;
    } else {
        assigned.value = "";
        assigned.disabled = true;
        assigned.required = false;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const status = document.getElementById("assetStatus");
    const assigned = document.getElementById("assignedTo");

    if (status.value !== "Assigned") {
        assigned.disabled = true;
        assigned.required = false;
    } else {
        assigned.disabled = false;
        assigned.required = true;
    }
});
</script>

</body>
</html>