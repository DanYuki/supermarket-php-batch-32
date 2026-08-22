# Modul Pengajaran: Session, Login & Proteksi Halaman
**Haltev IT Learning Center — Web Development**
**Pertemuan 15 | Tools: VS Code, XAMPP**

---

## Tujuan Pembelajaran

Siswa memahami cara kerja **session** dan **cookie** sebagai solusi atas sifat HTTP yang tidak mengingat apa pun, lalu mampu membangun sistem login yang aman: password di-hash dengan benar, halaman admin terkunci, dan sesi bisa diakhiri. Di akhir pertemuan, `toko-sederhana` sudah menjadi aplikasi yang lengkap dan siap diujikan di Exam 2.

> 📌 **Ini pertemuan terakhir sebelum Exam 2.** Setelah hari ini, aplikasimu punya semua yang diujikan: CRUD, keamanan, upload, dan autentikasi.

---

## Daftar Isi

- [Recap: Aplikasimu Terbuka untuk Siapa Saja](#recap-aplikasimu-terbuka-untuk-siapa-saja)
- [Bagian 1 — HTTP Tidak Ingat Apa-apa](#bagian-1--http-tidak-ingat-apa-apa)
- [Bagian 2 — Session & Cookie](#bagian-2--session--cookie)
- [Bagian 3 — Kenapa Password Harus Di-hash](#bagian-3--kenapa-password-harus-di-hash)
- [Bagian 4 — Tabel User & Admin Pertama](#bagian-4--tabel-user--admin-pertama)
- [Bagian 5 — Halaman Login](#bagian-5--halaman-login)
- [Bagian 6 — Proteksi Halaman](#bagian-6--proteksi-halaman)
- [Bagian 7 — Logout](#bagian-7--logout)
- [Bagian 8 — Flash Message dengan Session](#bagian-8--flash-message-dengan-session)
- [Bagian 9 — Navbar yang Menyesuaikan Status Login](#bagian-9--navbar-yang-menyesuaikan-status-login)
- [Latihan](#latihan)
- [Tugas Pertemuan](#tugas-pertemuan)
- [Ringkasan](#ringkasan)

---

## Recap: Aplikasimu Terbuka untuk Siapa Saja

Coba pikirkan ini sejenak. Aplikasi `toko-sederhana` sekarang punya:

| Fitur | Siapa yang bisa memakainya sekarang |
|-------|-------------------------------------|
| Lihat daftar produk | Siapa pun |
| Tambah produk | **Siapa pun** |
| Edit produk | **Siapa pun** |
| Hapus produk | **Siapa pun** |
| Upload gambar ke server | **Siapa pun** |

Siapa pun yang tahu alamat `produk/hapus.php?id=1` bisa menghapus produkmu. Tidak ada satu pun pemeriksaan tentang **siapa** yang sedang melakukannya.

Di P13 kita mengamankan aplikasi dari input berbahaya. Hari ini kita mengamankannya dari **orang yang tidak berhak**.

| Istilah | Pertanyaan yang dijawab | Contoh |
|---------|------------------------|--------|
| **Autentikasi** | "Siapa kamu?" | Login dengan username & password |
| **Otorisasi** | "Kamu boleh melakukan ini?" | Hanya admin yang bisa menghapus |

Hari ini fokusnya autentikasi. Otorisasi berbasis peran dibahas nanti di Laravel (P21).

---

## Bagian 1 — HTTP Tidak Ingat Apa-apa

Sebelum bicara login, pahami dulu masalah dasarnya.

**HTTP bersifat *stateless*** — setiap permintaan berdiri sendiri, dan server tidak punya ingatan tentang permintaan sebelumnya.

```
  Permintaan 1: "Beri saya halaman login"      → server kirim halaman
  Permintaan 2: "Ini username & password saya" → server bilang "benar!"
  Permintaan 3: "Beri saya halaman produk"     → server: "kamu siapa ya?"
```

Server benar-benar **lupa** antara permintaan 2 dan 3. Ia tidak punya cara mengenali bahwa permintaan ketiga datang dari orang yang sama.

Analoginya: seperti berbicara dengan seseorang yang ingatannya di-reset setiap kalimat. Kamu harus memperkenalkan diri lagi setiap kali bicara.

Solusinya adalah memberi pengunjung semacam **kartu tanda pengenal** yang mereka bawa di setiap permintaan berikutnya. Itulah cookie dan session.

---

## Bagian 2 — Session & Cookie

### Perbedaan Keduanya

| | Cookie | Session |
|---|--------|---------|
| Data disimpan di | **Browser** pengunjung | **Server** |
| Bisa dilihat pengunjung | ✅ Ya, lewat DevTools | ❌ Tidak |
| Bisa diubah pengunjung | ✅ Ya | ❌ Tidak |
| Ukuran maksimal | ~4 KB | Praktis tak terbatas |
| Bertahan setelah browser ditutup | ✅ Bisa diatur | ❌ Hilang (default) |
| Cocok untuk | Preferensi tema, bahasa | **Status login**, data sensitif |

> ⚠️ **Aturan yang tidak boleh dilanggar: jangan simpan apa pun yang sensitif di cookie.** Pengunjung bisa membuka DevTools dan mengubah isinya sesuka hati. Kalau kamu menyimpan `is_admin=0` di cookie, siapa pun bisa menggantinya jadi `1`.

### Cara Session Bekerja

Session sebenarnya **memakai cookie**, tapi hanya untuk menyimpan satu hal: nomor identitas sesi.

```
  [1] Pengunjung login                → PHP membuat session baru
  [2] PHP kirim cookie PHPSESSID      → berisi kode acak, misal "a3f9c1e8b2..."
  [3] Data asli disimpan di SERVER    → $_SESSION['user_id'] = 1
  [4] Permintaan berikutnya           → browser otomatis mengirim PHPSESSID
  [5] PHP mencocokkan kodenya         → "oh, ini user_id 1"
```

Yang dititipkan ke browser hanya **nomor kartunya**. Datanya sendiri tetap aman di server.

### Memakai Session di PHP

```php
<?php
session_start();          // WAJIB, sebelum output apa pun

$_SESSION['user_id']  = 1;
$_SESSION['username'] = 'admin';

echo $_SESSION['username'];   // admin
```

`$_SESSION` adalah array associative biasa — sama seperti `$_GET` dan `$_POST` yang sudah kamu kenal. Bedanya, isinya bertahan antar halaman.

| Perintah | Gunanya |
|----------|---------|
| `session_start()` | Memulai atau melanjutkan sesi |
| `$_SESSION['kunci'] = nilai` | Menyimpan data |
| `isset($_SESSION['kunci'])` | Memeriksa apakah data ada |
| `unset($_SESSION['kunci'])` | Menghapus satu data |
| `session_destroy()` | Mengakhiri seluruh sesi |

### ⚠️ `session_start()` Harus Sebelum Output

```php
<?php
session_start();       // ✅ baris pertama, sebelum HTML apa pun
require_once '../config/koneksi.php';
?>
<!DOCTYPE html>
```

```php
<!DOCTYPE html>
<?php session_start(); ?>   <!-- ❌ ERROR: headers already sent -->
```

Ini gotcha yang sudah disinggung dua kali — di P9 (soal spasi sebelum `<?php`) dan di P11 (soal `header()` redirect). Sekarang alasannya jadi jelas: `session_start()` mengirim cookie lewat **header HTTP**, dan header harus dikirim sebelum isi halaman.

> 💡 Kalau kamu dapat error `Cannot modify header information - headers already sent by (output started at ...)`, angka baris di akhir pesan itu menunjukkan **di mana output pertama terjadi**. Biasanya berupa satu spasi atau baris kosong sebelum `<?php`.

### Cookie (Sekilas)

```php
// Simpan cookie selama 30 hari
setcookie('tema', 'gelap', time() + (30 * 24 * 60 * 60), '/');

// Membacanya (baru tersedia di permintaan BERIKUTNYA)
$tema = $_COOKIE['tema'] ?? 'terang';
```

> 💡 Cookie yang baru di-set belum bisa dibaca di halaman yang sama — ia baru terkirim balik oleh browser pada permintaan berikutnya. Ini membingungkan kalau kamu tidak tahu.

Untuk pertemuan ini kita **hanya memakai session**. Cookie diperkenalkan supaya kamu paham bedanya, karena keduanya sering tertukar.

---

## Bagian 3 — Kenapa Password Harus Di-hash

### Jangan Pernah Menyimpan Password Apa Adanya

```sql
-- ❌ JANGAN PERNAH
INSERT INTO users (username, password) VALUES ('admin', 'rahasia123');
```

Kalau database bocor — dan database memang bisa bocor — seluruh password penggunamu terbaca. Lebih parah lagi: banyak orang memakai password yang sama di banyak layanan, jadi kebocoran di aplikasimu bisa merembet ke email dan rekening mereka.

### Hash Bukan Enkripsi

| | Enkripsi | Hash |
|---|----------|------|
| Bisa dikembalikan | ✅ Ya, dengan kunci | ❌ **Tidak bisa, selamanya** |
| Contoh | AES | bcrypt, Argon2 |
| Untuk | Data yang perlu dibaca lagi | **Password** |

Password **tidak perlu** bisa dibaca kembali. Yang perlu kita lakukan cuma: memeriksa apakah password yang diketik sama dengan yang dulu didaftarkan. Itu bisa dilakukan tanpa pernah tahu password aslinya.

```
  Saat daftar : "rahasia123" → hash → $2y$10$N9qo8uLOickgx2Z... → simpan ini
  Saat login  : "rahasia123" → hash → bandingkan dengan yang tersimpan → cocok!
```

### Jangan Pakai MD5 atau SHA1

Banyak tutorial lama mengajarkan `md5($password)`. **Jangan diikuti.**

| Masalah | Penjelasan |
|---------|------------|
| Terlalu cepat | Komputer modern bisa mencoba miliaran tebakan per detik |
| Tidak ada *salt* | Password sama menghasilkan hash sama — mudah dicocokkan dengan tabel siap pakai |
| Sudah pecah | `md5("password")` bisa dicari di Google dan langsung ketemu |

### Yang Benar: `password_hash()`

```php
// Saat mendaftar / membuat user
$hash = password_hash($passwordAsli, PASSWORD_DEFAULT);
// hasilnya: $2y$10$N9qo8uLOickgx2ZMRZoMye1J8/BHIQ4Bt.dEMxKJZzOBb1Y2...

// Saat login
if (password_verify($passwordDiketik, $hashDariDatabase)) {
    // cocok
}
```

| Kelebihan | Penjelasan |
|-----------|------------|
| *Salt* otomatis | Password sama menghasilkan hash **berbeda** setiap kali |
| Sengaja lambat | Menebak satu per satu jadi tidak praktis |
| Ikut berkembang | `PASSWORD_DEFAULT` otomatis memakai algoritma terbaik yang tersedia |

> 💡 Karena salt disertakan otomatis di dalam hasil hash, kamu **tidak perlu** membuat kolom terpisah untuk salt. Semuanya sudah termasuk dalam satu string itu.

> ⚠️ **Kolom password harus `VARCHAR(255)`, bukan `VARCHAR(60)`.** Hash bcrypt saat ini 60 karakter, tapi algoritma masa depan bisa lebih panjang. Kolom yang terlalu pendek akan memotong hash diam-diam, dan login tidak akan pernah berhasil — bug yang sangat sulit dilacak.

---

## Bagian 4 — Tabel User & Admin Pertama

### Membuat Tabel

Jalankan di tab SQL phpMyAdmin:

```sql
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    nama       VARCHAR(100) NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

`UNIQUE` pada `username` membuat MySQL sendiri yang menolak username kembar — pertahanan yang tidak bisa dilewati walau kode PHP-mu lupa memeriksanya.

### Membuat Admin Pertama

Kita tidak bisa mengetik hash secara manual. Buat file sementara `buat-admin.php` di akar proyek:

```php
<?php
require_once 'config/koneksi.php';

$username = 'admin';
$password = 'admin123';        // ganti dengan password pilihanmu
$nama     = 'Administrator';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, nama) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $nama);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin berhasil dibuat.<br>";
    echo "Username: $username<br>";
    echo "Password: $password<br><br>";
    echo "<strong>HAPUS FILE INI SEKARANG JUGA.</strong>";
} else {
    echo "Gagal: " . mysqli_stmt_error($stmt);
}
mysqli_stmt_close($stmt);
```

Buka `http://localhost/toko-sederhana/buat-admin.php` **satu kali**, lalu **hapus filenya**.

> ⚠️ **File ini wajib dihapus setelah dipakai.** Selama ia ada, siapa pun yang membukanya bisa membuat akun admin baru. Ini kesalahan nyata yang sering ditemukan di aplikasi sungguhan — file bantuan yang lupa dihapus setelah pemasangan.

Periksa hasilnya di phpMyAdmin. Kolom `password` harus berisi teks panjang diawali `$2y$`, bukan `admin123`.

---

## Bagian 5 — Halaman Login

Buat folder `auth/` dengan file `login.php`.

### `auth/login.php`

```php
<?php
// ===== [1] SETUP =====
session_start();
require_once '../config/koneksi.php';

// Kalau sudah login, tidak perlu ke halaman login lagi
if (isset($_SESSION['user_id'])) {
    header("Location: ../produk/index.php");
    exit;
}

$error = '';

// ===== [2] PROSES =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, nama FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            // Cegah session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];

            header("Location: ../produk/index.php");
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}

// ===== [3] TAMPILAN =====
$judul = "Login";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $judul ?> — Toko Sederhana</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container" style="max-width: 400px; margin-top: 8vh;">

  <div class="text-center mb-4">
    <h3>Toko Sederhana</h3>
    <p class="text-muted">Masuk untuk mengelola produk</p>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-4">

      <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" autofocus
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary w-100">Masuk</button>
      </form>

    </div>
  </div>

</div>

</body>
</html>
```

### Tiga Keputusan Penting di File Ini

#### 1. Pesan Error Sengaja Dibuat Kabur

```php
$error = 'Username atau password salah.';
```

Bukan "Username tidak ditemukan" atau "Password salah". Kenapa?

Kalau pesannya membedakan keduanya, penyerang bisa memakai form login untuk **menebak username mana yang ada** di sistemmu. Setelah tahu username yang valid, ia tinggal fokus menebak passwordnya.

Pesan yang sama untuk kedua kasus menutup celah itu. Ini disebut *username enumeration prevention*.

#### 2. `session_regenerate_id(true)`

```php
session_regenerate_id(true);
```

Ini mencegah serangan **session fixation**: penyerang memberi korban sebuah ID sesi, menunggu korban login dengan ID itu, lalu memakai ID yang sama untuk masuk sebagai korban.

Dengan mengganti ID sesi tepat setelah login berhasil, ID lama jadi tidak berguna. Satu baris, dan ia menutup satu kelas serangan.

#### 3. Password Tidak Dikembalikan ke Form

Perhatikan input username punya `value="..."` tapi input password **tidak**. Ini disengaja — password tidak boleh muncul kembali di HTML, sekalipun bertipe `password`.

### Kenapa Login Tidak Memakai `templates/header.php`?

Halaman login sengaja berdiri sendiri, karena `header.php` berisi navbar dengan menu admin. Menampilkan menu itu ke orang yang belum login membingungkan, dan sedikit membocorkan struktur aplikasi.

---

## Bagian 6 — Proteksi Halaman

Sekarang bagian intinya: mengunci semua halaman admin.

### `config/auth.php`

```php
<?php
// Mulai sesi kalau belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Pastikan pengguna sudah login.
 * Kalau belum, lempar ke halaman login.
 */
function wajibLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /toko-sederhana/auth/login.php");
        exit;
    }
}

/** Apakah pengguna sedang login? */
function sudahLogin()
{
    return isset($_SESSION['user_id']);
}

/** Nama pengguna yang sedang login */
function namaUser()
{
    return $_SESSION['nama'] ?? 'Pengguna';
}
```

> 💡 `session_status() === PHP_SESSION_NONE` memeriksa apakah sesi sudah dimulai. Tanpa pemeriksaan ini, memanggil `session_start()` dua kali memunculkan peringatan `Notice: session already started`.

### Memakainya

Tambahkan **dua baris** di paling atas setiap halaman admin:

```php
<?php
require_once '../config/auth.php';
wajibLogin();

require_once '../config/koneksi.php';
// ...sisa kode seperti biasa
```

| File | Perlu proteksi? | Alasan |
|------|:---------------:|--------|
| `produk/index.php` | ✅ Ya | Daftar admin |
| `produk/tambah.php` | ✅ Ya | Mengubah data |
| `produk/edit.php` | ✅ Ya | Mengubah data |
| `produk/hapus.php` | ✅ Ya | Menghapus data |
| `index.php` (etalase) | ❌ Tidak | Untuk pengunjung umum |
| `auth/login.php` | ❌ Tidak | Justru pintu masuknya |

> ⚠️ **`hapus.php` sering terlupa** karena ia tidak punya tampilan. Justru itu file yang paling berbahaya kalau terbuka. Periksa satu per satu, jangan mengandalkan ingatan.

### Uji Proteksinya

Setelah semua terpasang, buka halaman admin dalam **jendela penyamaran** (incognito) — di sana kamu tidak punya sesi. Semuanya harus melempar ke halaman login.

---

## Bagian 7 — Logout

### `auth/logout.php`

```php
<?php
session_start();

// Kosongkan semua data sesi
$_SESSION = [];

// Hapus cookie session dari browser
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
              $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}

// Musnahkan sesi di server
session_destroy();

header("Location: login.php?logout=1");
exit;
```

Tiga langkah, dan ketiganya perlu:

| Langkah | Membersihkan |
|---------|--------------|
| `$_SESSION = []` | Data di memori PHP saat ini |
| `setcookie(...)` masa lalu | Cookie PHPSESSID di browser |
| `session_destroy()` | File sesi di server |

Banyak tutorial hanya memakai `session_destroy()`. Itu sudah cukup untuk kebanyakan kasus, tapi menyisakan cookie yang tidak terpakai di browser pengguna.

### Pesan Setelah Logout

Di `login.php`, tambahkan:

```php
<?php if (isset($_GET['logout'])): ?>
  <div class="alert alert-success">Kamu sudah keluar. Sampai jumpa!</div>
<?php endif; ?>
```

---

## Bagian 8 — Flash Message dengan Session

Ingat cara memberi pesan sukses di P11 dan P12?

```php
header("Location: index.php?sukses=1");
```

Cara itu berfungsi, tapi punya kelemahan yang sudah kita sebut dua kali: **siapa pun bisa memalsukannya** hanya dengan mengetik `?sukses=1` di URL. Sekarang kita punya session, jadi bisa diperbaiki.

### Menambahkan Fungsi Flash di `config/auth.php`

```php
/** Simpan pesan untuk ditampilkan di halaman berikutnya */
function setFlash($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

/** Ambil pesan flash, lalu hapus supaya tidak muncul dua kali */
function ambilFlash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
```

Kuncinya ada di `unset()` — pesan **dihapus setelah dibaca**, jadi ia hanya muncul sekali. Itulah kenapa namanya *flash*.

### Memakainya

Di `tambah.php`, ganti redirect-nya:

```php
setFlash('success', 'Produk berhasil ditambahkan.');
header("Location: index.php");
exit;
```

Di `index.php`, tampilkan:

```php
<?php $flash = ambilFlash(); ?>
<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?>">
    <?= htmlspecialchars($flash['pesan']) ?>
  </div>
<?php endif; ?>
```

Sekarang URL-mu jadi bersih (`index.php` saja), pesannya tidak bisa dipalsukan, dan menekan F5 tidak memunculkan pesan yang sama berulang kali.

| | `?sukses=1` | Session flash |
|---|-------------|---------------|
| URL | Kotor | Bersih |
| Bisa dipalsukan | ✅ Ya | ❌ Tidak |
| Muncul lagi saat F5 | ✅ Ya | ❌ Tidak |
| Bisa memuat pesan panjang | ❌ Terbatas | ✅ Ya |

---

## Bagian 9 — Navbar yang Menyesuaikan Status Login

Perbarui `templates/header.php`:

```php
<?php
require_once __DIR__ . '/../config/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $judul ?? "Toko Sederhana" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/toko-sederhana/index.php">Toko Sederhana</a>

    <div class="d-flex align-items-center gap-2">
      <?php if (sudahLogin()): ?>
        <span class="text-white-50 small me-2">
          Halo, <?= htmlspecialchars(namaUser()) ?>
        </span>
        <a class="btn btn-sm btn-outline-light" href="/toko-sederhana/produk/index.php">Produk</a>
        <a class="btn btn-sm btn-outline-warning" href="/toko-sederhana/auth/logout.php">Keluar</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-light" href="/toko-sederhana/auth/login.php">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">
```

> 💡 `__DIR__` adalah folder tempat file itu sendiri berada. Memakai `__DIR__ . '/../config/auth.php'` membuat `require` tetap benar tidak peduli dari folder mana `header.php` dipanggil. Ini lebih andal daripada `'../config/auth.php'` yang bergantung pada posisi file pemanggil.

> ⚠️ **Menyembunyikan tombol bukan proteksi.** Navbar yang menyembunyikan menu Produk tidak menghentikan siapa pun yang mengetik alamatnya langsung. Yang benar-benar melindungi adalah `wajibLogin()` di Bagian 6. Menyembunyikan menu itu untuk kerapian tampilan, bukan keamanan.

---

## Latihan

### Latihan 1 — Session Dasar

Sebelum menyentuh login, pahami dulu session dengan percobaan sederhana. Buat dua file di akar proyek:

```php
<?php
// coba-session-1.php
session_start();
$_SESSION['pesan']   = 'Halo dari halaman pertama!';
$_SESSION['waktu']   = date('H:i:s');
echo 'Data disimpan. <a href="coba-session-2.php">Ke halaman kedua</a>';
```

```php
<?php
// coba-session-2.php
session_start();
echo "Pesan: "  . ($_SESSION['pesan'] ?? 'tidak ada') . "<br>";
echo "Waktu: "  . ($_SESSION['waktu'] ?? 'tidak ada') . "<br>";
echo "Session ID: " . session_id();
```

**Kerjakan dan jawab:**

| Percobaan | Pertanyaan |
|-----------|------------|
| Buka halaman 2 langsung tanpa lewat halaman 1 | Apa yang terjadi? |
| Buka DevTools → Application → Cookies | Cari `PHPSESSID`. Apa isinya? |
| Bandingkan isi cookie dengan `session_id()` di layar | Sama atau berbeda? |
| Hapus cookie `PHPSESSID`, muat ulang halaman 2 | Kenapa datanya hilang? |
| Buka halaman 2 di jendela incognito | Kenapa datanya tidak ada di sana? |

**Kriteria berhasil:** kamu bisa menjelaskan dengan kalimatmu sendiri bahwa cookie hanya menyimpan **nomor kartu**, sedangkan datanya ada di server.

> 🎯 Hapus kedua file percobaan ini setelah selesai.

---

### Latihan 2 — Hash Password

Buat file `coba-hash.php`:

```php
<?php
$password = 'rahasia123';

echo "<h4>Hash yang sama, dijalankan 3 kali:</h4>";
for ($i = 1; $i <= 3; $i++) {
    echo password_hash($password, PASSWORD_DEFAULT) . "<br>";
}

echo "<h4>Verifikasi:</h4>";
$hash = password_hash($password, PASSWORD_DEFAULT);
var_dump(password_verify('rahasia123', $hash));   // true
var_dump(password_verify('rahasia124', $hash));   // false
```

**Pertanyaan:** ketiga hash-nya **berbeda**, padahal passwordnya sama. Bagaimana `password_verify()` masih bisa mencocokkan? Cari tahu apa itu *salt* dan tuliskan jawabanmu.

---

### Latihan 3 — Login & Proteksi

Bangun sistem login lengkap: tabel `users`, admin pertama, `auth/login.php`, `config/auth.php`, `auth/logout.php`, dan proteksi di semua halaman admin.

**Kriteria berhasil:**

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Buka `produk/index.php` tanpa login | Dilempar ke halaman login |
| Login dengan password salah | Pesan "Username atau password salah" |
| Login dengan username yang tidak ada | Pesan **yang sama persis** |
| Login benar | Masuk ke daftar produk, navbar menampilkan namamu |
| Buka `auth/login.php` saat sudah login | Dilempar ke daftar produk |
| Klik Keluar, lalu tekan tombol Back browser | Tetap tidak bisa masuk halaman admin |
| Buka `produk/hapus.php?id=1` tanpa login | Dilempar ke login, **produk tidak terhapus** |
| Buka etalase `index.php` tanpa login | Tetap bisa dilihat |

> 🎯 **Uji lanjutan:** buka DevTools → Application → Cookies, hapus `PHPSESSID` saat sedang login, lalu muat ulang halaman admin. Apa yang terjadi, dan kenapa?

---

### Latihan 4 — Flash Message

Ganti seluruh pesan `?sukses=1`, `?diubah=1`, `?dihapus=1` dengan session flash.

**Kriteria berhasil:** setelah menambah produk, URL-nya bersih (`index.php` tanpa parameter), pesan muncul sekali, dan menekan F5 tidak memunculkannya lagi. Mengetik `index.php?sukses=1` secara manual tidak menampilkan pesan apa pun.

---

## Tugas Pertemuan

1. Tabel `users` dengan password ter-hash (`VARCHAR(255)`), dan file `buat-admin.php` sudah **dihapus**
2. Halaman login dengan pesan error yang tidak membocorkan username, dan `session_regenerate_id(true)` setelah login berhasil
3. `config/auth.php` dengan `wajibLogin()`, `sudahLogin()`, `namaUser()`, `setFlash()`, `ambilFlash()`
4. **Semua** halaman admin terproteksi — termasuk `hapus.php`
5. Logout yang benar-benar membersihkan sesi
6. Navbar menyesuaikan status login
7. Seluruh pesan memakai session flash, bukan parameter URL
8. Etalase publik tetap bisa diakses tanpa login
9. Tambahkan bagian baru di `LAPORAN-KEAMANAN.md`: hasil uji Latihan 3, terutama percobaan mengakses `hapus.php` tanpa login

**Kumpulkan:** folder proyek `.zip` + file `.sql` + screenshot: halaman login, pesan error login, navbar setelah login, dan bukti halaman admin melempar ke login saat belum masuk.

> 📌 **Pertemuan depan adalah Exam 2.** Baca modul ujiannya dari sekarang, dan pastikan seluruh fitur dari P11 sampai P15 sudah berjalan di proyekmu — karena semuanya akan diujikan.

---

## Ringkasan

| Konsep | Poin Penting |
|--------|--------------|
| **HTTP stateless** | Server lupa antar permintaan — itu masalah yang dipecahkan session |
| **Cookie** | Data di **browser**, bisa diubah pengunjung. Jangan simpan yang sensitif |
| **Session** | Data di **server**, browser cuma membawa nomor kartu (PHPSESSID) |
| **`session_start()`** | **Sebelum output apa pun** — penyebab error "headers already sent" |
| **`$_SESSION`** | Array associative biasa, tapi bertahan antar halaman |
| **Hash ≠ enkripsi** | Hash tidak bisa dikembalikan — dan memang tidak perlu |
| **❌ MD5 / SHA1** | Terlalu cepat, tanpa salt, sudah pecah |
| **✅ `password_hash()`** | Salt otomatis, sengaja lambat, ikut berkembang |
| **`password_verify()`** | Membandingkan password dengan hash |
| **`VARCHAR(255)`** | Kolom password jangan lebih pendek |
| **Pesan error login** | Sengaja kabur, agar username tidak bisa ditebak |
| **`session_regenerate_id(true)`** | Mencegah session fixation |
| **`wajibLogin()`** | Dipasang di **setiap** halaman admin, termasuk `hapus.php` |
| **Menyembunyikan menu ≠ proteksi** | Yang melindungi adalah pemeriksaan di server |
| **Logout** | Kosongkan `$_SESSION`, hapus cookie, `session_destroy()` |
| **Flash message** | Simpan di session, hapus setelah dibaca. Tidak bisa dipalsukan |
| **`buat-admin.php`** | **Hapus** setelah dipakai |

> ➡️ **Pertemuan berikutnya (P16): Exam 2.** Kamu akan membangun sistem informasi sendiri dari nol dengan tema pilihan — CRUD lengkap, keamanan, upload, dan login. Semua yang diuji sudah kamu kerjakan lima pertemuan terakhir.

---

## 📚 Referensi

- [PHP Sessions — php.net](https://www.php.net/manual/en/book.session.php)
- [password_hash — php.net](https://www.php.net/manual/en/function.password-hash.php)
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)

---

*Dengan login terpasang, `toko-sederhana` sudah punya semua lapisan aplikasi web sungguhan: tampilan, logika, database, keamanan, dan kontrol akses. Minggu depan kamu membangun semuanya sendiri dari nol — dan kamu akan terkejut betapa banyak yang sudah kamu bisa. 🔐*
