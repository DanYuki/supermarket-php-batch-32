<?php
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/templates/header.php';

// Ambil data produk
$sql = "SELECT * FROM products";
$result = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<!-- Content disini -->
<a href="/produk/create.php" class="btn btn-primary">+ Tambah Produk</a>

<h1>Daftar Produk</h1>

<ol>
    <?php foreach ($result as $produk): ?>
        <li>
            <?= $produk['nama']; ?> - Rp <?= $produk['harga']; ?>
        </li>
    <?php endforeach; ?>
</ol>

<?php
require __DIR__ . '/templates/footer.php';
?>