<?php

/**
 * Enkripsi Vigenère Cipher mod 256
 * @param string $plaintext
 * @param string $key
 * @return string
 */
function vigenereEncryptMod256($plaintext, $key)
{
    $encrypted = '';
    $keyLength = strlen($key);

    for ($i = 0; $i < strlen($plaintext); $i++) {
        $p = ord($plaintext[$i]);
        $k = ord($key[$i % $keyLength]);
        $c = ($p + $k) % 256;
        $encrypted .= chr($c);
    }

    return $encrypted;
}

/**
 * Enkripsi Rail Fence Cipher (3 rail)
 * @param string $text
 * @param int $rails
 * @return string
 */
function railFenceEncrypt($text, $rails = 3)
{
    if ($rails <= 1) return $text;

    $fence = array_fill(0, $rails, '');
    $rail = 0;
    $direction = 1;

    for ($i = 0; $i < strlen($text); $i++) {
        $fence[$rail] .= $text[$i];
        $rail += $direction;

        if ($rail === 0 || $rail === $rails - 1) {
            $direction *= -1;
        }
    }

    return implode('', $fence);
}

/**
 * Enkripsi gabungan Vigenère mod 256 + Rail Fence
 * @param string $plaintext
 * @param string $key
 * @return string Encrypted message
 */
function encryptMessage($plaintext, $key)
{
    $vigenere = vigenereEncryptMod256($plaintext, $key);
    $railfence = railFenceEncrypt($vigenere, 3);
    return $railfence;
}


/**
 * Dekripsi Rail Fence Cipher.
 * @param string $cipher
 * @param int $rails
 * @return string Decrypted message
 */
function railFenceDecrypt($cipher, $rails = 3)
{
    $len = strlen($cipher);
    $rail = array_fill(0, $rails, array_fill(0, $len, "\n"));

    $dir_down = null;
    $row = 0;
    $col = 0;

    for ($i = 0; $i < $len; $i++) {
        if ($row == 0) {
            $dir_down = true;
        } elseif ($row == $rails - 1) {
            $dir_down = false;
        }

        $rail[$row][$col++] = '*';

        $row += $dir_down ? 1 : -1;
    }

    $index = 0;
    for ($i = 0; $i < $rails; $i++) {
        for ($j = 0; $j < $len; $j++) {
            if ($rail[$i][$j] == '*' && $index < $len) {
                $rail[$i][$j] = $cipher[$index++];
            }
        }
    }

    $result = '';
    $row = 0;
    $col = 0;
    for ($i = 0; $i < $len; $i++) {
        if ($row == 0) {
            $dir_down = true;
        } elseif ($row == $rails - 1) {
            $dir_down = false;
        }

        if ($rail[$row][$col] != '*') {
            $result .= $rail[$row][$col++];
        }

        $row += $dir_down ? 1 : -1;
    }

    return $result;
}

/**
 * Dekripsi Vigenère Cipher mod 256
 * @param string $cipher
 * @param string $key
 * @return string Decrypted message
 */
function vigenereDecryptMod256($cipher, $key)
{
    $result = '';
    $keyLen = strlen($key);
    for ($i = 0, $j = 0; $i < strlen($cipher); $i++) {
        $c = ord($cipher[$i]);
        $k = ord($key[$j % $keyLen]);
        $dec = ($c - $k + 256) % 256;
        $result .= chr($dec);
        $j++;
    }
    return $result;
}

function decryptMessage($encrypted, $key)
{
    $afterRail = railFenceDecrypt($encrypted, 3);
    return vigenereDecryptMod256($afterRail, $key);
}

/**
 * Create a new message
 * @param int $senderId The ID of the sender
 * @param string $message The message content
 * @param int $receiverId The ID of the receiver
 * @return bool|int Returns the message ID on success, false on failure
 */


function createMessage($senderId, $message, $receiverId)
{
    global $conn;

    try {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, encrypted_message, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $senderId, $receiverId, $message);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error creating message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get messages between two users
 * @param int $userId The current user's ID
 * @param int $otherUserId The other user's ID
 * @param int $limit Optional limit of messages to return
 * @return array Returns array of messages (empty if none found)
 */
function getMessages($userId, $partnerId)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT * FROM messages
            WHERE 
                (sender_id = ? AND receiver_id = ?)
                OR
                (sender_id = ? AND receiver_id = ?)
            ORDER BY sent_at ASC
        ");
        $stmt->bind_param("iiii", $userId, $partnerId, $partnerId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        return $messages;
    } catch (Exception $e) {
        error_log("Error fetching messages: " . $e->getMessage());
        return [];
    }
}


/**
 * Update a message
 * @param int $messageId The ID of the message to update
 * @param int $userId The ID of the sender (for verification)
 * @param string $newMessage The new message content
 * @return bool Returns true on success, false on failure
 */
function updateMessage($messageId, $userId, $newMessage)
{
    global $conn;

    try {
        // First verify the message belongs to the user
        $stmt = $conn->prepare("SELECT sender_id FROM messages WHERE id = ? AND sender_id = ?");
        $stmt->bind_param("ii", $messageId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false; // Message doesn't exist or doesn't belong to user
        }

        // Update the message
        $stmt = $conn->prepare("UPDATE messages SET message = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newMessage, $messageId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating message: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a message
 * @param int $messageId The ID of the message to delete
 * @param int $userId The ID of the sender (for verification)
 * @return bool Returns true on success, false on failure
 */
function deleteMessage($messageId, $userId)
{
    global $conn;

    try {
        // First verify the message belongs to the user
        $stmt = $conn->prepare("SELECT sender_id FROM messages WHERE id = ? AND sender_id = ?");
        $stmt->bind_param("ii", $messageId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false; // Message doesn't exist or doesn't belong to user
        }

        // Delete the message
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->bind_param("i", $messageId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error deleting message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get recent conversations for a user
 * @param int $userId The user ID to get conversations for
 * @return array Returns array of recent conversations (empty if none found)
 */
function getRecentConversations($userId, $key)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT m.*
            FROM messages m
            INNER JOIN (
                SELECT 
                    LEAST(sender_id, receiver_id) AS user1,
                    GREATEST(sender_id, receiver_id) AS user2,
                    MAX(sent_at) AS latest
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY user1, user2
            ) grouped ON (
                LEAST(m.sender_id, m.receiver_id) = grouped.user1 AND
                GREATEST(m.sender_id, m.receiver_id) = grouped.user2 AND
                m.sent_at = grouped.latest
            )
            ORDER BY m.sent_at DESC
        ");
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $conversations = [];
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }

        foreach ($conversations as &$conversation) {
            $conversation['encrypted_message'] = decryptMessage(base64_decode($conversation['encrypted_message']), $key);
        }

        return $conversations;
    } catch (Exception $e) {
        error_log("Error getting recent conversations: " . $e->getMessage());
        return [];
    }
}
