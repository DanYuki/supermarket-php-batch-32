<?php

$img_placeholder = "https://www.svgrepo.com/show/508699/landscape-placeholder.svg";
$list_kategori = ['Buah', 'Sayur', 'Minuman', 'Sembako', 'Sabun', 'Snack', 'Bumbu'];

// Mulai session
session_start();

echo $_SESSION['nama_user'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $judul ?? "Toko Sederhana" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/toko-sederhana/index.php">Toko Sederhana</a>
            <div>
                <a class="btn btn-sm btn-light" href="/index.php">Home</a>
                <!-- <a class="btn btn-sm btn-light" href="/produk/create.php">Create</a> -->
                <a class="btn btn-sm btn-outline-light" href="/produk/index.php">Produk</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">