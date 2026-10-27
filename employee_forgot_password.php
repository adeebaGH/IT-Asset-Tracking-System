<?php
require_once "config/database.php";
$message = "";
$error = "";
$reset_link = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, email FROM employees WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();
        $stmt->close();
        if ($employee) {
            // Store only a hash of the random reset token.
            $token = bin2hex(random_bytes(32));
            $token_hash = hash("sha256", $token);
            $expires_at = date("Y-m-d H:i:s", time() + 900);
            $conn->query("
                CREATE TABLE IF NOT EXISTS employee_password_reset_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    token_hash CHAR(64) NOT NULL UNIQUE,
                    expires_at DATETIME NOT NULL,
                    used TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            // Invalidate previous unused reset tokens for this employee.
            $old = $conn->prepare("
                UPDATE employee_password_reset_tokens
                SET used = 1
                WHERE employee_id = ? AND used = 0
            ");
            $old->bind_param("i", $employee["id"]);
            $old->execute();
            $old->close();
            $insert = $conn->prepare("
                INSERT INTO employee_password_reset_tokens
                    (employee_id, token_hash, expires_at)
                VALUES (?, ?, ?)
            ");
            $insert->bind_param(
                "iss",
                $employee["id"],
                $token_hash,
                $expires_at
            );
            if ($insert->execute()) {
                $reset_link = "employee_reset_password.php?token="
                    . urlencode($token);
                $message = "Your local testing reset link is ready. It expires in 15 minutes.";
            } else {
                $error = "Could not generate a reset link. Please try again.";
            }
            $insert->close();
        } else {
            $message = "If the email belongs to an employee account, a reset link can be generated.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Forgot Password | IT Asset Tracking</title>
<style>
    * {
        box-sizing: border-box;
    }
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
        background: white;
        border-radius: 18px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
    }
    h1 {
        color: #123c70;
        margin-top: 0;
    }
    p {
        color: #64748b;
        line-height: 1.6;
    }
    label {
        display: block;
        margin: 20px 0 8px;
        font-weight: bold;
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
        margin-top: 18px;
        padding: 13px;
        border: none;
        border-radius: 8px;
        background: #087cf0;
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
    }
    .message, .error, .link-box {
        padding: 13px;
        margin-top: 15px;
        border-radius: 8px;
        overflow-wrap: anywhere;
        line-height: 1.5;
    }
    .message {
        background: #dcfce7;
        color: #166534;
    }
    .error {
        background: #fee2e2;
        color: #991b1b;
    }
    .link-box {
        background: #eff6ff;
        color: #123c70;
    }
    a {
        color: #087cf0;
        text-decoration: none;
    }
    .back {
        display: block;
        text-align: center;
        margin-top: 22px;
    }
    .note {
        font-size: 12px;
    }
</style>
</head>
<body>
    <div class="card">
        <h1>Employee Forgot Password?</h1>
    <p>
        Enter your registered employee email address to generate
        a password reset link.
    </p>
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
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your employee email"
            required
        >
        <button type="submit">Generate Reset Link</button>
    </form>
    <?php if ($reset_link): ?>
        <div class="link-box">
            <strong>Local testing link:</strong>
            <p>
                <a href="<?= htmlspecialchars($reset_link) ?>">
                    Open Employee Password Reset Page
                </a>
            </p>
            <p class="note">
                This link is displayed for local testing only.
                It is not sent by email.
            </p>
        </div>
    <?php endif; ?>
    <a class="back" href="employee_login.php">
        ← Back to Employee Login
    </a>
</div>
</body>
</html>