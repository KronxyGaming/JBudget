<?php
/* Inform browser returning JSON */
header('Content-Type: application/json');

/* Disable error reporting */
ini_set('display_errors', 0);
error_reporting(0);

/* Start session to track user login */
session_start();

include 'includes/db.php';

/* Check if user is authenticated */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

/* Store user ID and message for DB */
$user_id = $_SESSION['user_id'];

/* Get raw JSON data */
$input = json_decode(file_get_contents('php://input'), true);

/* Extract user's message */
$userMessage = $input['message'] ?? '';

/* Validate user's message is not empty */
if(empty(trim($userMessage))) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit();
}

/* Attribute in DB */
$timestampCol = 'timestamp';

/* Insert user message into chatbot table */
$stmt = $con->prepare("INSERT INTO chatbot(user_id, sender, message, $timestampCol) VALUES (?, 'user', ?, NOW())");
$stmt->bind_param("is", $user_id, $userMessage);
$stmt->execute();
$stmt->close();

/* API key */
$openaiApiKey = '';

/* Structure POST data for OpenAI API */
$postData = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful budget/financial assistant. The user is using this app to properly budget and manage their finances. Provide concise
        and well supported responses to their questions.'],
        ['role' => 'user', 'content' => $userMessage],
    ],
    'max_tokens' => 120,
];

/* Initialize cURL request */
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); /* Return response as a string */
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $openaiApiKey,
]);
curl_setopt($ch, CURLOPT_POST, true); /* Use POST */
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData)); /* Send JSON data */

/* Execute request */
$response = curl_exec($ch);

/* Error handling */
if(curl_errno($ch)) {
    echo json_encode(['error' => 'Failed to communicate with OpenAI API']);
    curl_close($ch);
    exit();
}
/* Close cURL session */
curl_close($ch);

/* Check for HTML response*/
if(strpos($response, '<') === 0) {
    echo json_encode(['error' => 'returned HTML instead of JSON', 'raw' => $response]);
    exit();
}

/* Decode JSON response in PHP array */
$responseData = json_decode($response, true);

if(isset($responseData['choices'][0]['message']['content'])) {
    /* Extract AI reply in DB */
    $botReply = $responseData['choices'][0]['message']['content'];

    /* Store AI reply in DB */
    $stmt = $con->prepare("INSERT INTO chatbot(user_id, sender, message, timestamp) VALUES (?, 'bot', ?, NOW())");
    $stmt->bind_param("is", $user_id, $botReply);
    $stmt->execute();
    $stmt->close();

    /* Return AI reply as JSON */
    echo json_encode(['reply' => $botReply]);
} else {
    echo json_encode(['error' => 'Invalid response from OpenAI API']);
}

exit();
