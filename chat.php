<?php
require_once __DIR__ . '/src/Message.php';
require_once __DIR__ . '/src/User.php';
require_once 'connection.php';
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$key = $_ENV['ENCRYPTION_KEY'];

// Check if user is not logged in
if (!isset($_COOKIE['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user info from cookie
$id = $_COOKIE['user_id'];
$username = $_COOKIE['username'];
$role = $_COOKIE['role'];

// Get CS user ID
if ($role == 'user') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'cs' LIMIT 1");
    $stmt->execute();
    $csResult = $stmt->get_result();
    $csUser = $csResult->fetch_assoc();
    $csId = $csUser['id'];
} else {
    $csId = $id;
}

// Get recent conversations
$conversations = getRecentConversations($id, $key);

// Handle new chat with CS
if (isset($_GET['to']) && $_GET['to'] === 'cs') {
    // Create initial message with CS
    $text = "Halo, saya membutuhkan bantuan";
    $encryptedText = encryptMessage($text, $key);
    $encryptedText = base64_encode($encryptedText);
    createMessage($id, $encryptedText, 6);
    // Redirect to chat with CS
    header("Location: chat.php?chat=" . 6);
    exit();
}

// Get current chat partner (default to CS if none selected)
$currentChatId = isset($_GET['chat']) ? (int)$_GET['chat'] : 0;

// Get messages for current chat
if ($currentChatId != 0) {
    $messages = getMessages($id, $currentChatId);
} else {
    $messages = [];
}
//print_r($messages);
// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $newMessage = trim($_POST['message']);
    if (!empty($newMessage)) {
        createMessage($id, $newMessage, $currentChatId);
        // Redirect to prevent form resubmission
        header("Location: chat.php?chat=" . $currentChatId);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Assistra Live Chat Interface</title>
    <link rel="icon" type="image/png" href="assets/favicon.png" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet" />
    <style>
        /* Base Reset & Typography */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Inter", sans-serif;
            background: #f5f7fa;
            color: #444;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Responsive Layout Limits */
        .container {
            width: 1200px;
            margin: 0 auto;
            padding: 12px;
            height: 90vh;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
            background: #f5f7fa;
        }

        /* Contact List Sidebar */
        .contact-sidebar {
            width: 300px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgb(0 0 0 / 0.1);
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .user-profile {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8f9ff;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #5a4de8;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .user-status {
            font-size: 0.8rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #4caf50;
            border-radius: 50%;
        }

        .contact-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .contact-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .contact-search {
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
        }

        .search-input {
            width: 100%;
            padding: 10px 16px;
            border: 1.5px solid #ddd;
            border-radius: 25px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            border-color: #5a4de8;
            box-shadow: 0 0 8px rgba(90, 77, 232, 0.4);
        }

        .contact-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .contact-item:hover {
            background-color: #f5f7fa;
        }

        .contact-item.active {
            background-color: #f0f0ff;
        }

        .contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .contact-info {
            flex: 1;
        }

        .contact-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
            font-size: 0.9rem;
        }

        .contact-status {
            font-size: 0.8rem;
            color: #666;
        }

        .contact-status.online {
            color: #4caf50;
        }

        /* Chat Card */
        .chat-card {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgb(0 0 0 / 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 600px;
        }

        /* Chat Header */
        .chat-header {
            background: url("assets/header.webp") center/cover;
            position: relative;
            padding: 24px 24px 24px 72px;
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 10px rgb(0 0 0 / 0.15);
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
            overflow: hidden;
        }

        .chat-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            filter: blur(8px);
            z-index: 0;
        }

        .chat-header>* {
            position: relative;
            z-index: 1;
        }

        /* Profile Circle Initial */
        .profile-circle {
            position: absolute;
            top: 16px;
            left: 24px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            user-select: none;
            box-shadow: 0 2px 8px rgb(0 0 0 / 0.2);
            z-index: 1;
        }

        /* Close Icon */
        .close-btn {
            position: absolute;
            right: 24px;
            top: 24px;
            background: transparent;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            line-height: 0;
        }

        .close-btn:hover,
        .close-btn:focus {
            opacity: 1;
            outline: none;
        }

        /* Header Title & Description */
        .chat-title {
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        .chat-subtitle {
            font-weight: 400;
            font-size: 0.9rem;
            opacity: 0.75;
            line-height: 1.4;
            max-width: 280px;
        }

        /* Chat Messages Area */
        .chat-messages {
            flex: 1;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            background: #fafaff;

        }

        /* Single Message */
        .message {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            max-width: 85%;
            word-wrap: break-word;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Assistant Message */
        .message.assistant {
            flex-direction: row;
        }

        .message.assistant .avatar {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            /* border: 2px solid #5a4de8; */
            box-shadow: 0 2px 8px rgba(90, 77, 232, 0.2);
            transition: transform 0.2s ease;
        }

        .message.assistant .avatar:hover {
            transform: scale(1.05);
        }

        .message.assistant .message-content {
            background: white;
            color: #222;
            border-radius: 18px 18px 18px 4px;
            padding: 14px 18px;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: relative;
            padding-bottom: 28px;
            min-height: 40px;
            min-width: 85px;
            display: inline-block;
            transition: all 0.2s ease;
            border: 1px solid rgba(90, 77, 232, 0.1);
        }

        .message.assistant .message-content:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }

        .message.assistant .timestamp {
            font-size: 0.6rem;
            opacity: 0.4;
            position: absolute;
            bottom: 6px;
            right: 12px;
            user-select: none;
            color: #666;
            display: block;
            white-space: nowrap;
            transition: opacity 0.2s ease;
        }

        .message.assistant:hover .timestamp {
            opacity: 0.6;
        }

        /* User Message */
        .message.user {
            justify-content: flex-end;
            margin-left: auto;
            width: 100%;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #5a4de8, #4a3dd8);
            color: white;
            border-radius: 18px 18px 4px 18px;
            padding: 14px 18px;
            font-size: 0.95rem;
            max-width: 75%;
            word-wrap: break-word;
            box-shadow: 0 4px 12px rgba(90, 77, 232, 0.25);
            position: relative;
            padding-bottom: 28px;
            min-height: 40px;
            min-width: 85px;
            margin-left: auto;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .message.user .message-content:hover {
            box-shadow: 0 6px 16px rgba(90, 77, 232, 0.35);
            transform: translateY(-1px);
        }

        .message.user .timestamp {
            font-size: 0.6rem;
            opacity: 0.7;
            position: absolute;
            bottom: 6px;
            right: 12px;
            user-select: none;
            color: rgba(255, 255, 255, 0.9);
            display: block;
            white-space: nowrap;
            transition: opacity 0.2s ease;
        }

        .message.user:hover .timestamp {
            opacity: 0.9;
        }

        /* Assistant typing indicator */
        .assistant-typing {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            max-width: 85%;
            animation: fadeIn 0.3s ease-in-out;
        }

        .assistant-typing .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid #5a4de8;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(90, 77, 232, 0.2);
        }

        .typing-indicator {
            display: flex;
            gap: 6px;
            background: white;
            padding: 14px 18px;
            border-radius: 18px 18px 18px 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(90, 77, 232, 0.1);
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: #5a4de8;
            border-radius: 50%;
            animation: blink 1.4s infinite ease-in-out;
            opacity: 0.4;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes blink {

            0%,
            80%,
            100% {
                opacity: 0.4;
                transform: scale(0.8);
            }

            40% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Chat Input Area */
        .chat-input-area {
            padding: 12px 24px;
            border-top: 1px solid #eee;
            background: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-input {
            flex: 1;
            border-radius: 25px;
            border: 1.5px solid #ddd;
            padding: 10px 16px;
            font-size: 1rem;
            line-height: 1.3;
            outline-offset: 2px;
            transition: border-color 0.3s ease;
        }

        .chat-input::placeholder {
            color: #999;
        }

        .chat-input:focus {
            border-color: #5a4de8;
            box-shadow: 0 0 8px rgba(90, 77, 232, 0.4);
        }

        /* Icon Buttons */
        .icon-button {
            background: transparent;
            border: none;
            cursor: pointer;
            outline-offset: 2px;
            padding: 8px;
            color: #777;
            transition: color 0.2s ease;
            font-size: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .icon-button:hover,
        .icon-button:focus {
            color: #5a4de8;
            outline: none;
            background: rgba(90, 77, 232, 0.1);
        }

        .icon-button:disabled {
            color: #ccc;
            cursor: default;
            background: none;
            pointer-events: none;
        }

        /* Accessibility and Focus */
        .icon-button:focus-visible,
        .chat-input:focus-visible {
            outline: 3px solid #5a4de8;
            outline-offset: 3px;
        }

        /* Scrollbar Styling for modern browsers */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 20px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background-color: #c8c8c8;
            border-radius: 12px;
            border: 2px solid #f0f0f0;
        }

        /* Responsive Adjustments */
        @media (max-width: 767px) {
            .container {
                margin-top: 100px;
                padding: 8px;
                height: 100vh;
                max-width: 100vw;
                flex-direction: column;
            }

            .contact-sidebar {
                width: 100%;
                height: 250px;
                border-radius: 0;
                box-shadow: none;
            }

            .chat-card {
                max-width: 100%;
                height: calc(100% - 250px);
                border-radius: 0;
                box-shadow: none;
            }

            .chat-header {
                padding-left: 56px;
            }

            .profile-circle {
                margin: 20px;
                width: 50px;
                height: 50px;
                font-size: 18px;
                top: 12px;
                right: 16px;
                left: auto;
                /* Override default left position */
                position: absolute;
                /* Ensure absolute positioning */
            }

            .close-btn {
                right: 16px;
                top: 16px;
                font-size: 22px;
            }

            .chat-messages {
                padding: 20px;
                gap: 25px;
            }
        }

        .no-conversations {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 20px;
        }

        .start-chat-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #5a4de8;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(90, 77, 232, 0.2);
        }

        .start-chat-btn:hover {
            background: #4a3dd8;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(90, 77, 232, 0.3);
        }

        .start-chat-btn .material-icons {
            font-size: 20px;
        }
    </style>
</head>

<body>
    <div class="container" role="main">
        <aside class="contact-sidebar" aria-label="Contact list">
            <div class="user-profile">
                <img
                    src="<?= $role == 'cs' ? 'assets/cs.png' : 'https://anteroaceh.com/files/images/33bac083ba44f180c1435fc41975bf36.jpg' ?>"
                    alt="Your profile"
                    class="user-avatar" />
                <div class="user-info">
                    <div class="user-name"><?= $username ?></div>
                    <div class="user-status">
                        <span class="status-dot"></span>
                        <?= $role ?>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout" style="display: flex; align-items: center; padding: 8px; border-radius: 50%; transition: all 0.3s ease; background: transparent; text-decoration: none;">
                    <span class="material-icons" style="color: #5a4de8; font-size: 24px; transition: transform 0.3s ease;">logout</span>
                    <style>
                        .logout-btn:hover {
                            background: rgba(90, 77, 232, 0.1);
                        }

                        .logout-btn:hover .material-icons {
                            transform: translateX(2px);
                        }
                    </style>
                </a>
            </div>
            <div class="contact-header">
                <h2 class="contact-title">Messages</h2>
            </div>
            <div class="contact-search">
                <input
                    type="text"
                    class="search-input"
                    placeholder="Search messages..."
                    aria-label="Search messages" />
            </div>
            <div class="contact-list">
                <?php if ($role == 'user' && empty($conversations)): ?>
                    <div class="no-conversations">
                        <a href="?to=cs" class="start-chat-btn">
                            <span class="material-icons">chat</span>
                            Start Chat
                        </a>
                    </div>
                <?php elseif ($role == 'user' && !empty($conversations)): ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="?chat=<?= $conv['receiver_id'] == $id ? $conv['sender_id'] : $conv['receiver_id'] ?>" class="contact-item <?= $currentChatId == ($conv['receiver_id'] == $id ? $conv['sender_id'] : $conv['receiver_id']) ? 'active' : '' ?>" style="text-decoration: none;">
                            <img
                                src="assets/cs.png"
                                alt="CS Support"
                                class="contact-avatar" />
                            <div class="contact-info">
                                <div class="contact-name">CS Support</div>
                                <div class="contact-status">
                                    <?= htmlspecialchars(substr($conv['encrypted_message'], 0, 30)) . (strlen($conv['encrypted_message']) > 30 ? '...' : '') ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($role == 'cs' && !empty($conversations)): ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="?chat=<?= $conv['sender_id'] == $id ? $conv['receiver_id'] : $conv['sender_id'] ?>" class="contact-item <?= $currentChatId == ($conv['sender_id'] == $id ? $conv['receiver_id'] : $conv['sender_id']) ? 'active' : '' ?>" style="text-decoration: none;">
                            <img
                                src="https://anteroaceh.com/files/images/33bac083ba44f180c1435fc41975bf36.jpg"
                                alt="CS Support"
                                class="contact-avatar" />
                            <div class="contact-info">
                                <div class="contact-name"><?php $user = getUserById($conv['sender_id'] == $id ? $conv['receiver_id'] : $conv['sender_id']);
                                                            echo $user['full_name'] ?></div>
                                <div class="contact-status">
                                    <?= htmlspecialchars(substr($conv['encrypted_message'], 0, 30)) . (strlen($conv['encrypted_message']) > 30 ? '...' : '') ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </aside>
        <section class="chat-card" aria-label="Assistra live chat interface">
            <header class="chat-header">
                <img
                    src="assets/logo2.png"
                    class="profile-circle"
                    alt="Assistra logo"
                    aria-hidden="true" />
                <h1 class="chat-title">Assistra</h1>
                <p class="chat-subtitle">
                    Protecting every message, empowering every conversation.
                </p>
            </header>

            <div class="chat-messages" role="log" aria-live="polite" aria-relevant="additions">
                <div class="loading-messages" style="text-align: center; padding: 20px; color: #666;">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>

            <form class="chat-input-area" method="POST" aria-label="Send a message form">
                <input
                    id="chatInput"
                    name="message"
                    class="chat-input"
                    type="text"
                    placeholder="Send a message..."
                    aria-label="Type your message here"
                    autocomplete="off"
                    required />
                <!-- <button
                    type="button"
                    class="icon-button"
                    aria-label="Attach image"
                    title="Attach image">
                    <span class="material-icons" aria-hidden="true">image</span>
                </button> -->
                <button
                    type="submit"
                    class="icon-button"
                    aria-label="Send message"
                    title="Send message"
                    id="sendButton">
                    <span class="material-icons" aria-hidden="true">send</span>
                </button>
            </form>
        </section>
    </div>

    <script>
        const chatInput = document.getElementById("chatInput");
        const sendButton = document.getElementById("sendButton");
        const chatMessages = document.querySelector(".chat-messages");
        let lastMessageId = 0;

        // Function to fetch messages
        function fetchMessages() {
            const currentChatId = new URLSearchParams(window.location.search).get('chat');
            if (!currentChatId) return;

            fetch(`get_messages.php?chat=${currentChatId}&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        // Remove loading indicator if it exists
                        const loadingIndicator = document.querySelector('.loading-messages');
                        if (loadingIndicator) {
                            loadingIndicator.remove();
                        }

                        data.messages.forEach(message => {
                            appendMessage(message);
                            lastMessageId = Math.max(lastMessageId, message.id);
                        });
                        // Scroll to bottom after new messages
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        // Function to append new message to chat
        function appendMessage(message) {
            const isUser = message.sender_id == <?= $id ?>;
            const messageHtml = `
                <article class="message ${isUser ? 'user' : 'assistant'}"
                    data-message-id="${message.id}"
                    aria-label="${isUser ? 'Your message' : 'Assistant message'}: ${message.encrypted_message}">
                    ${!isUser ? `
                        <img src="https://anteroaceh.com/files/images/33bac083ba44f180c1435fc41975bf36.jpg"
                            alt="${message.sender_id}"
                            class="avatar" />
                    ` : ''}
                    <div class="message-content">
                        ${message.encrypted_message}
                        <time class="timestamp" datetime="${message.sent_at}" aria-hidden="true">
                            ${new Date(message.sent_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}
                        </time>
                    </div>
                </article>
            `;
            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
        }

        // Function to send message via AJAX
        function sendMessage(message) {
            const currentChatId = new URLSearchParams(window.location.search).get('chat');
            if (!currentChatId) return;

            const formData = new FormData();
            formData.append('message', message);
            formData.append('chat_id', currentChatId);

            fetch('send_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        chatInput.value = '';
                        sendButton.disabled = true;
                        // Fetch messages immediately after sending
                        fetchMessages();
                    }
                })
                .catch(error => console.error('Error sending message:', error));
        }

        // Handle form submission
        document.querySelector('.chat-input-area').addEventListener('submit', function(e) {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (message) {
                sendMessage(message);
            }
        });

        chatInput.addEventListener("input", () => {
            sendButton.disabled = chatInput.value.trim() === "";
        });

        // Auto-scroll to bottom when new messages are added
        const observer = new MutationObserver(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });

        observer.observe(chatMessages, {
            childList: true,
            subtree: true
        });

        // Initial fetch and then poll every second
        fetchMessages();
        setInterval(fetchMessages, 1000);
    </script>
</body>

</html>