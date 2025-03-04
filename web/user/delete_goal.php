<?php
// delete_by_saving_id.php
session_start();
include_once("../connection.php");

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get saving_id from POST request
    $saving_id = $_POST['saving_id'];

    try {
        // Start a transaction
        $con->begin_transaction();

        // Delete from ledger table
        $delete_ledger_sql = "DELETE FROM ledge WHERE saving_id = ?";
        $stmt_ledger = $con->prepare($delete_ledger_sql);
        $stmt_ledger->bind_param("i", $saving_id);
        $stmt_ledger->execute();

        // Delete from savings_deduction_log table
        $delete_deduction_log_sql = "DELETE FROM savings_deduction_log WHERE saving_id = ?";
        $stmt_deduction_log = $con->prepare($delete_deduction_log_sql);
        $stmt_deduction_log->bind_param("i", $saving_id);
        $stmt_deduction_log->execute();

        // Delete from saving_goal table
        $delete_saving_goal_sql = "DELETE FROM saving_goal WHERE saving_id = ?";
        $stmt_saving_goal = $con->prepare($delete_saving_goal_sql);
        $stmt_saving_goal->bind_param("i", $saving_id);
        $stmt_saving_goal->execute();

        // Commit the transaction
        $con->commit();
        
        $_SESSION['message'] = "All records for saving_id $saving_id deleted successfully.";

    } catch (Exception $e) {
        // Rollback the transaction if anything fails
        $con->rollback();
        $_SESSION['message'] = "Error deleting records: " . $e->getMessage();
    }

    // Close statements
    $stmt_ledger->close();
    $stmt_deduction_log->close();
    $stmt_saving_goal->close();

    // Close the database connection
    $con->close();

    // Redirect back to your page
    header("Location: your_redirect_page.php"); // Replace with your actual redirect page
    exit();
} else {
    // If accessed directly, redirect to the main page
    header("Location: your_redirect_page.php"); // Replace with your actual redirect page
    exit();
}
?>
