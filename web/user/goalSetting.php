<?php
session_start();

// Use include_once or require_once to avoid redeclaring functions
include_once("../connection.php");
require_once("header_user.php");
include("user_dashboard.php");

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_data = check_login($con); // This will work now without redeclaring the function

?>

<!DOCTYPE html>
<html>

<head>
    <title>My website</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
