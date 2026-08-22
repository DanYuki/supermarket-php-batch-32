<?php 
// var_dump($_POST); die();

require __DIR__ . "/config/koneksi.php";

// Ambil data
$username = $_POST['username'];
$password = $_POST['password'];


// Ambil data user berdasarkan username
$sql = "select * from users where username = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$username]);

$user = $stmt->get_result()->fetch_assoc();

// Guard Clause
if(!$user) {
    // Jika user tidak ditemukan:
    // Tambahkan session error
    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit();
}

if(!password_verify($password, $user['password'])) {
    // Jika password tidak sama:
    // Tambahkan session error
    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit();
}

// Jika berhasil maka:

session_start();
$_SESSION['is_logged_in'] = true;
$_SESSION['username'] = $username;
header("Location: /produk/index.php");
?>