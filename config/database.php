<?php
$host = "localhost";
$username = "root";
$password = getenv("DB_PASSWORD") ?: "";
$database = "it_asset_tracking";
$port = 3307;

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>