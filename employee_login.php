<?php
session_start();
require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $employee_id = trim($_POST["employee_id"]);
    $password = trim($_POST["password"]);

    if ($employee_id == "" || $password == "") {
        $error = "Please enter Employee ID and Password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, employee_id, name, email, department, password
             FROM employees
             WHERE employee_id = ? AND password = ?"
        );

        $stmt->bind_param("ss", $employee_id, $password);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $employee = $result->fetch_assoc();

            $_SESSION["employee_id"] = $employee["employee_id"];
            $_SESSION["employee_name"] = $employee["name"];
            $_SESSION["employee_email"] = $employee["email"];
            $_SESSION["employee_department"] = $employee["department"];

            header("Location: employee_dashboard.php");
            exit();

        } else {
            $error = "Invalid Employee ID or Password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login | AssetTrack</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2ff, #f8fafc);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 900px;
            max-width: 95%;
            min-height: 520px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 15px 45px rgba(0,0,0,0.12);
        }

        .left {
            width: 50%;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 60px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .left h1 {
            font-size: 38px;
            margin-bottom: 15px;
        }

        .left p {
            font-size: 16px;
            line-height: 1.7;
            opacity: 0.9;
        }

        .right {
            width: 50%;
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right h2 {
            font-size: 30px;
            color: #111827;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            .login-box {
                flex-direction: column;
            }

            .left,
            .right {
                width: 100%;
            }

            .left {
                padding: 35px;
            }

            .right {
                padding: 40px 30px;
            }
        }
    </style>
</head>

<body>

<div class="login-box">

    <div class="left">
        <h1>AssetTrack</h1>
        <p>
            Employee Asset Management Portal
        </p>
        <br>
        <p>
            Request assets, track your requests and manage your assigned
            company assets from one place.
        </p>
    </div>

    <div class="right">

        <h2>Employee Login</h2>
        <p class="subtitle">Sign in to your employee account</p>

        <?php if ($error != ""): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Employee ID</label>
            <input
                type="text"
                name="employee_id"
                placeholder="Enter Employee ID"
                required
            >

            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Enter Password"
                required
            >

            <button type="submit">Login</button>

            <div style="text-align: center; margin-top: 15px;">
             <a href="employee_forgot_password.php"
             style="color: #087cf0; text-decoration: none; font-weight: 600;">
                Forgot Password?
              </a>
           </div>

        </form>

        <div class="back">
            <a href="home.php">← Back to Home</a>
        </div>

    </div>

</div>

</body>
</html>