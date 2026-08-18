<?php
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/templates/header.php';

// Pagination
$itemPerPage = 10;
$page = (int) ($_GET['page'] ?? 1);
$offset = ($page - 1) * 10;

// Harusnya, hitung data tetap mempertimbangkan hasil search
$total = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc();
$total = $total['total'];

$pageCount = ceil($total / $itemPerPage);

$search = trim($_GET['search'] ?? '');

$sql    = "SELECT * FROM products";
$param = '';

if ($search !== '') {
    $sql .= " WHERE nama LIKE ?";
    $param = "%$search%";
}

// Tambahkan query untuk limit
$sql .= " LIMIT $itemPerPage OFFSET $offset";

$stmt = $conn->prepare($sql);


if ($param) {
    $stmt->bind_param('s', $param);
}

$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- Content disini -->
<p>Page saat ini: <?= $page; ?></p>
<a href="/produk/create.php" class="btn btn-primary">+ Tambah Produk</a>

<h1>Daftar Produk</h1>
<form class="d-flex gap-5">
    <input type="text" class="form-control" name="search" value="<?= $search; ?>">
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<ul class="product-list">
    <?php foreach ($result as $produk): ?>
        <li class="card p-2">
            <img src="<?= $produk['gambar'] ?? $img_placeholder; ?>" alt="<?= $produk['gambar']; ?>" class="image-product"> <br>
            <div class="nama-harga">
                <div class="nama"> <?= htmlspecialchars($produk['nama']); ?> </div>
                <div class="harga"> Rp <?= $produk['harga']; ?> </div>
            </div>
            <div class="card-action">
                <button class="btn btn-primary">Buy</button>
                <a href="/produk/edit.php?id=<?= $produk['id']; ?>" class="btn btn-warning">Edit</a>
                <form action="./produk/delete.php" method="post">
                    <input type="hidden" name="id" value="<?= $produk['id']; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('You sure?')">Delete</button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<nav aria-label="Page navigation example">
    <ul class="pagination">
        <?php for ($i = 1; $i <= $pageCount; $i++): ?>
            <li class="page-item"><a class="page-link <?= $page == $i ? 'active' : ''; ?>" href="?page=<?= $i; ?>"><?= $i; ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>




<?php
require __DIR__ . '/templates/footer.php';
?>