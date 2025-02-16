<?php
include("../connection.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check database connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
} else {
    echo "Connected successfully.<br>";
}

// Get all users from the database
$sql = "SELECT user_id FROM users";
$result = $con->query($sql);

if (!$result) {
    die("Error fetching users: " . $con->error);
}

// Get the current date
$current_date = date('Y-m-d');

while ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    echo "Processing user: $user_id <br>";

    // Query to get the user's saving goals
    $sql_goal = "SELECT saving_id, saving_amount, total_balance FROM saving_goal WHERE user_id = ?";
    $stmt_goal = $con->prepare($sql_goal);
    $stmt_goal->bind_param("i", $user_id);

    if (!$stmt_goal->execute()) {
        echo "Error fetching savings goals: " . $stmt_goal->error;
        continue; // Skip to the next user if there's an error
    }

    $result_goal = $stmt_goal->get_result();

    while ($goal = $result_goal->fetch_assoc()) {
        $saving_id = $goal['saving_id'];
        $saving_amount = $goal['saving_amount'];
        $total_balance = $goal['total_balance'];

        echo "Saving goal ID: $saving_id, Saving amount: $saving_amount, Total balance: $total_balance <br>";

        // Check if today's deduction has already been made
        $check_sql = "SELECT * FROM savings_deduction_log WHERE deduction_date = ? AND user_id = ? AND saving_id = ?";
        $check_stmt = $con->prepare($check_sql);
        $check_stmt->bind_param("sii", $current_date, $user_id, $saving_id);

        if (!$check_stmt->execute()) {
            echo "Error checking deductions: " . $check_stmt->error;
            continue; // Skip to the next goal if there's an error
        }

        $check_result = $check_stmt->get_result();


            echo "Updating total balance for saving goal $saving_id<br>";
            $sql_saving_goal = "SELECT saving_goal FROM saving_goal WHERE user_id = ? AND saving_id = ?";
            $stmt_goal = $con->prepare($sql_saving_goal);
            $stmt_goal->bind_param("ii", $user_id, $saving_id);

            if (!$stmt_goal->execute()) {
                echo "Error fetching saving goal: " . $stmt_goal->error;
                continue; // Skip to the next goal if there's an error
            }

            $result_saving_goal = $stmt_goal->get_result();
            $saving_goal = $result_saving_goal->fetch_assoc()['saving_goal'];
            $new_total_balance = $total_balance + $saving_amount;
            echo "Saving goal for user $user_id and saving goal $saving_id is $saving_goal<br>";
        if ($new_total_balance <= $saving_goal){
            // First, check and update the starting budget
            if ($starting_budget >= $saving_amount) {
                // Calculate new total balance
                // Update the total_balance in the saving_goal table
                $update_sql = "UPDATE saving_goal SET total_balance = ?, starting_budget = ? WHERE saving_id = ?";
                $new_start_budget = $starting_budget - $saving_amount; // Reduce starting_budget by saving_amount
                $update_stmt = $con->prepare($update_sql);
                $update_stmt->bind_param("ddi", $new_total_balance, $new_start_budget, $saving_id);

                if (!$update_stmt->execute()) {
                    echo "Error updating total balance: " . $update_stmt->error;
                } else {
                    echo "Total balance updated successfully for saving goal $saving_id. New total balance: $new_total_balance.<br>";
                }

                // Log this deduction in the savings_deduction_log table
                $log_sql = "INSERT INTO savings_deduction_log (user_id, saving_id, amount_deducted) VALUES (?, ?, ?)";
                $log_stmt = $con->prepare($log_sql);
                $log_stmt->bind_param("iisdi", $user_id, $saving_id, $new_total_balance);
                if (!$log_stmt->execute()) {
                    echo "Error logging deduction: " . $log_stmt->error;
                } else {
                    echo "Deduction logged successfully for saving goal $saving_id.<br>";
                }
                $log_stmt->close();

            } else {
                // If starting budget is less than saving amount, no deduction is made, and a message is shown
                echo "Error: Not enough funds in starting budget for saving goal $saving_id. Please add more money to continue.<br>";
            }
        } else {
            echo "Saving goal $saving_id has reached its target amount. No further deductions will be made.<br>";
        }

        $check_stmt->close();
    }

    $stmt_goal->close();
}

$con->close(); // Close the database connection
?>
