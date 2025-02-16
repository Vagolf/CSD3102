<?php
session_start();

include("connection.php");
include("functions.php");

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	// Retrieve form data and check if each field is set
	$user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
	$password = isset($_POST['password']) ? trim($_POST['password']) : '';
	$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';  // Ensure confirm_password is set

	// Validate if the fields are not empty and the username is not numeric
	if (!empty($user_name) && !empty($password) && !empty($confirm_password) && !is_numeric($user_name)) {

		// Debugging: Check what values are being submitted
		error_log("user_name: " . $user_name);
		error_log("password: " . $password);
		error_log("confirm_password: " . $confirm_password);

		// Check if password matches confirm password
		if ($password !== $confirm_password) {
			$error_message = "Passwords do not match!";
		}
		// Ensure password meets length requirement (minimum 6 characters)
		elseif (strlen($password) < 6) {
			$error_message = "Password must be at least 6 characters long.";
		} else {
			// Save to the database
			$user_id = random_num(20);
			// Hash the password before storing it

			// Prepare the SQL query to avoid SQL injection
			$query = "INSERT INTO users (user_id, user_name, password) VALUES (?, ?, ?)";
			$stmt = $con->prepare($query);
			$stmt->bind_param("sss", $user_id, $user_name, $password);

			if ($stmt->execute()) {
				// Redirect to login page after successful signup
				header("Location: login.php");
				die;
			} else {
				// Error while inserting into database
				$error_message = "Error: " . $stmt->error;
			}

			$stmt->close();
		}
	} else {
		// Error when fields are not valid
		if (empty($user_name)) {
			$error_message = "Username is required!";
		} elseif (empty($password)) {
			$error_message = "Password is required!";
		} elseif (empty($confirm_password)) {
			$error_message = "Confirm password is required!";
		} elseif (is_numeric($user_name)) {
			$error_message = "Username should not be a number!";
		} else {
			$error_message = "Please enter valid information!";
		}
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<title>Signup</title>
	<style>
		body {
			text-align: center;
		}

		.header {
			display: flex;
			align-items: left;
			justify-content: left;
			margin-bottom: 20px;
		}

		.header img {
			width: 40px;
			height: 40px;
			margin: 20px 5px 0px 40px;
		}

		.header h1 {
			margin: 0;
			font-size: 24px;
			color: #333;
		}

		.form-container {
			display: inline-block;
			text-align: left;
			padding: 20px;
			border-radius: 10px;
			color: white;
			margin-right: 150px;
		}

		#text {
			font-size: 20px;
			margin-top: 20px;
			background-color: #D9D9D9;
			border-radius: 40px;
			border: none;
			width: 100%;
			padding: 10px;
		}

		#button {
			border: none;
		}

		#button:hover {
			background-color: #777;
		}

		.error-message {
			color: red;
			font-size: 20px;
			margin-top: 20px;
		}
		input:placeholder-shown {
			color: #fff;
			background-color: #fff;
		}

		input:not(:placeholder-shown) {
			color: #fff;
			background-color: #fff;
		}
	</style>
</head>

<body>
	<div>
		<img src="image/signup-logo.png" alt="Main Logo"
			style="width: 700px; height: auto; display: block; margin: 10px auto">
	</div>
	<div class="form-container">
		<form method="post">
			<div style="font-size: 30px;  margin-top: 5px; margin-right: 300px; color: black;">User Name</div>
			<input id="text" type="text" name="user_name" placeholder="Username"
				style="margin: 5px; background-color: #E9B265; color: white; border-radius: 5px; border: none; text-align: center; padding: 15px 100px; font-size: 20px;"><br><br>
			<div style="font-size: 30px; margin-right: 320px; color: black; ">Password</div>
			<input id="text" type="password" name="password" placeholder="Password"
				style="margin: 5px; background-color: #E9B265; color: white; border-radius: 5px; border: none; text-align: center; padding: 15px 100px; font-size: 20px;"><br><br>
			<div style="font-size: 30px; margin-right: 320px; color: black; ">Confirm
				Password</div>
			<input id="text" type="password" name="confirm_password" placeholder="Confirm Password"
				style="margin: 5px; background-color: #E9B265; color: white; border-radius: 5px; border: none; text-align: center; padding: 15px 100px; font-size: 20px;"><br><br>
			<?php
			echo "<div class='error-message' style='margin: 5px 0px 0px 80px; '>$error_message</div>";
			?>

			<input id="button" type="submit" value="Sign Up"
				style="border-radius: 10px; background-color: #E9B265; border-color: #E9B265; padding: 20px 120px; font-size: 60px; margin-top: 10px; display: block; margin-left: auto; margin-right: auto;"><br><br>


		</form>
		<div class="signup">
			<a href="login.php"
				style="font-size: 30px; color: #000000; display: block; text-align: center; margin: 20px auto;">Sign
				Up</a>

		</div>
	</div>

</body>

</html>