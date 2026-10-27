<?php

session_start();

unset($_SESSION["employee_id"]);
unset($_SESSION["employee_name"]);
unset($_SESSION["employee_email"]);
unset($_SESSION["employee_department"]);

header("Location: employee_login.php");
exit();

?>