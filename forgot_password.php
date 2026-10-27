<?php
session_start();
require_once "config/database.php";
$message = "";
$error = "";
$reset_link = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            // Generate a secure, one-time reset token.
            $token = bin2hex(random_bytes(32));
            $token_hash = hash("sha256", $token);
            $expires_at = date("Y-m-d H:i:s", time() + 900);
            // Create the token table if it does not exist.
            $conn->query("
                CREATE TABLE IF NOT EXISTS password_reset_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token_hash CHAR(64) NOT NULL UNIQUE,
                    expires_at DATETIME NOT NULL,
                    used TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            // Invalidate earlier unused tokens for this user.
            $delete = $conn->prepare(
                "UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0"
            );
            $delete->bind_param("i", $user["id"]);
            $delete->execute();
            $delete->close();
            $insert = $conn->prepare(
                "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)"
            );
            $insert->bind_param("iss", $user["id"], $token_hash, $expires_at);
            if ($insert->execute()) {
                $reset_link = "reset_password.php?token=" . urlencode($token);
                $message = "For local testing, use the reset link below. It expires in 15 minutes.";
            } else {
                $error = "Could not create a reset link. Please try again.";
            }
            $insert->close();
        } else {
            // Do not reveal whether an account exists.
            $message = "If that email belongs to an account, a reset link can be generated.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | IT Asset Tracking</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #07152e, #123c70);
            color: #172033;
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(0,0,0,.25);
        }
        h1 { margin-top: 0; color: #123c70; }
        p { color: #64748b; line-height: 1.6; }
        label { display: block; margin: 20px 0 8px; font-weight: bold; }
        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }
        button {
            width: 100%;
            margin-top: 18px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #087cf0;
            color: white;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
        }
        .message, .error, .link-box {
            padding: 12px;
            margin-top: 15px;
            border-radius: 8px;
            overflow-wrap: anywhere;
        }
        .message { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .link-box { background: #eff6ff; }
        a { color: #087cf0; }
        .back { display: block; text-align: center; margin-top: 22px; }
        .note { font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Forgot Password?</h1>
        <p>Enter your registered admin email address to generate a password reset link.</p>
    <?php if ($message): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <label for="email">Registered Email</label>
        <input type="email" id="email" name="email"
               placeholder="Enter your email" required>
        <button type="submit">Generate Reset Link</button>
    </form>
    <?php if ($reset_link): ?>
        <div class="link-box">
            <strong>Local testing link:</strong><br><br>
            <a href="<?= htmlspecialchars($reset_link) ?>">
                Open Password Reset Page
            </a>
            <p class="note">Demo only: this link is displayed locally, not emailed. Do not deploy this flow publicly without configuring email delivery and reviewing production security.</p>
        </div>
    <?php endif; ?>
    <a class="back" href="login.php">← Back to Login</a>
</div>
</body>
</html>