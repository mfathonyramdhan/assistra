<?php
function setupDatabase($host, $user, $pass)
{
    $dbname = 'assistra';

    // Koneksi tanpa database dulu
    $conn = new mysqli($host, $user, $pass);

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Buat database jika belum ada
    $sqlCreateDB = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sqlCreateDB) === TRUE) {
        echo "Database '$dbname' berhasil dipastikan ada.<br>";
    } else {
        die("Gagal membuat database: " . $conn->error);
    }

    // Pilih database
    $conn->select_db($dbname);

    // Buat tabel users
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        role ENUM('admin', 'user', 'cs') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Buat tabel messages
    $sqlMessages = "CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        encrypted_message TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id),
        INDEX(sender_id),
        INDEX(receiver_id)
    )";

    if ($conn->query($sqlUsers) === TRUE) {
        echo "Tabel 'users' berhasil dibuat.<br>";
    } else {
        echo "Gagal membuat tabel 'users': " . $conn->error . "<br>";
    }

    if ($conn->query($sqlMessages) === TRUE) {
        echo "Tabel 'messages' berhasil dibuat.<br>";
    } else {
        echo "Gagal membuat tabel 'messages': " . $conn->error . "<br>";
    }

    $conn->close();
}

setupDatabase('localhost', 'root', 'root');
