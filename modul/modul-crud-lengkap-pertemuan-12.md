# Modul Pengajaran: CRUD Lengkap — Edit, Hapus, Cari & Paginasi
**Haltev IT Learning Center — Web Development**
**Pertemuan 12 | Tools: VS Code, XAMPP (PHP 8.1+), phpMyAdmin**

---

## Tujuan Pembelajaran

Siswa mampu melengkapi aplikasi `toko-sederhana` dengan empat fitur yang membuatnya benar-benar layak pakai: **mengubah data** (Update), **menghapus data** (Delete), **mencari data** (Search), dan **membagi tampilan menjadi beberapa halaman** (Pagination). Di akhir pertemuan, aplikasi sudah memiliki CRUD utuh dan tetap nyaman dipakai walau isinya ratusan produk.

> 📌 **Tidak ada konsep SQL baru hari ini.** `UPDATE`, `DELETE`, `LIKE`, `LIMIT`, dan `OFFSET` semuanya sudah kamu pelajari di P10. Yang baru cuma cara merangkainya dengan PHP dan tampilan.

---

## Daftar Isi

- [Recap Pertemuan 11](#recap-pertemuan-11)
- [Catatan Gaya Kode: MySQLi Object-Oriented](#catatan-gaya-kode-mysqli-object-oriented)
- [Bagian 1 — Update: Mengubah Produk](#bagian-1--update-mengubah-produk)
- [Bagian 2 — Delete: Menghapus Produk](#bagian-2--delete-menghapus-produk)
- [Bagian 3 — Search: Mencari Produk](#bagian-3--search-mencari-produk)
- [Bagian 4 — Pagination: Membagi Halaman](#bagian-4--pagination-membagi-halaman)
- [Bagian 5 — Menggabungkan Search + Pagination](#bagian-5--menggabungkan-search--pagination)
- [Latihan](#latihan)
- [🎁 Bonus: Kenapa Delete Sebaiknya Pakai POST (Knowledge)](#-bonus-kenapa-delete-sebaiknya-pakai-post-knowledge)
- [Tugas Pertemuan](#tugas-pertemuan)
- [Tips Penggunaan AI](#tips-penggunaan-ai)
- [Ringkasan](#ringkasan)

---

## Recap Pertemuan 11

Yang sudah berjalan di proyekmu:

| File | Fungsinya | Huruf CRUD |
|------|-----------|:----------:|
| `config/koneksi.php` | Koneksi database | — |
| `templates/header.php` & `footer.php` | Tampilan berulang | — |
| `produk/index.php` | Daftar produk dari database | **R**ead |
| `produk/tambah.php` | Form tambah produk | **C**reate |

Hari ini kita lengkapi menjadi **CRUD** utuh:

| Huruf | Arti | File yang dibuat hari ini |
|:-----:|------|---------------------------|
| C | Create | ✅ sudah ada |
| R | Read | ✅ sudah ada |
| **U** | **Update** | `produk/edit.php` |
| **D** | **Delete** | `produk/hapus.php` |

Ditambah dua fitur pendukung yang membuat daftar tetap nyaman dipakai: **Search** dan **Pagination**.

### Pola yang Tetap Dipakai

```php
<?php
// [1] SETUP    — require koneksi
// [2] PROSES   — tangani POST / aksi
// [3] AMBIL    — query untuk ditampilkan
// [4] TAMPILAN — HTML
```

---

## Catatan Gaya Kode: MySQLi Object-Oriented

Mulai modul ini, seluruh kode memakai **gaya object-oriented** MySQLi. Ini gaya yang dipakai di dunia kerja dan yang akan memudahkanmu saat masuk Laravel nanti.

### Tiga Perbedaan yang Perlu Kamu Kenali

| Prosedural | Object-Oriented |
|------------|-----------------|
| `mysqli_connect($h, $u, $p, $d)` | `new mysqli($h, $u, $p, $d)` |
| `mysqli_query($conn, $sql)` | `$conn->query($sql)` |
| `mysqli_fetch_assoc($result)` | `$result->fetch_assoc()` |

Tanda `->` dibaca "punya" atau "milik". `$conn->query(...)` artinya "jalankan `query` **milik** objek `$conn`". Konsep di baliknya adalah OOP, yang baru dibahas tuntas di Pertemuan 17 — untuk sekarang cukup pahami pola penulisannya.

### `config/koneksi.php` yang Diperbarui

```php
<?php
// Sejak PHP 8.1, MySQLi otomatis melempar exception saat terjadi error.
// Baris ini ditulis eksplisit supaya perilakunya jelas dan tidak bergantung versi.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host     = 'localhost';
$username = 'root';
$password = '';
$database = 'toko_db';

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
```

### Error Sekarang Berupa Exception

Ini perubahan penting. Dulu query yang gagal mengembalikan `false`, sehingga kamu perlu memeriksanya sendiri:

```php
// Cara lama — sudah tidak berlaku
$result = mysqli_query($conn, $sql);
if (!$result) { die(mysqli_error($conn)); }
```

Sekarang query yang gagal **melempar exception** dan langsung menghentikan halaman dengan pesan errornya. Artinya:

| | Dulu | Sekarang |
|---|------|----------|
| Query gagal | Diam-diam, kembalikan `false` | Berhenti dengan pesan error jelas |
| Perlu `if (!$result)` | ✅ Ya | ❌ Tidak lagi |
| Menangani kegagalan | Cek nilai kembalian | `try` / `catch` |

Ini sebenarnya **kabar baik**: masalah "tabel kosong tanpa penjelasan" yang dibahas di bonus P11 tidak akan terjadi lagi. Query yang salah ketik langsung berteriak.

### `fetch_all()` — Ambil Semua Baris Sekaligus

```php
$result   = $conn->query("SELECT * FROM produk");
$produkList = $result->fetch_all(MYSQLI_ASSOC);   // array berisi semua baris
```

Sekarang `$produkList` adalah **array of associative array** — bentuk yang persis sama dengan array dummy P9. Jadi perulangannya kembali memakai `foreach`, bukan `while`:

```php
foreach ($produkList as $row) {
    echo $row['nama'];
}
```

| | `while` + `fetch_assoc()` | `fetch_all(MYSQLI_ASSOC)` + `foreach` |
|---|---------------------------|----------------------------------------|
| Cara kerja | Ambil sebaris demi sebaris | Ambil semua sekaligus jadi array |
| Pemakaian memori | Hemat | Semua baris masuk memori |
| Bisa diulang | Sekali jalan | Bisa di-`foreach` berkali-kali |
| Bisa dihitung | Perlu `num_rows` | `count($produkList)` |

> 💡 **Untuk halaman yang berpaginasi, `fetch_all()` adalah pilihan yang tepat** — kita cuma mengambil 5 baris per halaman, jadi tidak ada risiko memori. Kalau suatu saat kamu memproses puluhan ribu baris sekaligus (misalnya membuat laporan ekspor), barulah `while` + `fetch_assoc()` lebih hemat.

> 💡 Konstanta `MYSQLI_ASSOC` meminta hasilnya berupa key nama kolom saja. Tanpa itu, setiap baris berisi data ganda — sekali dengan key nama kolom, sekali dengan key angka.

---

## Bagian 1 — Update: Mengubah Produk

Update adalah gabungan dari dua hal yang sudah kamu bisa: **membaca satu baris** untuk mengisi form, lalu **menyimpan perubahannya**.

### Alur yang Akan Terjadi

```
  Klik tombol Edit di daftar   →  edit.php?id=3               (GET)
  PHP ambil data produk id=3   →  form tampil SUDAH TERISI
  Ubah isian, klik Simpan      →  data dikirim ke edit.php?id=3 (POST)
  PHP validasi & UPDATE        →  data berubah di database
  PHP redirect                 →  index.php?diubah=1
```

Perhatikan bedanya dengan `tambah.php`: form-nya **sudah terisi** saat dibuka, dan query-nya `UPDATE`, bukan `INSERT`.

### Mengambil Satu Baris Saja

```php
$id     = (int) ($_GET['id'] ?? 0);
$result = $conn->query("SELECT * FROM produk WHERE id = $id");
$produk = $result->fetch_assoc();   // satu baris, atau null kalau tidak ada
```

Untuk satu baris, pakai `fetch_assoc()` — bukan `fetch_all()`. Yang kita butuhkan memang cuma satu array, bukan array berisi satu array.

> ⚠️ **`(int)` di depan `$_GET['id']` itu penting.** Kalau seseorang membuka `edit.php?id=abc`, tanpa `(int)` query-mu jadi berantakan dan sekarang malah melempar exception. Dengan `(int)`, nilainya otomatis menjadi `0` dan tidak ada produk yang cocok — aman dan mudah ditangani.

### Menangani Produk yang Tidak Ada

Apa yang terjadi kalau seseorang membuka `edit.php?id=999`? Tanpa penanganan, halaman akan error saat mencoba menampilkan `$produk['nama']` yang tidak ada.

```php
if (!$produk) {
    header("Location: index.php?notfound=1");
    exit;
}
```

Ini disebut **guard clause** — periksa kondisi buruk sedini mungkin, lalu keluar. Kode di bawahnya jadi bisa berasumsi bahwa datanya pasti ada.

### File Lengkap — `produk/edit.php`

```php
<?php
// ===== [1] SETUP =====
require_once '../config/koneksi.php';
$judul  = "Edit Produk";
$errors = [];

$id = (int) ($_GET['id'] ?? 0);

// Ambil data produk yang mau diedit
$produk = $conn->query("SELECT * FROM produk WHERE id = $id")->fetch_assoc();

// Guard clause: produk tidak ditemukan
if (!$produk) {
    header("Location: index.php?notfound=1");
    exit;
}

// ===== [2] PROSES =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama        = trim($_POST['nama'] ?? '');
    $harga       = (int) ($_POST['harga'] ?? 0);
    $stok        = (int) ($_POST['stok'] ?? 0);
    $kategori_id = $_POST['kategori_id'] ?? '';

    if ($nama === '')  { $errors[] = "Nama produk wajib diisi."; }
    if ($harga <= 0)   { $errors[] = "Harga harus lebih dari 0."; }
    if ($stok < 0)     { $errors[] = "Stok tidak boleh negatif."; }

    $kategoriSql = ($kategori_id === '') ? "NULL" : (int) $kategori_id;

    if (empty($errors)) {
        try {
            $conn->query("UPDATE produk
                          SET nama = '$nama', harga = $harga, stok = $stok, kategori_id = $kategoriSql
                          WHERE id = $id");

            header("Location: index.php?diubah=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Gagal menyimpan: " . $e->getMessage();
        }
    }

    // Kalau validasi gagal, tampilkan ulang apa yang tadi diketik
    $produk['nama']        = $nama;
    $produk['harga']       = $harga;
    $produk['stok']        = $stok;
    $produk['kategori_id'] = $kategori_id;
}

// ===== [3] AMBIL DATA =====
$kategoriList = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC")
                     ->fetch_all(MYSQLI_ASSOC);

// ===== [4] TAMPILAN =====
require '../templates/header.php';
?>

<h3 class="mb-4">Edit Produk</h3>

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
               value="<?= htmlspecialchars($produk['nama']) ?>">
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Harga <span class="text-danger">*</span></label>
          <input type="number" name="harga" class="form-control"
                 value="<?= htmlspecialchars($produk['harga']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Stok</label>
          <input type="number" name="stok" class="form-control"
                 value="<?= htmlspecialchars($produk['stok']) ?>">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label">Kategori</label>
        <select name="kategori_id" class="form-select">
          <option value="">— Tidak berkategori —</option>
          <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>"
              <?= ($produk['kategori_id'] == $k['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="index.php" class="btn btn-secondary">Batal</a>

    </form>
  </div>
</div>

<?php require '../templates/footer.php'; ?>
```

### Merangkai Pemanggilan Method

Perhatikan baris ini:

```php
$produk = $conn->query("SELECT * FROM produk WHERE id = $id")->fetch_assoc();
```

Ia sama artinya dengan:

```php
$result = $conn->query("SELECT * FROM produk WHERE id = $id");
$produk = $result->fetch_assoc();
```

Bentuk pertama disebut **method chaining** — hasil dari `query()` langsung dipakai untuk memanggil `fetch_assoc()`, tanpa disimpan dulu ke variabel. Lebih ringkas, dan sangat umum dipakai.

> 💡 Pakai bentuk panjang kalau `$result`-nya masih dibutuhkan lagi (misalnya untuk `num_rows`), dan bentuk rangkai kalau hasilnya langsung dipakai sekali saja.

### Bandingkan `tambah.php` dan `edit.php`

| Bagian | `tambah.php` | `edit.php` |
|--------|--------------|------------|
| Ambil data awal | Tidak ada | `SELECT ... WHERE id = $id` |
| Guard clause | Tidak perlu | Wajib — produk bisa saja tidak ada |
| Isi awal form | Kosong | `value="<?= $produk['nama'] ?>"` |
| Query simpan | `INSERT INTO` | `UPDATE ... WHERE id = $id` |
| Dropdown terpilih | Dari `$_POST` | Dari `$produk['kategori_id']` |

> ⚠️ **`WHERE id = $id` di query UPDATE itu wajib.** Ingat slide "paling berbahaya" di P10: `UPDATE produk SET nama = '...'` tanpa `WHERE` akan mengubah **seluruh** produk sekaligus. Sekali lagi: tidak ada error, tidak ada konfirmasi, tidak bisa dibatalkan.

### Menambahkan Tombol Edit di Daftar

Di `produk/index.php`, tambahkan kolom Aksi pada tabel:

```php
<th class="text-center" style="width: 150px;">Aksi</th>
```

Lalu di dalam perulangan:

```php
<td class="text-center">
  <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
</td>
```

Perhatikan `?id=<?= $row['id'] ?>` — inilah cara satu halaman memberi tahu halaman berikutnya "baris yang mana". Ini persis `$_GET` yang kamu pelajari di P9.

---

## Bagian 2 — Delete: Menghapus Produk

Menghapus adalah operasi paling sederhana kodenya, tapi paling berisiko akibatnya. Karena itu ia butuh **dua lapis perlindungan**.

### File — `produk/hapus.php`

File ini tidak punya tampilan sama sekali. Ia bekerja, lalu langsung memindahkan pengguna.

```php
<?php
require_once '../config/koneksi.php';

$id = (int) ($_GET['id'] ?? 0);

// Guard: id tidak masuk akal
if ($id <= 0) {
    header("Location: index.php?notfound=1");
    exit;
}

// Pastikan produknya benar-benar ada sebelum dihapus
$ada = $conn->query("SELECT id FROM produk WHERE id = $id")->fetch_assoc();

if (!$ada) {
    header("Location: index.php?notfound=1");
    exit;
}

try {
    $conn->query("DELETE FROM produk WHERE id = $id");
    header("Location: index.php?dihapus=1");
} catch (mysqli_sql_exception $e) {
    header("Location: index.php?gagal=1");
}
exit;
```

### Tombol Hapus dengan Konfirmasi

```php
<a href="hapus.php?id=<?= $row['id'] ?>"
   class="btn btn-danger btn-sm"
   onclick="return confirm('Yakin hapus produk <?= htmlspecialchars($row['nama']) ?>?')">
  Hapus
</a>
```

`confirm()` adalah fungsi JavaScript bawaan browser. Kalau pengguna menekan **Batal**, `confirm()` mengembalikan `false`, dan `return false` membatalkan perpindahan halaman.

> ⚠️ **Konfirmasi JavaScript itu kenyamanan, bukan keamanan.** Ia bisa dilewati siapa saja yang langsung mengetik `hapus.php?id=3` di address bar. Itulah kenapa `hapus.php` tetap memeriksa sendiri apakah produknya ada. Prinsip yang sama dengan validasi form di P11: **browser untuk pengalaman, server untuk keamanan.**

### Memastikan Baris Benar-benar Terhapus

Objek `$conn` menyimpan berapa baris yang terpengaruh oleh query terakhir:

```php
$conn->query("DELETE FROM produk WHERE id = $id");

if ($conn->affected_rows === 0) {
    // Query berhasil, tapi tidak ada baris yang cocok
    header("Location: index.php?notfound=1");
    exit;
}
```

| Properti | Isinya |
|----------|--------|
| `$conn->affected_rows` | Jumlah baris yang berubah oleh INSERT/UPDATE/DELETE terakhir |
| `$conn->insert_id` | Nilai `id` dari baris yang baru saja di-INSERT |

> 💡 `affected_rows` berguna untuk membedakan "berhasil menghapus" dari "tidak ada yang dihapus" — dua hal yang sama-sama tidak menghasilkan error.

### Menampilkan Semua Pesan di `index.php`

Sekarang ada empat kemungkinan pesan. Rapikan jadi satu blok:

```php
<?php if (isset($_GET['sukses'])): ?>
  <div class="alert alert-success">Produk berhasil ditambahkan.</div>
<?php elseif (isset($_GET['diubah'])): ?>
  <div class="alert alert-success">Perubahan berhasil disimpan.</div>
<?php elseif (isset($_GET['dihapus'])): ?>
  <div class="alert alert-warning">Produk berhasil dihapus.</div>
<?php elseif (isset($_GET['notfound'])): ?>
  <div class="alert alert-danger">Produk tidak ditemukan.</div>
<?php elseif (isset($_GET['gagal'])): ?>
  <div class="alert alert-danger">Terjadi kesalahan. Coba lagi.</div>
<?php endif; ?>
```

> 💡 Cara ini sederhana dan cukup untuk sekarang, tapi punya kelemahan: siapa pun bisa memunculkan pesan "berhasil dihapus" palsu hanya dengan mengetik `?dihapus=1`. Di Pertemuan 15 kita ganti dengan **session flash message** yang tidak bisa dipalsukan.

---

## Bagian 3 — Search: Mencari Produk

Dengan 8 produk, mencari itu mudah. Dengan 800, daftar tanpa pencarian tidak ada gunanya.

### Kenapa `$_GET`, Bukan `$_POST`?

Pencarian memakai **GET**, dan ini keputusan yang disengaja:

| Alasan | Penjelasan |
|--------|------------|
| Hasil bisa dibagikan | URL `index.php?cari=laptop` bisa dikirim ke orang lain |
| Bisa di-bookmark | Pencarian yang sering dipakai bisa disimpan |
| Bisa di-refresh | F5 tidak memunculkan peringatan resubmission |
| Cocok dengan paginasi | Parameter halaman ikut menempel di URL yang sama |

Ini persis aturan praktis dari P9: **GET untuk membaca, POST untuk mengubah.**

### Form Pencarian

```php
<form method="GET" class="mb-4">
  <div class="input-group">
    <input type="text" name="cari" class="form-control"
           placeholder="Cari nama produk..."
           value="<?= htmlspecialchars($cari) ?>">
    <button type="submit" class="btn btn-dark">Cari</button>
    <?php if ($cari !== ''): ?>
      <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    <?php endif; ?>
  </div>
</form>
```

Dua detail kecil yang membuat perbedaan besar:

1. `value="<?= htmlspecialchars($cari) ?>"` — kata kunci tetap ada di kotak setelah pencarian. Tanpa ini, pengguna kehilangan konteks tentang apa yang sedang mereka lihat.
2. Tombol **Reset** hanya muncul kalau sedang ada pencarian aktif.

### Menyusun Query Pencarian

```php
$cari = trim($_GET['cari'] ?? '');

$where = "";
if ($cari !== '') {
    $where = "WHERE (p.nama LIKE '%$cari%' OR k.nama LIKE '%$cari%')";
}

$sql = "SELECT p.*, k.nama AS nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        $where
        ORDER BY p.id ASC";
```

Perhatikan tekniknya: variabel `$where` dibangun terpisah, lalu disisipkan ke query. Kalau tidak ada pencarian, `$where` berisi string kosong dan query berjalan normal.

> 💡 `LIKE '%$cari%'` persis seperti yang kamu pelajari di P10 — `%` berarti "boleh ada karakter apa saja di sini". Jadi mencari `mouse` juga menemukan "Mouse Wireless" dan "Mousepad XL".

### ⚠️ Coba Cari Kata yang Mengandung Tanda Petik

Setelah pencarianmu berjalan, coba ketik ini di kotak pencarian:

```
O'Brien
```

Halamanmu akan **mati** dengan pesan `Uncaught mysqli_sql_exception: You have an error in your SQL syntax`.

Kenapa? Karena tanda petik dari pengguna menutup petik di query-mu lebih awal:

```sql
WHERE (p.nama LIKE '%O'Brien%' ...)
                       ↑ petik ini menutup string terlalu cepat
```

Ini bukan sekadar bug tampilan. Ia adalah **gejala dari masalah yang jauh lebih serius**: pengguna bisa mengubah bentuk query-mu hanya dengan mengetik karakter tertentu. Kalau satu petik saja bisa merusak query, apa lagi yang bisa dilakukan orang yang sengaja?

> 📌 Untuk sekarang, catat saja gejalanya. Akar masalah dan solusinya dibahas tuntas di **Pertemuan 13** — dan setelah itu, mencari "O'Brien" akan berjalan normal seperti kata biasa.

### Mencari di Lebih dari Satu Kolom

Sering kali pengguna ingin mencari berdasarkan nama produk **atau** nama kategori:

```php
if ($cari !== '') {
    $where = "WHERE (p.nama LIKE '%$cari%' OR k.nama LIKE '%$cari%')";
}
```

> ⚠️ Tanda kurung di sekitar `OR` itu wajib. Ingat pelajaran P10: tanpa kurung, `AND` dieksekusi lebih dulu daripada `OR`, dan hasilnya bisa jauh dari yang kamu maksud. Nanti saat ada `WHERE` tambahan, kurung inilah yang menyelamatkanmu.

### Empty State yang Berbeda

Daftar kosong karena belum ada produk itu berbeda dengan daftar kosong karena pencarian tidak ketemu. Pesannya harus berbeda:

```php
<?php if (count($produkList) === 0): ?>
  <tr>
    <td colspan="6" class="text-center text-muted py-4">
      <?php if ($cari !== ''): ?>
        Tidak ada produk yang cocok dengan "<strong><?= htmlspecialchars($cari) ?></strong>".
        <br><a href="index.php">Tampilkan semua produk</a>
      <?php else: ?>
        Belum ada produk. <a href="tambah.php">Tambah sekarang</a>.
      <?php endif; ?>
    </td>
  </tr>
<?php endif; ?>
```

> 💡 Karena `fetch_all()` menghasilkan array biasa, kita bisa memakai `count()` — fungsi PHP yang sudah kamu kenal sejak P9. Tidak perlu lagi `num_rows`.

---

## Bagian 4 — Pagination: Membagi Halaman

Menampilkan 800 produk sekaligus membuat halaman berat dan sulit dibaca. Paginasi membaginya menjadi beberapa halaman.

### Rumusnya

Ingat `LIMIT` dan `OFFSET` dari P10? Inilah tempat pemakaiannya:

```
OFFSET = (nomor_halaman − 1) × jumlah_per_halaman
```

| Halaman | Perhitungan | `LIMIT 5 OFFSET ?` | Baris yang tampil |
|:-------:|-------------|:------------------:|-------------------|
| 1 | (1−1) × 5 | 0 | 1–5 |
| 2 | (2−1) × 5 | 5 | 6–10 |
| 3 | (3−1) × 5 | 10 | 11–15 |

### Empat Langkah Paginasi

```php
// [1] Tentukan halaman aktif dan jumlah per halaman
$perHalaman = 5;
$halaman    = (int) ($_GET['halaman'] ?? 1);
if ($halaman < 1) { $halaman = 1; }

// [2] Hitung TOTAL baris (tanpa LIMIT) — ini query terpisah
$sqlTotal  = "SELECT COUNT(*) AS total
              FROM produk p
              LEFT JOIN kategori k ON p.kategori_id = k.id
              $where";
$totalData = (int) $conn->query($sqlTotal)->fetch_assoc()['total'];

// [3] Hitung jumlah halaman dan offset
$totalHalaman = (int) ceil($totalData / $perHalaman);
if ($totalHalaman < 1) { $totalHalaman = 1; }
if ($halaman > $totalHalaman) { $halaman = $totalHalaman; }
$offset = ($halaman - 1) * $perHalaman;

// [4] Ambil data untuk halaman ini saja
$sql = "SELECT p.*, k.nama AS nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        $where
        ORDER BY p.id ASC
        LIMIT $perHalaman OFFSET $offset";
$produkList = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
```

> 💡 Perhatikan `->fetch_assoc()['total']` di langkah [2] — hasil `fetch_assoc()` adalah array, jadi bisa langsung diambil kuncinya di baris yang sama. Ini rangkaian yang sangat sering dipakai untuk query yang hasilnya cuma satu angka.

### Kenapa Butuh Dua Query?

Ini pertanyaan yang sering muncul, dan jawabannya penting untuk dipahami.

| Query | Tugasnya | Kenapa tidak bisa digabung |
|-------|----------|----------------------------|
| `COUNT(*)` | Menghitung **semua** baris yang cocok | Untuk tahu ada berapa halaman |
| `SELECT ... LIMIT` | Mengambil **5 baris** untuk halaman ini | Yang benar-benar ditampilkan |

Kalau kamu cuma menjalankan query kedua, kamu hanya tahu ada 5 baris di halaman ini — tapi tidak tahu apakah masih ada halaman 2, 3, atau 40 setelahnya. Informasi itu hanya bisa didapat dengan menghitung keseluruhan.

> 💡 `ceil()` membulatkan **ke atas**. 8 produk dibagi 5 per halaman = 1,6 → dibulatkan jadi **2 halaman**. Kalau memakai pembulatan biasa hasilnya 2 juga, tapi 11 produk ÷ 5 = 2,2 yang dibulatkan biasa jadi 2 — dan produk ke-11 tidak akan pernah bisa dilihat. Selalu `ceil()`.

### Tombol Navigasi Halaman

```php
<?php if ($totalHalaman > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">

      <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
        <a class="page-link" href="?halaman=<?= $halaman - 1 ?>&cari=<?= urlencode($cari) ?>">
          &laquo; Sebelumnya
        </a>
      </li>

      <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
        <li class="page-item <?= ($i === $halaman) ? 'active' : '' ?>">
          <a class="page-link" href="?halaman=<?= $i ?>&cari=<?= urlencode($cari) ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>

      <li class="page-item <?= ($halaman >= $totalHalaman) ? 'disabled' : '' ?>">
        <a class="page-link" href="?halaman=<?= $halaman + 1 ?>&cari=<?= urlencode($cari) ?>">
          Berikutnya &raquo;
        </a>
      </li>

    </ul>
  </nav>
<?php endif; ?>
```

| Detail | Kenapa penting |
|--------|----------------|
| `if ($totalHalaman > 1)` | Jangan tampilkan paginasi kalau cuma ada satu halaman |
| `class="active"` | Menandai halaman yang sedang dibuka |
| `class="disabled"` | Tombol Sebelumnya mati saat di halaman 1 |
| `&cari=<?= urlencode($cari) ?>` | **Membawa kata kunci pencarian** ke halaman berikutnya |

---

## Bagian 5 — Menggabungkan Search + Pagination

Di sinilah kesalahan paling sering terjadi. Dua fitur ini harus saling sadar.

### Kesalahan Klasik #1 — Kata Kunci Hilang saat Ganti Halaman

```php
<!-- ❌ SALAH: cari hilang, halaman 2 menampilkan SEMUA produk -->
<a href="?halaman=<?= $i ?>">

<!-- ✅ BENAR: cari ikut terbawa -->
<a href="?halaman=<?= $i ?>&cari=<?= urlencode($cari) ?>">
```

### Kesalahan Klasik #2 — Halaman Tidak Reset saat Mencari Baru

Bayangkan pengguna ada di halaman 5, lalu mengetik kata kunci baru. Kalau `halaman=5` ikut terbawa, hasil pencarian yang cuma 3 baris akan menampilkan halaman kosong.

Solusinya: form pencarian **tidak** mengirim parameter halaman sama sekali. Karena `<form method="GET">` mengganti seluruh query string, `halaman` otomatis hilang dan kembali ke 1.

### Kesalahan Klasik #3 — `COUNT(*)` Lupa Menyertakan `$where`

```php
// ❌ SALAH: totalnya 8 padahal hasil pencarian cuma 2
$sqlTotal = "SELECT COUNT(*) AS total FROM produk p";

// ✅ BENAR: sertakan $where yang sama
$sqlTotal = "SELECT COUNT(*) AS total
             FROM produk p
             LEFT JOIN kategori k ON p.kategori_id = k.id
             $where";
```

Gejalanya: paginasi menampilkan "halaman 1 2" padahal halaman 2 kosong.

> 💡 **Aturan praktis:** query `COUNT` dan query `SELECT` harus punya `FROM`, `JOIN`, dan `WHERE` yang **sama persis**. Yang berbeda cuma bagian `SELECT`-nya dan adanya `LIMIT`.

### `produk/index.php` Lengkap

```php
<?php
// ===== [1] SETUP =====
require_once '../config/koneksi.php';
$judul = "Daftar Produk";

// ===== [2] PARAMETER =====
$cari       = trim($_GET['cari'] ?? '');
$perHalaman = 5;
$halaman    = (int) ($_GET['halaman'] ?? 1);
if ($halaman < 1) { $halaman = 1; }

$where = "";
if ($cari !== '') {
    $where = "WHERE (p.nama LIKE '%$cari%' OR k.nama LIKE '%$cari%')";
}

// ===== [3] HITUNG TOTAL & HALAMAN =====
$sqlTotal  = "SELECT COUNT(*) AS total
              FROM produk p
              LEFT JOIN kategori k ON p.kategori_id = k.id
              $where";
$totalData = (int) $conn->query($sqlTotal)->fetch_assoc()['total'];

$totalHalaman = (int) ceil($totalData / $perHalaman);
if ($totalHalaman < 1) { $totalHalaman = 1; }
if ($halaman > $totalHalaman) { $halaman = $totalHalaman; }
$offset = ($halaman - 1) * $perHalaman;

// ===== [4] AMBIL DATA =====
$sql = "SELECT p.*, k.nama AS nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        $where
        ORDER BY p.id ASC
        LIMIT $perHalaman OFFSET $offset";
$produkList = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// ===== [5] TAMPILAN =====
require '../templates/header.php';
?>

<?php if (isset($_GET['sukses'])): ?>
  <div class="alert alert-success">Produk berhasil ditambahkan.</div>
<?php elseif (isset($_GET['diubah'])): ?>
  <div class="alert alert-success">Perubahan berhasil disimpan.</div>
<?php elseif (isset($_GET['dihapus'])): ?>
  <div class="alert alert-warning">Produk berhasil dihapus.</div>
<?php elseif (isset($_GET['notfound'])): ?>
  <div class="alert alert-danger">Produk tidak ditemukan.</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Daftar Produk</h3>
  <a href="tambah.php" class="btn btn-primary">+ Tambah Produk</a>
</div>

<form method="GET" class="mb-4">
  <div class="input-group">
    <input type="text" name="cari" class="form-control"
           placeholder="Cari nama produk atau kategori..."
           value="<?= htmlspecialchars($cari) ?>">
    <button type="submit" class="btn btn-dark">Cari</button>
    <?php if ($cari !== ''): ?>
      <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    <?php endif; ?>
  </div>
</form>

<p class="text-muted">
  Menampilkan <?= count($produkList) ?> dari <?= $totalData ?> produk
  <?php if ($cari !== ''): ?>
    untuk pencarian "<strong><?= htmlspecialchars($cari) ?></strong>"
  <?php endif; ?>
  — halaman <?= $halaman ?> dari <?= $totalHalaman ?>
</p>

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
          <th class="text-center" style="width: 150px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = $offset + 1; ?>
        <?php foreach ($produkList as $row): ?>
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
            <td class="text-center">
              <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <a href="hapus.php?id=<?= $row['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Yakin hapus produk <?= htmlspecialchars($row['nama']) ?>?')">
                Hapus
              </a>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (count($produkList) === 0): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              <?php if ($cari !== ''): ?>
                Tidak ada produk yang cocok dengan "<strong><?= htmlspecialchars($cari) ?></strong>".
                <br><a href="index.php">Tampilkan semua produk</a>
              <?php else: ?>
                Belum ada produk. <a href="tambah.php">Tambah sekarang</a>.
              <?php endif; ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalHalaman > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
        <a class="page-link" href="?halaman=<?= $halaman - 1 ?>&cari=<?= urlencode($cari) ?>">&laquo;</a>
      </li>
      <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
        <li class="page-item <?= ($i === $halaman) ? 'active' : '' ?>">
          <a class="page-link" href="?halaman=<?= $i ?>&cari=<?= urlencode($cari) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= ($halaman >= $totalHalaman) ? 'disabled' : '' ?>">
        <a class="page-link" href="?halaman=<?= $halaman + 1 ?>&cari=<?= urlencode($cari) ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php require '../templates/footer.php'; ?>
```

> 💡 `$no = $offset + 1;` — nomor urut ikut menyesuaikan halaman. Di halaman 2 nomornya mulai dari 6, bukan kembali ke 1.

---

## Latihan

### Latihan 1 — Update

Buat `produk/edit.php` lengkap, dan tambahkan tombol Edit di daftar produk.

**Kriteria berhasil:**

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Klik Edit pada produk mana pun | Form terbuka dengan isian sudah terisi |
| Ubah harga, klik Simpan | Kembali ke daftar, harga sudah berubah |
| Kosongkan nama, klik Simpan | Error muncul, isian lain tidak hilang |
| Buka `edit.php?id=999` | Dilempar ke daftar dengan pesan "tidak ditemukan" |
| Buka `edit.php?id=abc` | Tidak error, dilempar ke daftar |
| Dropdown kategori | Kategori produk saat ini sudah terpilih |

---

### Latihan 2 — Delete

Buat `produk/hapus.php` dan tombol Hapus dengan konfirmasi.

**Kriteria berhasil:** klik Hapus lalu Batal → tidak ada yang terhapus. Klik Hapus lalu OK → produk hilang dari daftar dan dari phpMyAdmin. Membuka `hapus.php?id=999` langsung tidak menyebabkan error.

> 🎯 **Uji pemahaman:** buka `hapus.php?id=2` langsung di address bar tanpa lewat tombol. Produknya tetap terhapus — kenapa? Apa artinya soal keandalan konfirmasi JavaScript?

---

### Latihan 3 — Search

Tambahkan pencarian ke `produk/index.php`.

**Kriteria berhasil:**

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Cari "mouse" | Muncul Mouse Wireless dan Mousepad XL |
| Cari "aksesoris" | Muncul semua produk kategori Aksesoris |
| Cari "zzz" | Pesan "tidak ada produk yang cocok" |
| Setelah mencari | Kata kunci masih ada di kotak, tombol Reset muncul |
| Salin URL hasil pencarian ke tab baru | Hasil yang sama muncul |
| **Cari `O'Brien`** | **Halaman mati dengan error SQL** — catat pesan errornya |

Uji terakhir sengaja dibuat gagal. Salin pesan errornya ke catatanmu, dan bawa ke pertemuan depan.

---

### Latihan 4 — Pagination

Ubah `$perHalaman` menjadi **3** supaya efeknya langsung terlihat dengan 8 produk.

```php
$perHalaman = 3;
```

**Kriteria berhasil:**

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Buka daftar | Muncul 3 produk, navigasi menampilkan halaman 1 2 3 |
| Klik halaman 2 | Muncul produk ke-4 sampai ke-6, nomor urut mulai dari 4 |
| Di halaman 1 | Tombol « mati (disabled) |
| Di halaman terakhir | Tombol » mati |
| Cari "mouse" lalu klik halaman 2 | Kata kunci **tidak hilang** |
| Cari "mouse" | Navigasi halaman menyesuaikan hasil pencarian, bukan total semua produk |

> 🎯 **Variasi (opsional):** tambahkan dropdown "Tampilkan 5 / 10 / 25 per halaman" yang juga memakai `$_GET`. Ingat: parameter ini juga harus ikut terbawa di link paginasi.

---

## 🎁 Bonus: Kenapa Delete Sebaiknya Pakai POST (Knowledge)

> ℹ️ Bagian ini **tidak wajib dikerjakan**. Bacalah sebagai bekal ke depan.

Kita memakai `hapus.php?id=3` lewat link biasa karena sederhana dan mudah dipahami. Tapi di aplikasi sungguhan, **operasi yang mengubah data sebaiknya tidak pernah memakai GET.**

### Tiga Alasannya

| Masalah | Penjelasan |
|---------|------------|
| **Bisa dipicu tanpa sengaja** | Bot pengindeks atau fitur *prefetch* browser bisa membuka semua link di halamanmu — termasuk semua link Hapus |
| **Ikut tersimpan di riwayat** | URL penghapusan tercatat di history dan log server |
| **Rawan CSRF** | Situs jahat bisa memasang `<img src="http://situsmu/hapus.php?id=1">` — begitu kamu membuka halaman mereka sambil login, produkmu terhapus |

### Bentuk yang Lebih Aman

```php
<form method="POST" action="hapus.php" class="d-inline"
      onsubmit="return confirm('Yakin hapus produk ini?')">
  <input type="hidden" name="id" value="<?= $row['id'] ?>">
  <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
</form>
```

```php
// hapus.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
```

Sekarang membuka `hapus.php` langsung di address bar tidak melakukan apa-apa, karena itu permintaan GET.

> 💡 Aturan umum di industri: **GET tidak boleh mengubah apa pun.** Kalau sebuah aksi mengubah, membuat, atau menghapus data — ia harus POST. Kamu akan melihat aturan ini ditegakkan ketat saat masuk Laravel di Pertemuan 19.

---

## Tugas Pertemuan

Lengkapi proyek `toko-sederhana` dengan ketentuan:

1. **Semua file** sudah memakai gaya object-oriented (`$conn->query()`, `->fetch_all(MYSQLI_ASSOC)`), termasuk `index.php` dan `tambah.php` dari P11
2. **CRUD produk utuh** — Create, Read, Update, Delete semuanya berfungsi
3. **Pencarian** minimal di dua kolom (nama produk dan nama kategori)
4. **Paginasi** dengan navigasi halaman, tombol disabled yang benar, dan nomor urut yang menyesuaikan halaman
5. Search dan paginasi **berjalan bersamaan** tanpa saling merusak
6. **Empty state berbeda** untuk "belum ada data" dan "pencarian tidak ketemu"
7. **Guard clause** di `edit.php` dan `hapus.php` untuk id yang tidak valid
8. Terapkan CRUD lengkap yang sama untuk **satu tabel lain** pilihanmu — `kategori`, `pelanggan`, atau `pesanan`
9. Catat pesan error dari uji `O'Brien` di Latihan 3 untuk dibawa ke P13

**Kumpulkan:**

- Folder proyek `.zip` + file `.sql` hasil Export
- Screenshot: daftar dengan paginasi, hasil pencarian, form edit terisi, konfirmasi hapus, empty state pencarian, dan **error saat mencari `O'Brien`**

> 📌 Mulai pertemuan depan kita **tidak menambah fitur baru** — kita memperbaiki yang sudah ada. Pastikan semua fitur di atas benar-benar berjalan sebelum masuk P13.

---

## Tips Penggunaan AI

**Memahami alur, bukan minta kode:**
> "Jelaskan kenapa paginasi butuh dua query terpisah (COUNT dan SELECT dengan LIMIT). Kenapa tidak bisa satu query saja?"

**Memahami gaya OOP:**
> "Saya baru berpindah dari mysqli_query() ke \$conn->query(). Jelaskan apa arti tanda -> dan kenapa gaya ini disebut object-oriented. Jangan berikan kode, cukup penjelasannya."

**Membedah bug:**
> "Paginasi saya menampilkan tombol halaman 1 dan 2, tapi halaman 2 selalu kosong padahal hasil pencarian cuma 2 baris. Ini kode saya: [tempel]. Bantu saya menemukan sendiri di mana logikanya salah, jangan langsung diperbaiki."

**Meminta skenario pengujian:**
> "Saya sudah membuat fitur search + pagination di PHP. Buatkan daftar skenario pengujian yang harus saya coba untuk memastikan keduanya tidak saling merusak. Berikan daftar ujinya saja, bukan kodenya."

> ⚠️ File `index.php`-mu sekarang cukup panjang dan menggabungkan lima hal sekaligus. Sangat menggoda untuk meminta AI merapikannya seluruhnya. Jangan — minggu depan kamu harus me-refactor file ini sendiri untuk keamanan, dan itu mustahil dilakukan pada kode yang tidak kamu pahami.

---

## Ringkasan

| Konsep | Poin Penting |
|--------|--------------|
| **Gaya OOP** | `new mysqli(...)`, `$conn->query()`, `$result->fetch_all(MYSQLI_ASSOC)` |
| **Error = exception** | Query gagal melempar `mysqli_sql_exception`, tidak lagi mengembalikan `false` |
| **`try` / `catch`** | Dipakai saat kegagalan perlu ditangani halus, misalnya saat menyimpan form |
| **`fetch_all` + `foreach`** | Hasilnya array biasa — sama bentuknya dengan array dummy P9 |
| **`fetch_assoc`** | Untuk satu baris saja |
| **Method chaining** | `$conn->query(...)->fetch_all(...)` — ringkas, sangat umum |
| **`count()`** | Menggantikan `num_rows` karena hasilnya sudah berupa array |
| **`affected_rows`** | Jumlah baris yang berubah oleh INSERT/UPDATE/DELETE |
| **Update** | Baca satu baris untuk isi form, lalu `UPDATE ... WHERE id = $id` |
| **Guard clause** | `if (!$produk) { redirect; exit; }` — tangani kondisi buruk sedini mungkin |
| **`(int)` pada `$_GET['id']`** | Mencegah id aneh merusak query |
| **`WHERE` di UPDATE & DELETE** | **Wajib.** Tanpa itu seluruh tabel terkena |
| **`confirm()`** | Kenyamanan saja — server tetap harus memeriksa sendiri |
| **Search** | Pakai `$_GET` supaya bisa dibagikan, di-bookmark, dan cocok dengan paginasi |
| **Paginasi** | `OFFSET = (halaman − 1) × perHalaman`, dan `ceil()` untuk jumlah halaman |
| **`$where` di COUNT** | Harus sama persis dengan query utama |
| **Bawa parameter** | `&cari=<?= urlencode($cari) ?>` di setiap link halaman |
| **⚠️ Mencari `O'Brien` merusak halaman** | Gejala dari masalah besar — dibedah di P13 |

> ➡️ **Pertemuan berikutnya (P13):** kita berhenti menambah fitur dan mulai **memperbaiki keamanan**. Kamu akan menyerang aplikasimu sendiri dengan SQL injection, melihat sendiri akibatnya, lalu me-refactor seluruh query memakai *prepared statement* — dan sekaligus memperbaiki bug `O'Brien` tadi.

---

## 📚 Referensi

- [MySQLi Class — php.net](https://www.php.net/manual/en/class.mysqli.php)
- [mysqli_result::fetch_all — php.net](https://www.php.net/manual/en/mysqli-result.fetch-all.php)
- [MySQL UPDATE Statement](https://dev.mysql.com/doc/refman/8.0/en/update.html)
- [Bootstrap 5 Pagination](https://getbootstrap.com/docs/5.3/components/pagination/)

---

*Dengan CRUD, pencarian, dan paginasi berjalan, aplikasimu sudah punya semua yang dimiliki aplikasi manajemen data sungguhan. Yang tersisa adalah membuatnya aman dan bisa dikunci — dan itu tiga pertemuan berikutnya. 🎉*
