<?php
require __DIR__ . "/../templates/header.php";
require __DIR__ . "/../config/koneksi.php";

// Ambil id dari $_GET
$id = $_GET['id'];

// Ambil dulu produknya
$sql = "SELECT * FROM products WHERE id=$id";
$produk = $conn->query($sql)->fetch_assoc();
?>

<!-- Content -->
<h1>Input Data Produk</h1>

<form action="./proses/update.php" method="post">
    <input type="hidden" name="id" value="<?= $produk['id']; ?>">
    <div>
        <label for="nama">Nama</label>
        <input type="text" name="nama" id="nama" value="<?= $produk['nama']; ?>">
    </div>

    <div>
        <label for="harga">Harga</label>
        <input type="number" name="harga" id="harga" value="<?= $produk['harga']; ?>">
    </div>

    <div>
        <label for="stock">Stock</label>
        <input type="number" name="stock" id="stock" value="<?= $produk['stock']; ?>">
    </div>

    <div>
        <label for="kategori">Kategori</label>
        <select name="kategori" id="kategori">
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($list_kategori as $k): ?>
                <option value="<?= $k; ?>" <?= $produk['kategori'] == $k ? 'selected' : '' ?>>
                    <?= $k; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit">Save</button>
</form>

<?php
require __DIR__ . "/../templates/footer.php";
?>