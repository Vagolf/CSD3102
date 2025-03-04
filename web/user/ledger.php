<?php
include_once("../connection.php");
require_once("header_user.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id']; // Get user_id from session

// Fetch the active saving goal for the current user
$sql_saving = "SELECT saving_id, starting_budget FROM saving_goal WHERE user_id = ?";
$stmt_saving = $con->prepare($sql_saving);
$stmt_saving->bind_param("i", $user_id);
$stmt_saving->execute();
$saving_result = $stmt_saving->get_result();

if ($saving_result->num_rows > 0) {
    $saving_row = $saving_result->fetch_assoc();
    $saving_id = $saving_row['saving_id'];
} else {
    echo "No active saving goal found.";
    exit();
}

// Fetch ledger data for the active saving goal
$sql = "SELECT date, transaction, amount, total FROM ledge WHERE user_id = ? AND saving_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $user_id, $saving_id); 
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <p>.</p>
    <hr>
    <div class="container mt-4">
        <h1>Transaction Ledger</h1>
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                Add Transaction
            </button>
            <a href="./goalSetting.php"><button type="button" class="btn btn-primary">
                Home
            </button></a>
        </div>
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Date</th>
                    <th>Transaction</th>
                    <th>Amount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Check if keys exist in the array before using them to avoid "undefined key" errors
                        $date = isset($row['date']) ? $row['date'] : 'N/A';
                        $transaction = isset($row['transaction']) ? $row['transaction'] : 'N/A';
                        $amount = isset($row['amount']) ? $row['amount'] : 'N/A';
                        $total = isset($row['total']) ? $row['total'] : 'N/A';
                        
                        echo "<tr>
                                <td>$date</td>
                                <td>$transaction</td>
                                <td>$amount</td>
                                <td>$total</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No transactions available</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>


    <!-- Modal for adding a new transaction -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">Add Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form to input transaction details -->
                    <form action="add_transaction.php" method="POST">
                        <div class="form-group">
                            <label for="transaction">Transaction Name</label>
                            <input type="text" class="form-control" name="transaction" id="transaction" required>
                        </div>
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" class="form-control" name="date" id="date" required>
                        </div>
                        <div class="form-group">
                            <label for="transaction_type">Transaction Type</label>
                            <select class="form-control" name="transaction_type" id="transaction_type" required>
                                <option value="income">Income</option>
                                <option value="expenditure">Expenditure</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="amount" required>
                        </div>
                        <input type="hidden" name="saving_id" value="<?php echo $saving_id; ?>">
                        <!-- Ensure saving_id is passed -->
                        <button type="submit" class="btn btn-primary">Add Transaction</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>