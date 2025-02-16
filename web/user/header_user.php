<?php
include("../connection.php");
include("../functions.php");
// Assuming this is where check_login is defined

// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_data = check_login($con);

$user_name = $user_data['user_name'];
$user_image = $user_data['image'];

$default_image = "../image/dp.png";

if (empty($user_image)) {
    $user_image = $default_image;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <style>
        .header {
            display: flex;
            align-items: left;
            justify-content: left;
            margin-bottom: 20px;
        }

        .header img {
            width: 30px;
            height:30px;
            margin: 10px 5px 0px 40px;
        }

        .header h1 a {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-toggler {
            padding: 0 !important;
        }
    </style>
</head>

<body>
    <nav class="navbar fixed-top" style="background-color: white; border-color: black; border-bottom: 1px solid black;">
        <div class="container-fluid">
            
            <div class="header">
                <a class="navbar-brand" href="./goalSetting.php" style="font-size: 25px; margin: 10px -100px -10px 5px" ><img src="../image/iconBB2.png" alt="Budget Buddy" style="margin-top: -10px;">BudgetBuddy</a>
            </div>

            <!-- Replace Navbar Toggler with Profile Image -->
            <button class="navbar-toggler rounded-circle" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                <img src="../uploads/<?php echo !empty($user_image) ? $user_image : $default_image; ?>" alt="Profile"
                    class="rounded-circle" width="40" height="40" style="border: 1px solid black">
            </button>
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
                aria-labelledby="offcanvasNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasNavbarLabel"><img
                            src="../uploads/<?php echo !empty($user_image) ? $user_image : $default_image; ?>"
                            alt="Profile" class="rounded-circle" width="80" height="80" style="border: 1px solid black"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" style="width: 60px; height: 60px;"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                        <li class="nav-item">
                            <a class="nav-link" href="./user_edit.php" style="font-size: 25px">Edit profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="../logout.php"
                                style="color:red; font-size: 25px">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <img src="../image/<?php echo $user_image; ?>" alt="Profile" class="rounded-circle" width="40" height="40">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>