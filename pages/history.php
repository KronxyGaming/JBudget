<?php
    /* include(s) */
    include '../includes/db.php';
    include '../includes/header.php';

    /* start a session and verify the user to ensure secure access to this pahge */
    session_start();
    if(!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    /* storing the user_id with $_SESSION because this is done at the start when user logs in */
    $user_id = $_SESSION['user_id'];
    $message = "";

    /* Retrieve all transactions and display them for the user */
    /* this time we are querying for all transactions and not putting a limit, because we want the history page to display all transactions */
    $stmt = $con->prepare("SELECT * FROM transaction WHERE user_id = ? ORDER BY date");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $con->close();
?>

<!-- the html for the frontend so the transactins can be displayed -->
 <!DOCTYPE html>
 <html>
    <head>
        <title>History Page</title>
    </head>
    <body>
        <div class="content-two-column">
            <div class="main-content">
                <div id="checkbox">
                    <input type="checkbox" id="housing" name="housing" value="housing">
                    <label for="housing">Housing</label>
                    <input type="checkbox" id="groceries" name="groceries" value="groceries">
                    <label for="groceries">Groceries</label>
                    <input type="checkbox" id="medical" name="medical" value="medical">
                    <label for="medical">Medical</label>
                    <input type="checkbox" id="entertainment" name="entertainment" value="entertainment">
                    <label for="entertainment">Entertainment</label>
                    <input type="checkbox" id="other" name="other" value="other">
                    <label for="other">Other</label>
                </div>
                <h2>View Transaction History</h2>
                <div class="transaction-container">
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $transaction): ?>
                        <?php $category = strtolower(htmlspecialchars(trim($transaction['category']))); ?>
                            <div class="filterDiv <?php echo $category; ?> ">
                                <div class="transaction-card">
                                    <p>
                                        <strong>Amount:</strong> <?php echo htmlspecialchars($transaction['amount']); ?>
                                        <strong> | Category:</strong> <?php echo htmlspecialchars($transaction['category']); ?><br>
                                        <strong>Date:</strong> <?php echo htmlspecialchars($transaction['date']); ?>
                                        <strong> | Note:</strong> <?php echo htmlspecialchars($transaction['note']); ?>
                                    </p>
                                </div><br>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No transaction history </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="chatbot-column">
                <?php include '../includes/chatbot.php'; ?>
            </div>
        </div>
    </body>
 </html>

 <!-- JS script for checkbox functionality -->
 <script>
    /* Wait until the entire HTML document has been fully loaded */
    document.addEventListener('DOMContentLoaded', function() {
        
        /* select all checkbox inputs within the div that have id="checkbox */
        const checkboxes = document.querySelectorAll('#checkbox input[type = "checkbox"]');
        /* select all transaction elements with class="filterDiv" */
        const transactions = document.querySelectorAll('.filterDiv');

        /* this filters the transactions list based on the checked checkboxes/categories */
        function filterTransactions() {
            /* create an array of values from the checked checkboxes */
            const checked = Array.from(checkboxes). /* con vert NodeList to Array */
                            filter(checkbox=>checkbox.checked) /* keep only checked checkboxes */
                            .map(checkbox=>checkbox.value.toLowerCase()); /* get the value of each checked checkbox */
            
            /* loop through each transaction and check if it matches any of the checked categories */
            transactions.forEach(transaction => {
                /* if no checkboxes are checked, show all transactions */
                if(checked.length === 0) {
                    transaction.classList.remove('hidden');
                /* check if th transaction has a class that matches any of the selected checkbox values */
                } else {
                    /* if it matches one of the selected categories, remove the 'hidden' class */
                    const matchesCategory = checked.some(category => transaction.classList.contains(category));
                    if(matchesCategory) {
                        transaction.classList.remove('hidden');
                    } else {
                        /* if it does not match, add the 'hidden' class to hide it */
                        transaction.classList.add('hidden');
                    }
                }
            });
        }

        /* add event listener to each checkbox so we filter the transactions each time a checkbox is selected*/
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', filterTransactions);
        });

        /* call the filterTransactions function to apply the default state (show all) */
        filterTransactions();
    });
 </script>
