<?php
session_start();
require_once "config/database.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $stmt = $conn->prepare(
        "SELECT id, username, email, password
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stored_password = $user["password"];
        // Support both existing plaintext passwords and password hashes.
        $password_matches = password_verify($password, $stored_password)
            || hash_equals((string)$stored_password, (string)$password);
        if ($password_matches) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["email"] = $user["email"];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - AssetTrack</title>
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
    }
    body {
        min-height: 100vh;
        background: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .login-wrapper {
        width: 900px;
        max-width: 100%;
        min-height: 520px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: white;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    }
    .login-left {
        background: linear-gradient(135deg, #1d4ed8, #2563eb, #3b82f6);
        color: white;
        padding: 55px 45px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .brand {
        font-size: 30px;
        font-weight: bold;
        margin-bottom: 25px;
    }
    .login-left h1 {
        font-size: 32px;
        line-height: 1.2;
        margin-bottom: 18px;
    }
    .login-left p {
        color: #dbeafe;
        font-size: 15px;
        line-height: 1.7;
    }
    .features {
        margin-top: 30px;
    }
    .feature {
        margin-bottom: 15px;
        font-size: 14px;
    }
    .login-right {
        padding: 55px 45px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .login-title {
        font-size: 27px;
        font-weight: bold;
        color: #0f172a;
        margin-bottom: 8px;
    }
    .login-subtitle {
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }
    input {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
    }
    input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
    }
    .login-btn {
        width: 100%;
        padding: 13px;
        border: none;
        border-radius: 8px;
        background: #2563eb;
        color: white;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 5px;
    }
    .login-btn:hover {
        background: #1d4ed8;
    }
    .forgot-password {
        display: block;
        text-align: right;
        margin-top: -8px;
        margin-bottom: 20px;
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }
    .forgot-password:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    .error {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px 14px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .footer-text {
        text-align: center;
        color: #94a3b8;
        font-size: 12px;
        margin-top: 25px;
    }
    @media (max-width: 700px) {
        .login-wrapper {
            grid-template-columns: 1fr;
        }
        .login-left {
            padding: 35px;
        }
        .login-left h1 {
            font-size: 25px;
        }
        .features {
            display: none;
        }
        .login-right {
            padding: 40px 30px;
        }
    }
</style>
</head>
<body>
<div class="login-wrapper">
<div class="login-left">
    <div class="brand">🖥️ AssetTrack</div>
    <h1>IT Asset Tracking System</h1>
    <p>
        Manage, monitor and track your organization's
        IT assets from one simple dashboard.
    </p>
    <div class="features">
        <div class="feature">✓ Asset Inventory Management</div>
        <div class="feature">✓ Employee Asset Assignment</div>
        <div class="feature">✓ Asset Reports & Analytics</div>
        <div class="feature">✓ Easy Asset Tracking</div>
    </div>
</div>
<div class="login-right">
    <div class="login-title">Welcome Back Admin 👋</div>
    <div class="login-subtitle">
        Sign in to access your AssetTrack dashboard.
    </div>
    <?php if ($error !== ""): ?>
        <div class="error">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                autocomplete="username"
                required
            >
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
            >
        </div>
        <a href="forgot_password.php" class="forgot-password">
            Forgot Password?
        </a>
        <button type="submit" class="login-btn">
            🔐 Sign In
        </button>
    </form>
    <div class="footer-text">
        AssetTrack • IT Asset Management System
    </div>
</div>
</div>
</body>
</html>