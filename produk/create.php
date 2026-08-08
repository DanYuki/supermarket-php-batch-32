<?php
require __DIR__ . "/../templates/header.php";

?>

<!-- Content -->
<h1>Input Data Produk</h1>

<form action="./store.php" method="post">
    <div>
        <label for="nama">Nama</label>
        <input type="text" name="nama" id="nama">
    </div>

    <div>
        <label for="harga">Harga</label>
        <input type="number" name="harga" id="harga">
    </div>

    <div>
        <label for="stock">Stock</label>
        <input type="number" name="stock" id="stock">
    </div>

    <div>
        <label for="kategori">Kategori</label>
        <input type="text" name="kategori" id="kategori">
    </div>

    <button type="submit">Save</button>
</form>

<?php
require __DIR__ . "/../templates/footer.php";
?>