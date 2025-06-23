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

// Get parameters
$chatId = isset($_GET['chat']) ? (int)$_GET['chat'] : 0;
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if (!$chatId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid chat ID']);
    exit();
}

// Get messages newer than lastId
try {
    $stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE 
        ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND id > ?
    ORDER BY sent_at ASC
");
    $userId = $_COOKIE['user_id'];
    $stmt->bind_param("iiiii", $userId, $chatId, $chatId, $userId, $lastId);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    // Dekripsi pesan langsung dalam array (by reference)
    foreach ($messages as &$message) {
        $message['encrypted_message'] = decryptMessage(base64_decode($message['encrypted_message']), $key);
    }

    // Kembalikan hasil dalam format JSON
    echo json_encode(['messages' => $messages]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
