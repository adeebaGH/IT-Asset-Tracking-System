<?php
require_once "config/database.php";
/* DATABASE STATISTICS */
function getCount($conn, $sql) {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row["total"];
    }
    return 0;
}
$total_assets = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM assets"
);
$assigned_assets = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM assets WHERE status = 'Assigned'"
);
$available_assets = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM assets WHERE status = 'Available'"
);
$maintenance_assets = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM assets WHERE status = 'Maintenance'"
);
$pending_requests = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM asset_requests WHERE status = 'Pending'"
);
$total_employees = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM employees"
);
$utilization = $total_assets > 0
    ? min(100, round(($assigned_assets / $total_assets) * 100))
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IT Asset Tracking System | Smart Asset Management</title>
<meta name="description"
      content="Manage IT assets, employee allocations and asset requests through a centralized IT Asset Tracking System.">
<style>
:root {
    --bg: #06101f;
    --bg-light: #0a1930;
    --card: rgba(15, 35, 61, 0.78);
    --cyan: #00d4ff;
    --blue: #4285ff;
    --green: #20e69a;
    --white: #f4f8ff;
    --muted: #a6b7cc;
    --border: rgba(255,255,255,0.10);
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
html {
    scroll-behavior: smooth;
    scroll-padding-top: 90px;
}
body {
    font-family: "Segoe UI", Arial, Helvetica, sans-serif;
    background:
        radial-gradient(ellipse at 10% 10%, rgba(0,212,255,.10), transparent 35%),
        radial-gradient(ellipse at 90% 35%, rgba(66,133,255,.09), transparent 38%),
        var(--bg);
    background-attachment: fixed;
    color: var(--white);
    overflow-x: hidden;
    line-height: 1.6;
}
a {
    -webkit-tap-highlight-color: transparent;
}
.container {
    width: min(1160px, 90%);
    margin: auto;
}
/* =====================================
   ANIMATED BACKGROUND
===================================== */
.background-glow {
    position: fixed;
    width: 500px;
    height: 500px;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    opacity: .10;
    animation: glowMove 16s ease-in-out infinite alternate;
}
.glow-one {
    top: 10%;
    left: -250px;
    background: #00d4ff;
}
.glow-two {
    top: 45%;
    right: -280px;
    background: #456cff;
    animation-delay: -5s;
}
@keyframes glowMove {
    from {
        transform: translate3d(0, 0, 0) scale(1);
    }
    to {
        transform: translate3d(100px, 80px, 0) scale(1.2);
    }
}
/* MOUSE FOLLOW LIGHT */
.mouse-glow {
    position: fixed;
    top: 0;
    left: 0;
    width: 450px;
    height: 450px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(0,212,255,.16) 0%,
        rgba(66,133,255,.08) 35%,
        transparent 70%
    );
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 0;
    opacity: 0;
    transition: opacity .35s ease;
    will-change: left, top;
}
/* PARTICLES */
.particle {
    position: fixed;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--cyan);
    box-shadow: 0 0 12px var(--cyan);
    opacity: .4;
    pointer-events: none;
    z-index: 0;
    animation: particleFloat 9s ease-in-out infinite;
}
@keyframes particleFloat {
    0%, 100% {
        transform: translate(0, 0);
        opacity: .12;
    }
    50% {
        transform: translate(24px, -65px);
        opacity: .65;
    }
}
/* CONTENT ABOVE BACKGROUND EFFECTS */
nav,
main,
section,
footer {
    position: relative;
    z-index: 1;
}
/* =====================================
   NAVIGATION
===================================== */
nav {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(6,16,31,.82);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--border);
}
.navbar {
    min-height: 78px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}
.logo {
    color: white;
    font-size: 21px;
    font-weight: 850;
    text-decoration: none;
    white-space: nowrap;
    letter-spacing: -.5px;
}
.logo-icon {
    display: inline-grid;
    place-items: center;
    width: 36px;
    height: 36px;
    margin-right: 7px;
    vertical-align: middle;
    border-radius: 10px;
    color: #06101f;
    background: linear-gradient(135deg, var(--cyan), #80edff);
}
.logo span {
    color: var(--cyan);
}
.nav-links {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 19px;
}
.nav-links > a:not(.btn) {
    color: var(--muted);
    font-size: 12px;
    text-decoration: none;
    transition: color .25s ease;
}
.nav-links > a:not(.btn):hover {
    color: var(--cyan);
}
/* =====================================
   BUTTONS
===================================== */
.btn {
    position: relative;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 12px 21px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 750;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    overflow: hidden;
    transition: transform .25s ease,
                box-shadow .25s ease,
                background .25s ease,
                border-color .25s ease;
}
.btn:hover {
    transform: translateY(-3px);
}
.btn-primary {
    color: #031321;
    background: linear-gradient(120deg, var(--cyan), #79e9ff);
    box-shadow: 0 5px 20px rgba(0,212,255,.13);
}
.btn-primary:hover {
    box-shadow: 0 10px 30px rgba(0,212,255,.30);
}
.btn-primary::after {
    content: "";
    position: absolute;
    top: 0;
    left: -130%;
    width: 65%;
    height: 100%;
    transform: skewX(-22deg);
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,.55),
        transparent
    );
    pointer-events: none;
}
.btn-primary:hover::after {
    animation: buttonShine .65s ease;
}
@keyframes buttonShine {
    to {
        left: 150%;
    }
}
.btn-secondary {
    color: white;
    background: rgba(255,255,255,.045);
    border-color: rgba(255,255,255,.17);
}
.btn-secondary:hover {
    background: rgba(0,212,255,.07);
    border-color: rgba(0,212,255,.65);
    box-shadow: 0 8px 25px rgba(0,212,255,.10);
}
/* =====================================
   HERO SECTION
===================================== */
.hero {
    padding: 95px 0 80px;
}
.hero-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 55px;
}
.hero-content {
    animation: fadeUp .8s ease both;
}
.badge {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 8px 14px;
    margin-bottom: 24px;
    border-radius: 50px;
    border: 1px solid rgba(0,212,255,.25);
    background: rgba(0,212,255,.065);
    color: var(--cyan);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.2px;
}
.status-dot {
    width: 7px;
    height: 7px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--green);
    box-shadow: 0 0 12px var(--green);
    animation: pulse 1.8s infinite;
}
@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: .5;
        transform: scale(1.35);
    }
}
.hero h1 {
    font-size: clamp(39px, 5vw, 65px);
    line-height: 1.1;
    letter-spacing: -2px;
    margin-bottom: 23px;
}
.gradient-text {
    color: var(--cyan);
    background: linear-gradient(100deg, #00d4ff, #78e9ff, #7d9cff);
    background-size: 200% auto;
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: gradientShift 5s ease infinite;
}
@keyframes gradientShift {
    0%, 100% {
        background-position: 0% center;
    }
    50% {
        background-position: 100% center;
    }
}
.hero-description {
    max-width: 510px;
    margin-bottom: 30px;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.9;
}
.hero-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 13px;
}
.hero-note {
    margin-top: 23px;
    color: #92a8c1;
    font-size: 11px;
    line-height: 2;
}
/* =====================================
   LIVE ASSET OVERVIEW
===================================== */
.dashboard {
    position: relative;
    padding: 25px;
    border-radius: 21px;
    background: linear-gradient(
        145deg,
        rgba(17,39,67,.94),
        rgba(8,25,46,.91)
    );
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 25px 80px rgba(0,0,0,.30);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: border-color .3s ease,
                box-shadow .3s ease;
    animation: fadeUp 1s ease both;
}
.dashboard:hover {
    border-color: rgba(0,212,255,.4);
    box-shadow: 0 25px 80px rgba(0,0,0,.30),
                0 0 30px rgba(0,212,255,.07);
}
.dashboard-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 23px;
}
.dashboard-top h3 {
    font-size: 17px;
}
.live-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: var(--green);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .7px;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 13px;
}
.stat-card {
    padding: 18px;
    min-width: 0;
    border-radius: 13px;
    background: rgba(255,255,255,.045);
    border: 1px solid rgba(255,255,255,.075);
    transition: transform .25s ease,
                background .25s ease,
                border-color .25s ease,
                box-shadow .25s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    background: rgba(0,212,255,.055);
    border-color: rgba(0,212,255,.42);
    box-shadow: 0 10px 24px rgba(0,212,255,.07);
}
.stat-icon {
    font-size: 22px;
    margin-bottom: 8px;
}
.stat-number {
    font-size: 30px;
    font-weight: 850;
    line-height: 1.3;
    color: white;
}
.stat-label {
    margin-top: 5px;
    color: var(--muted);
    font-size: 11px;
}
.dashboard-bottom {
    margin-top: 17px;
    padding: 17px;
    background: rgba(255,255,255,.035);
    border: 1px solid rgba(255,255,255,.04);
    border-radius: 12px;
}
.progress-heading {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 11px;
    font-size: 11px;
}
.progress-heading span:last-child {
    color: var(--cyan);
    font-weight: 800;
}
.progress-track {
    width: 100%;
    height: 7px;
    background: rgba(255,255,255,.10);
    border-radius: 20px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    width: 0;
    border-radius: 20px;
    background: linear-gradient(90deg, #00bce8, #658cff);
    box-shadow: 0 0 12px rgba(0,212,255,.3);
    transition: width 1.3s cubic-bezier(.2,.8,.2,1);
}
.pending-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 14px;
    color: var(--muted);
    font-size: 11px;
}
.pending-count {
    color: #ffc857;
    font-weight: 800;
}
/* =====================================
   SECTION HEADINGS
===================================== */
.section {
    padding: 76px 0;
}
.section-heading {
    max-width: 680px;
    margin: 0 auto 43px;
    text-align: center;
}
.section-heading h2 {
    margin-bottom: 12px;
    font-size: clamp(27px, 4vw, 39px);
    line-height: 1.25;
    letter-spacing: -.8px;
}
.section-heading h2 span {
    color: var(--cyan);
}
.section-heading p {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.8;
}
/* =====================================
   FEATURE CARDS
===================================== */
.features-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 19px;
}
.feature-card {
    position: relative;
    overflow: hidden;
    padding: 27px;
    border-radius: 16px;
    background: var(--card);
    border: 1px solid var(--border);
    transform-style: preserve-3d;
    transition: transform .25s ease,
                border-color .25s ease,
                box-shadow .25s ease;
}
.feature-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--cyan), var(--blue));
    transition: width .4s ease;
}
.feature-card:hover::before {
    width: 100%;
}
.feature-card:hover {
    border-color: rgba(0,212,255,.35);
    box-shadow: 0 15px 35px rgba(0,0,0,.2);
}
.feature-icon {
    display: grid;
    place-items: center;
    width: 49px;
    height: 49px;
    margin-bottom: 20px;
    border-radius: 13px;
    background: rgba(0,212,255,.09);
    border: 1px solid rgba(0,212,255,.10);
    color: var(--cyan);
    font-size: 22px;
    transition: transform .3s ease,
                background .3s ease;
}
.feature-card:hover .feature-icon {
    transform: translateY(-3px) scale(1.06);
    background: rgba(0,212,255,.16);
}
.feature-card h3 {
    margin-bottom: 10px;
    font-size: 16px;
}
.feature-card p {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.9;
}
/* =====================================
   HOW IT WORKS
===================================== */
.workflow {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 20px;
}
.workflow-card {
    padding: 28px 22px;
    text-align: center;
    background: rgba(255,255,255,.025);
    border: 1px solid var(--border);
    border-radius: 16px;
    transition: transform .25s ease,
                border-color .25s ease,
                background .25s ease;
}
.workflow-card:hover {
    transform: translateY(-5px);
    background: rgba(0,212,255,.035);
    border-color: rgba(0,212,255,.35);
}
.step-number {
    display: grid;
    place-items: center;
    width: 46px;
    height: 46px;
    margin: 0 auto 17px;
    border-radius: 50%;
    color: #041322;
    background: linear-gradient(135deg, var(--cyan), #78e9ff);
    font-weight: 900;
    box-shadow: 0 0 25px rgba(0,212,255,.12);
}
.workflow-card h3 {
    margin-bottom: 9px;
    font-size: 15px;
}
.workflow-card p {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.9;
}
/* =====================================
   LOGIN PORTALS
===================================== */
.portal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 22px;
}
.portal-card {
    position: relative;
    overflow: hidden;
    padding: 31px;
    border-radius: 18px;
    background: linear-gradient(
        145deg,
        rgba(16,37,65,.95),
        rgba(9,25,45,.95)
    );
    border: 1px solid var(--border);
    transition: transform .3s ease,
                border-color .3s ease,
                box-shadow .3s ease;
}
.portal-card::after {
    content: "";
    position: absolute;
    width: 170px;
    height: 170px;
    right: -95px;
    top: -95px;
    border-radius: 50%;
    background: rgba(0,212,255,.08);
    transition: transform .4s ease;
}
.portal-card:hover {
    transform: translateY(-6px);
    border-color: rgba(0,212,255,.38);
    box-shadow: 0 15px 35px rgba(0,0,0,.2);
}
.portal-card:hover::after {
    transform: scale(1.5);
}
.portal-card h3 {
    position: relative;
    z-index: 1;
    margin-bottom: 10px;
    font-size: 21px;
}
.portal-card p {
    position: relative;
    z-index: 1;
    margin-bottom: 22px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.9;
}
.portal-card .btn {
    position: relative;
    z-index: 2;
}
/* =====================================
   CONTACT SECTION
===================================== */
.cta-box {
    position: relative;
    overflow: hidden;
    padding: 48px 30px;
    text-align: center;
    border: 1px solid rgba(0,212,255,.22);
    border-radius: 21px;
    background: linear-gradient(
        120deg,
        rgba(0,212,255,.075),
        rgba(66,133,255,.09)
    );
}
.cta-box::before,
.cta-box::after {
    content: "";
    position: absolute;
    width: 150px;
    height: 150px;
    border: 1px solid rgba(0,212,255,.12);
    border-radius: 50%;
    pointer-events: none;
}
.cta-box::before {
    left: -70px;
    top: -70px;
}
.cta-box::after {
    right: -70px;
    bottom: -70px;
}
.cta-box h2 {
    margin-bottom: 12px;
    font-size: clamp(25px, 4vw, 36px);
}
.cta-box p {
    margin-bottom: 24px;
    color: var(--muted);
    font-size: 13px;
}
/* =====================================
   FOOTER
===================================== */
footer {
    margin-top: 65px;
    padding: 27px 0;
    background: rgba(3,10,20,.58);
    border-top: 1px solid var(--border);
}
.footer-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}
.footer-inner p {
    color: var(--muted);
    font-size: 11px;
}
.footer-links {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}
.footer-links a {
    color: var(--muted);
    font-size: 11px;
    text-decoration: none;
    transition: color .2s ease;
}
.footer-links a:hover {
    color: var(--cyan);
}
/* =====================================
   REVEAL ANIMATIONS
===================================== */
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(23px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.reveal {
    opacity: 0;
    transform: translateY(22px);
    transition: opacity .7s ease, transform .7s ease;
}
.reveal.visible {
    opacity: 1;
    transform: translateY(0);
}
/* =====================================
   RESPONSIVE DESIGN
===================================== */
@media (max-width: 1000px) {
    .navbar {
        padding: 15px 0;
        align-items: flex-start;
        flex-direction: column;
    }
    .nav-links {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 13px;
    }
    .hero-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    .hero {
        padding-top: 65px;
    }
    .hero-content {
        max-width: 700px;
    }
    .dashboard {
        max-width: 700px;
        width: 100%;
        margin: auto;
    }
    .features-grid {
        grid-template-columns: repeat(2, minmax(0,1fr));
    }
}
@media (max-width: 650px) {
    .container {
        width: 92%;
    }
    .mouse-glow {
        display: none;
    }
    .nav-links {
        gap: 10px;
    }
    .nav-links > a:not(.btn) {
        font-size: 11px;
    }
    .nav-links .btn {
        padding: 9px 12px;
        font-size: 11px;
    }
    .hero {
        padding: 50px 0 35px;
    }
    .hero h1 {
        font-size: clamp(36px, 10vw, 48px);
        letter-spacing: -1.2px;
    }
    .hero-description {
        font-size: 13px;
    }
    .hero-buttons {
        flex-direction: column;
    }
    .hero-buttons .btn {
        width: 100%;
    }
    .hero-note {
        font-size: 10px;
    }
    .dashboard {
        padding: 17px;
    }
    .stat-card {
        padding: 15px;
    }
    .stat-number {
        font-size: 25px;
    }
    .section {
        padding: 53px 0;
    }
    .section-heading {
        margin-bottom: 30px;
    }
    .section-heading p {
        font-size: 12px;
    }
    .features-grid,
    .workflow,
    .portal-grid {
        grid-template-columns: 1fr;
    }
    .feature-card,
    .portal-card {
        padding: 23px;
    }
    .cta-box {
        padding: 37px 19px;
    }
    .footer-inner {
        flex-direction: column;
        text-align: center;
    }
    .footer-links {
        justify-content: center;
    }
}
/* ACCESSIBILITY: RESPECT REDUCED MOTION */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
    .reveal {
        opacity: 1;
        transform: none;
    }
}
</style>
</head>
<body>
<div class="background-glow glow-one" aria-hidden="true"></div>
<div class="background-glow glow-two" aria-hidden="true"></div>
<div class="mouse-glow" id="mouseGlow" aria-hidden="true"></div>
<!-- NAVIGATION -->
<nav>
    <div class="container navbar">
        <a href="home.php" class="logo">
            <span class="logo-icon">▦</span>
            IT <span>Asset</span> Tracking
        </a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="#features">Features</a>
            <a href="#workflow">How It Works</a>
            <a href="contact.php">Contact</a>
            <a href="employee_login.php" class="btn btn-secondary">
                Employee Login
            </a>
            <a href="login.php" class="btn btn-primary">
                Admin Login ↗
            </a>
        </div>
    </div>
</nav>
<main>
<!-- HERO SECTION -->
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content">
            <div class="badge">
                <span class="status-dot"></span>
                SMART ASSET MANAGEMENT
            </div>
            <h1>
                Your Assets.<br>
                Your Control.<br>
                <span class="gradient-text">One Platform.</span>
            </h1>
            <p class="hero-description">
                Manage, monitor and track your organization's IT assets
                in one place. Keep asset information organized, monitor
                availability and simplify employee asset requests.
            </p>
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">
                    Admin Dashboard →
                </a>
                <a href="employee_login.php" class="btn btn-secondary">
                    Employee Portal ↗
                </a>
            </div>
            <div class="hero-note">
                ✓ Organized asset records &nbsp;·&nbsp;
                ✓ Request tracking &nbsp;·&nbsp;
                ✓ Centralized management
            </div>
        </div>
        <!-- DATABASE OVERVIEW -->
        <div class="dashboard">
            <div class="dashboard-top">
                <h3>Asset Overview</h3>
                <div class="live-label">
                    <span class="status-dot"></span>
                    DATABASE STATS
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💻</div>
                    <div class="stat-number count-up"
                         data-target="<?php echo $total_assets; ?>">0</div>
                    <div class="stat-label">Total Assets</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number count-up"
                         data-target="<?php echo $total_employees; ?>">0</div>
                    <div class="stat-label">Employees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-number count-up"
                         data-target="<?php echo $assigned_assets; ?>">0</div>
                    <div class="stat-label">Assigned Assets</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number count-up"
                         data-target="<?php echo $available_assets; ?>">0</div>
                    <div class="stat-label">Available Assets</div>
                </div>
            </div>
            <div class="dashboard-bottom">
                <div class="progress-heading">
                    <span>Asset Utilization</span>
                    <span><?php echo $utilization; ?>%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill"
                         data-width="<?php echo $utilization; ?>"></div>
                </div>
                <div class="pending-line">
                    <span>Pending Requests</span>
                    <span class="pending-count">
                        <?php echo $pending_requests; ?> Pending
                    </span>
                </div>
                <div class="pending-line">
                    <span>Under Maintenance</span>
                    <span><?php echo $maintenance_assets; ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- FEATURES -->
<section class="section reveal" id="features">
    <div class="container">
        <div class="section-heading">
            <h2>Everything You Need to <span>Manage Assets</span></h2>
            <p>
                A centralized system designed to make IT asset management
                more organized, accessible and efficient.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card tilt-card">
                <div class="feature-icon">💻</div>
                <h3>Asset Management</h3>
                <p>
                    Add, update, view and maintain IT asset records
                    through one centralized system.
                </p>
            </div>
            <div class="feature-card tilt-card">
                <div class="feature-icon">📊</div>
                <h3>Asset Overview</h3>
                <p>
                    View asset counts, availability, assignments and
                    pending requests using database statistics.
                </p>
            </div>
            <div class="feature-card tilt-card">
                <div class="feature-icon">👨‍💼</div>
                <h3>Employee Management</h3>
                <p>
                    Maintain employee records and organize information
                    required for asset allocation.
                </p>
            </div>
            <div class="feature-card tilt-card">
                <div class="feature-icon">📝</div>
                <h3>Asset Requests</h3>
                <p>
                    Employees can submit requests and check their
                    request status through the employee portal.
                </p>
            </div>
            <div class="feature-card tilt-card">
                <div class="feature-icon">🔐</div>
                <h3>Separate Portals</h3>
                <p>
                    Dedicated login pages provide separate entry points
                    for administrators and employees.
                </p>
            </div>
            <div class="feature-card tilt-card">
                <div class="feature-icon">📄</div>
                <h3>Reports</h3>
                <p>
                    Access organized asset information to support
                    monitoring and administrative work.
                </p>
            </div>
        </div>
    </div>
</section>
<!-- WORKFLOW -->
<section class="section reveal" id="workflow">
    <div class="container">
        <div class="section-heading">
            <h2>How the <span>System Works</span></h2>
            <p>
                A simple workflow for managing assets and employee requests.
            </p>
        </div>
        <div class="workflow">
            <div class="workflow-card tilt-card">
                <div class="step-number">01</div>
                <h3>Manage Assets</h3>
                <p>
                    The administrator adds assets and maintains
                    their details and availability.
                </p>
            </div>
            <div class="workflow-card tilt-card">
                <div class="step-number">02</div>
                <h3>Submit a Request</h3>
                <p>
                    Employees log in and submit an asset request
                    with the required information.
                </p>
            </div>
            <div class="workflow-card tilt-card">
                <div class="step-number">03</div>
                <h3>Review and Assign</h3>
                <p>
                    The administrator reviews requests and can
                    assign an available asset after approval.
                </p>
            </div>
        </div>
    </div>
</section>
<!-- LOGIN PORTALS -->
<section class="section reveal" id="portals">
    <div class="container">
        <div class="section-heading">
            <h2>Choose Your <span>Portal</span></h2>
            <p>
                Access the system through the appropriate login page.
            </p>
        </div>
        <div class="portal-grid">
            <div class="portal-card tilt-card">
                <div class="feature-icon">🛠️</div>
                <h3>Admin Portal</h3>
                <p>
                    Manage assets and employees, review requests,
                    allocate assets and access administrative features.
                </p>
                <a href="login.php" class="btn btn-primary">
                    Admin Login →
                </a>
            </div>
            <div class="portal-card tilt-card">
                <div class="feature-icon">🙋</div>
                <h3>Employee Portal</h3>
                <p>
                    Log in to submit asset requests, track request
                    status and access available employee features.
                </p>
                <a href="employee_login.php" class="btn btn-secondary">
                    Employee Login →
                </a>
            </div>
        </div>
    </div>
</section>
<!-- CONTACT -->
<section class="section reveal">
    <div class="container">
        <div class="cta-box">
            <h2>Need Help with Asset Management?</h2>
            <p>
                Visit our contact page to send a message or ask for assistance.
            </p>
            <a href="contact.php" class="btn btn-primary">
                Contact Us →
            </a>
        </div>
    </div>
</section>
</main>
<!-- FOOTER -->
<footer>
    <div class="container footer-inner">
        <p>
            &copy; <?php echo date("Y"); ?> IT Asset Tracking System.
            All rights reserved.
        </p>
        <div class="footer-links">
            <a href="home.php">Home</a>
            <a href="login.php">Admin Login</a>
            <a href="employee_login.php">Employee Login</a>
            <a href="contact.php">Contact</a>
        </div>
    </div>
</footer>
<script>
/* =====================================
   SMOOTH MOUSE-FOLLOW GLOW
===================================== */
(function () {
    const glow = document.getElementById("mouseGlow");
    if (!glow) return;
    const finePointer = window.matchMedia(
        "(hover: hover) and (pointer: fine)"
    ).matches;
    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;
    if (!finePointer || reduceMotion) {
        glow.style.display = "none";
        return;
    }
    let targetX = window.innerWidth / 2;
    let targetY = window.innerHeight / 2;
    let currentX = targetX;
    let currentY = targetY;
    document.addEventListener("mousemove", function (event) {
        targetX = event.clientX;
        targetY = event.clientY;
        glow.style.opacity = "1";
    }, { passive: true });
    document.addEventListener("mouseleave", function () {
        glow.style.opacity = "0";
    });
    function animateGlow() {
        currentX += (targetX - currentX) * 0.13;
        currentY += (targetY - currentY) * 0.13;
        glow.style.left = currentX + "px";
        glow.style.top = currentY + "px";
        requestAnimationFrame(animateGlow);
    }
    animateGlow();
})();
/* =====================================
   FLOATING PARTICLES
===================================== */
(function () {
    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;
    if (reduceMotion) return;
    const count = window.innerWidth < 600 ? 8 : 20;
    const fragment = document.createDocumentFragment();
    for (let i = 0; i < count; i++) {
        const particle = document.createElement("span");
        particle.className = "particle";
        particle.style.left = Math.random() * 100 + "vw";
        particle.style.top = Math.random() * 100 + "vh";
        particle.style.animationDelay = Math.random() * 8 + "s";
        particle.style.animationDuration =
            (6 + Math.random() * 8) + "s";
        fragment.appendChild(particle);
    }
    document.body.appendChild(fragment);
})();
/* =====================================
   ANIMATED STATISTICS
===================================== */
(function () {
    const counters = document.querySelectorAll(".count-up");
    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;
    counters.forEach(function (counter) {
        const target = Number(counter.dataset.target) || 0;
        if (reduceMotion || target === 0) {
            counter.textContent = target.toLocaleString("en-IN");
            return;
        }
        const duration = 1100;
        const startTime = performance.now();
        function updateCounter(now) {
            const progress = Math.min(
                (now - startTime) / duration,
                1
            );
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(target * eased);
            counter.textContent = current.toLocaleString("en-IN");
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString("en-IN");
            }
        }
        requestAnimationFrame(updateCounter);
    });
})();
/* =====================================
   ANIMATE UTILIZATION BAR
===================================== */
(function () {
    const bar = document.querySelector(".progress-fill");
    if (!bar) return;
    const width = Math.max(
        0,
        Math.min(100, Number(bar.dataset.width) || 0)
    );
    if (window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches) {
        bar.style.width = width + "%";
        return;
    }
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            bar.style.width = width + "%";
        });
    });
})();
/* =====================================
   SCROLL REVEAL
===================================== */
(function () {
    const sections = document.querySelectorAll(".reveal");
    if (window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches || !("IntersectionObserver" in window)) {
        sections.forEach(function (section) {
            section.classList.add("visible");
        });
        return;
    }
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12
    });
    sections.forEach(function (section) {
        observer.observe(section);
    });
})();
/* =====================================
   3D CARD TILT
===================================== */
(function () {
    const finePointer = window.matchMedia(
        "(hover: hover) and (pointer: fine)"
    ).matches;
    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;
    if (!finePointer || reduceMotion) return;
    const cards = document.querySelectorAll(".tilt-card");
    cards.forEach(function (card) {
        card.addEventListener("mousemove", function (event) {
            const rect = card.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width;
            const y = (event.clientY - rect.top) / rect.height;
            const rotateY = (x - 0.5) * 7;
            const rotateX = (0.5 - y) * 7;
            card.style.transform =
                "perspective(900px) rotateX(" + rotateX +
                "deg) rotateY(" + rotateY +
                "deg) translateY(-4px)";
        });
        card.addEventListener("mouseleave", function () {
            card.style.transform = "";
        });
    });
})();
</script>
</body>
</html>