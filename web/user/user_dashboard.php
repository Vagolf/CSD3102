<?php
include("../connection.php");
// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}  // Ensure the file containing check_login function is included

$user_data = check_login($con);
$user_id = $_SESSION['user_id'];  // Example: Storing user ID in session

// Query to get the user's saving goals from the saving_goal table
$sql = "SELECT saving_id, saving_name, saving_goal, saving_amount, total_balance FROM saving_goal WHERE user_id = ?";
$stmt = $con->prepare($sql);  // Use prepared statements to avoid SQL injection
$stmt->bind_param("i", $user_id);  // Bind the user ID as an integer
$stmt->execute();
$result = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $saving_name = $_POST['saving_name'];
    $saving_goal = $_POST['saving_goal'];
    $saving_amount = $_POST['saving_amount'];

    // Check for duplicate saving goal
    $check_duplicate_sql = "SELECT * FROM saving_goal WHERE user_id = ? AND saving_name = ?";
    $check_stmt = $con->prepare($check_duplicate_sql);
    $check_stmt->bind_param("is", $user_id, $saving_name);
    $check_stmt->execute();
    $duplicate_result = $check_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        Saving goal with the name <strong>' . htmlspecialchars($saving_name) . '</strong> already exists!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';
    } else {
        if (!empty($saving_name) && !empty($starting_budget) && !empty($saving_goal) && !empty($saving_amount) && !empty($target_date)) {
            // Insert the new saving goal into the database
            $stmt = $con->prepare("INSERT INTO saving_goal (user_id, saving_name, saving_goal, saving_amount) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isddsss", $user_id, $saving_name, $saving_goal, $saving_amount);

            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <h4 class="alert-heading">Well done!</h4>
                <p>You successfully created a new saving goal: <strong>' . htmlspecialchars($saving_name) . '</strong>.</p>
                <hr>
                <p class="mb-0">You can now track your progress towards this goal.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
                $new_value = 0;
                // Get the ID of the newly inserted saving goal
                $saving_id = $stmt->insert_id;

                // Insert initial deduction into the savings_deduction_log
                $deduction_date = date('Y-m-d');  // Current date
                $stmt_log = $con->prepare("INSERT INTO savings_deduction_log (user_id, saving_id, deduction_date, amount_deducted) VALUES (?, ?, ?, ?)");
                $stmt_log->bind_param("iisd", $user_id, $saving_id, $deduction_date, $new_value);

                if ($stmt_log->execute()) {
                    echo "Initial deduction added to savings deduction log!";
                } else {
                    echo "Error inserting into savings deduction log: " . $stmt_log->error;
                }

                $stmt_log->close();

            } else {
                echo "Error adding saving goal: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            Please fill in all required fields!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }
    }

    $check_stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <style>
        /* Modal-like appearance for the tab content */
        .custom-modal-like-tab {
            position: fixed;
            top: 50%;
            left: 55%;
            transform: translate(-50%, -50%);

            width: 100%;
            max-width: 1300px;
            height: 630px;
            margin: 0 auto;
            padding: 0;
            /* Remove default padding */
            border-radius: 15px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
            /* Hidden initially */
        }

        .custom-modal-like-tab.active {
            display: block;
        }

        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999;
            display: none;
        }

        .custom-modal-overlay.active {
            display: block;
        }

        .modal-header-custom {
            background-color: #00AAFF;
            color: white;
            padding: 30px;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            width: 100%;
            /* Make sure it spans the entire width */
        }

        /* Padding for modal body */
        .modal-body {
            padding: 20px;
        }

        .btn-custom {
            border-radius: 30px;
            /* Rounded corners */
            padding: 10px 20px;
            /* Increased padding for a larger button */
            font-size: 16px;
            /* Increase font size */
        }

        .icon {
            color: #00AAFF;
            /* Color for the "+" symbol */
            font-weight: bold;
            /* Make the icon bolder */
        }

        .text {
            color: white;
            /* Color for the text "Create Goal" */
            margin-left: 5px;
            /* Space between icon and text */
        }

        .progress.vertical {
            position: relative;
            width: 50px;
            height: 590px;
            background-color: #f5f5f5;
            border-radius: 0.25rem;
            display: flex;
            flex-direction: column-reverse;
            /* Make progress start from the bottom */
            margin: 20px auto;
        }

        .progress-bar {
            width: 100%;
            background-color: #00AAFF;
            transition: height 0.6s ease;
        }

        .progress-bar-striped {
            background-image: linear-gradient(45deg, rgba(255, 255, 255, .15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, .15) 50%, rgba(255, 255, 255, .15) 75%, transparent 75%, transparent);
            background-size: 1rem 1rem;
        }
    </style>
</head>

<body>
    <p>.</p>
    <p>.</p>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-2" style="border-right: 1px solid black">
                <ul class="nav nav-tabs flex-column" id="myTabContent" role="tablist">
                    <!-- Ledger Button -->
                    <li class="nav-item mb-4" role="presentation">
                    <li class="nav-item">
                        <a class="nav-link" href="./ledger.php"
                            style="border: 1px solid black; border-radius: 30px; padding-top: 20px">
                            <p class="text-center">Ledger</p>
                        </a>
                    </li>
                    </li>
                    <hr>

                    <!-- Create Goal Button -->
                    <li class="nav-item mb-4" role="presentation">
                        <button class="nav-link btn btn-custom w-100" id="create-goal-tab" data-bs-toggle="tab"
                            data-bs-target="#create-goal" type="button" role="tab" aria-controls="create-goal"
                            aria-selected="false"
                            style="border: 1px solid black; border-radius: 30px; padding: 20px 0px 20px 0px">
                            <span class="icon">+</span> Create Goal
                        </button>
                    </li>
                    <hr>

                    <!-- Saving Goals Section -->
                    <li class="nav-item mb-4" role="presentation">
                        <h6 class="mt-2">Saving Goals</h6>
                        <?php
                        $result->data_seek(0);  // Reset result pointer
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                if (isset($row['saving_id'])) {
                                    $saving_id = $row['saving_id'];
                                    $saving_name = htmlspecialchars($row['saving_name']);
                                    echo '<li class="nav-item mb-2" role="presentation">
                    <button class="nav-link btn btn-custom w-100" id="goal-tab-' . $saving_id . '" data-bs-toggle="tab" data-bs-target="#goal-' . $saving_id . '" type="button" role="tab" aria-controls="goal-' . $saving_id . '" aria-selected="false" style="border-radius: 20px;">' . $saving_name . '</button>
                  </li>';
                                }
                            }
                        }
                        ?>
                    </li>
                    <hr>

                    <!-- Home Button -->
                    <li class="nav-item mt-2 mb-4">
                        <a href="./goalSetting.php" class="btn btn-custom w-100" style="border-radius: 30px;">
                            <i class="bi bi-house" style="padding-right: 5px"></i> Home
                        </a>
                    </li>

                    <!-- Log Out Button -->
                    <li class="nav-item mb-4">
                        <a href="../logout.php" class="btn btn-custom w-100" style="border-radius: 30px;">
                            <i class="bi bi-arrow-right-square" style="padding-right: 8px"></i>Log Out</a>
                    </li>
                </ul>
            </div>

            <div class="tab-content col-10" id="goal-tabContent">
                <?php
                $result->data_seek(0);  // Reset result pointer again for content generation
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if (isset($row['saving_id'])) {
                            $saving_id = $row['saving_id'];
                            $saving_name = htmlspecialchars($row['saving_name']);
                            $saving_amount = $row['saving_amount'];
                            $starting_budget = $row['starting_budget'];
                            $saving_goal = $row['saving_goal'];
                            $total_balance = $row['total_balance'];
                            $target_date = isset($row['target_date']) ? $row['target_date'] : ''; // Fetching target date
                            $description = isset($row['description']) ? $row['description'] : '';

                            // Calculate the progress percentage
                            $progress_percentage = ($total_balance / $saving_goal) * 100;
                            $progress_percentage = min(100, $progress_percentage);  // Ensure it doesn't exceed 100%
                
                            // Calculate the position for the current savings arrow (height-based for vertical progress bar)
                            $arrow_position = ($progress_percentage < 100) ? $progress_percentage : 100;

                            // Display the saving goal and actions
                            echo '<div class="tab-pane fade" id="goal-' . $saving_id . '" role="tabpanel" aria-labelledby="goal-tab-' . $saving_id . '">
                <h3 class="text-center">' . $saving_name . '</h3>
                <div class="row">
                    <div class="col-5">
                        <div class="progress-container" style="position: relative;">
                            <div class="progress vertical" role="progressbar" aria-valuenow="' . $progress_percentage . '" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="height: ' . $progress_percentage . '%"></div>
                            </div>
                            <p class="text-center">' . number_format($progress_percentage, 2) . '% completed</p>';
                            
                            if ($progress_percentage == 100) {
                                echo '<h3 class="text-center text-success">Congratulations! You have reached your goal!</h3>';
                            }
                            
                            echo '</div>
                    </div>
                    <div class="col-7 text-end">
                        <button class="btn btn-primary col-1" data-bs-toggle="modal" data-bs-target="#editGoalModal' . $saving_id . '" style="float: right;">Edit</button> <!-- Edit button -->
                        <button class="btn btn-danger col-1" data-bs-toggle="modal" data-bs-target="#deleteGoalModal' . $saving_id . '" style="float: right; margin-left: 30px;">Delete</button> <!-- Delete button -->
                        <table class="table table-bordered mt-3 rounded" style="padding-top: 30px">
                            <thead>
                                <tr>
                                    <th>Starting Budget</th>
                                    <th>Date</th>
                                    <th>Total Balance</th>
                                </tr>
                            </thead>
                            <tbody>';

                            $log_sql = "SELECT sdl.amount_deducted, sdl.deduction_date, sdl.starting_budget, sg.saving_amount
                        FROM savings_deduction_log AS sdl 
                        JOIN saving_goal AS sg ON sdl.saving_id = sg.saving_id 
                        WHERE sdl.saving_id = ? 
                        ORDER BY sdl.deduction_date ASC";
                            $log_stmt = $con->prepare($log_sql);
                            $log_stmt->bind_param("i", $saving_id);
                            $log_stmt->execute();
                            $log_result = $log_stmt->get_result();

                            if ($log_result->num_rows > 0) {
                                while ($log_row = $log_result->fetch_assoc()) {
                                    // Check starting_budget before displaying the row
                                    if ($log_row['starting_budget'] != 0) {
                                        echo '<tr>
                                <td>' . number_format($log_row['starting_budget'], 2) . ' - ' . number_format($log_row['saving_amount'], 2) . '</td>
                                <td>' . htmlspecialchars($log_row['deduction_date']) . '</td>
                                <td>' . number_format($log_row['amount_deducted'], 2) . '</td>
                            </tr>';
                                    }
                                }
                            } else {
                                echo "<tr><td colspan='3'>No records found.</td></tr>";
                            }

                            echo '</tbody></table></div></div></div>';

                            // Edit Modal
                            echo '<div class="modal fade" id="editGoalModal' . $saving_id . '" tabindex="-1" aria-labelledby="editGoalModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editGoalModalLabel">Edit Saving Goal: ' . $saving_name . '</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="update_goal.php"> <!-- Update action to handle form submission -->
                                            <input type="hidden" name="saving_id" value="' . $saving_id . '">
                                            <div class="mb-3">
                                                <label for="saving_name" class="form-label">Title</label>
                                                <input class="form-control" type="text" id="saving_name" name="saving_name" value="' . htmlspecialchars($saving_name) . '">
                                            </div>
                                            <div class="mb-3">
                                                <label for="saving_goal" class="form-label">Saving Goal</label>
                                                <input class="form-control" type="number" id="saving_goal" name="saving_goal" value="' . $saving_goal . '" step="0.01">
                                            </div>
                                            <div class="mb-3">
                                                <label for="saving_amount" class="form-label">Saving Amount</label>
                                                <input class="form-control" type="number" id="saving_amount" name="saving_amount" value="' . $saving_amount . '" step="0.01">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Update Goal</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';

                            // Delete Confirmation Modal
                            echo '<div class="modal fade" id="deleteGoalModal' . $saving_id . '" tabindex="-1" aria-labelledby="deleteGoalModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteGoalModalLabel">Delete Saving Goal</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete the saving goal "<strong>' . $saving_name . '</strong>"? This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form method="POST" action="delete_goal.php"> <!-- Action to handle deletion -->
                                            <input type="hidden" name="saving_id" value="' . $saving_id . '">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
    </div>

    <div class="custom-modal-overlay" id="modalOverlay"></div>
    <div class="custom-modal-like-tab" id="createGoalModal" style="width: 1300px; height: 655px;">
        <!-- Custom Header with Background Color -->
        <div class="modal-header-custom" style="width: 1300px;">
            <button type="button" class="btn-close" id="closeModal" aria-label="Close" style="float: right;"></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <label for="saving_name" style="padding-left: 25px">Title</label><br>
                <input class="form-control" type="text" id="saving_name" name="saving_name" step="0.01"
                    placeholder="Add title" required style="width: 1250px; margin-left: 23px"><br>

                <label for="saving_goal" style="padding-left: 25px">Saving Goal</label><br>
                <input class="form-control" type="number" id="saving_goal" name="saving_goal" step="0.01"
                    placeholder="How much" required style="width: 1250px; margin-left: 23px"><br>

                <button class="btn" type="submit"
                    style="width: 100px; border-radius: 10px; background-color: #00AAFF; color: white; margin-left: 23px">Create</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal-like tab functionality
        const createGoalTab = document.getElementById('create-goal-tab');
        const createGoalModal = document.getElementById('createGoalModal');
        const modalOverlay = document.getElementById('modalOverlay');
        const closeModal = document.getElementById('closeModal');

        // Show modal-like tab on "Create Goal" click
        createGoalTab.addEventListener('click', function () {
            createGoalModal.classList.add('active');
            modalOverlay.classList.add('active');
        });

        // Close modal-like tab when clicking outside or close button
        closeModal.addEventListener('click', function () {
            createGoalModal.classList.remove('active');
            modalOverlay.classList.remove('active');
        });

        modalOverlay.addEventListener('click', function () {
            createGoalModal.classList.remove('active');
            modalOverlay.classList.remove('active');
        });
    </script>
</body>

</html>