<?php
require_once __DIR__ . '/../connection.php';

/**
 * Get user information by ID
 * @param int $userId The user ID to get information for
 * @return array|bool Returns user data array or false if not found
 */
function getUserById($userId)
{
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting user: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user information by username
 * @param string $username The username to get information for
 * @return array|bool Returns user data array or false if not found
 */
function getUserByUsername($username)
{
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting user: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all users with a specific role
 * @param string $role The role to filter users by
 * @return array Returns array of users with the specified role
 */
function getUsersByRole($role)
{
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE role = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    } catch (Exception $e) {
        error_log("Error getting users by role: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new user
 * @param string $username The username for the new user
 * @param string $password The password for the new user
 * @param string $role The role for the new user (default: 'user')
 * @return bool|int Returns the new user's ID on success, false on failure
 */
function createUser($username, $password, $role = 'user')
{
    global $conn;

    try {
        // Check if username already exists
        if (getUserByUsername($username)) {
            return false;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $role);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error creating user: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user information
 * @param int $userId The ID of the user to update
 * @param array $data Array of fields to update (username, password, role)
 * @return bool Returns true on success, false on failure
 */
function updateUser($userId, $data)
{
    global $conn;

    try {
        $updates = [];
        $types = "";
        $values = [];

        if (isset($data['username'])) {
            $updates[] = "username = ?";
            $types .= "s";
            $values[] = $data['username'];
        }

        if (isset($data['password'])) {
            $updates[] = "password = ?";
            $types .= "s";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['role'])) {
            $updates[] = "role = ?";
            $types .= "s";
            $values[] = $data['role'];
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $types .= "i";
        $values[] = $userId;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating user: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a user
 * @param int $userId The ID of the user to delete
 * @return bool Returns true on success, false on failure
 */
function deleteUser($userId)
{
    global $conn;

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify user credentials
 * @param string $username The username to verify
 * @param string $password The password to verify
 * @return array|bool Returns user data array if credentials are valid, false otherwise
 */
function verifyUser($username, $password)
{
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            unset($user['password']); // Remove password from returned data
            return $user;
        }

        return false;
    } catch (Exception $e) {
        error_log("Error verifying user: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all users who have chatted with a specific user
 * @param int $userId The user ID to get chat partners for
 * @return array Returns array of users who have chatted with the specified user
 */
function getChatPartners($userId)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN sender_id = ? THEN receiver_id 
                    ELSE sender_id 
                END as partner_id,
                u.username,
                u.role
            FROM messages m
            JOIN users u ON (
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END = u.id
            )
            WHERE m.sender_id = ? OR m.receiver_id = ?
            ORDER BY u.username
        ");
        $stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $partners = [];
        while ($row = $result->fetch_assoc()) {
            $partners[] = $row;
        }
        return $partners;
    } catch (Exception $e) {
        error_log("Error getting chat partners: " . $e->getMessage());
        return [];
    }
}
