# Modul Pengajaran: Keamanan PHP & AI Audit
**Haltev IT Learning Center — Web Development**
**Pertemuan 13 | Tools: VS Code, XAMPP (PHP 8.1+), ChatGPT / Copilot**

---

## Tujuan Pembelajaran

Siswa memahami bagaimana serangan **SQL Injection** dan **XSS** bekerja dengan mempraktikkannya langsung pada aplikasinya sendiri, lalu mampu menutup celah tersebut menggunakan **prepared statement** dan pembersihan output. Di akhir pertemuan, seluruh file di proyek `toko-sederhana` sudah di-refactor menjadi aman, dan siswa mampu memakai AI sebagai alat bantu audit keamanan secara kritis.

> 📌 **Hari ini kita tidak menambah satu fitur pun.** Semua yang dikerjakan adalah memperbaiki kode yang sudah ada. Ini pekerjaan yang terasa "tidak menghasilkan apa-apa" — padahal justru inilah yang membedakan aplikasi latihan dari aplikasi yang layak dipakai orang.

---

## Daftar Isi

- [Recap: Janji dari P11 dan P12](#recap-janji-dari-p11-dan-p12)
- [Bagian 1 — Serang Aplikasimu Sendiri](#bagian-1--serang-aplikasimu-sendiri)
- [Bagian 2 — Kenapa Ini Bisa Terjadi](#bagian-2--kenapa-ini-bisa-terjadi)
- [Bagian 3 — Prepared Statement](#bagian-3--prepared-statement)
- [Bagian 4 — Refactor: tambah.php](#bagian-4--refactor-tambahphp)
- [Bagian 5 — Refactor: edit.php](#bagian-5--refactor-editphp)
- [Bagian 6 — Refactor: hapus.php](#bagian-6--refactor-hapusphp)
- [Bagian 7 — Refactor: index.php](#bagian-7--refactor-indexphp)
- [Bagian 8 — Yang Tidak Bisa Diganti Tanda Tanya](#bagian-8--yang-tidak-bisa-diganti-tanda-tanya)
- [Bagian 9 — XSS: Ancaman di Sisi Tampilan](#bagian-9--xss-ancaman-di-sisi-tampilan)
- [Bagian 10 — AI sebagai Auditor Keamanan](#bagian-10--ai-sebagai-auditor-keamanan)
- [Latihan](#latihan)
- [Tugas Pertemuan](#tugas-pertemuan)
- [Ringkasan](#ringkasan)

---

## Recap: Janji dari P11 dan P12

Dua pertemuan terakhir, modul ini berulang kali menutup bagian dengan kalimat yang sama:

> ⚠️ *Kode ini belum aman. Kita perbaiki di P13.*

Hari ini adalah P13. Mari kita tepati janjinya.

Inilah pola yang ada di hampir semua file-mu sekarang:

```php
$conn->query("INSERT INTO produk (nama, harga) VALUES ('$nama', $harga)");
$conn->query("UPDATE produk SET nama = '$nama' WHERE id = $id");
$conn->query("DELETE FROM produk WHERE id = $id");
$conn->query("SELECT ... WHERE p.nama LIKE '%$cari%'");
```

Semuanya punya masalah yang sama: **data dari pengguna ditempelkan langsung ke dalam perintah SQL.**

### Bug `O'Brien` dari Minggu Lalu

Di Latihan 3 P12 kamu diminta mencari `O'Brien`, dan halamanmu mati dengan pesan error SQL. Keluarkan catatan itu sekarang.

Bug itu bukan kasus khusus. Ia adalah **versi paling jinak** dari masalah yang akan kita bedah hari ini. Kalau satu tanda petik saja bisa merusak query-mu, bayangkan apa yang bisa dilakukan orang yang sengaja menyusun karakternya.

---

## Bagian 1 — Serang Aplikasimu Sendiri

Cara tercepat memahami sebuah celah keamanan adalah dengan memanfaatkannya sendiri. Semua percobaan di bawah dilakukan **di aplikasimu, di komputermu sendiri**.

> ⚠️ **Sebelum mulai: export dulu database-mu.** phpMyAdmin → pilih `toko_db` → tab Export → Go. Simpan filenya. Beberapa percobaan di bawah bisa merusak data.

### Percobaan 1 — Melihat Semua Data Lewat Pencarian

Buka halaman daftar produk, lalu ketik ini di kotak pencarian:

```
' OR '1'='1
```

Cari, lalu lihat hasilnya: **semua produk muncul**, padahal tidak ada satu pun yang namanya mengandung teks itu.

Sekarang lihat kenapa. Query-mu berbunyi:

```php
$where = "WHERE (p.nama LIKE '%$cari%' OR k.nama LIKE '%$cari%')";
```

Setelah `$cari` diganti isinya, yang benar-benar dikirim ke MySQL adalah:

```sql
WHERE (p.nama LIKE '%' OR '1'='1%' OR k.nama LIKE '%' OR '1'='1%')
```

Perhatikan bagian `p.nama LIKE '%'` — pola `%` cocok dengan **apa saja**. Filter pencarianmu jadi tidak berarti apa-apa, dan seluruh isi tabel keluar.

### Percobaan 2 — Membuat Query Rusak dengan Sengaja

Ketik ini di kotak pencarian:

```
' UNION SELECT 1,2,3,4,5,6,7 -- 
```

Kali ini halamanmu mati dengan pesan seperti:

```
Fatal error: Uncaught mysqli_sql_exception: The used SELECT statements have a
different number of columns in .../produk/index.php on line 34
```

Dua hal penting dari sini:

1. **Penyerang berhasil menempelkan perintahnya sendiri** ke query-mu. Kali ini gagal karena jumlah kolomnya tidak cocok — tapi penyerang tinggal mencoba 1, 2, 3, ... kolom sampai ketemu. Setelah cocok, ia bisa membaca tabel lain, termasuk tabel `users` beserta password-nya yang akan kamu buat di P15.

2. **Pesan errornya sendiri membocorkan informasi.** Ia menyebutkan nama file, nomor baris, dan struktur query-mu. Di aplikasi sungguhan, pesan error mentah **tidak boleh** ditampilkan ke pengunjung — ia jadi peta bagi penyerang.

> 💡 Di server produksi, `display_errors` dimatikan dan error hanya ditulis ke file log. Di komputermu sendiri ia sengaja dinyalakan supaya kamu bisa belajar. Ingat bedanya.

### Percobaan 3 — Mengubah Data Lewat URL

Buka alamat ini di browser:

```
http://localhost/toko-sederhana/produk/hapus.php?id=1 OR 1=1
```

Kalau di kode-mu ada `(int)` pada `$_GET['id']`, percobaan ini **gagal** — dan itu bagus. `(int) "1 OR 1=1"` menghasilkan `1`, jadi yang terhapus cuma satu produk.

Sekarang coba hapus sementara `(int)`-nya, lalu ulangi. Query yang terbentuk:

```sql
DELETE FROM produk WHERE id = 1 OR 1=1
```

`1=1` selalu benar, jadi **seluruh isi tabel produk terhapus.**

> 💡 Type casting ternyata sudah melindungimu di sebagian tempat. Tapi ia hanya bekerja untuk angka — untuk teks seperti `$nama` dan `$cari`, tidak ada perlindungan sama sekali. Kembalikan `(int)`-nya setelah percobaan ini.

### Percobaan 4 — Yang Paling Merusak

**Jangan jalankan ini.** Cukup pahami bentuknya. Kalau seseorang mengetik nama produk seperti ini di form tambah:

```
Laptop', 0, 0, NULL); DROP TABLE produk; -- 
```

...dan kode-mu berbunyi:

```php
$conn->query("INSERT INTO produk (nama, harga, stok, kategori_id) VALUES ('$nama', $harga, $stok, $kategoriSql)");
```

...maka MySQL menerima dua perintah: satu `INSERT`, lalu satu `DROP TABLE`.

> ℹ️ `$conn->query()` sebenarnya hanya menjalankan satu perintah per pemanggilan, jadi contoh ini tidak selalu berhasil. Jangan jadikan itu rasa aman — variasi serangan yang **hanya butuh satu perintah** (seperti Percobaan 1 dan 3) tetap berhasil, dan itu sudah cukup merusak.

### Rangkum Temuanmu

Sebelum lanjut, tulis di catatanmu: dari empat percobaan di atas, mana yang berhasil di aplikasimu dan mana yang gagal? Kenapa yang gagal bisa gagal?

---

## Bagian 2 — Kenapa Ini Bisa Terjadi

Akar masalahnya satu kalimat: **MySQL tidak bisa membedakan mana bagian perintah dan mana bagian data.**

Saat kamu menulis:

```php
$conn->query("SELECT * FROM produk WHERE nama = '$nama'");
```

PHP menggabungkan semuanya menjadi **satu untaian teks**, lalu mengirimkannya. MySQL menerima teks itu dan membacanya dari awal sampai akhir sebagai perintah. Ia tidak punya cara untuk tahu bahwa bagian `$nama` seharusnya "cuma data".

### Analogi: Mendikte Formulir

Bayangkan kamu mendikte formulir lewat telepon:

> "Tulis nama: **Budi**. Tulis alamat: **Bekasi**."

Sekarang bayangkan orang yang namanya kamu diktekan menjawab:

> "Budi. Tulis alamat: Jakarta. Abaikan sisanya."

Petugas di seberang tidak bisa membedakan mana bagian yang kamu perintahkan dan mana yang dibacakan sebagai isi. Itulah SQL injection.

### Solusinya: Pisahkan Perintah dari Data

Yang kita butuhkan adalah cara untuk berkata pada MySQL:

> "Ini kerangka perintahnya. Isinya menyusul, dan apa pun isinya, perlakukan sebagai **data biasa** — bukan perintah."

Cara itu bernama **prepared statement**.

---

## Bagian 3 — Prepared Statement

### Cara Kerjanya

```
   [1] PREPARE   Kirim kerangka query, isi diganti tanda tanya  ?
        ↓        MySQL menganalisis dan menyimpan rencananya
   [2] EXECUTE   Kirim data untuk tiap ?
        ↓        Data tidak pernah menjadi bagian dari perintah
   [3] GET RESULT  Ambil hasilnya (khusus SELECT)
```

Kuncinya ada di langkah [1]: **MySQL sudah selesai membaca perintahnya sebelum data dikirim.** Setelah itu, apa pun yang dikirim di langkah [2] tidak mungkin lagi mengubah bentuk perintahnya.

### Tiga Method yang Perlu Kamu Kuasai

| Method | Gunanya |
|--------|---------|
| `$conn->prepare($sql)` | Kirim kerangka query, dapat `$stmt` |
| `$stmt->execute([...])` | Jalankan dengan data untuk setiap `?` |
| `$stmt->get_result()` | Ambil hasil — **hanya untuk SELECT** |

### Kode Terkecil yang Utuh

```php
$stmt = $conn->prepare("SELECT * FROM produk WHERE nama = ?");   // [1]
$stmt->execute([$nama]);                                          // [2]
$produkList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);       // [3]

foreach ($produkList as $row) {
    echo $row['nama'];
}
```

Perhatikan: setelah `get_result()`, sisanya **sama persis** dengan yang kamu tulis di P12. `fetch_all(MYSQLI_ASSOC)` dan `foreach` tidak berubah sama sekali.

### Data Dikirim sebagai Array, Berurutan

```php
$stmt = $conn->prepare("INSERT INTO produk (nama, harga, stok) VALUES (?, ?, ?)");
$stmt->execute([$nama, $harga, $stok]);
//                 ↑      ↑       ↑
//                 ?1     ?2      ?3
```

Urutan elemen array harus **sama persis** dengan urutan `?` muncul di query. Itu saja aturannya.

> ⚠️ **Jumlah elemen array harus sama dengan jumlah `?`.** Kalau tidak, PHP melempar error `mysqli_sql_exception: Number of bind variables doesn't match number of fields in prepared statement`. Kalau kamu dapat error ini, hitung ulang `?`-nya.

### Tanda `?` Tidak Pakai Petik

Kesalahan paling sering saat pertama kali belajar:

```php
// ❌ SALAH — jangan beri petik pada ?
$stmt = $conn->prepare("SELECT * FROM produk WHERE nama = '?'");

// ✅ BENAR
$stmt = $conn->prepare("SELECT * FROM produk WHERE nama = ?");
```

Prepared statement sudah tahu bahwa `?` adalah tempat data. Memberi petik justru membuatnya dianggap teks tanda tanya biasa.

### Untuk INSERT / UPDATE / DELETE

Tidak ada hasil yang perlu diambil, jadi tidak perlu `get_result()`:

```php
$stmt = $conn->prepare("INSERT INTO produk (nama, harga, stok) VALUES (?, ?, ?)");
$stmt->execute([$nama, $harga, $stok]);

echo $stmt->affected_rows;   // berapa baris yang tersimpan
echo $conn->insert_id;       // id baris baru
```

### Kalau Query Gagal

Karena MySQLi memakai exception (lihat P12), kegagalan ditangani dengan `try` / `catch`:

```php
try {
    $stmt = $conn->prepare("INSERT INTO produk (nama, harga) VALUES (?, ?)");
    $stmt->execute([$nama, $harga]);

    header("Location: index.php?sukses=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $errors[] = "Gagal menyimpan: " . $e->getMessage();
}
```

> ℹ️ **Sekilas info:** kamu mungkin menemukan tutorial yang memakai `$stmt->bind_param("sii", $nama, $harga, $stok)` sebelum `execute()`. Itu cara lama yang masih berlaku, tapi mengharuskanmu menghitung huruf tipe (`s` string, `i` integer, `d` desimal) dan mencocokkannya dengan jumlah `?`. Sejak **PHP 8.1**, data bisa langsung dioper ke `execute()` sebagai array — lebih ringkas dan satu sumber kesalahan hilang. Kita pakai cara ini.

---

## Bagian 4 — Refactor: tambah.php

Sekarang kita perbaiki file demi file. Mulai dari yang paling sederhana.

### Sebelum

```php
$conn->query("INSERT INTO produk (nama, harga, stok, kategori_id)
              VALUES ('$nama', $harga, $stok, $kategoriSql)");

header("Location: index.php?sukses=1");
exit;
```

### Sesudah

```php
// kategori_id: null kalau tidak dipilih, angka kalau dipilih
$kategori_id = ($_POST['kategori_id'] ?? '') === '' ? null : (int) $_POST['kategori_id'];

try {
    $stmt = $conn->prepare(
        "INSERT INTO produk (nama, harga, stok, kategori_id) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$nama, $harga, $stok, $kategori_id]);

    header("Location: index.php?sukses=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $errors[] = "Gagal menyimpan: " . $e->getMessage();
}
```

### Yang Berubah dan Kenapa

| Sebelum | Sesudah | Alasan |
|---------|---------|--------|
| `'$nama'` | `?` + elemen array | Nama tidak bisa lagi menjadi perintah |
| `$kategoriSql = "NULL"` (teks) | `$kategori_id = null` (nilai PHP) | `execute()` mengirim NULL sungguhan kalau nilainya `null` |
| `$conn->query()` | `prepare()` + `execute()` | Perintah dan data dikirim terpisah |

> 💡 Perhatikan bahwa `$kategoriSql` yang berisi teks `"NULL"` sudah tidak dibutuhkan lagi. Dengan prepared statement kamu memakai nilai `null` PHP yang sesungguhnya, dan itu jauh lebih bersih.

### Query Kategori untuk Dropdown

```php
$kategoriList = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC")
                     ->fetch_all(MYSQLI_ASSOC);
```

Query ini **tidak punya input dari pengguna sama sekali**, jadi secara teknis sudah aman dan boleh tetap memakai `query()`. Aturannya sederhana:

> **Kalau ada satu saja nilai yang datang dari pengguna, pakai prepared statement. Kalau tidak ada, `query()` biasa sudah cukup.**

---

## Bagian 5 — Refactor: edit.php

File ini punya **dua** query yang perlu diperbaiki: `SELECT` untuk mengisi form, dan `UPDATE` untuk menyimpan.

### SELECT — Mengambil Data Produk

```php
$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->execute([$id]);
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) {
    header("Location: index.php?notfound=1");
    exit;
}
```

Perhatikan `->fetch_assoc()` — bukan `fetch_all()` — karena kita cuma mengambil satu baris.

### UPDATE — Menyimpan Perubahan

```php
try {
    $stmt = $conn->prepare(
        "UPDATE produk
         SET nama = ?, harga = ?, stok = ?, kategori_id = ?
         WHERE id = ?"
    );
    $stmt->execute([$nama, $harga, $stok, $kategori_id, $id]);

    header("Location: index.php?diubah=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $errors[] = "Gagal menyimpan: " . $e->getMessage();
}
```

### Menghitung Urutan dengan Benar

```
UPDATE produk SET nama = ?, harga = ?, stok = ?, kategori_id = ? WHERE id = ?
                         1          2         3               4           5

$stmt->execute([$nama, $harga, $stok, $kategori_id, $id]);
                  1      2       3          4        5
```

Urutkan dari kiri ke kanan **sesuai urutan `?` muncul di query**, termasuk yang ada di `WHERE`. Ini kesalahan yang sering terjadi: `$id` di `WHERE` sering terlupa, sehingga jumlah elemen kurang satu.

> 💡 Cara aman: hitung dulu jumlah `?` di query. Kalau ada 5, elemen array-nya juga harus 5. Cocokkan satu per satu sambil menunjuk layar.

---

## Bagian 6 — Refactor: hapus.php

```php
<?php
require_once '../config/koneksi.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php?notfound=1");
    exit;
}

// Pastikan produknya ada
$stmt = $conn->prepare("SELECT id FROM produk WHERE id = ?");
$stmt->execute([$id]);
$ada = $stmt->get_result()->fetch_assoc();

if (!$ada) {
    header("Location: index.php?notfound=1");
    exit;
}

// Hapus
try {
    $stmt = $conn->prepare("DELETE FROM produk WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: index.php?dihapus=1");
} catch (mysqli_sql_exception $e) {
    header("Location: index.php?gagal=1");
}
exit;
```

Sekarang coba ulangi **Percobaan 3** dari Bagian 1 — bahkan tanpa `(int)` sekalipun, `hapus.php?id=1 OR 1=1` tidak lagi menghapus seluruh tabel. Teks `"1 OR 1=1"` dikirim sebagai **data**, dan tidak ada produk yang id-nya berupa teks itu.

> 💡 Tetap pertahankan `(int)`-nya. Prepared statement dan type casting saling melengkapi — yang satu mencegah injeksi, yang lain memastikan tipe datanya masuk akal.

---

## Bagian 7 — Refactor: index.php

Ini file tersulit, karena querynya punya bagian yang berubah-ubah: pencarian dan paginasi.

### Trik untuk Pencarian yang Bisa Kosong

Sebelumnya kamu membangun `$where` secara kondisional. Dengan prepared statement itu merepotkan, karena jumlah `?` jadi berubah-ubah.

Ada trik yang jauh lebih rapi: **selalu pakai `LIKE`, dan biarkan kata kuncinya menjadi `%%` saat pencarian kosong.**

```php
$cari    = trim($_GET['cari'] ?? '');
$keyword = '%' . $cari . '%';       // kalau $cari kosong → '%%'
```

`LIKE '%%'` cocok dengan **semua** teks. Jadi query-nya sekarang selalu berbentuk sama, dan jumlah `?`-nya tetap.

> 💡 **Tanda `%` sekarang ditulis di PHP, bukan di SQL.** Ini penting: `LIKE ?` dengan data `%mouse%` itu benar, sedangkan `LIKE '%?%'` itu salah — `?` di dalam petik tidak dianggap tempat data.

### Query COUNT

```php
$sqlTotal = "SELECT COUNT(*) AS total
             FROM produk p
             LEFT JOIN kategori k ON p.kategori_id = k.id
             WHERE (p.nama LIKE ? OR k.nama LIKE ?)";

$stmt = $conn->prepare($sqlTotal);
$stmt->execute([$keyword, $keyword]);
$totalData = (int) $stmt->get_result()->fetch_assoc()['total'];
```

### Query Utama

```php
$sql = "SELECT p.*, k.nama AS nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        WHERE (p.nama LIKE ? OR k.nama LIKE ?)
        ORDER BY p.id ASC
        LIMIT $perHalaman OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute([$keyword, $keyword]);
$produkList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
```

### ⚠️ Kenapa LIMIT dan OFFSET Tidak Pakai `?`

Kamu pasti memperhatikan bahwa `$perHalaman` dan `$offset` masih disisipkan langsung ke string. Ini **disengaja**, dan ada dua alasannya.

**Alasan pertama — teknis.** Data yang dioper lewat `execute([...])` dikirim sebagai teks. MySQL menolak teks di posisi `LIMIT`, sehingga `LIMIT ? OFFSET ?` sering gagal dengan error sintaks.

**Alasan kedua — dan ini yang lebih penting — kedua nilai itu memang tidak berasal dari pengguna:**

```php
$perHalaman = 5;                                    // kita yang menentukan
$halaman    = (int) ($_GET['halaman'] ?? 1);        // dipaksa jadi integer
if ($halaman < 1) { $halaman = 1; }                 // dibatasi minimal 1
if ($halaman > $totalHalaman) { $halaman = $totalHalaman; }
$offset = ($halaman - 1) * $perHalaman;             // hasil hitungan, pasti integer
```

Setelah melewati `(int)` dan dua pembatasan itu, `$halaman` **dijamin berupa angka** — tidak mungkin lagi berisi teks apa pun. Nilai yang kita jamin sendiri tipenya aman untuk disisipkan.

> 📌 **Inilah aturan sesungguhnya, dan ia lebih tepat daripada "jangan pernah ada `$` di dalam SQL":**
>
> Prepared statement melindungi **data yang datang dari luar**. Untuk nilai yang **kamu jamin sendiri bentuknya** — lewat `(int)`, pembatasan rentang, atau daftar putih — menyisipkannya langsung itu sah. Yang tidak boleh adalah menyisipkan sesuatu yang bentuknya ditentukan pengguna.

### Bagian [1]–[4] `index.php` Setelah Refactor

```php
<?php
// ===== [1] SETUP =====
require_once '../config/koneksi.php';
$judul = "Daftar Produk";

// ===== [2] PARAMETER =====
$cari       = trim($_GET['cari'] ?? '');
$keyword    = '%' . $cari . '%';
$perHalaman = 5;
$halaman    = (int) ($_GET['halaman'] ?? 1);
if ($halaman < 1) { $halaman = 1; }

// ===== [3] HITUNG TOTAL =====
$sqlTotal = "SELECT COUNT(*) AS total
             FROM produk p
             LEFT JOIN kategori k ON p.kategori_id = k.id
             WHERE (p.nama LIKE ? OR k.nama LIKE ?)";

$stmt = $conn->prepare($sqlTotal);
$stmt->execute([$keyword, $keyword]);
$totalData = (int) $stmt->get_result()->fetch_assoc()['total'];

$totalHalaman = (int) ceil($totalData / $perHalaman);
if ($totalHalaman < 1) { $totalHalaman = 1; }
if ($halaman > $totalHalaman) { $halaman = $totalHalaman; }
$offset = ($halaman - 1) * $perHalaman;

// ===== [4] AMBIL DATA =====
$sql = "SELECT p.*, k.nama AS nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        WHERE (p.nama LIKE ? OR k.nama LIKE ?)
        ORDER BY p.id ASC
        LIMIT $perHalaman OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute([$keyword, $keyword]);
$produkList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ===== [5] TAMPILAN =====
require '../templates/header.php';
```

**Seluruh bagian HTML di bawahnya tidak berubah sama sekali.** `foreach ($produkList as $row)` tetap sama, tabelnya tetap sama, paginasinya tetap sama.

### Uji Sekarang Juga

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Cari `' OR '1'='1` | **0 produk** — teks itu kini dicari sebagai kata biasa |
| Cari `O'Brien` | **Tidak error lagi** — 0 produk, halaman normal |
| Cari `mouse` | Tetap menemukan Mouse Wireless dan Mousepad XL |
| Paginasi dan tombol halaman | Masih berjalan seperti biasa |

Bug `O'Brien` dari P12 hilang sebagai efek samping. Itu bukan kebetulan — ia dan SQL injection adalah masalah yang sama persis, cuma beda niat.

---

## Bagian 8 — Yang Tidak Bisa Diganti Tanda Tanya

Prepared statement melindungi **data**, tapi tidak bisa dipakai untuk **bagian struktur query**.

```php
// ❌ TIDAK BISA — ini bukan data, ini nama kolom
$conn->prepare("SELECT * FROM produk ORDER BY ? ASC");

// ❌ TIDAK BISA — nama tabel juga struktur
$conn->prepare("SELECT * FROM ?");
```

Kalau kamu ingin membuat fitur "urutkan berdasarkan", satu-satunya cara aman adalah **whitelist**: daftar nilai yang boleh, dan tolak semua yang lain.

```php
// Daftar kolom yang BOLEH dipakai untuk mengurutkan
$kolomBoleh = ['id', 'nama', 'harga', 'stok'];
$arahBoleh  = ['ASC', 'DESC'];

$urut = $_GET['urut'] ?? 'id';
$arah = strtoupper($_GET['arah'] ?? 'ASC');

// Kalau tidak ada di daftar, paksa kembali ke nilai aman
if (!in_array($urut, $kolomBoleh, true)) { $urut = 'id'; }
if (!in_array($arah, $arahBoleh, true))  { $arah = 'ASC'; }

$sql = "SELECT ... ORDER BY p.$urut $arah LIMIT $perHalaman OFFSET $offset";
```

Sekarang `?urut=harga` berfungsi, sedangkan `?urut=nama; DROP TABLE produk` diabaikan dan dikembalikan ke `id`.

> 💡 **Prinsip whitelist:** jangan mendaftar apa yang dilarang (daftarnya tak terbatas), tapi daftarkan apa yang diizinkan (daftarnya pendek dan kamu yang menentukan). Prinsip ini berlaku di mana-mana dalam keamanan, bukan cuma SQL.

> ⚠️ Perhatikan `in_array($urut, $kolomBoleh, true)` — argumen ketiga `true` membuat perbandingannya ketat (`===`). Tanpa itu, perbandingan longgar bisa memberi hasil yang mengejutkan. Biasakan selalu menulis `true`.

### Tiga Cara Mengamankan, Sesuai Jenisnya

| Yang diamankan | Contoh | Caranya |
|----------------|--------|---------|
| **Data** dari pengguna | nama, harga, kata kunci | Prepared statement (`?`) |
| **Angka** yang kamu jamin | `LIMIT`, `OFFSET`, `id` | `(int)` + pembatasan rentang |
| **Struktur** query | `ORDER BY`, nama kolom | Whitelist (`in_array`) |

---

## Bagian 9 — XSS: Ancaman di Sisi Tampilan

SQL injection menyerang **database**. XSS (*Cross-Site Scripting*) menyerang **pengunjung** halamanmu.

### Cara Kerjanya

Tambahkan produk baru dengan nama berikut, lalu buka daftar produk:

```
<script>alert('Kena XSS!')</script>
```

Kalau kode tampilanmu berbunyi `<?= $row['nama'] ?>` tanpa perlindungan, browser akan **menjalankan** kode itu.

Sekarang bayangkan penyerang tidak menulis `alert()`, tapi kode yang diam-diam mengirim data login pengunjung ke server mereka. Setiap orang yang membuka halamanmu jadi korban.

> 💡 Perhatikan: prepared statement **tidak** mencegah ini. Nama produk berisi `<script>` tersimpan dengan aman ke database — memang itu tugasnya. Bahayanya baru muncul saat data itu **ditampilkan**.

### Perlindungannya

```php
// ❌ Berbahaya
<?= $row['nama'] ?>

// ✅ Aman
<?= htmlspecialchars($row['nama']) ?>
```

`htmlspecialchars()` mengubah karakter berbahaya menjadi teks biasa:

| Sebelum | Sesudah | Tampil di layar sebagai |
|---------|---------|-------------------------|
| `<` | `&lt;` | `<` |
| `>` | `&gt;` | `>` |
| `"` | `&quot;` | `"` |
| `&` | `&amp;` | `&` |

Tag `<script>` jadi tampil sebagai teks, bukan dijalankan.

### Kabar Baiknya

Kalau kamu mengikuti modul P11 dan P12 dengan benar, **kode tampilanmu sudah aman** — kita memakai `htmlspecialchars()` sejak awal. Bagian ini adalah pemeriksaan ulang, bukan pekerjaan baru.

### Di Mana Saja Harus Dipakai

| Tempat | Contoh |
|--------|--------|
| Isi elemen | `<td><?= htmlspecialchars($row['nama']) ?></td>` |
| Atribut HTML | `value="<?= htmlspecialchars($produk['nama']) ?>"` |
| Di dalam JavaScript | `confirm('Hapus <?= htmlspecialchars($row['nama']) ?>?')` |

> ⚠️ **Jangan pakai `htmlspecialchars()` saat menyimpan ke database.** Simpan data apa adanya, bersihkan saat **menampilkan**. Kalau dibersihkan saat menyimpan, datamu jadi rusak permanen — nama "Toko A & B" akan tersimpan sebagai "Toko A &amp; B" dan muncul salah di tempat lain, misalnya saat diekspor ke Excel.

### Aturan Emas Keamanan Web

| Arah data | Perlindungan | Melawan |
|-----------|--------------|---------|
| Pengguna → Database | Prepared statement | SQL Injection |
| Database → Halaman | `htmlspecialchars()` | XSS |

Dua-duanya wajib. Yang satu tidak menggantikan yang lain.

---

## Bagian 10 — AI sebagai Auditor Keamanan

AI sangat berguna untuk menemukan celah keamanan — asalkan kamu tahu cara memakainya dan tahu batasnya.

### Prompt Audit yang Efektif

**Audit menyeluruh satu file:**
> "Bertindaklah sebagai security reviewer. Periksa kode PHP berikut untuk celah SQL injection, XSS, dan validasi input yang lemah. Untuk setiap temuan: sebutkan nomor barisnya, jelaskan bagaimana celah itu bisa dimanfaatkan, dan beri tingkat keparahannya. **Jangan tuliskan kode perbaikannya** — saya ingin memperbaikinya sendiri. [tempel kode]"

Bagian "jangan tuliskan perbaikannya" itu penting. Kalau AI langsung memberi versi jadi, kamu belajar jauh lebih sedikit.

**Meminta bukti, bukan klaim:**
> "Kamu bilang baris 24 rentan SQL injection. Tunjukkan input konkret yang bisa dimasukkan pengguna untuk memanfaatkannya, dan tuliskan query akhir yang akan diterima MySQL."

Ini memaksa AI membuktikan temuannya, dan sekaligus mengajarimu cara berpikir seperti penyerang.

**Memeriksa hasil refactor-mu:**
> "Saya sudah mengubah kode ini ke prepared statement gaya MySQLi OOP: [tempel]. Periksa apakah masih ada celah tersisa, dan apakah jumlah serta urutan elemen di execute() sudah cocok dengan jumlah tanda tanya."

**Menguji alasanmu:**
> "Di file ini saya sengaja menyisipkan \$offset langsung ke dalam string SQL, bukan memakai tanda tanya, karena nilainya sudah saya paksa jadi integer. Apakah alasan saya benar? Kalau ada kondisi di mana ini tetap berbahaya, tunjukkan."

Prompt terakhir itu contoh pemakaian AI yang paling berguna: **menguji penalaranmu sendiri**, bukan meminta jawaban.

### Batas AI yang Harus Kamu Sadari

| Batas | Akibatnya | Yang harus kamu lakukan |
|-------|-----------|-------------------------|
| **Hanya melihat yang kamu tempel** | Celah di file lain tidak terdeteksi | Audit file per file, jangan cuma satu |
| **Bisa melewatkan celah** | "Kode ini aman" belum tentu benar | Jangan jadikan satu-satunya pemeriksaan |
| **Bisa memberi alarm palsu** | Kamu memperbaiki yang tidak rusak | Minta bukti sebelum percaya |
| **Tidak tahu konteks bisnismu** | Tidak sadar bahwa suatu data itu sensitif | Kamu yang menentukan mana yang penting |

> ⚠️ **Kesalahan berpikir yang paling berbahaya:** "AI bilang kodenya aman, berarti aman." AI adalah pemeriksa tambahan yang cepat dan murah, bukan jaminan. Pemahamanmu sendiri tetap lapisan pertahanan utama — dan itulah kenapa modul ini menyuruhmu menyerang aplikasimu sendiri di Bagian 1 sebelum menyentuh AI sama sekali.

---

## Latihan

### Latihan 1 — Serang, Catat, Pahami

Jalankan keempat percobaan di Bagian 1 (kecuali Percobaan 4 — cukup dipahami). Buat tabel catatan:

| Percobaan | Berhasil? | Kalau gagal, kenapa? | Query akhir yang terbentuk |
|-----------|-----------|----------------------|----------------------------|
| 1 — `' OR '1'='1` | | | |
| 2 — `UNION SELECT` | | | |
| 3 — `id=1 OR 1=1` (dengan `(int)`) | | | |
| 3b — `id=1 OR 1=1` (tanpa `(int)`) | | | |

**Kriteria berhasil:** kamu bisa menuliskan query akhir yang terbentuk untuk setiap percobaan, dan menjelaskan kenapa Percobaan 3 gagal saat `(int)` dipasang.

---

### Latihan 2 — Refactor Seluruh Proyek

Ubah semua query yang menerima input pengguna menjadi prepared statement.

| File | Query yang harus diubah |
|------|------------------------|
| `produk/index.php` | COUNT + SELECT utama |
| `produk/tambah.php` | INSERT |
| `produk/edit.php` | SELECT produk + UPDATE |
| `produk/hapus.php` | SELECT pemeriksaan + DELETE |
| `index.php` (etalase) | SELECT produk kalau ada filter |

**Kriteria berhasil:** di seluruh proyekmu, tidak ada lagi variabel yang **berasal dari pengguna** disisipkan ke string SQL.

Cara memeriksanya di VS Code — tekan `Ctrl+Shift+F` lalu cari:

```
'$
```

Setiap hasil yang muncul di dalam query harus bisa kamu jelaskan. Hanya tiga jenis yang boleh tersisa:

| Boleh tersisa | Kenapa |
|---------------|--------|
| `$perHalaman`, `$offset` | Angka yang kamu jamin sendiri lewat `(int)` |
| `$urut`, `$arah` | Sudah lewat whitelist |
| `$where` yang isinya tetap | Tidak mengandung nilai dari pengguna |

---

### Latihan 3 — Uji Ulang Setelah Refactor

| Uji | Hasil yang diharapkan |
|-----|----------------------|
| Cari `' OR '1'='1` | 0 produk ditemukan, tidak error |
| Cari `O'Brien` | **Berjalan normal**, 0 produk, tidak ada error SQL |
| Cari `%` | 0 produk — `%%%%` mencari teks `%` secara harfiah |
| `hapus.php?id=1 OR 1=1` | Tidak ada yang terhapus |
| Tambah produk bernama `<script>alert(1)</script>` | Tersimpan, tampil sebagai **teks biasa** |
| Tambah produk bernama `Toko A & B` | Tampil sebagai `Toko A & B`, bukan `Toko A &amp; B` |
| Semua fitur normal (CRUD, cari, paginasi) | Masih berjalan seperti sebelumnya |

Uji terakhir itu yang paling penting: **refactor keamanan tidak boleh merusak fitur.**

---

### Latihan 4 — Audit dengan AI

Pilih satu file yang **sudah** kamu refactor, lalu minta AI mengauditnya dengan prompt dari Bagian 10 — termasuk prompt "menguji alasanmu" soal `$offset`.

Tulis laporan singkat:

1. Apa saja yang ditemukan AI?
2. Mana yang benar-benar masalah, mana yang alarm palsu? Jelaskan alasanmu.
3. Bagaimana tanggapan AI soal `$offset` yang disisipkan langsung? Apakah kamu setuju?
4. Adakah yang AI lewatkan, yang kamu sendiri sadari?

**Kriteria berhasil:** kamu **tidak menerima** semua temuan AI mentah-mentah, dan bisa menjelaskan alasan menolak minimal satu temuan.

---

## Tugas Pertemuan

1. Seluruh query yang menerima input pengguna sudah memakai prepared statement gaya `prepare()` + `execute([...])`
2. Semua tampilan data dari database sudah dibungkus `htmlspecialchars()`
3. Tambahkan fitur **urutkan** (klik header kolom Nama / Harga / Stok) memakai **whitelist** — dan pastikan parameter urutan ikut terbawa di link paginasi dan pencarian
4. Bug `O'Brien` sudah hilang
5. Buat file `LAPORAN-KEAMANAN.md` di dalam folder proyek berisi:
   - Tabel hasil Latihan 1 (percobaan serangan sebelum refactor)
   - Daftar file dan jumlah query yang diubah
   - Daftar variabel yang **sengaja tetap** disisipkan ke SQL, beserta alasan kenapa itu aman
   - Hasil pengujian ulang Latihan 3
   - Laporan audit AI dari Latihan 4, termasuk temuan yang kamu tolak beserta alasannya

**Kumpulkan:** folder proyek `.zip` + `LAPORAN-KEAMANAN.md` + screenshot sebelum/sesudah untuk minimal satu percobaan serangan.

> 📌 Mulai P14, kita kembali menambah fitur — tapi dengan standar baru. **Setiap kode baru yang kamu tulis harus langsung memakai prepared statement.** Tidak ada lagi "nanti diperbaiki".

---

## Ringkasan

| Konsep | Poin Penting |
|--------|--------------|
| **Akar masalah** | MySQL tidak bisa membedakan perintah dan data kalau digabung jadi satu string |
| **SQL Injection** | Input pengguna mengubah bentuk query. Coba `' OR '1'='1` untuk membuktikannya |
| **Bug `O'Brien`** | Versi paling jinak dari masalah yang sama — hilang sendiri setelah refactor |
| **Prepared statement** | Kirim kerangka dulu, data menyusul — data tidak pernah jadi perintah |
| **3 langkah** | `$conn->prepare()` → `$stmt->execute([...])` → `$stmt->get_result()` |
| **Data berupa array** | Urutan elemen = urutan `?` muncul. Jumlahnya harus sama |
| **`?` tanpa petik** | `WHERE nama = ?` — bukan `'?'` |
| **NULL** | Pakai nilai `null` PHP, bukan teks `"NULL"` |
| **`bind_param`** | Cara lama, masih berlaku. Sejak PHP 8.1 tidak perlu lagi |
| **`try` / `catch`** | Menangani `mysqli_sql_exception` saat menyimpan |
| **LIKE** | Tanda `%` ditulis di PHP: `$keyword = '%' . $cari . '%'` |
| **Pencarian kosong** | `'%%'` cocok dengan semua — query jadi selalu berbentuk sama |
| **LIMIT / OFFSET** | Tetap disisipkan langsung — aman karena dijamin integer oleh `(int)` |
| **Struktur query** | `ORDER BY` dan nama kolom tidak bisa pakai `?` → **whitelist** |
| **Aturan sesungguhnya** | Lindungi yang datang dari **luar**. Yang kamu jamin sendiri bentuknya, boleh disisipkan |
| **XSS** | `htmlspecialchars()` saat **menampilkan**, bukan saat menyimpan |
| **Dua arah, dua perlindungan** | Masuk: prepared statement. Keluar: `htmlspecialchars()` |
| **Pesan error mentah** | Membocorkan struktur — matikan `display_errors` di server sungguhan |
| **AI audit** | Minta temuan + bukti, larang beri kode jadi. "Aman menurut AI" ≠ aman |

> ➡️ **Pertemuan berikutnya (P14):** kembali menambah fitur — **upload gambar produk**. Kamu akan belajar menangani file dari pengguna, yang punya risiko keamanannya sendiri: seseorang bisa mengunggah file `.php` dan menjalankannya di servermu.

---

## 📚 Referensi

- [mysqli::prepare — php.net](https://www.php.net/manual/en/mysqli.prepare.php)
- [mysqli_stmt::execute — php.net](https://www.php.net/manual/en/mysqli-stmt.execute.php)
- [PHP Prepared Statements — php.net](https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php)
- [OWASP SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)

---

*Kode yang aman jarang terlihat lebih keren daripada kode yang tidak aman — tampilannya sama persis. Bedanya baru terasa saat aplikasimu dipakai orang sungguhan. Mulai sekarang, tulis kode aman sebagai kebiasaan, bukan sebagai tambahan. 🔒*
