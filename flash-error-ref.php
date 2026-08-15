<?php
session_start();

// read once, then remove so it only shows on this request
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>

<form action="store.php" method="post">
    <input name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
    <?php if (isset($errors['name'])): ?>
        <small style="color:red"><?= htmlspecialchars($errors['name']) ?></small>
    <?php endif; ?>

    <input name="price" value="<?= htmlspecialchars($old['price'] ?? '') ?>">
    <?php if (isset($errors['price'])): ?>
        <small style="color:red"><?= htmlspecialchars($errors['price']) ?></small>
    <?php endif; ?>

    <button>Simpan</button>
</form>