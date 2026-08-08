<?php
require __DIR__ . "/../config/koneksi.php";

var_dump($_POST);

$nama = $_POST['nama'];
$harga = $_POST['harga'];
$stock = $_POST['stock'];
$kategori = $_POST['kategori'];

$sql = "INSERT INTO products(nama, harga, stock, kategori) VALUES ('$nama', $harga, $stock, '$kategori')";
$conn->query($sql);

// Redirect ke home
header("Location: /index.php");
?>