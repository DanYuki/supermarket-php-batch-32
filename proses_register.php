<?php 
// Step 1: Ambil data dari post

// Step 2 (opt): Cek apakah username sudah terdaftar atau belum

// Step 3: Hash password
$hashed_password = password_hash("password", PASSWORD_BCRYPT);

// Step 4: Insert data ke database, ke table user dengan password yang sudah di hash

// Step 5: Balikin ke login page, kasih session "Berhasil daftar akun"

// Step 6: Login
?>