<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";


if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: assets.php");
    exit();
}


$id = intval($_GET["id"]);


/* Delete asset */

$stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");

$stmt->bind_param("i", $id);


if ($stmt->execute()) {

    header("Location: assets.php?deleted=1");
    exit();

} else {

    echo "Error deleting asset: " . $conn->error;

}

?>