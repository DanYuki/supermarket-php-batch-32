<?php
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/templates/header.php';
?>

<div>
    <h1>Login Page</h1>

    <form action="./proses_login.php" method="post">
        <div>
            <label for="form-label">Username</label>
            <input type="text" name="username" class="form-control">
        </div>

        <div>
            <label for="form-label">Password</label>
            <input type="password" name="password" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Login</button>

    </form>
</div>

<?php
require __DIR__ . '/templates/footer.php';
?>