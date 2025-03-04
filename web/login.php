<?php

session_start();

include("connection.php");
include("functions.php");

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	//something was posted
	$user_name = $_POST['user_name'];
	$password = $_POST['password'];
	$remember_me = isset($_POST['remember_me']);

	if (!empty($user_name) && !empty($password) && !is_numeric($user_name)) {

		//read from database
		$query = "select * from users where user_name = '$user_name' limit 1";
		$result = mysqli_query($con, $query);

		if ($result) {
			if ($result && mysqli_num_rows($result) > 0) {

				$user_data = mysqli_fetch_assoc($result);

				if ($user_data['password'] === $password) {

					$_SESSION['user_id'] = $user_data['user_id'];

					if ($remember_me) {
						setcookie('user_name', $user_name, time() + (86400 * 30), "/"); // 30 days
					} else {
						setcookie('user_name', '', time() - 3600, "/"); // delete cookie
					}

					header("Location: ./dashboard.php");
					die;
				}
			}
		}

		$error_message = "Wrong Username or Password!";
	} else {
		$error_message = "Wrong Username or Password!";
	}
}

$user_name = isset($_COOKIE['user_name']) ? $_COOKIE['user_name'] : '';

?>

<!DOCTYPE html>
<html>

<head>
	<title>Login</title>
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

		* {
			font-family: "Instrument Sans", serif;
			font-optical-sizing: auto;
			font-weight: weight;
			font-style: normal;
			font-variation-settings: "wdth" 100;
		}

		input:placeholder-shown {
			color: #fff;
			background-color: #fff;
		}

		input:not(:placeholder-shown) {
			color: #fff;
			background-color: #fff;
		}

		#button {
			border: none;
		}
	</style>
</head>

<body>
	<div>
		<img src="image/login-logo.png" alt="Main Logo"
			style="width: 650px; height: auto; display: block; margin: 10px auto">
	</div>

	<div class="login" style="text-align: center;">
		<form method="post">
			<div style="font-size: 40px; margin-right: 610px; margin-top: 10px; color: black;">User Name</div>
			<input id="text" type="text" name="user_name" value="<?php echo htmlspecialchars($user_name); ?>"
				style="margin: 5px; background-color: #E9B265; color: white; border-radius: 5px; border: none; text-align: center; padding: 15px 300px; font-size: 20px; margin-left: 60px;"
				placeholder="Username"><br><br>
			<div style="font-size: 40px; margin-right: 630px; color: black; ">Password</div>
			<input id="text" type="password" name="password"
				style="margin: 5px; background-color: #E9B265; font-color: white; border-radius: 5px; border: none; text-align: center; padding: 15px 300px; font-size: 20px; margin-left: 60px;"
				placeholder="Password"><br><br>
			<input id="button" type="submit" value="Login"
				style="border-radius: 10px; background-color: #E9B265; border-color: #E9B265; padding: 20px 120px; font-size: 60px; margin-top: 10px; margin-left: 60px;"><br><br>
		</form>
		<?php if (!empty($error_message)): ?>
			<div style="text-align: center; color: red; margin-top: -10px; margin-left: 75px; font-size: 24px;">
				<?php echo $error_message; ?>
			</div>
		<?php endif; ?>
	</div>
	</div>
	<div class="signup">
		<a href="signup.php"
			style="font-size: 30px; color: #000000; display: block; text-align: center; margin: 20px auto; margin-left: 60px;">Sign
			Up</a>

	</div>
	</div>

</body>

</html>