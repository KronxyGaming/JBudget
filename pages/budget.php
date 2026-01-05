<!-- Budget Page -->

<?php
    /* include(s) */
    include '../includes/db.php'; /* Make sure are sucessfully connected to the db each time */
    include '../includes/header.php'; /* Include out header which contains out nav bar on every page */

    /* start a session and verify that the user_id in order to give access to other pages */
    session_start();
    if(!isset($_SESSION['user_id'])) {
        header('Location: login.php'); /* If there is no user_id send the user to the login page */
        exit();
    }

    $user_id = $_SESSION['user_id']; /* $_SESSION used because id is stored from the time user logs in*/
    $message = "";

    $editing_data = null; /* Holds budget data if editing */

    /* Form Handling */
    if($_SERVER['REQUEST_METHOD'] == 'POST') { /* this sends all the form data to the server */
        /* Add new budget */
        if(isset($_POST['add'])) {
            $amount = $_POST['amount']; /* $_POST comes from form when submitted */
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $goal = $_POST['goal'];
            $duration = $_POST['duration'];

            /* Prepare to insert the data into the budget table */
            $stmt= $con->prepare("INSERT INTO Budget (user_id, amount, start_date, end_date, goal, duration) /* here is where tie the user_id to the budget (FK)*/
                                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idssss", $user_id, $amount, $start_date, $end_date, $goal, $duration);
            $stmt->execute();
            $stmt->close();
            $message = "Budget added successfully!";
        }
        /* Load transaction data for editing */
        elseif(isset($_POST['edit'])) {
            $budget_id = intval($_POST['budget_id']); /* Convert input to int for safety */
            /* Prepare SQL to fetch only current user's budgets */
            $stmt = $con->prepare('SELECT * FROM budget WHERE budget_id = ? AND user_id = ?');
            $stmt->bind_param("ii", $budget_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editing_data = $result->fetch_assoc(); /* Store budget for pre-filling the form */
            $stmt->close();
        }
        /* Update existing transaction */
        elseif (isset($_POST['update'])) {
            $budget_id = intval($_POST['budget_id']);
            $amount = $_POST['amount'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $goal = $_POST['goal'];
            $duration = $_POST['duration'];

            /* Prepare to update only the budget belonging to current user*/
            $stmt = $con->prepare("UPDATE budget SET amount = ?, start_date = ?, end_date = ?, goal = ?, duration = ? WHERE budget_id = ? AND user_id = ?");
            $stmt->bind_param("dssssii", $amount, $start_date, $end_date, $goal, $duration, $budget_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Budget updated successfully!";
        }
        /* Deleting a budget */
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
            $budget_id = intval($_POST['budget_id']);
            /* Prepare to delete only the budget belonging to current user */
            $stmt = $con->prepare("DELETE FROM budget WHERE budget_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $budget_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Budget deleted successfully!";
        }
    }

    /* Retrieve all budgets for the user */
    $stmt4= $con->prepare("SELECT * FROM budget WHERE user_id = ?");
    $stmt4->bind_param("i", $user_id);
    $stmt4->execute();
    $result = $stmt4->get_result();
    $budgets = $result->fetch_all(MYSQLI_ASSOC);
    $stmt4->close();
    $con->close();

    if (isset($stmt2)) {
        $stmt2->close();
    }

    if (isset($stmt3)) {
        $stmt3->close();
    }

?>

<!-- the html to go with the backend code, displaying the ability to update or create a budget -->
<!DOCTYPE html>
<html>
    <head>
        <title>Budget Page</title>
    </head>
    <body>
        <div class="content-two-column">
            <div class="main-content">
                <div id="budget">
                    <h2>Set a Budget</h2>
                    <?php if(!empty($message)) echo "<p><strong>$message</strong></p>"; ?>
                    <form action="/my-website/pages/budget.php" method="POST">
                        <label for="amount">Amount:</label>
                        <input type="number" name="amount" id="amount" step="0.1" required
                                value="<?= htmlspecialchars($editing_data['amount'] ?? '') ?>" required>


                        <label for="start_date">Start Date:</label>
                        <input type="date" name="start_date" id="start_date" required
                                value="<?= htmlspecialchars($editing_data['start_date'] ?? '') ?>" required>

                        <label for="end_date">End Date:</label>
                        <input type="date" name="end_date" id="end_date" required
                                value="<?= htmlspecialchars($editing_data['end_date'] ?? '') ?>" required>

                        <label for="duration">Duration:</label>
                        <select name="duration" id="duration" required>
                            <?php
                                $durations = ['Weekly', 'Monthly', 'Yearly'];
                                foreach ($durations as $duration) {
                                    $selected = (isset($editing_data['duration']) && $editing_data['duration'] == $duration) ? 'selected' : '';
                                    echo "<option value=\"$duration\" $selected>" . ucfirst($duration) . "</option>";
                                }
                            ?>
                        </select>

                        <label for="goal">Goal:</label>
                        <input type="text" name="goal" id="goal"
                                value="<?= htmlspecialchars($editing_data['goal'] ?? '') ?>" required>

                        <button type="submit" name="<?= $editing_data ? 'update' : 'add'; ?>">
                            <?= $editing_data ? 'Update Budget' : 'Add Budget'; ?>
                        </button>
                    </form>
                </div>

                <!-- Tab content for displaying the current goal -->
                <h2>Your Budget(s)</h2>
                <div class="user-budget">
                    <?php if (!empty($budgets)): ?>
                        <?php foreach ($budgets as $budget): ?>
                            <div class="budget-card">
                                <p><strong>Budget ID:</strong> <?php echo htmlspecialchars($budget['budget_id']); ?></p>
                                <p><strong>Budget Amount:</strong> <?php echo htmlspecialchars($budget['amount']); ?></p>
                                <p><strong>Start Date:</strong> <?php echo htmlspecialchars($budget['start_date']); ?></p>
                                <p><strong>End Date:</strong> <?php echo htmlspecialchars($budget['end_date']); ?></p>
                                <p><strong>Duration:</strong> <?php echo htmlspecialchars($budget['duration']); ?></p>
                                <p><strong>Goal:</strong> <?php echo htmlspecialchars($budget['goal']); ?></p>
                            
                                <form action="budget.php" method="post" class="inline-form">
                                    <input type="hidden" name="budget_id" value="<?= (int)$budget['budget_id'] ?>">
                                    <button type="submit" name="edit" class="edit-button">Edit</button>
                                </form>

                                <form action="budget.php" method="post" class="inline-form">
                                    <input type="hidden" name="budget_id" value="<?= (int)$budget['budget_id'] ?>">
                                    <button type="submit" name="delete" class="delete-button">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No budget set yet. Make a budget.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chatbot-column">
                <?php include '../includes/chatbot.php'; ?>
            </div>
        </div>
    </body>
</html>
