<?php
// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php';
function insertDummyData($conn)
{
    try {
        // Enable error reporting for mysqli
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        // Dummy users: username, full_name, role, password (plain untuk contoh)
        $users = [
            ['budi_admin', 'Budi Santoso', 'admin', '123'],
            ['siti_cs', 'Siti Rahayu', 'cs', '123'],
            ['agus_user', 'Agus Setiawan', 'user', '123'],
            ['dewi_user', 'Dewi Lestari', 'user', '123']
        ];

        // Insert users
        foreach ($users as $u) {
            $username = $u[0];
            $full_name = $u[1];
            $role = $u[2];
            $plain_password = $u[3];
            $hashed = password_hash($plain_password, PASSWORD_BCRYPT);

            // Cek dulu apakah sudah ada
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashed, $full_name, $role);
                $stmt->execute();
                echo "User '$username' ditambahkan.<br>";
            } else {
                echo "User '$username' sudah ada, dilewati.<br>";
            }
        }

        // Ambil ID user
        $user_ids = [];
        $res = $conn->query("SELECT id, username FROM users");
        while ($row = $res->fetch_assoc()) {
            $user_ids[$row['username']] = $row['id'];
        }

        // Dummy messages
        $messages = [
            ['user1', 'cs1', 'Pesan dari user1 ke cs1'],
            ['cs1', 'user1', 'Balasan dari cs1 ke user1'],
            ['user1', 'user2', 'Halo user2!'],
            ['user2', 'user1', 'Hai user1, apa kabar?']
        ];

        // Insert messages
        foreach ($messages as $m) {
            $sender = $user_ids[$m[0]] ?? null;
            $receiver = $user_ids[$m[1]] ?? null;
            $message = $m[2];

            if ($sender && $receiver) {
                $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, encrypted_message) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $sender, $receiver, $message);
                $stmt->execute();
                echo "Pesan dari '{$m[0]}' ke '{$m[1]}' ditambahkan.<br>";
            }
        }

        echo "<br>✅ Dummy data selesai ditambahkan.";
    } catch (mysqli_sql_exception $e) {
        echo "<br>❌ Error: " . $e->getMessage();
    } catch (Exception $e) {
        echo "<br>❌ Unexpected error: " . $e->getMessage();
    }
}

insertDummyData($conn);
