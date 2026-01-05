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
    $message = "";/* Initialize message variable to store feedback messages */

    $editing_data = null; /* Holds transaction data if editing */

    /* Form Handling */
    if($_SERVER['REQUEST_METHOD'] == 'POST') { /* this sends all the form data to the server */
        /* Add new transaction */
        if(isset($_POST['add'])) {
            $amount = $_POST['amount']; /* $_POST comes from form when submitted */
            $category = $_POST['category'];
            $date = $_POST['date'];
            $note = $_POST['note'];

            /* Prepare to insert the data into the transaction table */
            $stmt= $con->prepare("INSERT INTO transaction (user_id, amount, category, date, note) /* here is where tie the user_id to the transaction (FK)*/
                                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $user_id, $amount, $category, $date, $note);
            $stmt->execute();
            $stmt->close();
            $message = "Transaction added successfully!";
        }
        /* Load transaction data for editing */
        elseif(isset($_POST['edit'])) {
            $transaction_id = intval($_POST['transaction_id']); /* Convert input to int for safety */
            /* Prepare SQL to fetch only current user's transactions */
            $stmt = $con->prepare('SELECT * FROM transaction WHERE transaction_id = ? AND user_id = ?');
            $stmt->bind_param("ii", $transaction_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editing_data = $result->fetch_assoc(); /* Store transaction for pre-filling the form */
            $stmt->close();
        }
        /* Update existing transaction */
        elseif (isset($_POST['update'])) {
            $transaction_id = intval($_POST['transaction_id']);
            $amount = $_POST['amount'];
            $category = $_POST['category'];
            $date = $_POST['date'];
            $note = $_POST['note'];

            /* Prepare to update only the transaction belonging to current user*/
            $stmt = $con->prepare("UPDATE transaction SET amount = ?, category = ?, date = ?, note = ? WHERE transaction_id = ? AND user_id = ?");
            $stmt->bind_param("dssssi", $amount, $category, $date, $note, $transaction_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Transaction updated successfully!";
        }
        /* Deleting a transaction */
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
            $transaction_id = intval($_POST['transaction_id']);
            /* Prepare to delete only the transaction belonging to current user */
            $stmt = $con->prepare("DELETE FROM transaction WHERE transaction_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $transaction_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Transaction deleted successfully!";
        }
    }

    /* Retrieve the most recent (10) transactions for the user */
    $stmt= $con->prepare("SELECT * FROM transaction WHERE user_id = ? ORDER BY date DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $con->close();

    /* Create transaction summary, concatenate transactions into readable string for AI prompt */
    $transactionSummary = "";
    foreach ($transactions as $transaction) {
        $transactionSummary .= "{$transaction['amount']} | {$transaction['category']} | {$transaction['date']} | {$transaction['note']}<br>";
    }

    /* Prepare prompt for AI */
    $prompt = "You are a financial assistant. The user has made the following transactions: $transactionSummary. Please provide 3 recommendations for managing their finances
                BASED ON THESE TRANSACTIONS. Make these recommendations concise, yet relative to their spending. Provide alternative ways to save in the categories they are spending money in.
                You have a limit of 120 tokens to provide these financial suggestions.";

    /* AI prompt */
    $apiKey = '';
    /* Initialize a cURL session */
    $ch = curl_init();
    /* Set URL to send request to OpenAI's chat completions endpoint */
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    /* Return response as a string instead of outputting it */
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    /* Set the request method to POST (sending data to API) */
    curl_setopt($ch, CURLOPT_POST, true);
    /* Set HTTP headers for content type and authorization */
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json', /* inform server sending in JSON format */
        'Authorization: Bearer ' . $apiKey /* Pass API key */
    ]);
    /* Set the POST fields for the request */
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful budget.financial manager. The user wants to capitalize on efficient spending and saving strategies. Analyze their transactions
                                                and provide actionable recommendations based on these. Give relative suggestions for alternative ways to save in the categories
                                                they are spending money in.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        "max_tokens" => 120
    ]));

    /* Send the request to OpenAI API and store response as string */
    $response = curl_exec($ch);
    if($response === false) {
        die("cURL Error: " . curl_error($ch));
    }

    /* Close cURL session */
    curl_close($ch);

    /* Decode JSON response */
    $data = json_decode($response, true);

    if(isset($data['error'])) {
        die("API Error: " . $data['error']['message']);
    }

    // Storing AI suggestion or fallback message
    $aiSuggestion = $data['choices'][0]['message']['content'] ?? 'No recommendations available.';

    $suggestions = preg_split('/\d+\.\s+|\r\n|\r|\n/', $aiSuggestion);
    /* 
    ^ breaks down as:

        \d+ → matches one or more digits (0–9).
        \. → matches a literal period . right after the digits.
        \s+ → matches one or more whitespace characters (space, tab).
        Combined: \d+\.\s+ matches things like 1. , 2. , 15. etc.

        | → means “OR” in regex.
        \r\n → Windows-style line break.
        \r → Mac-style line break.
        \n → Unix/Linux line break.

    */
    $suggestions = array_filter(array_map('trim', $suggestions)); // cleaning up response

?>

<!-- the html to go with the backend code, displaying the ability to list or view a transaction -->
<!DOCTYPE html>
<html>
    <head>
        <title>Transaction Page</title>
    </head>
    <body>
        <div class="content-two-column">
            <div class="main-content">
                <div id="transaction">
                    <h2>List a Transaction</h2>
                    <?php if(!empty($message)) echo "<p><strong>$message</strong></p>"; ?>
                    <form action="/my-website/pages/transaction.php" method="POST">
                        <input type="hidden" name="transaction_id" value="<?=$editing_data['transaction_id'] ?? ''; ?>">
                        

                        <label for="amount">Amount:</label>
                        <input type="number" name="amount" id="amount" step="0.1" value="<?=htmlspecialchars($editing_data['amount'] ?? ''); ?>" required>

                        <label for="category">Category:</label>
                        <select name="category" id="category" required>
                            <?php
                            $categories = ['housing', 'groceries', 'entertainment', 'medical', 'other'];
                            foreach ($categories as $category) {
                                $selected = (isset($editing_data['category']) && $editing_data['category'] == $category) ? 'selected' : '';
                                echo "<option value=\"$category\" $selected>" . ucfirst($category) . "</option>";
                            }
                            ?>
                            <!-- <option value="housing">Housing</option>
                            <option value="groceries">Groceries</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="medical">Medical</option>
                            <option value="other">Other</option> -->
                        </select>

                        <label for="date">Date:</label>
                        <input type="date" name="date" id="date"
                            value="<?=htmlspecialchars($editing_data['date'] ?? ''); ?>" required>

                        <label for="note">Note:</label>
                        <input type="text" name="note" id="note"
                            value="<?=htmlspecialchars($editing_data['note'] ?? ''); ?>" required>

                        <button type="submit" name="<?= isset($editing_data) ? 'update' : 'add'; ?>">
                            <?= isset($editing_data) ? 'Update Transaction' : 'List Transaction'; ?>
                        </button>
                    </form>
                </div>

                <!-- Tab content for displaying the last 10 transactions on frontend -->
                <h2>Recent Transactions</h2><br>
                <div class="transaction-container">
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="transaction-card">
                                <p>
                                    <strong>Amount:</strong> <?php echo htmlspecialchars($transaction['amount']); ?>
                                    <strong> | Category:</strong> <?php echo htmlspecialchars($transaction['category']); ?><br>
                                    <strong>Date:</strong> <?php echo htmlspecialchars($transaction['date']); ?>
                                    <strong> | Note:</strong> <?php echo htmlspecialchars($transaction['note']); ?>
                                </p>

                                <form action="transaction.php" method="post" class="inline-form">
                                    <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($transaction['transaction_id']); ?>">
                                    <button type="submit" name="edit" class="edit-button">Edit</button>
                                </form>

                                <form action="transaction.php" method="post" class="inline-form">
                                    <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($transaction['transaction_id']); ?>">
                                    <button type="submit" name="delete" class="delete-button">Delete</button>
                                </form>
                            </div><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No transactions made.</p>
                    <?php endif; ?>
                </div>

                <h2>AI Financial Assistant</h2>
                <div class="ai-suggestion-container">
                    <?php foreach ($suggestions as $s): ?>
                        <?php $s = strip_tags($s); ?>
                        <div class="ai-suggestion">
                            <p><?php echo htmlspecialchars($s); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="chatbot-column">
                <?php include '../includes/chatbot.php'; ?>
            </div>
        </div>
    </body>
</html>
