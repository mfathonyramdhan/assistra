<?php
$host = 'localhost';           // Ganti jika bukan localhost
$user = 'root';                // Ganti dengan username MySQL kamu
$pass = 'root'; // Ganti dengan password MySQL kamu
$dbname = 'assistra';          // Nama database yang akan digunakan

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Optional: Set karakter untuk dukungan UTF-8 penuh
$conn->set_charset("utf8mb4");
