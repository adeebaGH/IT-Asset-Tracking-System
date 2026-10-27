<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$error = "";

/* FETCH EMPLOYEES */
$employees = $conn->query(
    "SELECT employee_id, name
     FROM employees
     ORDER BY name ASC"
);

/* ADD ASSET */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $asset_code = trim($_POST["asset_code"] ?? "");
    $asset_name = trim($_POST["asset_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $brand = trim($_POST["brand"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $purchase_date = $_POST["purchase_date"] ?? "";
    $price = (float)($_POST["price"] ?? 0);
    $status = $_POST["status"] ?? "Available";
    $assigned_to = trim($_POST["assigned_to"] ?? "");
    $location = trim($_POST["location"] ?? "");

    if ($asset_code === "" || $asset_name === "" || $category === "") {
        $error = "Please fill in all required fields.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO assets
            (asset_code, asset_name, category, brand, model,
             purchase_date, price, status, assigned_to, location)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            $error = "Unable to prepare the asset record.";
        } else {

            $stmt->bind_param(
                "ssssssdsss",
                $asset_code,
                $asset_name,
                $category,
                $brand,
                $model,
                $purchase_date,
                $price,
                $status,
                $assigned_to,
                $location
            );

            try {
                if ($stmt->execute()) {
                    header("Location: assets.php?added=1");
                    exit();
                }

                $error = "Unable to add asset.";
            } catch (mysqli_sql_exception $e) {
                $error = "Unable to add asset. Asset code may already exist.";
            }

            $stmt->close();
        }
    }
}

function oldValue($name) {
    return htmlspecialchars(
        (string)($_POST[$name] ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Asset | AssetTrack Admin Panel</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
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

/* PAGE HEADER */
.top {
    margin-bottom: 27px;
}

.page-label {
    font-size: 11px;
    color: #2563eb;
    font-weight: 800;
    letter-spacing: 1.5px;
    margin-bottom: 9px;
}

.top h1 {
    font-size: 30px;
    color: #14213c;
    margin-bottom: 8px;
}

.top p {
    font-size: 13px;
    color: #64748b;
}

/* FORM CARD */
.form-box {
    background: white;
    padding: 30px;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    max-width: 1100px;
    box-shadow: 0 8px 28px rgba(27,48,85,.06);
}

.form-header {
    margin-bottom: 26px;
    padding-bottom: 19px;
    border-bottom: 1px solid #e8edf5;
}

.form-header h2 {
    font-size: 19px;
    color: #172641;
    margin-bottom: 7px;
}

.form-header p {
    font-size: 12px;
    color: #8491a5;
}

/* FORM LAYOUT */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.form-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 9px;
}

.required {
    color: #dc2626;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 13px;
    color: #1e293b;
    background: white;
    outline: none;
    transition: .2s;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.09);
}

.form-group input::placeholder {
    color: #a1adbc;
}

/* BUTTONS */
.buttons {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 30px;
    padding-top: 23px;
    border-top: 1px solid #e8edf5;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 12px 22px;
    border-radius: 9px;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    text-align: center;
    transition: .2s;
}

.btn-primary {
    background: linear-gradient(135deg,#2563eb,#0891b2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 18px rgba(37,99,235,.2);
}

.btn-secondary {
    background: #e2e8f0;
    color: #334155;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

/* ERROR MESSAGE */
.error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #dc2626;
    padding: 14px 17px;
    border-radius: 9px;
    margin-bottom: 22px;
    font-size: 13px;
}

/* FOOTER */
.footer {
    color: #94a3b8;
    font-size: 11px;
    text-align: center;
    margin-top: 26px;
    max-width: 1100px;
}

/* RESPONSIVE */
@media (max-width: 850px) {
    .main {
        padding: 25px 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }
}

@media (max-width: 700px) {
    .main {
        margin-left: 0;
        padding: 20px 14px;
    }

    .top h1 {
        font-size: 26px;
    }

    .form-box {
        padding: 20px;
    }

    .buttons {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<!-- SHARED ADMIN PANEL SIDEBAR -->
<?php require_once __DIR__ . "/admin/sidebar.php"; ?>

<!-- MAIN CONTENT -->
<main class="main">

    <div class="top">
        <div class="page-label">ADMIN PANEL / ASSET MANAGEMENT</div>
        <h1>Add New Asset</h1>
        <p>Register new IT equipment in your asset inventory.</p>
    </div>

    <section class="form-box">

        <div class="form-header">
            <h2>Asset Information</h2>
            <p>Enter the details of the asset you want to register.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-grid">

                <!-- ASSET CODE -->
                <div class="form-group">
                    <label for="asset_code">
                        Asset Code <span class="required">*</span>
                    </label>

                    <input
                        id="asset_code"
                        type="text"
                        name="asset_code"
                        placeholder="Example: AST041"
                        value="<?php echo oldValue('asset_code'); ?>"
                        required
                    >
                </div>

                <!-- ASSET NAME -->
                <div class="form-group">
                    <label for="asset_name">
                        Asset Name <span class="required">*</span>
                    </label>

                    <input
                        id="asset_name"
                        type="text"
                        name="asset_name"
                        placeholder="Example: Laptop"
                        value="<?php echo oldValue('asset_name'); ?>"
                        required
                    >
                </div>

                <!-- CATEGORY -->
                <div class="form-group">
                    <label for="category">
                        Category <span class="required">*</span>
                    </label>

                    <select id="category" name="category" required>
                        <option value="">Select Category</option>

                        <?php
                        $categories = [
                            "Laptop",
                            "Desktop",
                            "Printer",
                            "Monitor",
                            "Networking",
                            "Server",
                            "Scanner",
                            "Projector",
                            "Accessories",
                            "Storage",
                            "Other"
                        ];

                        foreach ($categories as $category):
                        ?>
                            <option
                                value="<?php echo htmlspecialchars($category); ?>"
                                <?php echo ($_POST["category"] ?? "") === $category
                                    ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- BRAND -->
                <div class="form-group">
                    <label for="brand">Brand</label>

                    <input
                        id="brand"
                        type="text"
                        name="brand"
                        placeholder="Example: Dell"
                        value="<?php echo oldValue('brand'); ?>"
                    >
                </div>

                <!-- MODEL -->
                <div class="form-group">
                    <label for="model">Model</label>

                    <input
                        id="model"
                        type="text"
                        name="model"
                        placeholder="Example: Inspiron 15"
                        value="<?php echo oldValue('model'); ?>"
                    >
                </div>

                <!-- PURCHASE DATE -->
                <div class="form-group">
                    <label for="purchase_date">Purchase Date</label>

                    <input
                        id="purchase_date"
                        type="date"
                        name="purchase_date"
                        value="<?php echo oldValue('purchase_date'); ?>"
                    >
                </div>

                <!-- PRICE -->
                <div class="form-group">
                    <label for="price">Price (₹)</label>

                    <input
                        id="price"
                        type="number"
                        name="price"
                        placeholder="Example: 55000"
                        min="0"
                        step="0.01"
                        value="<?php echo oldValue('price'); ?>"
                    >
                </div>

                <!-- STATUS -->
                <div class="form-group">
                    <label for="status">Status</label>

                    <select id="status" name="status">
                        <?php
                        $selected_status = $_POST["status"] ?? "Available";

                        foreach (["Available", "Assigned", "Maintenance"] as $option):
                        ?>
                            <option
                                value="<?php echo $option; ?>"
                                <?php echo $selected_status === $option
                                    ? "selected" : ""; ?>
                            >
                                <?php echo $option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ASSIGNED TO -->
                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>

                    <select id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>

                        <?php if ($employees): ?>
                            <?php while ($employee = $employees->fetch_assoc()): ?>
                                <option
                                    value="<?php echo htmlspecialchars(
                                        $employee["name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                    <?php echo ($_POST["assigned_to"] ?? "") === $employee["name"]
                                        ? "selected" : ""; ?>
                                >
                                    <?php
                                    echo htmlspecialchars($employee["name"]);
                                    ?>
                                    (<?php
                                    echo htmlspecialchars($employee["employee_id"]);
                                    ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- LOCATION -->
                <div class="form-group full">
                    <label for="location">Location / Department</label>

                    <input
                        id="location"
                        type="text"
                        name="location"
                        placeholder="Example: IT Department"
                        value="<?php echo oldValue('location'); ?>"
                    >
                </div>

            </div>

            <!-- FORM ACTIONS -->
            <div class="buttons">

                <button type="submit" class="btn btn-primary">
                    ＋ Add Asset
                </button>

                <a href="assets.php" class="btn btn-secondary">
                    Cancel
                </a>

            </div>

        </form>

    </section>

    <footer class="footer">
        AssetTrack · IT Asset Tracking System · Admin Panel
    </footer>

</main>

</body>
</html>