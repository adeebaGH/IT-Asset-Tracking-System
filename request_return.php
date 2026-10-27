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
$employee_department = $_SESSION["employee_department"] ?? "Employee";

$message = "";
$error = "";
$selected_asset = "";
$reason = "";

$assets = [];
$pending_asset_ids = [];

/* HTML ESCAPE */
function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* EMPLOYEE INITIAL */
$initial = strtoupper(
    substr(trim($employee_name), 0, 1)
);

if ($initial === "") {
    $initial = "E";
}

/* HANDLE RETURN REQUEST */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selected_asset = trim(
        (string) ($_POST["asset_id"] ?? "")
    );

    $reason = trim($_POST["reason"] ?? "");

    $asset_id = filter_var(
        $selected_asset,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($asset_id === false) {

        $error = "Please select a valid asset.";

    } elseif ($reason === "") {

        $error = "Please enter a reason for returning the asset.";

    } elseif (strlen($reason) > 2000) {

        $error = "Return reason must not exceed 2000 characters.";

    } else {

        /* VERIFY ASSET OWNERSHIP */
        $check = $conn->prepare("
            SELECT id, asset_code, asset_name
            FROM assets
            WHERE id = ?
              AND assigned_to = ?
              AND status = 'Assigned'
            LIMIT 1
        ");

        if (!$check) {

            $error = "Unable to verify the asset. Please try again.";

        } else {

            $check->bind_param(
                "is",
                $asset_id,
                $employee_name
            );

            $check->execute();

            $asset = $check->get_result()->fetch_assoc();

            $check->close();

            if (!$asset) {

                $error = "This asset is not assigned to your account or is no longer available for return.";

            } else {

                /* PREVENT DUPLICATE PENDING REQUESTS */
                $duplicate = $conn->prepare("
                    SELECT id
                    FROM asset_returns
                    WHERE asset_id = ?
                      AND employee_id = ?
                      AND status = 'Pending'
                    LIMIT 1
                ");

                if (!$duplicate) {

                    $error = "Unable to check existing return requests.";

                } else {

                    $duplicate->bind_param(
                        "is",
                        $asset_id,
                        $employee_id
                    );

                    $duplicate->execute();

                    $existing = $duplicate
                        ->get_result()
                        ->fetch_assoc();

                    $duplicate->close();

                    if ($existing) {

                        $error = "A return request for this asset is already pending.";

                    } else {

                        /* INSERT RETURN REQUEST */
                        $insert = $conn->prepare("
                            INSERT INTO asset_returns
                            (
                                asset_id,
                                employee_id,
                                employee_name,
                                reason
                            )
                            VALUES (?, ?, ?, ?)
                        ");

                        if (!$insert) {

                            $error = "Unable to prepare your return request.";

                        } else {

                            $insert->bind_param(
                                "isss",
                                $asset_id,
                                $employee_id,
                                $employee_name,
                                $reason
                            );

                            if ($insert->execute()) {

                                $message = "Your asset return request has been submitted successfully! It is now pending admin review.";

                                $selected_asset = "";
                                $reason = "";

                            } else {

                                $error = "Could not submit your return request. Please try again.";
                            }

                            $insert->close();
                        }
                    }
                }
            }
        }
    }
}

/* FETCH EMPLOYEE ASSIGNED ASSETS */
$stmt = $conn->prepare("
    SELECT
        id,
        asset_code,
        asset_name,
        category,
        brand,
        model
    FROM assets
    WHERE assigned_to = ?
      AND status = 'Assigned'
    ORDER BY id DESC
");

if ($stmt) {

    $stmt->bind_param("s", $employee_name);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }

    $stmt->close();
}

/* FETCH PENDING RETURN REQUESTS */
$pending = $conn->prepare("
    SELECT ar.asset_id
    FROM asset_returns ar
    INNER JOIN assets a
        ON a.id = ar.asset_id
    WHERE ar.employee_id = ?
      AND ar.status = 'Pending'
      AND a.assigned_to = ?
      AND a.status = 'Assigned'
");

if ($pending) {

    $pending->bind_param(
        "ss",
        $employee_id,
        $employee_name
    );

    $pending->execute();

    $result = $pending->get_result();

    while ($row = $result->fetch_assoc()) {
        $pending_asset_ids[] = (int) $row["asset_id"];
    }

    $pending->close();
}

$asset_count = count($assets);
$pending_count = count(array_unique($pending_asset_ids));

$available_count = 0;

foreach ($assets as $asset) {
    if (!in_array(
        (int) $asset["id"],
        $pending_asset_ids,
        true
    )) {
        $available_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Request Asset Return | AssetTrack</title>

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

/* BANNER */
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

/* STATISTICS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 17px;
    margin-bottom: 28px;
}

.stat-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    padding: 23px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.stat-card:nth-child(1) {
    border-top: 3px solid #2563eb;
}

.stat-card:nth-child(2) {
    border-top: 3px solid #f59e0b;
}

.stat-card:nth-child(3) {
    border-top: 3px solid #0891b2;
}

.stat-icon {
    font-size: 23px;
    margin-bottom: 12px;
}

.stat-card h3 {
    font-size: 29px;
    color: #172641;
    margin-bottom: 7px;
}

.stat-card p {
    font-size: 12px;
    color: #64748b;
}

/* CONTENT */
.content-grid {
    display: grid;
    grid-template-columns: minmax(0,1fr) 270px;
    gap: 22px;
    align-items: start;
}

.form-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 25px rgba(27,48,85,0.05);
}

.card-heading {
    margin-bottom: 24px;
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

/* FORM */
.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 10px;
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    background: white;
    color: #334155;
    font-size: 13px;
    outline: none;
}

.form-group select:focus,
.form-group textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}

.form-group textarea {
    min-height: 135px;
    resize: vertical;
    line-height: 1.7;
}

.required {
    color: #dc2626;
}

.helper-text {
    margin-top: 9px;
    color: #94a3b8;
    font-size: 11px;
    line-height: 1.7;
}

.form-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}

.submit-btn {
    padding: 14px 22px;
    background: linear-gradient(
        90deg,
        #2563eb,
        #0891b2
    );
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.submit-btn:hover {
    opacity: 0.9;
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.cancel-btn {
    display: inline-block;
    padding: 14px 22px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 9px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}

/* ALERTS */
.alert {
    padding: 15px 18px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.7;
    margin-bottom: 22px;
}

.alert.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert.info {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

/* INFORMATION */
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
    line-height: 1.8;
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
    line-height: 1.7;
}

.notice-card {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    padding: 20px;
}

.notice-card h3 {
    color: #1d4ed8;
    font-size: 13px;
    margin-bottom: 10px;
}

.notice-card p {
    color: #475569;
    font-size: 12px;
    line-height: 1.8;
}

.footer {
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 30px;
    padding: 18px 0;
}

/* RESPONSIVE */
@media(max-width:1100px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: repeat(2, minmax(0,1fr));
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

    .stats-grid {
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

    <!-- HEADER -->
    <header class="topbar">

        <div class="welcome">

            <div class="page-label">
                EMPLOYEE PORTAL / ASSET RETURNS
            </div>

            <h1>Request Asset Return</h1>

            <p>
                Manage the return of your assigned equipment.
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

        <h2>Ready to Return an Asset?</h2>

        <p>
            Select your assigned equipment, provide a
            reason for returning it, and submit your
            request for administrator review.
        </p>

    </section>

    <!-- STATISTICS -->
    <section class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">💻</div>

            <h3><?= $asset_count ?></h3>

            <p>Currently Assigned Assets</p>

        </div>

        <div class="stat-card">

            <div class="stat-icon">⏳</div>

            <h3><?= $pending_count ?></h3>

            <p>Pending Return Requests</p>

        </div>

        <div class="stat-card">

            <div class="stat-icon">↩️</div>

            <h3><?= $available_count ?></h3>

            <p>Assets Eligible for Return Request</p>

        </div>

    </section>

    <div class="content-grid">

        <!-- RETURN FORM -->
        <section class="form-card">

            <div class="card-heading">

                <h3>Asset Return Request Form</h3>

                <p>
                    Choose an assigned asset and provide
                    the reason for your return request.
                </p>

            </div>

            <?php if ($message !== ""): ?>

                <div class="alert success" role="alert">
                    <?= e($message) ?>
                </div>

            <?php endif; ?>

            <?php if ($error !== ""): ?>

                <div class="alert error" role="alert">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <?php if ($asset_count > 0 && $available_count > 0): ?>

                <form method="POST" action="request_return.php">

                    <div class="form-group">

                        <label for="asset_id">
                            Select Assigned Asset
                            <span class="required">*</span>
                        </label>

                        <select
                            name="asset_id"
                            id="asset_id"
                            required
                        >

                            <option value="">
                                -- Choose Your Assigned Asset --
                            </option>

                            <?php foreach ($assets as $asset): ?>

                                <?php
                                $is_pending = in_array(
                                    (int) $asset["id"],
                                    $pending_asset_ids,
                                    true
                                );
                                ?>

                                <option
                                    value="<?= (int) $asset["id"] ?>"
                                    <?= $selected_asset === (string) $asset["id"]
                                        ? "selected" : "" ?>
                                    <?= $is_pending ? "disabled" : "" ?>
                                >
                                    <?= e(
                                        $asset["asset_code"] .
                                        " - " .
                                        $asset["asset_name"] .
                                        " (" .
                                        $asset["category"] .
                                        ")"
                                    ) ?>

                                    <?= $is_pending ? " - Return Pending" : "" ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <p class="helper-text">
                            Assets with pending return requests
                            cannot be selected again.
                        </p>

                    </div>

                    <div class="form-group">

                        <label for="reason">
                            Reason for Return
                            <span class="required">*</span>
                        </label>

                        <textarea
                            name="reason"
                            id="reason"
                            maxlength="2000"
                            placeholder="Example: My project is completed, the equipment is no longer required, or I have received a replacement."
                            required
                        ><?= e($reason) ?></textarea>

                        <p class="helper-text">
                            Explain why you want to return this asset.
                        </p>

                    </div>

                    <div class="form-actions">

                        <button
                            type="submit"
                            class="submit-btn"
                        >
                            Submit Return Request
                        </button>

                        <a
                            href="employee_dashboard.php"
                            class="cancel-btn"
                        >
                            Back to Dashboard
                        </a>

                    </div>

                </form>

            <?php elseif ($asset_count > 0): ?>

                <div class="alert info">
                    All your currently assigned assets already
                    have pending return requests.
                    Please wait for administrator review.
                </div>

                <a href="employee_dashboard.php"
                   class="cancel-btn">
                    Back to Dashboard
                </a>

            <?php else: ?>

                <div class="alert info">
                    No assigned assets were found for your account.
                    If you believe this is incorrect,
                    please contact the administrator.
                </div>

                <a href="employee_dashboard.php"
                   class="cancel-btn">
                    Back to Dashboard
                </a>

            <?php endif; ?>

        </section>

        <!-- INFORMATION PANEL -->
        <aside class="info-column">

            <div class="info-card">

                <div class="info-icon">↩️</div>

                <h3>Return Process</h3>

                <div class="steps">

                    <div class="step">

                        <div class="step-number">1</div>

                        <div>
                            <strong>Select Asset</strong>
                            <p>
                                Choose the equipment
                                assigned to your account.
                            </p>
                        </div>

                    </div>

                    <div class="step">

                        <div class="step-number">2</div>

                        <div>
                            <strong>Submit Reason</strong>
                            <p>
                                Explain why you want
                                to return the equipment.
                            </p>
                        </div>

                    </div>

                    <div class="step">

                        <div class="step-number">3</div>

                        <div>
                            <strong>Admin Review</strong>
                            <p>
                                Your return request will
                                be reviewed by the administrator.
                            </p>
                        </div>

                    </div>

                </div>

            </div>

            <div class="notice-card">

                <h3>Important Notice</h3>

                <p>
                    Submitting a return request does not
                    immediately remove the asset from your
                    account. The asset remains assigned until
                    the administrator processes the return.
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