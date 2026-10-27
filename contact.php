<?php
require_once "config/database.php";

$message_sent = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $employee_id = trim($_POST["employee_id"]);
    $email = trim($_POST["email"]);
    $department = trim($_POST["department"]);
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);

    if ($name && $email && $department && $subject && $message) {

        $stmt = $conn->prepare(
            "INSERT INTO contact_messages
            (name, employee_id, email, department, subject, message)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssss",
            $name,
            $employee_id,
            $email,
            $department,
            $subject,
            $message
        );

        if ($stmt->execute()) {
            $message_sent = true;
        } else {
            $error = "Something went wrong. Please try again.";
        }

        $stmt->close();

    } else {
        $error = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us | AssetTrack</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #0f172a;
        }

        /* NAVBAR */

        nav {
            height: 72px;
            padding: 0 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;

            background: rgba(255,255,255,0.95);
            border-bottom: 1px solid #e2e8f0;
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            color: #0f172a;
        }

        .logo span {
            color: #4f46e5;
        }

        nav a {
            text-decoration: none;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
            margin-left: 28px;
            transition: 0.3s;
        }

        nav a:hover {
            color: #4f46e5;
        }

        .admin-btn {
            background: #4f46e5;
            color: white !important;
            padding: 10px 18px;
            border-radius: 8px;
        }

        .admin-btn:hover {
            background: #4338ca;
        }

        /* HERO */

        .hero {
            text-align: center;
            padding: 65px 20px 45px;

            background:
                radial-gradient(circle at top left, #dbeafe, transparent 35%),
                radial-gradient(circle at top right, #ede9fe, transparent 35%),
                #f8fafc;
        }

        .badge {
            display: inline-block;
            background: #eef2ff;
            color: #4f46e5;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .hero h1 {
            font-size: 44px;
            margin-bottom: 15px;
            letter-spacing: -1px;
        }

        .hero h1 span {
            color: #4f46e5;
        }

        .hero p {
            max-width: 650px;
            margin: auto;
            color: #64748b;
            line-height: 1.7;
            font-size: 16px;
        }

        /* MAIN */

        .container {
            max-width: 1150px;
            margin: 20px auto 70px;
            padding: 0 20px;

            display: grid;
            grid-template-columns: 0.85fr 1.15fr;
            gap: 30px;
        }

        /* INFO CARD */

        .info {
            background: linear-gradient(145deg, #0f172a, #1e1b4b);
            color: white;
            padding: 42px;
            border-radius: 22px;
            position: relative;
            overflow: hidden;

            box-shadow: 0 20px 50px rgba(15,23,42,0.15);
        }

        .info::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            background: rgba(99,102,241,0.18);
            border-radius: 50%;
            right: -90px;
            bottom: -90px;
        }

        .info h2 {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .info > p {
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 35px;
        }

        .info-item {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }

        .icon {
            width: 42px;
            height: 42px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
            flex-shrink: 0;
        }

        .info-item strong {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .info-item span {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.5;
        }

        /* FORM */

        .form-box {
            background: white;
            padding: 42px;
            border-radius: 22px;

            box-shadow: 0 15px 45px rgba(15,23,42,0.08);

            border: 1px solid #e2e8f0;
        }

        .form-box h2 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .form-subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 28px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #334155;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 13px 14px;

            border: 1px solid #cbd5e1;
            border-radius: 10px;

            outline: none;
            font-size: 14px;
            color: #0f172a;

            background: #fff;

            transition: 0.25s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.10);
        }

        textarea {
            height: 140px;
            resize: vertical;
        }

        textarea::placeholder,
        input::placeholder {
            color: #94a3b8;
        }

        /* BUTTON */

        button {
            width: 100%;
            padding: 14px;

            background: linear-gradient(135deg, #4f46e5, #6366f1);

            color: white;
            border: none;
            border-radius: 10px;

            font-size: 15px;
            font-weight: 700;

            cursor: pointer;

            box-shadow: 0 8px 20px rgba(79,70,229,0.25);

            transition: 0.3s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(79,70,229,0.30);
        }

        /* ALERTS */

        .success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;

            padding: 13px 15px;
            border-radius: 10px;

            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 600;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;

            padding: 13px 15px;
            border-radius: 10px;

            margin-bottom: 22px;
            font-size: 14px;
        }

        /* FOOTER */

        footer {
            text-align: center;
            padding: 25px;
            background: #0f172a;
            color: #94a3b8;
            font-size: 13px;
        }

        /* RESPONSIVE */

        @media (max-width: 850px) {

            .container {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 34px;
            }

        }

        @media (max-width: 600px) {

            .row {
                grid-template-columns: 1fr;
            }

            .form-box,
            .info {
                padding: 28px;
            }

            nav {
                padding: 0 5%;
            }

            nav a {
                display: none;
            }

            .admin-btn {
                display: inline-block !important;
            }

        }

    </style>

</head>

<body>

<!-- NAVBAR -->

<nav>

    <div class="logo">
        Asset<span>Track</span>
    </div>

    <div>

        <a href="home.php">Home</a>

        <a href="#contact">Contact</a>

        <a href="login.php" class="admin-btn">
            Admin Login
        </a>

    </div>

</nav>


<!-- HERO -->

<section class="hero">

    <div class="badge">
        AssetTrack Support
    </div>

    <h1>
        How Can We <span>Help?</span>
    </h1>

    <p>
        Have an issue with an IT asset or need assistance?
        Send us a message and our support team will get back to you.
    </p>

</section>


<!-- CONTACT SECTION -->

<div class="container" id="contact">

    <!-- LEFT -->

    <div class="info">

        <h2>Let's Talk</h2>

        <p>
            Whether you need help with an asset, have a technical
            issue, or need information about the system, we're here
            to help.
        </p>


        <div class="info-item">

            <div class="icon">📧</div>

            <div>

                <strong>Email</strong>

                <span>
                    support@assettrack.com
                </span>

            </div>

        </div>


        <div class="info-item">

            <div class="icon">💻</div>

            <div>

                <strong>Support Team</strong>

                <span>
                    IT Asset Management Team
                </span>

            </div>

        </div>


        <div class="info-item">

            <div class="icon">🕐</div>

            <div>

                <strong>Working Hours</strong>

                <span>
                    Monday – Friday<br>
                    9:00 AM – 6:00 PM
                </span>

            </div>

        </div>

    </div>


    <!-- RIGHT -->

    <div class="form-box">

        <h2>Send us a Message</h2>

        <p class="form-subtitle">
            Fill in the details below and we'll get back to you.
        </p>


        <?php if ($message_sent): ?>

            <div class="success">
                ✓ Your message has been submitted successfully!
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="row">

                <div class="field">

                    <label>
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter your name"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Employee ID
                    </label>

                    <input
                        type="text"
                        name="employee_id"
                        placeholder="e.g. EMP001"
                    >

                </div>

            </div>


            <div class="row">

                <div class="field">

                    <label>
                        Email *
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="you@example.com"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Department *
                    </label>

                    <select name="department" required>

                        <option value="">
                            Select Department
                        </option>

                        <option>IT Department</option>
                        <option>HR Department</option>
                        <option>Finance Department</option>
                        <option>Sales Department</option>
                        <option>Marketing Department</option>
                        <option>Admin Department</option>
                        <option>Design Department</option>
                        <option>Customer Support</option>
                        <option>Other</option>

                    </select>

                </div>

            </div>


            <div class="field">

                <label>
                    Subject *
                </label>

                <input
                    type="text"
                    name="subject"
                    placeholder="What can we help you with?"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Message *
                </label>

                <textarea
                    name="message"
                    placeholder="Describe your issue or request..."
                    required
                ></textarea>

            </div>


            <button type="submit">
                Send Message →
            </button>

        </form>

    </div>

</div>


<footer>

    © 2026 AssetTrack — IT Asset Management System

</footer>


</body>

</html>