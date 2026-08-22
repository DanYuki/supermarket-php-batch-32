<?php

require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../templates/header.php';

cekLogin();

$sql = "SELECT * FROM products";
$result = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

?>

<!-- CSS -->
<link rel="stylesheet" href="/../style.css">

<!-- Konten -->
<h1 style="text-align: center;">Daftar Produk</h1>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Produk</th>
            <th>Harga</th>
            <th>Stock</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($result as $p): ?>
            <tr>
                <td><?= $p['id']; ?></td>
                <td style="text-align: left;"><?= htmlspecialchars($p['nama']); ?></td>
                <td>
                    <div style="display: flex; flex-direction:row; justify-content:center;">
                        <span>Rp </span>
                        <span><?= number_format($p['harga'], 0, ',', '.'); ?></span>
                    </div>
                </td>
                <td><?= $p['stock']; ?></td>
                <td class="d-flex col p-2 gap-2">
                    <a href="/produk/edit.php?id=<?= $p['id']; ?>" class="card-item btn btn-warning" style="width: 100%;">
                        <i class="bi bi-pencil-square" style="color: #fff4d3;"></i>
                    </a>
                    <a href="/produk/proses/delete.php?id=<?= $p['id']; ?>" class="card-item btn btn-danger" style="width: 100%;"><i class="bi bi-trash-fill" style="color: #ff808d;"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php

require __DIR__ . '/../templates/footer.php'

?>