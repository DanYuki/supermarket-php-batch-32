# Modul Pengajaran: PHP + MySQL — Menyatukan Keduanya
**Haltev IT Learning Center — Web Development**
**Pertemuan 11 | Tools: VS Code, XAMPP, phpMyAdmin**

---

## Tujuan Pembelajaran

Siswa mampu menghubungkan kode PHP dengan database MySQL, lalu membangun dua fitur pertama dari sebuah aplikasi web sungguhan: **menampilkan data dari database** (Read) dan **menyimpan data baru lewat form** (Create). Di akhir pertemuan, katalog produk yang minggu lalu masih memakai array dummy sudah berjalan dengan data asli dari database, dan bisa ditambah dari browser tanpa menyentuh kode.

> 📌 **Hari ini tidak ada konsep baru.** Loop, array, dan `foreach` sudah kamu kuasai di P9. Query `SELECT`, `INSERT`, dan `JOIN` sudah kamu kuasai di P10. Yang kita pelajari hari ini cuma **jembatan** di antara keduanya — segelintir fungsi untuk menyuruh PHP berbicara dengan MySQL.

---

## Daftar Isi

- [Recap: Dua Hal yang Akan Disatukan](#recap-dua-hal-yang-akan-disatukan)
- [Bagian 1 — Momen Panen](#bagian-1--momen-panen)
- [Bagian 2 — Struktur Proyek](#bagian-2--struktur-proyek)
- [Bagian 3 — File Koneksi](#bagian-3--file-koneksi)
- [Bagian 4 — Alur PHP → MySQL](#bagian-4--alur-php--mysql)
- [Bagian 5 — Read: Menampilkan Produk](#bagian-5--read-menampilkan-produk)
- [Bagian 6 — Pola Struktur File](#bagian-6--pola-struktur-file)
- [Bagian 7 — Create: Menambah Produk](#bagian-7--create-menambah-produk)
- [Bagian 8 — Kode Ini Belum Aman](#bagian-8--kode-ini-belum-aman)
- [Latihan](#latihan)
- [🎁 Bonus: Membedah Query yang Gagal (Knowledge)](#-bonus-membedah-query-yang-gagal-knowledge)
- [Tugas Pertemuan](#tugas-pertemuan)
- [Tips Penggunaan AI](#tips-penggunaan-ai)
- [Ringkasan](#ringkasan)

---

## Recap: Dua Hal yang Akan Disatukan

| Pertemuan | Yang kamu kuasai | Keterbatasannya |
|-----------|------------------|-----------------|
| **P9 — PHP Dasar** | Menampilkan array jadi tabel HTML dengan `foreach` | Datanya diketik manual di file kode |
| **P10 — SQL** | `SELECT`, `INSERT`, `WHERE`, `JOIN` di phpMyAdmin | Query-nya harus diketik manual tiap kali |

Dua-duanya setengah jalan. P9 punya tampilan tapi datanya palsu. P10 punya data asli tapi tidak ada tampilannya.

Hari ini keduanya disambung, dan hasilnya adalah **aplikasi web sungguhan**.

### Yang Harus Sudah Siap Sebelum Mulai

| Cek | Cara memastikan |
|-----|-----------------|
| Apache & MySQL hijau di XAMPP | Buka XAMPP Control Panel |
| Database `toko_db` masih ada | Buka phpMyAdmin, lihat panel kiri |
| Tabel `kategori` dan `produk` berisi data | `SELECT * FROM produk;` → harus keluar 8 baris |

> ⚠️ Kalau database-mu terhapus, jalankan ulang script `CREATE TABLE` dan `INSERT` dari modul P10 sebelum melanjutkan. Seluruh pertemuan hari ini bergantung pada data itu.

---

## Bagian 1 — Momen Panen

Sebelum menulis kode apa pun, lihat dulu dua potong data ini.

**Yang kamu tulis manual di P9:**

```php
$daftarProduk = [
    ["id" => 1, "nama" => "Laptop Pro 14",  "harga" => 9990000, "stok" => 4],
    ["id" => 2, "nama" => "Monitor 24 inch","harga" => 2100000, "stok" => 3],
];
```

**Yang dikembalikan database di P11:**

```php
// Hasil dari mysqli_fetch_assoc(), satu baris per pemanggilan:
["id" => 1, "nama" => "Laptop Pro 14",   "harga" => 9990000, "stok" => 4, "kategori_id" => 1]
["id" => 2, "nama" => "Monitor 24 inch", "harga" => 2100000, "stok" => 3, "kategori_id" => 1]
```

**Bentuknya sama persis.** Keduanya adalah *array associative* dengan key berupa nama kolom.

Artinya seluruh kode tampilan yang kamu tulis di P9 — `foreach`, `<?= $p["nama"] ?>`, `number_format`, badge stok — **tidak perlu diubah sama sekali**. Yang berubah hanya dari mana array itu datang.

| | P9 | P11 |
|---|-----|-----|
| Sumber data | Diketik di file PHP | Diambil dari database |
| Bentuk data | Array associative | Array associative |
| Kode tampilan | `foreach` + `<?= ?>` | **Sama persis** |
| Menambah produk | Edit file, upload ulang | Isi form di browser |

> 💡 Inilah kenapa P9 dan P10 sengaja dipisah. Kalau ketiganya diajarkan sekaligus, kamu akan sibuk menghafal sintaks dan tidak sempat menyadari betapa sedikitnya yang sebenarnya baru hari ini.

---

## Bagian 2 — Struktur Proyek

Kita mulai proyek baru bernama `toko-sederhana`. Proyek inilah yang akan terus dikembangkan sampai Exam 2 di Pertemuan 16.

```
htdocs/
└── toko-sederhana/
    ├── config/
    │   └── koneksi.php        ← Koneksi database (1 file, dipakai semua halaman)
    ├── templates/
    │   ├── header.php         ← Navbar + pembuka <body>
    │   └── footer.php         ← Penutup </body>
    ├── produk/
    │   ├── index.php          ← Daftar produk        (Read)
    │   └── tambah.php         ← Form + proses simpan (Create)
    └── index.php              ← Halaman depan
```

Buat semua folder ini sekarang, meski isinya masih kosong.

### Kenapa Dipisah ke Folder?

| Folder | Isinya | Alasan |
|--------|--------|--------|
| `config/` | Pengaturan yang dipakai semua halaman | Ganti password database cukup di satu tempat |
| `templates/` | Potongan tampilan yang berulang | Ganti navbar cukup di satu file — sudah kamu pelajari di P9 |
| `produk/` | Semua halaman yang mengurus produk | Nanti akan ada `pelanggan/`, `pesanan/`, dan seterusnya |

> 💡 Perhatikan bahwa file di dalam `produk/` memanggil file di folder lain dengan `../` — artinya "naik satu tingkat dulu". Contoh: `require_once '../config/koneksi.php';`

### `templates/header.php`

Ini pengembangan dari yang sudah kamu buat di P9, dengan navbar yang lebih sesuai kebutuhan proyek.

```php
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $judul ?? "Toko Sederhana" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
```

### `templates/footer.php`

```php
</div><!-- /container -->

<footer class="bg-dark text-white text-center py-3 mt-5">
  <small>&copy; <?= date("Y") ?> Toko Sederhana — Haltev IT Learning Center</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

> 💡 Alamat link ditulis mulai dari `/toko-sederhana/...` (diawali garis miring) supaya navbar tetap benar walau diakses dari folder mana pun. Ini disebut *absolute path*.

---

## Bagian 3 — File Koneksi

Satu file, dipakai seluruh halaman. Inilah gerbang antara PHP dan MySQL.

### `config/koneksi.php`

```php
<?php
$host     = 'localhost';
$username = 'root';
$password = '';          // kosong — ini default XAMPP
$database = 'toko_db';   // database yang kamu buat di P10

$conn = mysqli_connect($host, $username, $password, $database);

// Kalau koneksi gagal, hentikan semua dan tampilkan alasannya
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
```

### Membedah Baris demi Baris

| Baris | Fungsinya |
|-------|-----------|
| `$host = 'localhost'` | Database ada di komputer yang sama dengan web server |
| `$username = 'root'` | User default XAMPP |
| `$password = ''` | XAMPP tidak memasang password secara default |
| `$database = 'toko_db'` | Database mana yang mau dibuka |
| `mysqli_connect(...)` | Membuka koneksi, hasilnya disimpan di `$conn` |
| `if (!$conn) die(...)` | Kalau gagal, halaman berhenti total dengan pesan yang jelas |
| `mysqli_set_charset(...)` | Supaya huruf beraksen dan emoji tidak jadi karakter aneh |

### Variabel `$conn` — Pahami Ini Baik-baik

`$conn` bukan data. Ia adalah **jalur komunikasi** ke database. Setiap kali kamu ingin bertanya sesuatu ke MySQL, kamu harus menyodorkan `$conn` sebagai bukti "aku sudah tersambung".

Karena itu hampir semua fungsi `mysqli_` meminta `$conn` sebagai argumen pertama.

### Memakainya di File Lain

```php
require_once '../config/koneksi.php';
// Setelah baris ini, variabel $conn sudah tersedia dan siap dipakai
```

> 💡 Pakai `require_once`, bukan `include`. `require` menghentikan halaman kalau file tidak ketemu (lebih baik daripada error beruntun yang membingungkan), dan `_once` mencegah file dimuat dua kali.

### Uji Koneksi Sebelum Lanjut

Buat file `toko-sederhana/index.php` sementara:

```php
<?php
require_once 'config/koneksi.php';
echo "Berhasil tersambung ke database!";
```

Buka `http://localhost/toko-sederhana/index.php`. Kalau muncul tulisan tersebut, kamu siap lanjut.

| Pesan error | Artinya |
|-------------|---------|
| `Unknown database 'toko_db'` | Nama database salah ketik, atau database belum dibuat |
| `Access denied for user 'root'` | Username/password salah |
| `No such file or directory` | MySQL belum di-Start di XAMPP |

---

## Bagian 4 — Alur PHP → MySQL

Ini inti pertemuan hari ini. **Semua interaksi PHP dengan database selalu mengikuti alur lima langkah yang sama.**

```
   [1] SAMBUNG          mysqli_connect()      → dapat $conn
        ↓
   [2] TULIS QUERY      $sql = "SELECT ..."   → cuma string biasa
        ↓
   [3] KIRIM            mysqli_query()        → dapat $result
        ↓
   [4] AMBIL            mysqli_fetch_assoc()  → dapat 1 baris (array)
        ↓
   [5] TAMPILKAN        foreach / while       → HTML
```

### Fungsi yang Perlu Kamu Hafal

Cuma segini. Semua yang lain bisa dicari saat dibutuhkan.

| Fungsi | Gunanya | Mengembalikan |
|--------|---------|---------------|
| `mysqli_connect($h,$u,$p,$d)` | Membuka koneksi | `$conn`, atau `false` kalau gagal |
| `mysqli_query($conn, $sql)` | Mengirim query | `$result` untuk SELECT, `true`/`false` untuk INSERT |
| `mysqli_fetch_assoc($result)` | Mengambil **satu** baris | Array associative, atau `null` kalau habis |
| `mysqli_num_rows($result)` | Menghitung jumlah baris | Angka |
| `mysqli_error($conn)` | Pesan error terakhir | Teks — untuk debugging |
| `mysqli_insert_id($conn)` | `id` baris yang baru saja di-INSERT | Angka |

### Contoh Terkecil yang Utuh

```php
<?php
require_once 'config/koneksi.php';                          // [1]

$sql    = "SELECT nama, harga FROM produk WHERE stok > 0";  // [2]
$result = mysqli_query($conn, $sql);                        // [3]

while ($row = mysqli_fetch_assoc($result)) {                // [4]
    echo $row['nama'] . " — Rp " . number_format($row['harga'], 0, ',', '.') . "<br>";  // [5]
}
```

Enam baris, dan kamu sudah menampilkan data asli dari database.

### Kenapa `while`, Bukan `foreach`?

Pertanyaan yang bagus, dan penting.

`mysqli_fetch_assoc()` **tidak** mengembalikan semua baris sekaligus. Ia mengembalikan **satu baris**, lalu menggeser penanda ke baris berikutnya. Kalau sudah habis, ia mengembalikan `null`.

```php
$row = mysqli_fetch_assoc($result);   // baris 1
$row = mysqli_fetch_assoc($result);   // baris 2
$row = mysqli_fetch_assoc($result);   // baris 3
$row = mysqli_fetch_assoc($result);   // null — habis
```

Karena itu polanya memakai `while`: "selama masih ada baris, ambil dan proses."

```php
while ($row = mysqli_fetch_assoc($result)) {
    // $row berisi satu baris, berbentuk array associative
}
```

| | `foreach` | `while` + `fetch_assoc` |
|---|-----------|--------------------------|
| Dipakai untuk | Array yang sudah utuh di memori | Hasil query, diambil sebaris demi sebaris |
| Kenapa begitu | Datanya memang sudah ada semua | Hemat memori — 100.000 baris tidak dimuat sekaligus |

> 💡 Anggap `$result` sebagai antrean, bukan keranjang. `mysqli_fetch_assoc()` memanggil orang berikutnya dalam antrean, satu per satu, sampai antreannya habis.

---

## Bagian 5 — Read: Menampilkan Produk

Sekarang kita bangun halaman daftar produk yang sesungguhnya.

### Versi 1 — Query Sederhana

```php
$sql    = "SELECT * FROM produk ORDER BY id ASC";
$result = mysqli_query($conn, $sql);
```

Ini berjalan, tapi hasilnya menampilkan `kategori_id` berupa angka — persis masalah yang kamu temui di P10.

### Versi 2 — Pakai JOIN

Sekarang ilmu JOIN dari P10 terpakai:

```php
$sql = "SELECT p.*, k.nama AS nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        ORDER BY p.id ASC";
$result = mysqli_query($conn, $sql);
```

| Bagian | Kenapa begitu |
|--------|---------------|
| `p.*` | Ambil semua kolom dari tabel produk |
| `k.nama AS nama_kategori` | Beri alias supaya tidak bentrok dengan `p.nama` |
| `LEFT JOIN` | Bukan INNER — supaya Webcam HD yang belum punya kategori tetap tampil |

> ⚠️ **Alias `AS nama_kategori` itu wajib di sini.** Tanpa alias, PHP menerima dua key bernama `nama`, dan yang satu akan menimpa yang lain. Ini bug halus yang sulit dilacak.

### File Lengkap — `produk/index.php`

```php
<?php
// ===== [1] SETUP =====
require_once '../config/koneksi.php';
$judul = "Daftar Produk";

// ===== [2] AMBIL DATA =====
$sql = "SELECT p.*, k.nama AS nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        ORDER BY p.id ASC";
$result = mysqli_query($conn, $sql);

// Kalau query gagal, hentikan dengan pesan yang jelas
if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}

// ===== [3] TAMPILAN =====
require '../templates/header.php';
?>

<?php if (isset($_GET['sukses'])): ?>
  <div class="alert alert-success">Produk berhasil ditambahkan.</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Daftar Produk</h3>
  <a href="tambah.php" class="btn btn-primary">+ Tambah Produk</a>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th class="text-end">Harga</th>
          <th class="text-center">Stok</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
            <td>
              <?php if ($row['nama_kategori']): ?>
                <?= htmlspecialchars($row['nama_kategori']) ?>
              <?php else: ?>
                <span class="text-muted fst-italic">Belum dikategorikan</span>
              <?php endif; ?>
            </td>
            <td class="text-end">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
            <td class="text-center">
              <span class="badge <?= $row['stok'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                <?= $row['stok'] ?>
              </span>
            </td>
          </tr>
        <?php endwhile; ?>

        <?php if (mysqli_num_rows($result) === 0): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              Belum ada produk. <a href="tambah.php">Tambah sekarang</a>.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require '../templates/footer.php'; ?>
```

### Bandingkan dengan Kode P9-mu

Buka file `katalog.php` dari tugas P9 dan taruh berdampingan. Perhatikan:

| Bagian | P9 | P11 |
|--------|-----|-----|
| Sumber data | `$daftarProduk = [...]` | `mysqli_query(...)` |
| Perulangan | `foreach ($daftarProduk as $p):` | `while ($row = mysqli_fetch_assoc($result)):` |
| Akses kolom | `$p["nama"]` | `$row['nama']` |
| Format harga | `number_format(...)` | **Sama persis** |
| Badge stok | `$p["stok"] > 0 ? ... : ...` | **Sama persis** |

Hanya dua baris pertama yang berubah. Sisanya identik.

> 💡 `mysqli_num_rows($result) === 0` dipakai untuk menampilkan pesan "belum ada produk". Halaman kosong tanpa penjelasan membuat pengguna bingung — selalu sediakan *empty state*.

---

## Bagian 6 — Pola Struktur File

Setiap file PHP mulai sekarang mengikuti urutan yang **sama dan konsisten**. Hafalkan pola ini — dipakai terus sampai Pertemuan 15.

```php
<?php
// ============================================
// [1] SETUP — require yang dibutuhkan
// ============================================
require_once '../config/koneksi.php';
$judul = "Judul Halaman";

// ============================================
// [2] PROSES — tangani kiriman form (POST)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // validasi, simpan, lalu redirect
}

// ============================================
// [3] AMBIL DATA — query untuk ditampilkan
// ============================================
$result = mysqli_query($conn, "SELECT ...");

// ============================================
// [4] TAMPILAN — HTML dimulai di sini
// ============================================
require '../templates/header.php';
?>

<!-- konten halaman -->

<?php require '../templates/footer.php'; ?>
```

> ⚠️ **Bagian [2] PROSES wajib berada di atas HTML.** Alasannya teknis tapi penting: PHP tidak bisa melakukan redirect (`header('Location: ...')`) kalau sudah ada satu karakter pun yang terkirim ke browser. Bahkan satu spasi sebelum `<?php` di baris pertama sudah cukup untuk merusaknya — ini gotcha yang sudah disinggung di P9, dan sekarang akibatnya jadi nyata.

---

## Bagian 7 — Create: Menambah Produk

Sekarang bagian yang membuat aplikasimu benar-benar hidup: menambah data dari browser.

### Alur yang Akan Terjadi

```
  Buka tambah.php           →  form kosong tampil            (GET)
  Isi form, klik Simpan     →  data dikirim ke tambah.php    (POST)
  PHP validasi & INSERT     →  data masuk database
  PHP redirect              →  pindah ke index.php?sukses=1
  index.php tampil          →  produk baru sudah ada di tabel
```

### Mengambil Daftar Kategori untuk Dropdown

Form butuh pilihan kategori. Ambil dari database, jangan diketik manual:

```php
$kategori = mysqli_query($conn, "SELECT id, nama FROM kategori ORDER BY nama ASC");
```

### File Lengkap — `produk/tambah.php`

```php
<?php
// ===== [1] SETUP =====
require_once '../config/koneksi.php';
$judul  = "Tambah Produk";
$errors = [];

// ===== [2] PROSES =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil dan bersihkan input
    $nama        = trim($_POST['nama'] ?? '');
    $harga       = (int) ($_POST['harga'] ?? 0);
    $stok        = (int) ($_POST['stok'] ?? 0);
    $kategori_id = $_POST['kategori_id'] ?? '';

    // Validasi
    if ($nama === '') {
        $errors[] = "Nama produk wajib diisi.";
    }
    if ($harga <= 0) {
        $errors[] = "Harga harus lebih dari 0.";
    }
    if ($stok < 0) {
        $errors[] = "Stok tidak boleh negatif.";
    }

    // Kalau kategori tidak dipilih, simpan sebagai NULL
    $kategoriSql = ($kategori_id === '') ? "NULL" : (int) $kategori_id;

    // Kalau tidak ada error, simpan ke database
    if (empty($errors)) {
        $sql = "INSERT INTO produk (nama, harga, stok, kategori_id)
                VALUES ('$nama', $harga, $stok, $kategoriSql)";

        if (mysqli_query($conn, $sql)) {
            header("Location: index.php?sukses=1");
            exit;
        } else {
            $errors[] = "Gagal menyimpan: " . mysqli_error($conn);
        }
    }
}

// ===== [3] AMBIL DATA =====
$kategori = mysqli_query($conn, "SELECT id, nama FROM kategori ORDER BY nama ASC");

// ===== [4] TAMPILAN =====
require '../templates/header.php';
?>

<h3 class="mb-4">Tambah Produk</h3>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="POST">

      <div class="mb-3">
        <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Harga <span class="text-danger">*</span></label>
          <input type="number" name="harga" class="form-control"
                 value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Stok</label>
          <input type="number" name="stok" class="form-control"
                 value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label">Kategori</label>
        <select name="kategori_id" class="form-select">
          <option value="">— Tidak berkategori —</option>
          <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
            <option value="<?= $k['id'] ?>"
              <?= (($_POST['kategori_id'] ?? '') == $k['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="index.php" class="btn btn-secondary">Batal</a>

    </form>
  </div>
</div>

<?php require '../templates/footer.php'; ?>
```

### Tiga Hal Penting di File Ini

#### 1. Redirect Setelah Simpan (Pola PRG)

```php
header("Location: index.php?sukses=1");
exit;
```

Kenapa harus redirect, kenapa tidak langsung menampilkan pesan sukses?

Karena kalau halaman POST ditampilkan apa adanya, pengguna yang menekan **F5** akan mengirim ulang form yang sama — dan produknya tersimpan dua kali. Browser bahkan menampilkan peringatan "Confirm Form Resubmission".

Dengan redirect, alamat di browser berubah menjadi permintaan GET biasa. F5 hanya memuat ulang daftar, tidak menyimpan apa pun.

Pola ini bernama **PRG** (*Post – Redirect – Get*), dan dipakai di hampir semua aplikasi web.

> ⚠️ **`exit;` setelah `header()` itu wajib.** Tanpa itu, PHP melanjutkan mengeksekusi sisa file, dan kadang kode di bawahnya sempat berjalan sebelum browser pindah halaman.

#### 2. Input Tidak Hilang Saat Validasi Gagal

```php
value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
```

Kalau validasi gagal, form ditampilkan ulang. Tanpa `value`, semua isian yang sudah diketik pengguna akan hilang — pengalaman yang sangat menjengkelkan. Baris ini mengembalikan apa yang tadi mereka ketik.

`?? ''` dipakai supaya tidak error saat halaman pertama kali dibuka (waktu itu `$_POST` masih kosong).

#### 3. Pesan Sukses Lewat URL

```php
// tambah.php mengirim:
header("Location: index.php?sukses=1");

// index.php menerimanya:
<?php if (isset($_GET['sukses'])): ?>
  <div class="alert alert-success">Produk berhasil ditambahkan.</div>
<?php endif; ?>
```

Cara sederhana untuk memberi kabar antar halaman. Nanti di Pertemuan 15 kita ganti dengan **session** yang lebih rapi, karena cara ini bisa dipalsukan siapa saja hanya dengan mengetik `?sukses=1` di URL.

### Validasi Dua Lapis

Perhatikan bahwa kita **tidak** mengandalkan `required` di HTML saja.

| Lapis | Di mana | Gunanya | Bisa dilewati? |
|-------|---------|---------|----------------|
| HTML (`required`) | Browser | Kenyamanan pengguna | ✅ Ya, lewat DevTools |
| PHP (`if ($nama === '')`) | Server | Keamanan sesungguhnya | ❌ Tidak |

Ini persis prinsip yang sudah dibahas di P9: **validasi di browser untuk pengalaman, validasi di server untuk keamanan.**

---

## Bagian 8 — Kode Ini Belum Aman

Jujur sejak awal: **kode `INSERT` di atas punya lubang keamanan serius.** Kita sengaja memakainya hari ini supaya kamu bisa fokus pada alur PHP–MySQL tanpa dibebani sintaks tambahan. Tapi kamu harus tahu masalahnya sekarang, bukan nanti.

### Letak Masalahnya

```php
$sql = "INSERT INTO produk (nama, harga, stok, kategori_id)
        VALUES ('$nama', $harga, $stok, $kategoriSql)";
```

Variabel `$nama` datang langsung dari pengguna, lalu ditempelkan mentah-mentah ke dalam perintah SQL. Kalau seseorang mengetik nama produk seperti ini:

```
Laptop', 0, 0, NULL); DROP TABLE produk; --
```

...perintah yang benar-benar dijalankan MySQL bukan lagi yang kamu maksud. Serangan ini bernama **SQL Injection**, dan merupakan salah satu celah keamanan paling merusak dalam sejarah aplikasi web.

### Apa yang Sudah Aman

Bagian **tampilan** sudah kita amankan sejak awal:

```php
<?= htmlspecialchars($row['nama']) ?>
```

`htmlspecialchars()` mencegah serangan XSS — ini sudah benar dan tetap dipakai. Yang belum aman hanya bagian yang **mengirim data ke database**.

| Arah data | Pelindung | Status hari ini |
|-----------|-----------|-----------------|
| Database → halaman | `htmlspecialchars()` | ✅ Sudah dipakai |
| Form → database | *Prepared statement* | ❌ Belum — dibahas di P13 |

### Yang Akan Kita Lakukan di P13

Di Pertemuan 13 kamu akan:

1. Mencoba sendiri menyerang aplikasimu dengan SQL injection dan melihat akibatnya
2. Mempelajari **prepared statement** — cara memisahkan perintah SQL dari data
3. Me-refactor seluruh file di proyek ini agar aman

> 📌 **Jangan pernah memakai pola `"... '$variabel' ..."` untuk aplikasi sungguhan.** Hari ini kita memakainya sebagai batu loncatan belajar, dan kita akan memperbaikinya. Tandai bagian ini di catatanmu.

---

## Latihan

Semua latihan dikerjakan di proyek `toko-sederhana`.

### Latihan 1 — Menyambungkan dan Menampilkan

Buat struktur folder lengkap, `config/koneksi.php`, kedua file template, lalu bangun `produk/index.php` sampai daftar produk tampil dari database.

**Kriteria berhasil:** membuka `http://localhost/toko-sederhana/produk/index.php` menampilkan 8 produk dari P10. Webcam HD tampil dengan keterangan "Belum dikategorikan", bukan hilang.

> 🎯 **Uji pemahaman:** ubah `LEFT JOIN` menjadi `INNER JOIN`, muat ulang halaman, dan hitung barisnya. Bisa jelaskan kenapa berkurang jadi 7?

---

### Latihan 2 — Menambah Data

Bangun `produk/tambah.php` lengkap dengan validasi, dropdown kategori dari database, redirect PRG, dan pesan sukses.

**Kriteria berhasil:**

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Isi form dengan benar → Simpan | Kembali ke daftar, produk baru muncul paling bawah |
| Tekan F5 setelah simpan | Tidak ada produk kedua yang tersimpan |
| Kosongkan nama → Simpan | Pesan error muncul, isian lain **tidak hilang** |
| Isi harga dengan 0 → Simpan | Pesan error "Harga harus lebih dari 0" |
| Pilih "Tidak berkategori" | Produk tersimpan dengan kategori NULL |
| Cek phpMyAdmin | Baris baru benar-benar ada di tabel `produk` |

---

### Latihan 3 — Halaman Depan Toko

Buat `toko-sederhana/index.php` sebagai etalase untuk pengunjung — bukan tabel admin, tapi **kartu produk** seperti latihan katalog di P9.

```php
<?php
require_once 'config/koneksi.php';
$judul = "Selamat Datang";

// TODO: query produk yang stoknya masih ada (stok > 0),
//       JOIN dengan kategori, urutkan dari yang terbaru
//       Petunjuk: ORDER BY id DESC

require 'templates/header.php';
?>

<h3 class="mb-4">Produk Tersedia</h3>

<div class="row g-3">
  <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <!-- TODO: nama produk sebagai judul -->
          <!-- TODO: nama kategori sebagai teks kecil abu-abu -->
          <!-- TODO: harga format rupiah -->
          <!-- TODO: sisa stok -->
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<!-- TODO: kalau tidak ada produk sama sekali, tampilkan pesan -->

<?php require 'templates/footer.php'; ?>
```

**Kriteria berhasil:** hanya produk dengan stok di atas 0 yang tampil (Mouse Wireless dan Headset Gaming tidak muncul). Produk yang baru kamu tambah di Latihan 2 muncul paling atas.

> 🎯 **Variasi (opsional):** tambahkan filter kategori memakai `$_GET` seperti Latihan 3 di P9 — tapi kali ini filternya dilakukan oleh SQL (`WHERE kategori_id = ...`), bukan oleh PHP. Bandingkan mana yang lebih efisien dan kenapa.

---

## 🎁 Bonus: Membedah Query yang Gagal (Knowledge)

> ℹ️ Bagian ini **tidak wajib dikerjakan**, tapi akan menghemat berjam-jam frustrasi di pertemuan-pertemuan berikutnya.

Query yang gagal adalah hal paling sering terjadi saat belajar PHP+MySQL. Masalahnya, gejalanya sering **membingungkan**: bukan pesan error, tapi halaman kosong atau tabel tanpa isi.

### Kenapa Bisa Diam-diam Gagal?

```php
$result = mysqli_query($conn, "SELECT * FROM produkk");  // salah ketik!
while ($row = mysqli_fetch_assoc($result)) { ... }       // tidak jalan, tanpa pesan
```

`mysqli_query()` mengembalikan `false` saat gagal. Perulangan `while` dengan `false` langsung berhenti tanpa berkomentar — jadi kamu cuma melihat tabel kosong.

### Kebiasaan yang Menyelamatkan

**Selalu periksa hasil query.** Satu baris ini akan menghemat banyak waktumu:

```php
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}
```

Sekarang kamu mendapat pesan yang tepat, misalnya:

```
Query gagal: Table 'toko_db.produkk' doesn't exist
```

### Menu Diagnosis Cepat

| Gejala | Penyebab paling mungkin | Cara memastikan |
|--------|------------------------|-----------------|
| Halaman kosong putih total | Error PHP, `display_errors` mati | Nyalakan `display_errors` di `php.ini` |
| Tabel tampil tapi tanpa baris | Query gagal, atau `WHERE`-nya terlalu ketat | Tambahkan `if (!$result) die(...)` |
| `Undefined array key 'nama'` | Nama kolom salah, atau alias JOIN lupa | `print_r($row)` untuk melihat key yang ada |
| Kolom kategori kosong terus | Lupa `AS nama_kategori`, tertimpa `p.nama` | Cek query-nya di phpMyAdmin dulu |
| `Cannot modify header information` | Ada output sebelum `header()` | Cari spasi sebelum `<?php` di baris pertama |

### Teknik Paling Ampuh: Uji Query di phpMyAdmin Dulu

Kalau sebuah query bermasalah, **jangan debug di PHP.** Lakukan ini:

```php
$sql = "SELECT p.*, k.nama AS nama_kategori FROM produk p ...";
echo $sql;   // tampilkan query jadinya, lalu hapus baris ini
```

Salin hasilnya, tempel di tab SQL phpMyAdmin, jalankan. Kalau di sana error, masalahnya di SQL. Kalau di sana berhasil tapi di PHP tidak, masalahnya di kode PHP.

> 💡 Teknik memisahkan "masalahnya di lapisan mana" ini berlaku untuk semua jenis debugging, bukan cuma database. Ini salah satu kebiasaan paling berharga yang bisa kamu bangun sebagai developer.

---

## Tugas Pertemuan

Lanjutkan proyek `toko-sederhana` dengan ketentuan berikut:

1. Struktur folder sesuai modul, dengan `config/koneksi.php` dan folder `templates/`
2. **Halaman daftar produk** (`produk/index.php`) menampilkan data dari database, lengkap dengan nama kategori lewat `LEFT JOIN`, format rupiah, badge stok, dan *empty state*
3. **Halaman tambah produk** (`produk/tambah.php`) dengan validasi server, dropdown kategori dari database, pola PRG, dan input yang tidak hilang saat validasi gagal
4. **Halaman depan** (`index.php`) berupa etalase kartu produk yang stoknya masih ada
5. Tambahkan **satu tabel dan satu halaman baru** di luar produk — bebas pilih: daftar kategori, daftar pelanggan, atau daftar pesanan (pakai tabel yang sudah kamu buat di P10). Minimal halaman daftar (Read) dengan satu JOIN
6. Semua query yang bisa gagal diberi pemeriksaan `if (!$result) die(mysqli_error($conn));`

**Kumpulkan:**

- Folder proyek dalam bentuk `.zip`
- File `.sql` hasil Export database dari phpMyAdmin
- Screenshot: daftar produk, form tambah, pesan validasi gagal, dan halaman depan

> 📌 **Jangan hapus proyek ini.** Pertemuan 12 tinggal menambahkan Edit, Hapus, Pencarian, dan Paginasi ke proyek yang sama. Sampai Exam 2 nanti, proyek inilah yang terus tumbuh.

---

## Tips Penggunaan AI

**Memahami alur, bukan meminta kode jadi:**
> "Jelaskan apa yang terjadi di balik layar saat PHP menjalankan `mysqli_query()` lalu `mysqli_fetch_assoc()`. Kenapa harus dipanggil berulang dalam `while`, bukan sekali saja?"

**Membedah error:**
> "Kode PHP saya menampilkan tabel kosong padahal di phpMyAdmin query-nya mengembalikan 8 baris. Ini kodenya: [tempel]. Jangan langsung perbaiki — bantu saya menemukan sendiri di lapisan mana masalahnya."

**Meminta review, bukan penulisan ulang:**
> "Ini file tambah.php saya: [tempel]. Tunjukkan bagian mana yang belum aman atau belum rapi, jelaskan alasannya, tapi jangan tuliskan versi perbaikannya."

**Melihat ke depan:**
> "Saya sedang belajar PHP+MySQL dan baru memakai `mysqli_query` dengan variabel disisipkan langsung ke string SQL. Jelaskan kenapa ini berbahaya dan seperti apa bentuk amannya — saya cuma mau paham dulu, belum menerapkan."

> ⚠️ **Godaan terbesar di pertemuan ini:** meminta AI menuliskan seluruh `tambah.php`. AI akan sanggup, dan hasilnya akan jalan. Tapi di Pertemuan 12 kamu harus menambahkan Edit dan Hapus ke file yang sama, dan di P13 kamu harus me-refactor-nya jadi aman. Kalau file itu bukan tulisanmu, kedua pertemuan itu akan terasa mustahil.

---

## Ringkasan

| Konsep | Poin Penting |
|--------|--------------|
| **Bentuk data** | Hasil `mysqli_fetch_assoc()` = array associative, sama persis dengan array dummy P9 |
| **Alur 5 langkah** | Sambung → tulis query → kirim → ambil → tampilkan |
| **`$conn`** | Bukan data, melainkan jalur komunikasi. Jadi argumen pertama hampir semua fungsi `mysqli_` |
| **`mysqli_query`** | Mengembalikan `$result` untuk SELECT, `true`/`false` untuk INSERT |
| **`while` bukan `foreach`** | `fetch_assoc` mengambil sebaris demi sebaris, seperti antrean |
| **`mysqli_num_rows`** | Untuk menampilkan *empty state* saat data kosong |
| **JOIN di PHP** | Sama persis dengan di phpMyAdmin. Wajib pakai alias `AS` kalau nama kolom bentrok |
| **`LEFT JOIN`** | Supaya produk tanpa kategori tetap tampil — ingat Webcam HD |
| **Struktur file** | [1] Setup → [2] Proses → [3] Ambil data → [4] Tampilan |
| **Pola PRG** | POST → redirect → GET. Mencegah data tersimpan dua kali saat F5 |
| **`exit;`** | Wajib setelah `header('Location: ...')` |
| **Validasi dua lapis** | HTML untuk kenyamanan, PHP untuk keamanan |
| **Nilai form kembali** | `value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"` |
| **Debugging** | `if (!$result) die(mysqli_error($conn));` — dan uji query di phpMyAdmin dulu |
| **⚠️ Belum aman** | INSERT masih menyisipkan variabel ke string SQL. Diperbaiki di P13 |

> ➡️ **Pertemuan berikutnya (P12):** melengkapi CRUD — **Update** (edit produk), **Delete** (hapus dengan konfirmasi), **Search** (pakai `LIKE` dari P10), dan **Pagination** (pakai `LIMIT` + `OFFSET` dari P10). Semua bahannya sudah kamu punya; tinggal dirangkai.

---

## 📚 Referensi

- [PHP: MySQLi — php.net](https://www.php.net/manual/en/book.mysqli.php)
- [mysqli_fetch_assoc — php.net](https://www.php.net/manual/en/mysqli-result.fetch-assoc.php)
- [PHP MySQL Select — W3Schools](https://www.w3schools.com/php/php_mysql_select.asp)
- [Post/Redirect/Get — Wikipedia](https://en.wikipedia.org/wiki/Post/Redirect/Get)
- [Bootstrap 5 Forms](https://getbootstrap.com/docs/5.3/forms/overview/)

---

*Begitu daftar produkmu tampil dari database dan form tambahnya berfungsi, kamu sudah membangun aplikasi web yang utuh — frontend, backend, dan database bekerja bersama. Sisa bootcamp ini tinggal menambah fitur di atas fondasi yang sudah kamu bangun hari ini. 🎉*
