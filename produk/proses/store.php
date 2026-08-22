<?php
require __DIR__ . '/../../config/koneksi.php';

// Ambil data dari halaman create.php
$nama = $_POST['nama'];
$harga = $_POST['harga'];
$stock = $_POST['stock'];
$kategori = $_POST['kategori'];
$file = $_FILES['image'];

session_start();
// Validasi image terlebih dahulu!

// Cek apakah image ada atau tidak
if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    // kembalikan user ke dalam halaman create
    $_SESSION['error'] = "File image tidak ada"; // Buat dulu session error untuk ditampilkan di depan
    header("Location: {$_SERVER['HTTP_REFERER']}"); // gunakan session untuk flash error
}

// Validasi apakah yang dikirim image atau bukan
$info = getimagesize($file['tmp_name']);
if ($info === false) {
    header("Location: {$_SERVER['HTTP_REFERER']}?message=not an image");
}

// Validasi apakah format yang dikirim sesuai (jpg, jpeg, webp, png)
$allowed_types = [
    IMAGETYPE_JPEG => 'jpeg',
    IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_PNG => 'png',
];

// $info[2] menyimpan extension image dalam bentuk angka
if(!array_key_exists($info[2], $allowed_types)) {
    // Jika format yang diberikan, itu tidak ada di dalam $allowed_types, maka kembalikan
    header("Location: {$_SERVER['HTTP_REFERER']}?message=type_not_allowed");
}

// Validasi size image agar tidak terlalu besar 

$filename = $file['name'];
$tmp_name = $file['tmp_name'];

// Ambil tipe image
// $tipe = $allowed_types[3];
$tipe = pathinfo($filename, PATHINFO_EXTENSION);
$image_name = "produk_" . time() . "." . $tipe;
$target_dir = __DIR__ . '/../../assets/produk/' . $image_name;

// var_dump($target_dir); die();
move_uploaded_file($tmp_name, $target_dir);

$sql = "INSERT INTO products(nama, harga, stock, kategori, gambar) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->execute([$nama, $harga, $stock, $kategori, $image_name]);

// $conn->query($sql);

header("Location: /index.php");
