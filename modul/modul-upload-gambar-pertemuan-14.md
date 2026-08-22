# Modul Pengajaran: Upload Gambar Produk
**Haltev IT Learning Center — Web Development**
**Pertemuan 14 | Tools: VS Code, XAMPP**

---

## Tujuan Pembelajaran

Siswa mampu menerima file dari pengguna, memvalidasinya dengan benar, menyimpannya di server, dan menampilkannya kembali di halaman. Di akhir pertemuan, setiap produk di `toko-sederhana` bisa punya gambar yang diunggah lewat form — lengkap dengan penanganan ganti gambar saat edit dan pembersihan file saat produk dihapus.

> 📌 **Upload file adalah fitur pertama yang menerima sesuatu selain teks dari pengguna.** Karena itu risikonya berbeda dan lebih besar: file yang salah bisa dijalankan sebagai program di servermu. Bagian validasi di modul ini bukan pelengkap — ia bagian terpenting.

---

## Daftar Isi

- [Recap Pertemuan 13](#recap-pertemuan-13)
- [Bagian 1 — Cara Kerja Upload di PHP](#bagian-1--cara-kerja-upload-di-php)
- [Bagian 2 — Menyiapkan Database & Folder](#bagian-2--menyiapkan-database--folder)
- [Bagian 3 — Form Upload](#bagian-3--form-upload)
- [Bagian 4 — Memproses Upload](#bagian-4--memproses-upload)
- [Bagian 5 — Validasi: Bagian Terpenting](#bagian-5--validasi-bagian-terpenting)
- [Bagian 6 — Menampilkan Gambar](#bagian-6--menampilkan-gambar)
- [Bagian 7 — Ganti Gambar saat Edit](#bagian-7--ganti-gambar-saat-edit)
- [Bagian 8 — Hapus Gambar saat Produk Dihapus](#bagian-8--hapus-gambar-saat-produk-dihapus)
- [Latihan](#latihan)
- [Tugas Pertemuan](#tugas-pertemuan)
- [Ringkasan](#ringkasan)

---

## Recap Pertemuan 13

Standar baru yang berlaku mulai sekarang:

| Aturan | Kenapa |
|--------|--------|
| Semua query pakai **prepared statement** | Mencegah SQL injection |
| Semua tampilan data pakai **`htmlspecialchars()`** | Mencegah XSS |
| Bagian struktur query pakai **whitelist** | `ORDER BY` tidak bisa di-bind |

Hari ini kita menambah fitur baru, dan **semua kode baru langsung ditulis dengan standar itu.** Tidak ada lagi "nanti diperbaiki".

Prinsip dari P13 yang akan terpakai lagi hari ini:

> **Whitelist: daftarkan apa yang diizinkan, bukan apa yang dilarang.**

---

## Bagian 1 — Cara Kerja Upload di PHP

### Perjalanan Sebuah File

```
  [1] Pengguna pilih file di form           → file masih di komputer mereka
  [2] Klik Simpan                           → file dikirim lewat HTTP
  [3] PHP menyimpannya di folder SEMENTARA  → nama acak, otomatis dihapus
  [4] Kode kamu memvalidasi                 → tipe, ukuran, isi
  [5] move_uploaded_file()                  → pindahkan ke folder permanen
  [6] Simpan NAMA FILE-nya ke database      → bukan filenya
```

> 💡 **Yang disimpan di database hanya nama filenya**, misalnya `produk_65f2a1.jpg`. Filenya sendiri ada di folder. Menyimpan file gambar langsung di database itu mungkin, tapi membuat database membengkak dan lambat — hampir tidak pernah dilakukan di aplikasi nyata.

### Dua Syarat pada Form

```html
<form method="POST" enctype="multipart/form-data">
```

| Syarat | Kalau lupa |
|--------|-----------|
| `method="POST"` | File tidak bisa dikirim lewat GET |
| `enctype="multipart/form-data"` | `$_FILES` **kosong**, tanpa pesan error apa pun |

> ⚠️ **Lupa `enctype` adalah kesalahan nomor satu saat pertama kali membuat fitur upload.** Gejalanya membingungkan: form terkirim, tidak ada error, tapi `$_FILES` kosong. Kalau uploadmu "tidak terjadi apa-apa", periksa ini lebih dulu.

### Struktur `$_FILES`

Kalau input-nya bernama `gambar`, PHP menyediakan:

```php
$_FILES['gambar']['name']      // "foto laptop.jpg"  — nama asli dari pengguna
$_FILES['gambar']['type']      // "image/jpeg"       — JANGAN DIPERCAYA (Bagian 5)
$_FILES['gambar']['tmp_name']  // "/tmp/phpA1B2C3"   — lokasi sementara di server
$_FILES['gambar']['size']      // 245678             — ukuran dalam byte
$_FILES['gambar']['error']     // 0                  — 0 berarti berhasil
```

### Kode Error yang Perlu Dikenali

| Kode | Konstanta | Artinya |
|:----:|-----------|---------|
| 0 | `UPLOAD_ERR_OK` | Berhasil |
| 1 | `UPLOAD_ERR_INI_SIZE` | Melebihi `upload_max_filesize` di `php.ini` |
| 2 | `UPLOAD_ERR_FORM_SIZE` | Melebihi batas di form |
| 3 | `UPLOAD_ERR_PARTIAL` | Terkirim sebagian — koneksi putus |
| 4 | `UPLOAD_ERR_NO_FILE` | Tidak ada file dipilih |

Kode **4** bukan error sungguhan — artinya pengguna memang tidak memilih file. Kalau gambar bersifat opsional, kode 4 harus diperlakukan sebagai kondisi normal.

### Batas Bawaan XAMPP

| Pengaturan di `php.ini` | Nilai default | Artinya |
|-------------------------|:-------------:|---------|
| `upload_max_filesize` | 2M | Maksimal 2 MB per file |
| `post_max_size` | 8M | Total seluruh data form |
| `max_file_uploads` | 20 | Maksimal file sekaligus |

Kalau perlu diubah: edit `C:\xampp\php\php.ini`, lalu **restart Apache**. Untuk pertemuan ini, 2 MB sudah cukup.

---

## Bagian 2 — Menyiapkan Database & Folder

### Menambah Kolom di Tabel Produk

Tabel `produk` belum punya tempat untuk menyimpan nama file gambar. Tambahkan lewat tab SQL phpMyAdmin:

```sql
ALTER TABLE produk ADD COLUMN gambar VARCHAR(255) NULL AFTER stok;
```

| Bagian | Artinya |
|--------|---------|
| `ALTER TABLE` | Mengubah struktur tabel yang sudah ada |
| `VARCHAR(255)` | Cukup untuk nama file mana pun |
| `NULL` | Boleh kosong — produk lama belum punya gambar |
| `AFTER stok` | Kolom baru diletakkan setelah kolom `stok` |

Periksa hasilnya:

```sql
DESCRIBE produk;
```

> 💡 `ALTER TABLE` adalah perintah SQL yang belum sempat dibahas di P10 karena jarang dipakai pemula. Tapi di dunia kerja ia sangat sering dipakai — setiap kali aplikasi butuh kolom baru, `ALTER TABLE`-lah yang dijalankan (di Laravel nanti ini disebut *migration*).

### Membuat Folder Penyimpanan

```
toko-sederhana/
├── config/
├── templates/
├── produk/
└── uploads/
    └── produk/        ← gambar disimpan di sini
```

Buat folder `uploads/produk/`. Pastikan folder ini **bisa ditulisi** — di Windows biasanya otomatis, di macOS/Linux mungkin perlu `chmod 755`.

---

## Bagian 3 — Form Upload

Tambahkan ke `produk/tambah.php`:

```html
<form method="POST" enctype="multipart/form-data">

  <!-- ...isian nama, harga, stok, kategori... -->

  <div class="mb-3">
    <label class="form-label">Gambar Produk</label>
    <input type="file" name="gambar" class="form-control" accept="image/*">
    <div class="form-text">Format: JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
  </div>

  <button type="submit" class="btn btn-primary">Simpan</button>
</form>
```

| Atribut | Gunanya |
|---------|---------|
| `type="file"` | Memunculkan tombol pilih file |
| `name="gambar"` | Kunci di `$_FILES['gambar']` |
| `accept="image/*"` | Menyaring pilihan di dialog file — **kenyamanan saja** |
| `form-text` | Memberi tahu batasan sebelum pengguna mencoba |

> ⚠️ `accept="image/*"` hanya menyaring tampilan dialog file. Pengguna tetap bisa memilih "All Files" dan mengunggah apa pun. **Validasi sesungguhnya tetap harus di PHP** — prinsip yang sama seperti `required` di P11.

---

## Bagian 4 — Memproses Upload

### Fungsi Bantu untuk Upload

Daripada menyalin logika yang sama di `tambah.php` dan `edit.php`, buat satu fungsi yang dipakai keduanya. Simpan di file baru `config/upload.php`:

```php
<?php
/**
 * Memproses upload gambar.
 * Mengembalikan array: ['status' => bool, 'nama' => string|null, 'error' => string|null]
 */
function uploadGambar($file, $folderTujuan)
{
    // Pengguna tidak memilih file — bukan error
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['status' => true, 'nama' => null, 'error' => null];
    }

    // Error lain dari PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'nama' => null,
                'error' => 'Gagal mengunggah file (kode ' . $file['error'] . ').'];
    }

    // --- Validasi ukuran ---
    $maksUkuran = 2 * 1024 * 1024;   // 2 MB dalam byte
    if ($file['size'] > $maksUkuran) {
        return ['status' => false, 'nama' => null,
                'error' => 'Ukuran gambar maksimal 2 MB.'];
    }

    // --- Validasi: benar-benar gambar? ---
    $info = getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['status' => false, 'nama' => null,
                'error' => 'File yang diunggah bukan gambar.'];
    }

    // --- Whitelist tipe gambar ---
    $tipeBoleh = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    if (!array_key_exists($info[2], $tipeBoleh)) {
        return ['status' => false, 'nama' => null,
                'error' => 'Format gambar harus JPG, PNG, atau WEBP.'];
    }

    $ekstensi = $tipeBoleh[$info[2]];

    // --- Buat nama file baru yang aman & unik ---
    $namaBaru = 'produk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi;

    // --- Pindahkan dari folder sementara ---
    if (!move_uploaded_file($file['tmp_name'], $folderTujuan . $namaBaru)) {
        return ['status' => false, 'nama' => null,
                'error' => 'Gagal menyimpan file. Periksa izin folder uploads.'];
    }

    return ['status' => true, 'nama' => $namaBaru, 'error' => null];
}
```

### Memakainya di `tambah.php`

```php
require_once '../config/koneksi.php';
require_once '../config/upload.php';

// ... di dalam blok if ($_SERVER['REQUEST_METHOD'] === 'POST') ...

$namaGambar = null;

$hasilUpload = uploadGambar($_FILES['gambar'] ?? null, '../uploads/produk/');

if (!$hasilUpload['status']) {
    $errors[] = $hasilUpload['error'];
} else {
    $namaGambar = $hasilUpload['nama'];   // null kalau tidak ada file dipilih
}

if (empty($errors)) {
    $sql  = "INSERT INTO produk (nama, harga, stok, gambar, kategori_id)
             VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "siisi", $nama, $harga, $stok, $namaGambar, $kategori_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: index.php?sukses=1");
        exit;
    }
    $errors[] = "Gagal menyimpan: " . mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
}
```

Perhatikan tipe bind berubah menjadi `"siisi"` — ada satu `s` tambahan untuk kolom `gambar`.

### Kenapa Nama File Diganti?

```php
$namaBaru = 'produk_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi;
// hasil: produk_20260815_143052_a3f9c1e8.jpg
```

| Masalah nama asli | Contoh | Akibat |
|-------------------|--------|--------|
| Bisa bertabrakan | Dua orang unggah `foto.jpg` | File pertama tertimpa |
| Bisa mengandung spasi & karakter aneh | `foto saya (1).jpg` | URL rusak |
| Bisa mengandung path traversal | `../../config/koneksi.php` | **Berbahaya** — bisa menimpa file lain |
| Bisa membocorkan informasi | `KTP-budi-santoso.jpg` | Privasi |

Mengganti nama file menyelesaikan keempatnya sekaligus.

> 💡 `random_bytes(4)` menghasilkan 4 byte acak, dan `bin2hex()` mengubahnya jadi 8 karakter heksadesimal. Digabung dengan tanggal dan jam, kemungkinan bertabrakan praktis nol.

---

## Bagian 5 — Validasi: Bagian Terpenting

Bagian ini yang membedakan fitur upload yang aman dari yang berbahaya.

### Jangan Percaya `$_FILES['type']`

Banyak tutorial di internet mengajarkan ini:

```php
// ❌ BERBAHAYA — jangan ditiru
if ($_FILES['gambar']['type'] === 'image/jpeg') { ... }
```

Masalahnya: `$_FILES['type']` **dikirim oleh browser**, bukan diperiksa oleh PHP. Siapa pun bisa mengubahnya. Penyerang bisa mengunggah file `virus.php` sambil menyatakan tipenya `image/jpeg`, dan pemeriksaan di atas akan meloloskannya.

### Jangan Percaya Ekstensi File Juga

```php
// ❌ Juga tidak cukup
$ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
if ($ext === 'jpg') { ... }
```

Ekstensi cuma bagian dari nama. File `virus.php` bisa dinamai `virus.jpg` dan tetap berisi kode PHP.

### Yang Benar: Periksa Isi Filenya

```php
// ✅ AMAN — getimagesize() benar-benar membaca isi file
$info = getimagesize($file['tmp_name']);
if ($info === false) {
    // Bukan gambar sama sekali
}
$tipeAsli = $info[2];   // konstanta IMAGETYPE_*
```

`getimagesize()` mencoba membaca file sebagai gambar. Kalau isinya bukan gambar, ia mengembalikan `false` — tidak peduli apa nama atau tipe yang diklaim.

| Cara memeriksa | Sumbernya | Bisa dipalsukan? |
|----------------|-----------|------------------|
| `$_FILES['type']` | Browser | ✅ Ya, sangat mudah |
| Ekstensi nama file | Nama yang diketik pengguna | ✅ Ya |
| `getimagesize()` | **Isi file sesungguhnya** | ❌ Sangat sulit |

### Ekstensi Ditentukan oleh Kita, Bukan Pengguna

Perhatikan kode di Bagian 4:

```php
$tipeBoleh = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_WEBP => 'webp',
];
$ekstensi = $tipeBoleh[$info[2]];
```

Ekstensi file baru diambil dari **daftar yang kita tentukan**, berdasarkan tipe asli hasil pemeriksaan isi. Nama file dari pengguna tidak dipakai sama sekali.

Ini prinsip **whitelist** yang sama dengan `ORDER BY` di P13 — muncul lagi dalam bentuk berbeda.

### Lapisan Terakhir: Matikan PHP di Folder Upload

Bahkan dengan semua validasi di atas, ada baiknya memasang satu pengaman terakhir. Buat file bernama `.htaccess` di dalam folder `uploads/`:

```apache
# uploads/.htaccess
php_flag engine off

<FilesMatch "\.(php|phtml|php3|php4|php5|phar)$">
    Require all denied
</FilesMatch>
```

Sekarang, seandainya ada file PHP yang lolos masuk ke folder itu, Apache **tetap menolak menjalankannya**.

> 💡 Ini disebut **defense in depth** — pertahanan berlapis. Kamu tidak mengandalkan satu perlindungan saja, karena setiap perlindungan bisa punya celah yang belum kamu sadari. Kalau satu lapis jebol, masih ada lapis berikutnya.

### Ringkasan Lapisan Perlindungan

| Lapis | Perlindungan | Melawan |
|:-----:|--------------|---------|
| 1 | `accept="image/*"` | Salah pilih (kenyamanan saja) |
| 2 | Batas ukuran | File raksasa yang membebani server |
| 3 | `getimagesize()` | File yang bukan gambar |
| 4 | Whitelist tipe | Format gambar aneh yang tidak diinginkan |
| 5 | Ganti nama file | Tabrakan nama, path traversal |
| 6 | `.htaccess` | File berbahaya yang lolos semua lapis di atas |

---

## Bagian 6 — Menampilkan Gambar

### Di Tabel Daftar Produk

Tambahkan kolom gambar di `produk/index.php`:

```php
<th style="width: 80px;">Gambar</th>
```

Lalu di dalam `while`:

```php
<td class="text-center">
  <?php if (!empty($row['gambar'])): ?>
    <img src="../uploads/produk/<?= htmlspecialchars($row['gambar']) ?>"
         alt="<?= htmlspecialchars($row['nama']) ?>"
         style="width:60px; height:60px; object-fit:cover;"
         class="rounded">
  <?php else: ?>
    <div class="rounded bg-secondary d-flex align-items-center justify-content-center text-white"
         style="width:60px; height:60px; font-size:1.2rem;">
      —
    </div>
  <?php endif; ?>
</td>
```

| Detail | Kenapa |
|--------|--------|
| `if (!empty(...))` | Produk lama belum punya gambar — jangan tampilkan `<img>` kosong |
| `object-fit: cover` | Gambar dipotong proporsional, tidak gepeng |
| `alt="..."` | Aksesibilitas, dan tampil kalau gambar gagal dimuat |
| `htmlspecialchars()` | Tetap wajib — kebiasaan dari P13 |

> 💡 **Placeholder itu penting.** Tanpa kotak abu-abu pengganti, baris tanpa gambar akan terlihat rusak dan tinggi barisnya tidak seragam.

### Di Kartu Etalase

```php
<div class="card h-100">
  <?php if (!empty($row['gambar'])): ?>
    <img src="uploads/produk/<?= htmlspecialchars($row['gambar']) ?>"
         class="card-img-top" style="height:180px; object-fit:cover;"
         alt="<?= htmlspecialchars($row['nama']) ?>">
  <?php else: ?>
    <div class="bg-secondary d-flex align-items-center justify-content-center text-white"
         style="height:180px;">Tanpa Gambar</div>
  <?php endif; ?>
  <div class="card-body">
    <!-- ...nama, harga, stok... -->
  </div>
</div>
```

> ⚠️ Perhatikan bedanya alamat gambar: `../uploads/...` dari dalam folder `produk/`, tapi `uploads/...` dari `index.php` di akar proyek. Salah satu kesalahan paling sering saat gambar "tidak muncul" adalah path yang salah — periksa lewat tab Network di DevTools, kalau statusnya 404 berarti pathnya keliru.

---

## Bagian 7 — Ganti Gambar saat Edit

Di halaman edit, ada tiga kemungkinan:

| Aksi pengguna | Yang harus terjadi |
|---------------|--------------------|
| Tidak memilih file baru | Gambar lama **tetap dipakai** |
| Memilih file baru | Gambar baru dipakai, **gambar lama dihapus** dari folder |
| Mencentang "Hapus gambar" | Gambar dihapus, kolom jadi `NULL` |

### Tampilan Gambar Saat Ini di Form Edit

```php
<div class="mb-3">
  <label class="form-label">Gambar Produk</label>

  <?php if (!empty($produk['gambar'])): ?>
    <div class="mb-2">
      <img src="../uploads/produk/<?= htmlspecialchars($produk['gambar']) ?>"
           style="width:120px; height:120px; object-fit:cover;" class="rounded border">
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="hapus_gambar" value="1" id="hapusGambar">
        <label class="form-check-label" for="hapusGambar">Hapus gambar ini</label>
      </div>
    </div>
  <?php endif; ?>

  <input type="file" name="gambar" class="form-control" accept="image/*">
  <div class="form-text">Kosongkan kalau tidak ingin mengubah gambar.</div>
</div>
```

### Logikanya di `edit.php`

```php
$folderUpload = '../uploads/produk/';
$gambarLama   = $produk['gambar'];      // dari query SELECT di awal file
$gambarBaru   = $gambarLama;            // default: tidak berubah
$hapusFileLama = false;

// Kasus 1: pengguna mencentang "hapus gambar"
if (isset($_POST['hapus_gambar'])) {
    $gambarBaru    = null;
    $hapusFileLama = true;
}

// Kasus 2: pengguna mengunggah file baru
$hasilUpload = uploadGambar($_FILES['gambar'] ?? null, $folderUpload);

if (!$hasilUpload['status']) {
    $errors[] = $hasilUpload['error'];
} elseif ($hasilUpload['nama'] !== null) {
    $gambarBaru    = $hasilUpload['nama'];
    $hapusFileLama = true;
}

if (empty($errors)) {
    $sql  = "UPDATE produk
             SET nama = ?, harga = ?, stok = ?, gambar = ?, kategori_id = ?
             WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "siisii", $nama, $harga, $stok, $gambarBaru, $kategori_id, $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // Hapus file lama SETELAH database berhasil diperbarui
        if ($hapusFileLama && !empty($gambarLama) && file_exists($folderUpload . $gambarLama)) {
            unlink($folderUpload . $gambarLama);
        }

        header("Location: index.php?diubah=1");
        exit;
    }
    $errors[] = "Gagal menyimpan: " . mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
}
```

### Urutan Itu Penting

> ⚠️ **Hapus file lama SETELAH database berhasil diperbarui, jangan sebelumnya.**

Kalau kamu menghapus file dulu lalu query-nya gagal, hasilnya: database masih menunjuk ke file yang sudah tidak ada. Produk jadi menampilkan gambar rusak, dan tidak ada cara mengembalikannya.

Urutan yang benar membuat kegagalan tetap aman: kalau query gagal, file lama masih utuh dan tidak ada yang rusak.

| Fungsi | Gunanya |
|--------|---------|
| `file_exists($path)` | Cek apakah file benar-benar ada sebelum dihapus |
| `unlink($path)` | Menghapus file dari disk |

> 💡 `unlink()` adalah nama yang aneh untuk "hapus file" — itu warisan dari sistem Unix. Ia menghapus permanen, tidak ada Recycle Bin. Karena itu `file_exists()` di depannya penting supaya tidak memunculkan peringatan saat filenya memang sudah tidak ada.

---

## Bagian 8 — Hapus Gambar saat Produk Dihapus

Kalau produk dihapus tapi gambarnya dibiarkan, folder `uploads/` akan penuh dengan file yatim yang tidak terpakai selamanya.

Perbarui `produk/hapus.php`:

```php
<?php
require_once '../config/koneksi.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?notfound=1");
    exit;
}

// Ambil nama gambar SEBELUM barisnya dihapus
$stmt = mysqli_prepare($conn, "SELECT gambar FROM produk WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$produk) {
    header("Location: index.php?notfound=1");
    exit;
}

// Hapus barisnya
$stmt = mysqli_prepare($conn, "DELETE FROM produk WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
$berhasil = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($berhasil) {
    // Baru hapus filenya
    $path = '../uploads/produk/' . $produk['gambar'];
    if (!empty($produk['gambar']) && file_exists($path)) {
        unlink($path);
    }
    header("Location: index.php?dihapus=1");
} else {
    header("Location: index.php?gagal=1");
}
exit;
```

Perhatikan urutannya sekali lagi: **ambil nama gambar → hapus baris → hapus file.** Nama gambar harus diambil lebih dulu, karena setelah barisnya hilang informasi itu tidak bisa didapat lagi.

---

## Latihan

### Latihan 1 — Upload Dasar

Siapkan kolom `gambar`, folder `uploads/produk/`, file `config/upload.php`, lalu tambahkan upload ke `produk/tambah.php`.

**Kriteria berhasil:**

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Tambah produk dengan gambar JPG | Tersimpan, file muncul di `uploads/produk/` dengan nama baru |
| Cek phpMyAdmin | Kolom `gambar` berisi nama file, bukan path lengkap |
| Tambah produk **tanpa** memilih gambar | Tetap tersimpan, kolom `gambar` bernilai NULL |
| Hapus `enctype` dari form, coba lagi | Upload gagal — dan kamu bisa menjelaskan kenapa |

---

### Latihan 2 — Menembus Validasimu Sendiri

Seperti di P13, cara terbaik memahami perlindungan adalah mencoba menembusnya.

Buat file bernama `jahat.php` berisi:

```php
<?php echo "File ini berhasil dijalankan di server!"; ?>
```

Lalu coba tiga hal ini:

| Percobaan | Hasil yang diharapkan | Yang menghentikannya |
|-----------|----------------------|----------------------|
| Unggah `jahat.php` langsung | Ditolak: "File yang diunggah bukan gambar" | `getimagesize()` |
| Ganti namanya jadi `jahat.jpg`, unggah | Tetap ditolak | `getimagesize()` membaca isi, bukan nama |
| Unggah file 5 MB | Ditolak: "maksimal 2 MB" | Pemeriksaan ukuran |

**Kriteria berhasil:** ketiganya ditolak, dan kamu bisa menjelaskan **lapis mana** yang menghentikan masing-masing.

> 🎯 **Uji lanjutan (opsional):** ganti sementara validasimu menjadi memakai `$_FILES['type']` saja, lalu buktikan bahwa file PHP bisa lolos. Kembalikan validasinya setelah selesai. Sekarang kamu tahu kenapa banyak tutorial di internet berbahaya untuk diikuti mentah-mentah.

---

### Latihan 3 — Tampilkan dan Ganti

Tambahkan kolom gambar di tabel daftar dan kartu etalase, lalu lengkapi `edit.php` dengan ketiga kasus (tetap, ganti, hapus).

**Kriteria berhasil:**

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Edit produk tanpa menyentuh input file | Gambar lama tetap ada |
| Edit dan pilih gambar baru | Gambar berganti, **file lama hilang** dari folder |
| Centang "Hapus gambar" lalu simpan | Kolom jadi NULL, file terhapus, placeholder muncul |
| Produk tanpa gambar | Kotak abu-abu tampil, tinggi baris tetap rapi |
| Hapus produk yang punya gambar | Baris **dan** filenya sama-sama hilang |

---

## Tugas Pertemuan

1. Fitur upload gambar berjalan penuh di `tambah.php`, `edit.php`, dan `hapus.php`
2. Validasi memakai `getimagesize()` + whitelist tipe, **bukan** `$_FILES['type']`
3. Nama file diganti dengan nama unik buatan sistem
4. File `.htaccess` terpasang di folder `uploads/`
5. Gambar tampil di tabel daftar dan kartu etalase, lengkap dengan placeholder
6. Tidak ada file yatim: mengganti atau menghapus produk juga membersihkan filenya
7. **Semua query baru memakai prepared statement** — standar P13 tetap berlaku
8. Tambahkan bagian baru di `LAPORAN-KEAMANAN.md`: hasil Latihan 2, dengan penjelasan lapis mana yang menghentikan tiap percobaan

**Kumpulkan:** folder proyek `.zip` (sertakan isi `uploads/`) + file `.sql` + screenshot daftar produk bergambar, form edit dengan pratinjau, dan pesan penolakan upload.

> 📌 **Pertemuan depan adalah yang terakhir sebelum Exam 2.** Pastikan seluruh fitur sampai P14 sudah berjalan, karena P15 menambahkan login yang akan mengunci semua halaman admin yang sudah kamu buat.

---

## Ringkasan

| Konsep | Poin Penting |
|--------|--------------|
| **`enctype`** | `multipart/form-data` wajib, kalau lupa `$_FILES` kosong tanpa error |
| **`$_FILES`** | `name`, `type`, `tmp_name`, `size`, `error` |
| **`error === 4`** | Bukan error — artinya pengguna tidak memilih file |
| **Yang disimpan di DB** | Nama filenya saja, bukan filenya |
| **`move_uploaded_file()`** | Memindahkan dari folder sementara ke folder permanen |
| **⚠️ `$_FILES['type']`** | Dikirim browser, **mudah dipalsukan** — jangan dipakai untuk validasi |
| **⚠️ Ekstensi nama file** | Juga tidak bisa dipercaya |
| **✅ `getimagesize()`** | Membaca isi file sesungguhnya — inilah validasi yang benar |
| **Whitelist tipe** | Ekstensi ditentukan sistem dari tipe asli, bukan dari nama pengguna |
| **Ganti nama file** | Mencegah tabrakan, path traversal, dan kebocoran informasi |
| **`.htaccess`** | Lapis terakhir — matikan PHP di folder upload |
| **Defense in depth** | Enam lapis perlindungan, jangan andalkan satu saja |
| **Placeholder** | Wajib untuk produk tanpa gambar agar tampilan tetap rapi |
| **Urutan saat ganti** | Update database **dulu**, baru `unlink()` file lama |
| **Urutan saat hapus** | Ambil nama gambar → hapus baris → hapus file |
| **`ALTER TABLE`** | Menambah kolom ke tabel yang sudah ada dan berisi data |

> ➡️ **Pertemuan berikutnya (P15):** **Session, Login & Proteksi Halaman.** Sampai sekarang siapa pun yang tahu alamatnya bisa menambah, mengubah, dan menghapus produkmu. Minggu depan kita kunci semuanya — dan ini pertemuan terakhir sebelum Exam 2.

---

## 📚 Referensi

- [PHP File Upload Handling — php.net](https://www.php.net/manual/en/features.file-upload.php)
- [getimagesize — php.net](https://www.php.net/manual/en/function.getimagesize.php)
- [move_uploaded_file — php.net](https://www.php.net/manual/en/function.move-uploaded-file.php)
- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)

---

*Upload file terlihat sederhana — pilih, kirim, simpan. Tapi ia adalah pintu paling lebar yang kamu buka untuk pengguna, dan karena itu paling sering jadi jalan masuk penyerang. Kalau kamu mengerjakan Latihan 2 dengan sungguh-sungguh, kamu sudah tahu lebih banyak soal ini daripada kebanyakan tutorial di internet. 🛡️*
