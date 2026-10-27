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
$employee_email = $_SESSION["employee_email"] ?? "";
$employee_department = $_SESSION["employee_department"] ?? "";

$message = "";
$message_type = "";

$asset_type = "";
$reason = "";

$asset_options = [
    "Laptop",
    "Desktop Computer",
    "Monitor",
    "Keyboard",
    "Mouse",
    "Printer",
    "Other"
];

function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$initial = strtoupper(substr(trim($employee_name), 0, 1));

if ($initial === "") {
    $initial = "E";
}

/* PROCESS ASSET REQUEST */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $asset_type = trim($_POST["asset_type"] ?? "");
    $reason = trim($_POST["reason"] ?? "");

    if ($asset_type === "" || $reason === "") {

        $message = "Please select an asset type and enter a reason.";
        $message_type = "error";

    } elseif (!in_array($asset_type, $asset_options, true)) {

        $message = "Please select a valid asset type.";
        $message_type = "error";

    } elseif (strlen($reason) > 2000) {

        $message = "Reason must not exceed 2000 characters.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO asset_requests
            (
                employee_name,
                employee_id,
                email,
                department,
                asset_type,
                reason,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");

        if ($stmt) {

            $stmt->bind_param(
                "ssssss",
                $employee_name,
                $employee_id,
                $employee_email,
                $employee_department,
                $asset_type,
                $reason
            );

            if ($stmt->execute()) {

                $message = "Your asset request has been submitted successfully! It is now pending admin approval.";
                $message_type = "success";

                $asset_type = "";
                $reason = "";

            } else {

                $message = "Unable to submit request. Please try again.";
                $message_type = "error";
            }

            $stmt->close();

        } else {

            $message = "Unable to prepare the request. Please contact the administrator.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Request New Asset | AssetTrack</title>

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

/* EMPLOYEE SIDEBAR */
.sidebar {
    width: 255px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
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

/* HEADER */
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

/* PAGE BANNER */
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

/* CONTENT GRID */
.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 270px;
    gap: 22px;
    align-items: start;
}

/* FORM CARD */
.form-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.card-heading {
    margin-bottom: 25px;
    padding-bottom: 18px;
    border-bottom: 1px solid #edf1f7;
}

.card-heading h3 {
    font-size: 19px;
    color: #172641;
    margin-bottom: 8px;
}

.card-heading p {
    font-size: 12px;
    color: #64748b;
    line-height: 1.7;
}

.section-title {
    color: #2563eb;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    margin: 25px 0 17px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 18px;
}

.form-group {
    margin-bottom: 19px;
}

.form-group label {
    display: block;
    font-size: 12px;
    color: #334155;
    font-weight: 700;
    margin-bottom: 9px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    background: #fff;
    color: #334155;
    font-size: 13px;
    outline: none;
    transition: 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}

.form-group input[readonly] {
    background: #f6f9fd;
    color: #64748b;
}

.form-group textarea {
    min-height: 130px;
    resize: vertical;
    line-height: 1.7;
}

.required {
    color: #dc2626;
}

.helper-text {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 8px;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    margin-top: 20px;
}

.submit-btn {
    border: none;
    background: linear-gradient(
        90deg,
        #2563eb,
        #0891b2
    );
    color: white;
    padding: 14px 23px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.submit-btn:hover {
    opacity: 0.9;
}

.cancel-btn {
    display: inline-block;
    text-decoration: none;
    color: #475569;
    background: #f1f5f9;
    padding: 14px 22px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

.cancel-btn:hover {
    background: #e2e8f0;
}

/* MESSAGES */
.message {
    padding: 15px 18px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.7;
    margin-bottom: 23px;
}

.message.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.message a {
    color: inherit;
    font-weight: 800;
}

/* INFORMATION CARDS */
.info-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 15px;
    padding: 23px;
    margin-bottom: 18px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.info-icon {
    font-size: 26px;
    margin-bottom: 15px;
}

.info-card h3 {
    font-size: 15px;
    color: #172641;
    margin-bottom: 12px;
}

.info-card p {
    font-size: 12px;
    color: #64748b;
    line-height: 1.9;
}

.steps {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-top: 18px;
}

.step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.step-number {
    width: 29px;
    height: 29px;
    display: grid;
    place-items: center;
    background: #eaf2ff;
    color: #2563eb;
    border-radius: 50%;
    font-size: 12px;
    font-weight: 800;
    flex-shrink: 0;
}

.step strong {
    display: block;
    font-size: 12px;
    color: #172641;
    margin-bottom: 5px;
}

.step p {
    font-size: 11px;
    color: #64748b;
    line-height: 1.6;
}

.notice-card {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    padding: 20px;
}

.notice-card h3 {
    font-size: 13px;
    color: #1d4ed8;
    margin-bottom: 10px;
}

.notice-card p {
    color: #475569;
    font-size: 12px;
    line-height: 1.8;
}

.footer {
    margin-top: 30px;
    padding: 18px 0;
    text-align: center;
    font-size: 11px;
    color: #94a3b8;
}

/* RESPONSIVE */
@media(max-width:1100px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .info-column {
        display: grid;
        grid-template-columns: repeat(2, minmax(0,1fr));
        gap: 16px;
    }

    .info-card {
        margin-bottom: 0;
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

    .form-card {
        padding: 20px;
    }

    .form-grid,
    .info-column {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- COMMON EMPLOYEE SIDEBAR -->
<?php require __DIR__ . "/employee/sidebar.php"; ?>

<!-- MAIN CONTENT -->
<main class="main-content">

    <header class="topbar">

        <div class="welcome">

            <div class="page-label">
                EMPLOYEE PORTAL / REQUEST ASSET
            </div>

            <h1>Request New Asset</h1>

            <p>
                Submit a request for workplace equipment.
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

        <h2>Need New Equipment?</h2>

        <p>
            Submit your asset requirement using the form below.
            Your request will be forwarded to the administrator
            for review and approval.
        </p>

    </section>

    <div class="content-grid">

        <!-- REQUEST FORM -->
        <section class="form-card">

            <div class="card-heading">

                <h3>Asset Request Form</h3>

                <p>
                    Complete the following information
                    to submit your asset request.
                </p>

            </div>

            <?php if ($message !== ""): ?>

                <div class="message <?= e($message_type) ?>"
                     role="alert">

                    <?= e($message) ?>

                    <?php if ($message_type === "success"): ?>
                        <a href="my_requests.php">
                            View My Requests
                        </a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <form method="POST" action="request_asset.php">

                <div class="section-title">
                    EMPLOYEE INFORMATION
                </div>

                <div class="form-grid">

                    <div class="form-group">
                        <label>Employee ID</label>

                        <input
                            type="text"
                            value="<?= e($employee_id) ?>"
                            readonly
                        >
                    </div>

                    <div class="form-group">
                        <label>Employee Name</label>

                        <input
                            type="text"
                            value="<?= e($employee_name) ?>"
                            readonly
                        >
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>

                        <input
                            type="email"
                            value="<?= e($employee_email) ?>"
                            readonly
                        >
                    </div>

                    <div class="form-group">
                        <label>Department</label>

                        <input
                            type="text"
                            value="<?= e($employee_department) ?>"
                            readonly
                        >
                    </div>

                </div>

                <div class="section-title">
                    ASSET REQUIREMENT
                </div>

                <div class="form-group">

                    <label for="asset_type">
                        Asset Type <span class="required">*</span>
                    </label>

                    <select
                        name="asset_type"
                        id="asset_type"
                        required
                    >

                        <option value="">
                            -- Select Asset Type --
                        </option>

                        <?php foreach ($asset_options as $option): ?>

                            <option
                                value="<?= e($option) ?>"
                                <?= $asset_type === $option ? "selected" : "" ?>
                            >
                                <?= e($option) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="reason">
                        Reason for Request
                        <span class="required">*</span>
                    </label>

                    <textarea
                        name="reason"
                        id="reason"
                        maxlength="2000"
                        placeholder="Explain why you need this asset and how it will support your work..."
                        required
                    ><?= e($reason) ?></textarea>

                    <p class="helper-text">
                        Provide a clear explanation of your requirement.
                    </p>

                </div>

                <div class="form-actions">

                    <button type="submit" class="submit-btn">
                        Submit Asset Request
                    </button>

                    <a href="employee_dashboard.php"
                       class="cancel-btn">
                        Back to Dashboard
                    </a>

                </div>

            </form>

        </section>

        <!-- RIGHT INFORMATION PANEL -->
        <aside class="info-column">

            <div class="info-card">

                <div class="info-icon">📋</div>

                <h3>How It Works</h3>

                <div class="steps">

                    <div class="step">

                        <div class="step-number">1</div>

                        <div>
                            <strong>Submit Request</strong>
                            <p>
                                Select the required asset and
                                explain your requirement.
                            </p>
                        </div>

                    </div>

                    <div class="step">

                        <div class="step-number">2</div>

                        <div>
                            <strong>Admin Review</strong>
                            <p>
                                Your request will be reviewed
                                by the administrator.
                            </p>
                        </div>

                    </div>

                    <div class="step">

                        <div class="step-number">3</div>

                        <div>
                            <strong>Track Status</strong>
                            <p>
                                Check approval updates
                                through My Requests.
                            </p>
                        </div>

                    </div>

                </div>

            </div>

            <div class="notice-card">

                <h3>Important Information</h3>

                <p>
                    All new requests initially receive
                    <strong>Pending</strong> status.
                    Asset allocation depends on administrator
                    approval and equipment availability.
                </p>

            </div>

        </aside>

    </div>

    <footer class="footer">
        © <?= date("Y") ?> AssetTrack |
        IT Asset Tracking System
    </footer>

</main>

</body>
</html>