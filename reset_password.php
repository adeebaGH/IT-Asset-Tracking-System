<?php
session_start();
require_once "config/database.php";
$message = "";
$error = "";
$token = trim($_GET["token"] ?? $_POST["token"] ?? "");
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $error = "Invalid or missing reset token. Please generate a new reset link.";
    } else {
        $token_hash = hash("sha256", $token);
        $stmt = $conn->prepare("
            SELECT id, user_id
            FROM password_reset_tokens
            WHERE token_hash = ?
              AND used = 0
              AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset = $result->fetch_assoc();
        $stmt->close();
        if (!$reset) {
            $error = "This reset link is invalid or has expired. Please generate a new one.";
        } else {
            $new_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $new_password, $reset["user_id"]);
            if ($update->execute() && $update->affected_rows >= 0) {
                $update->close();
                $mark = $conn->prepare(
                    "UPDATE password_reset_tokens SET used = 1 WHERE id = ? AND used = 0"
                );
                $mark->bind_param("i", $reset["id"]);
                $mark->execute();
                $mark->close();
                $message = "Password reset successfully! You can now log in with your new password.";
                $token = "";
            } else {
                $error = "Could not update the password. Please try again.";
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
    <title>Reset Password | IT Asset Tracking</title>
    <style>
        * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #07152e, #123c70);
    }
    .card {
        width: 100%;
        max-width: 440px;
        padding: 35px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 18px 50px rgba(0,0,0,.25);
    }
    h1 { color: #123c70; margin-top: 0; }
    p { color: #64748b; line-height: 1.6; }
    label {
        display: block;
        margin: 18px 0 8px;
        font-weight: bold;
        color: #172033;
    }
    input {
        width: 100%;
        padding: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 15px;
    }
    button {
        width: 100%;
        margin-top: 22px;
        padding: 13px;
        border: none;
        border-radius: 8px;
        background: #087cf0;
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
    }
    .message, .error {
        padding: 13px;
        margin: 15px 0;
        border-radius: 8px;
        line-height: 1.5;
    }
    .message { background: #dcfce7; color: #166534; }
    .error { background: #fee2e2; color: #991b1b; }
    a {
        display: block;
        margin-top: 20px;
        text-align: center;
        color: #087cf0;
        text-decoration: none;
    }
</style>
</head>
<body>
    <div class="card">
        <h1>Reset Password 🔐</h1>
        <p>Choose a new password for your admin account.</p>
    <?php if ($message): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
            <a href="login.php">Go to Login</a>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (preg_match('/^[a-f0-9]{64}$/', $token)): ?>
            <form method="POST">
                <input type="hidden" name="token"
                       value="<?= htmlspecialchars($token) ?>">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password"
                       minlength="8" required autocomplete="new-password">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password"
                       name="confirm_password" minlength="8"
                       required autocomplete="new-password">
                <button type="submit">Reset Password</button>
            </form>
        <?php elseif (!$error): ?>
            <div class="error">
                Open the reset link generated from the Forgot Password page.
            </div>
            <a href="forgot_password.php">Generate a New Reset Link</a>
        <?php endif; ?>
    <?php endif; ?>
    <a href="login.php">← Back to Login</a>
</div>
</body>
</html>