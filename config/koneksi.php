<?php 
// Digunakan untuk konfigurasi database yang digunakan oleh aplikasi
$hostname = 'localhost';
$username = 'root';
$password = 'root';
$database = 'belajar_sql_elbas';
$port = 3306;


$conn = new mysqli($hostname, $username, $password, $database, $port);

// Tangani error jika gagal koneksi ke database
if ($conn->connect_error) {
    die("Connection to database failed: " . $conn->connect_error);
} 
?>