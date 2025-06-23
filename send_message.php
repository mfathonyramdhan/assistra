<?php
require_once __DIR__ . '/src/Message.php';
require_once __DIR__ . '/src/User.php';
require_once 'connection.php';
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$key = $_ENV['ENCRYPTION_KEY'];

// Check if user is logged in
if (!isset($_COOKIE['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get parameters
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;

if (empty($message) || !$chatId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid message or chat ID']);
    exit();
}


$encryptedMessage = encryptMessage($message, $key);
$encryptedMessage = base64_encode($encryptedMessage);
// Create the message
$result = createMessage($_COOKIE['user_id'], $encryptedMessage, $chatId);

if ($result) {
    echo json_encode(['success' => true, 'message_id' => $result]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}
