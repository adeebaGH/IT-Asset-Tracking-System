<?php
session_start();
require_once __DIR__ . "/config/database.php";

// Admin authentication
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";

$employee_id = "";
$name = "";
$email = "";
$department = "";

// Handle employee registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $employee_id = trim($_POST["employee_id"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $employee_id === "" ||
        $name === "" ||
        $email === "" ||
        $department === "" ||
        $password === ""
    ) {
        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";
        $message_type = "error";

    } else {

        // Check duplicate employee ID or email
        $check = $conn->prepare("
            SELECT id
            FROM employees
            WHERE employee_id = ? OR email = ?
            LIMIT 1
        ");

        $check->bind_param("ss", $employee_id, $email);
        $check->execute();

        $existing = $check->get_result();

        if ($existing->num_rows > 0) {

            $message = "Employee ID or email already exists.";
            $message_type = "error";

        } else {

            // Securely hash employee password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insert = $conn->prepare("
                INSERT INTO employees
                (employee_id, name, email, department, password)
                VALUES (?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "sssss",
                $employee_id,
                $name,
                $email,
                $department,
                $hashed_password
            );

            try {

                if ($insert->execute()) {

                    $message = "Employee added successfully!";
                    $message_type = "success";

                    // Clear form after successful registration
                    $employee_id = "";
                    $name = "";
                    $email = "";
                    $department = "";

                } else {

                    $message = "Unable to add employee. Please try again.";
                    $message_type = "error";
                }

            } catch (mysqli_sql_exception $e) {

                $message = "Unable to add employee. Please try again.";
                $message_type = "error";
            }

            $insert->close();
        }

        $check->close();
    }
}

function escapeHtml($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
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

<title>Add Employee | AssetTrack Admin Panel</title>

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

/* PAGE HEADER */
.header {
    margin-bottom: 28px;
}

.page-label {
    font-size: 11px;
    color: #2563eb;
    font-weight: 800;
    letter-spacing: 1.5px;
    margin-bottom: 10px;
}

.header h1 {
    font-size: 30px;
    color: #14213c;
    margin-bottom: 8px;
}

.header p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
}

/* FORM CONTAINER */
.form-container {
    max-width: 1000px;
    background: white;
    padding: 30px;
    border-radius: 16px;
    border: 1px solid #e8edf5;
    box-shadow: 0 8px 28px rgba(27,48,85,.06);
}

.form-heading {
    margin-bottom: 27px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e8edf5;
}

.form-heading h2 {
    font-size: 19px;
    color: #172641;
    margin-bottom: 8px;
}

.form-heading p {
    color: #8491a5;
    font-size: 12px;
}

/* FORM GRID */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 22px;
}

.form-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.form-group label {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 9px;
    color: #334155;
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
    outline: none;
    background: white;
    color: #1e293b;
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

.help-text {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 8px;
}

/* SUCCESS AND ERROR MESSAGES */
.message {
    padding: 15px 17px;
    border-radius: 10px;
    margin-bottom: 23px;
    font-size: 13px;
    line-height: 1.7;
    font-weight: 600;
}

.message.success {
    background: #dcfce7;
    color: #166534;
    border-left: 4px solid #22c55e;
}

.message.error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #dc2626;
}

.message a {
    color: #1d4ed8;
    font-weight: 700;
}

/* FORM BUTTONS */
.form-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 30px;
    padding-top: 23px;
    border-top: 1px solid #e8edf5;
}

.btn {
    display: inline-block;
    padding: 12px 20px;
    border: none;
    border-radius: 9px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    transition: .2s;
}

.btn-primary {
    background: linear-gradient(135deg,#2563eb,#0891b2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(37,99,235,.2);
}

.btn-secondary {
    background: #e2e8f0;
    color: #334155;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

/* SECURITY NOTE */
.info-box {
    margin-top: 24px;
    background: #eff6ff;
    color: #1e40af;
    padding: 16px 18px;
    border-radius: 10px;
    font-size: 12px;
    line-height: 1.8;
}

/* FOOTER */
.footer {
    text-align: center;
    max-width: 1000px;
    margin-top: 25px;
    color: #94a3b8;
    font-size: 11px;
}

/* RESPONSIVE */
@media (max-width: 850px) {
    .main {
        padding: 25px 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .main {
        margin-left: 0;
        padding: 20px 14px;
    }

    .header h1 {
        font-size: 25px;
    }

    .form-container {
        padding: 20px;
    }

    .form-actions {
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

    <header class="header">
        <div class="page-label">
            ADMIN PANEL / EMPLOYEE MANAGEMENT
        </div>

        <h1>Add New Employee</h1>

        <p>
            Register a new employee in the AssetTrack management system.
        </p>
    </header>

    <section class="form-container">

        <div class="form-heading">
            <h2>Employee Information</h2>
            <p>Enter the employee's registration and login details.</p>
        </div>

        <?php if ($message !== ""): ?>

            <div
                class="message <?php echo escapeHtml($message_type); ?>"
                id="statusMessage"
            >
                <?php echo escapeHtml($message); ?>

                <?php if ($message_type === "success"): ?>
                    <div>
                        <a href="employees.php">
                            View all employees →
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="add_employee.php">

            <div class="form-grid">

                <!-- EMPLOYEE ID -->
                <div class="form-group">
                    <label for="employee_id">
                        Employee ID <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="employee_id"
                        name="employee_id"
                        placeholder="Example: EMP031"
                        value="<?php echo escapeHtml($employee_id); ?>"
                        required
                    >
                </div>

                <!-- FULL NAME -->
                <div class="form-group">
                    <label for="name">
                        Full Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter employee full name"
                        value="<?php echo escapeHtml($name); ?>"
                        required
                    >
                </div>

                <!-- EMAIL -->
                <div class="form-group">
                    <label for="email">
                        Email Address <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="employee@example.com"
                        value="<?php echo escapeHtml($email); ?>"
                        required
                    >
                </div>

                <!-- DEPARTMENT -->
                <div class="form-group">
                    <label for="department">
                        Department <span class="required">*</span>
                    </label>

                    <select
                        id="department"
                        name="department"
                        required
                    >
                        <option value="">Select Department</option>

                        <?php
                        $departments = [
                            "IT",
                            "HR",
                            "Finance",
                            "Operations",
                            "Marketing",
                            "Sales",
                            "Administration",
                            "Other"
                        ];

                        foreach ($departments as $dept):
                        ?>
                            <option
                                value="<?php echo escapeHtml($dept); ?>"
                                <?php echo $department === $dept
                                    ? "selected" : ""; ?>
                            >
                                <?php echo escapeHtml($dept); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- PASSWORD -->
                <div class="form-group">
                    <label for="password">
                        Login Password <span class="required">*</span>
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a secure password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <span class="help-text">
                        Minimum 8 characters. The password will be securely hashed.
                    </span>
                </div>

            </div>

            <!-- BUTTONS -->
            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    ＋ Add Employee
                </button>

                <a href="employees.php" class="btn btn-secondary">
                    View Employees
                </a>

                <a href="dashboard.php" class="btn btn-secondary">
                    Back to Dashboard
                </a>

            </div>

        </form>

        <div class="info-box">
            🔒 <strong>Security Note:</strong>
            Employee passwords are securely hashed before being stored
            in the database. Employee login credentials should be
            shared only with the respective employee.
        </div>

    </section>

    <footer class="footer">
        AssetTrack · IT Asset Tracking System · Admin Panel
    </footer>

</main>

</body>
</html>