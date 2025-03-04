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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the values from the form submission
    $saving_id = $_POST['saving_id']; // Hidden field to identify the saving goal
    
    // Prepare an array to hold the fields to be updated
    $fields_to_update = [];
    $params = [];
    $types = ''; // For binding params

    // Check each field and only add it to the update if it has a value
    if (!empty($_POST['saving_name'])) {
        $fields_to_update[] = "saving_name = ?";
        $params[] = $_POST['saving_name'];
        $types .= 's';
    }

    // Get the user's input for starting_budget
    if (!empty($_POST['starting_budget'])) {
        $new_starting_budget_input = (float)$_POST['starting_budget']; // User's input (amount to add)
        
        // Fetch current starting_budget from the database
        $sql = "SELECT starting_budget FROM saving_goal WHERE saving_id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $saving_id); // Use the saving_id to fetch the record
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Fetch the current starting budget
            $row = $result->fetch_assoc();
            $current_starting_budget = $row['starting_budget'];
            
            // Add the user's input to the current starting budget
            $updated_starting_budget = $current_starting_budget + $new_starting_budget_input;

            // Add to fields to be updated
            $fields_to_update[] = "starting_budget = ?";
            $params[] = $updated_starting_budget;
            $types .= 'd'; // Double (decimal/float) type for starting_budget
        }
        $stmt->close();
    }

    if (!empty($_POST['saving_goal'])) {
        $fields_to_update[] = "saving_goal = ?";
        $params[] = (float)$_POST['saving_goal'];
        $types .= 'd';
    }

    if (!empty($_POST['saving_amount'])) {
        $fields_to_update[] = "saving_amount = ?";
        $params[] = (float)$_POST['saving_amount'];
        $types .= 'd';
    }

    if (!empty($_POST['target_date'])) {
        $fields_to_update[] = "target_date = ?";
        $params[] = $_POST['target_date'];
        $types .= 's'; // 's' for string since date is treated as string in SQL
    }

    if (!empty($_POST['description'])) {
        $fields_to_update[] = "description = ?";
        $params[] = $_POST['description'];
        $types .= 's';
    }

    // Check if there are fields to update
    if (count($fields_to_update) > 0) {
        // Build the final update query dynamically
        $update_sql = "UPDATE saving_goal SET " . implode(", ", $fields_to_update) . " WHERE saving_id = ?";
        
        // Prepare the statement
        $update_stmt = $con->prepare($update_sql);
        
        // Add saving_id to the params list
        $params[] = $saving_id;
        $types .= 'i'; // 'i' for integer

        // Use call_user_func_array to bind the parameters dynamically
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }

        // Bind the params dynamically
        call_user_func_array([$update_stmt, 'bind_param'], $bind_names);
        
        // Execute the update statement
        if ($update_stmt->execute()) {
            echo "Savings goal updated successfully!";
        } else {
            echo "Error updating savings goal: " . $con->error;
        }

        $update_stmt->close();
    } else {
        echo "No fields were updated, since no input was provided.";
    }
}

// Close the database connection
$con->close();
?>