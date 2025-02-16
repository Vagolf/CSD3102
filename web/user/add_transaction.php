<?php
include("../connection.php");
include("../functions.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_data = check_login($con); // Assuming check_login is a function that validates the session
$user_id = $_SESSION['user_id']; // Get user ID from session

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form input values
    $transaction = $_POST['transaction'];
    $date = $_POST['date'];
    $amount = $_POST['amount'];
    $transaction_type = $_POST['transaction_type'];

    // Fetch the current saving goal for the user
    $fetch_saving_sql = "SELECT saving_id, starting_budget FROM saving_goal WHERE user_id = ?";
    $fetch_saving_stmt = $con->prepare($fetch_saving_sql);
    $fetch_saving_stmt->bind_param("i", $user_id);
    $fetch_saving_stmt->execute();
    $saving_result = $fetch_saving_stmt->get_result();

    if ($saving_result->num_rows > 0) {
        // Fetch saving_id and starting_budget
        $saving_row = $saving_result->fetch_assoc();
        $saving_id = $saving_row['saving_id'];
        $starting_budget = $saving_row['starting_budget'];
    } else {
        echo "No saving goal found for this user.";
        exit();
    }

    // Calculate the new total based on transaction type
    if ($transaction_type == "income") {
        $new_total = $starting_budget + $amount;
    } elseif ($transaction_type == "expenditure") {
        $new_total = $starting_budget - $amount;
    } else {
        echo "Invalid transaction type.";
        exit();
    }

    // Insert the transaction into the ledger table
    $insert_sql = "INSERT INTO ledge (user_id, saving_id, date, transaction, amount, total, transaction_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $con->prepare($insert_sql);
    $insert_stmt->bind_param("iissdds", $user_id, $saving_id, $date, $transaction, $amount, $new_total, $transaction_type);

    if ($insert_stmt->execute()) {
        // Update the starting_budget in the saving_goal table
        $update_budget_sql = "UPDATE saving_goal SET starting_budget = ? WHERE saving_id = ?";
        $update_budget_stmt = $con->prepare($update_budget_sql);
        $update_budget_stmt->bind_param("di", $new_total, $saving_id);
        
        if ($update_budget_stmt->execute()) {
            // Redirect to the ledger page
            header("Location: ledger.php?saving_id=$saving_id");
            exit();
        } else {
            echo "Error updating budget: " . $update_budget_stmt->error;
        }

        // Close the update statement
        $update_budget_stmt->close();
    } else {
        echo "Error adding transaction: " . $insert_stmt->error;
    }

    // Close statements
    $insert_stmt->close();
    $fetch_saving_stmt->close();
}

// Close connection
$con->close();
?>
