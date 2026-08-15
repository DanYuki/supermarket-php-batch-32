<?php
$img_placeholder = "https://img.magnific.com/free-vector/illustration-gallery-icon_53876-27002.jpg?semt=ais_test_b&w=740&q=80";
$list_kategori = [
    'Buah',
    'Sayur',
    'Minuman',
    'Sembako',
    'Sabun',
    'Snack',
    'Bumbu',
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $judul ?? "Toko Sederhana" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .product-list {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            text-wrap: wordwrap;
            gap: 1em;
        }

        .card {
            list-style-type: none;
            text-align: center;
            border-radius: 10px;
        }

        .img-container {
            max-width: 200px;
        }

        .image-product {
            border-radius: 10px;
            height: 100%;
            width: 100%;
        }


        .nama-harga {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/toko-sederhana/index.php">Toko Sederhana</a>
            <div>
                <a class="btn btn-sm btn-outline-light" href="/toko-sederhana/produk/index.php">Produk</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">