<?php
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../templates/header.php';
?>

<!-- CSS -->
<link rel="stylesheet" href="/style.css">

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['error']; ?>
    </div>
<?php
// Setelah di-display, unset/hilangkan pesan error
unset($_SESSION['error']);
endif;
?>
<form action="./proses/store.php" method="post" enctype="multipart/form-data" class="form-create container">
    <div class="d-flex col gap-3 my-2">
        <label for="nama" class="col-form-label col-3">Nama</label>
        <input type="text" name="nama" id="nama" class="form-control" required>
    </div>
    <div class="d-flex col gap-3 my-2">
        <label for="harga" class="col-form-label col-3">Harga</label>
        <input type="number" name="harga" id="harga" class="form-control" required>
    </div>
    <div class="d-flex col gap-3 my-2">
        <label for="stock" class="col-form-label col-3">Stock</label>
        <input type="number" name="stock" id="stock" class="form-control">
    </div>
    <div class="d-flex col gap-3 my-2">
        <label for="kategori" class="col-form-label col-3">Kategori</label>
        <select name="kategori" id="kategori" class="form-control">
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($list_kategori as $k): ?>
                <option value="<?= $k; ?>">
                    <?= $k; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="">
        <label for="image" class="form-label">Upload Image</label>
        <input type="file" name="image" id="image" class="form-control" accept="image/*">
    </div>
    <div class="d-flex justify-content-end">
        <button type="submit" class="my-2 btn btn-primary" style="width: 14%;">Submit</button>
    </div>
</form>

<?php
require __DIR__ . '/../templates/footer.php';
?>