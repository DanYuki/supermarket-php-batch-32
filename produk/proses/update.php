<?php
require __DIR__ . "/../../config/koneksi.php";

$id = $_POST['id'];
$nama = $_POST['nama'];
$harga = $_POST['harga'];
$stock = $_POST['stock'];
$kategori = $_POST['kategori'];

// Jalankan query update
$sql = "UPDATE products SET nama='$nama', harga=$harga, stock=$stock, kategori='$kategori' WHERE id=$id";
$conn->query($sql);

// Redirect ke home
header("Location: /index.php");
