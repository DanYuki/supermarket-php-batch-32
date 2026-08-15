<?php
require __DIR__ . "/../../config/koneksi.php";

var_dump($_POST);

$nama = $_POST['nama'];
$harga = $_POST['harga'];
$stock = $_POST['stock'];
$kategori = $_POST['kategori'];

// Pengggunaan prepared statement untuk mencegah SQL Injection
$sql = "INSERT INTO products(nama, harga, stock, kategori) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->execute([$nama, $harga, $stock, $kategori]);

// Redirect ke home
header("Location: /index.php");
?>