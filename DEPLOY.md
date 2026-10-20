# Deploy M.O.R.P.H.I.C ke Hosting (cPanel + phpMyAdmin)

Panduan ini menaikkan proyek dari XAMPP lokal ke hosting berbagi (shared hosting)
berbasis cPanel, dan cara mengimpor database lewat **phpMyAdmin**. Tidak ada
`composer install`, `npm install`, atau build step — cukup upload file + impor SQL.

Ringkasan yang berubah dibanding instalasi lokal:

| Hal | Lokal (XAMPP) | Hosting |
|---|---|---|
| `base_url` | otomatis `http://localhost/MORPHIC/` | otomatis dari domain (tanpa edit) |
| Environment | `development` (error tampil) | `production` (error disembunyikan & dicatat) |
| File SQL | `database.sql` (CLI) | `database_hosting.sql` (phpMyAdmin) |
| Kredensial DB | default XAMPP | edit `database.php` atau env var |
| Cookie | non-secure | otomatis `Secure` saat HTTPS |

---

## 1. Siapkan paket upload

Cara termudah — dari folder proyek jalankan:

```powershell
powershell -ExecutionPolicy Bypass -File tools\build_deploy_zip.ps1
```

Hasilnya `dist\morphic-hosting.zip`: berisi hanya file yang dibutuhkan situs
(`.htaccess` tersembunyi ikut), tanpa `.git`, `tools/`, file SQL, dokumentasi, dan
`composer.json`. Upload zip itu ke `public_html` lalu **Extract** lewat File Manager.

Alternatif manual di Windows (PowerShell):

```powershell
Compress-Archive -Path * -DestinationPath ..\morphic-hosting.zip `
  -CompressionLevel Optimal
```

Lalu hapus dari zip: folder `.git`, folder `tools/`, `database.sql`,
`database_hosting.sql`, `composer.json`, dan file `*.md`.

Yang **wajib** ikut terupload (termasuk file tersembunyi):

```
.htaccess
index.php
application/     system/     assets/     storage/
```

> `assets/uploads/` dan `storage/private_products/` ikut, tetapi isinya nanti
> diganti dengan file asli. File `.htaccess` di dalamnya **jangan hilang** —
> keduanya yang membuat folder upload tidak bisa mengeksekusi PHP dan folder
> produk tidak bisa diunduh langsung.

---

## 2. Buat database di cPanel

1. Buka **cPanel → MySQL® Databases**.
2. **Create New Database** — misal `morphic`. cPanel menambahkan prefix akun:
   hasilnya seperti `userku_morphic`. Catat nama lengkapnya.
3. **Add New User** — buat user + password kuat. Catat.
4. **Add User To Database** → pilih user dan database → **All Privileges** → Make Changes.

Catat 4 nilai ini:

| Setting | Contoh |
|---|---|
| Host | `localhost` (kadang `127.0.0.1`) |
| Nama database | `userku_morphic` |
| User database | `userku_morphic` |
| Password | (yang baru dibuat) |

---

## 3. Impor database lewat phpMyAdmin

File yang dipakai: **`database_hosting.sql`**.

1. cPanel → **phpMyAdmin**.
2. Klik database yang baru dibuat di panel kiri (jangan pilih database lain).
3. Tab **Import** → **Choose File** → `database_hosting.sql`.
4. Character set: `utf-8`. Format: `SQL`.
5. **Go** / **Import**.

Setelah selesai harus ada **10 tabel**: `categories`, `ci_sessions`, `order_items`,
`orders`, `page_visits`, `products`, `rate_limits`, `site_settings`,
`user_licenses`, `users`.

### Kenapa bukan `database.sql`?

`database.sql` diawali:

```sql
CREATE DATABASE IF NOT EXISTS `morphic_store`;
USE `morphic_store`;
```

Di hosting berbagi, akun MySQL biasanya **tidak boleh** `CREATE DATABASE`, dan nama
database selalu berprefix (mis. `userku_morphic`), sehingga `USE morphic_store`
gagal. `database_hosting.sql` adalah salinan yang sama **tanpa** dua baris itu,
jadi mengikuti database yang sedang dipilih di phpMyAdmin.

`database_hosting.sql` dibuat dari `database.sql` oleh:

```bash
php tools/build_hosting_sql.php
```

Jalankan ulang perintah itu setiap kali `database.sql` berubah agar keduanya sinkron.

### Kalau impor gagal

| Pesan | Solusi |
|---|---|
| `Access denied ... CREATE DATABASE` | Pastikan memakai `database_hosting.sql`. |
| `No database selected` | Klik dulu nama database di panel kiri, baru tab Import. |
| `Unknown collation: utf8mb4_unicode_ci` | Server terlalu lama (MySQL < 5.7 / MariaDB < 10.4). Minta upgrade PHP/MySQL ke hosting, atau ganti semua `utf8mb4_unicode_ci` menjadi `utf8mb4_general_ci`. |
| `Specified key was too long` | Server MySQL < 5.7. Upgrade versi database. |
| Halaman putih setelah import | Cek langkah 5 (kredensial DB), lihat bagian Troubleshooting. |

> Untuk database yang lebih besar, kompres dump menjadi `.sql.gz` lalu impor file
> itu — phpMyAdmin menerima gzip dan batas ukuran upload jadi lebih longgar.

---

## 4. Upload file ke hosting

1. cPanel → **File Manager** → masuk ke `public_html`.
   - Domain utama: taruh **isi** proyek langsung di `public_html`.
   - Subfolder/subdomain: buat folder, mis. `public_html/toko`, lalu taruh di sana.
2. **Upload** `morphic-hosting.zip` → klik kanan → **Extract**.
3. Hapus file bawaan hosting yang menimpa halaman, mis. `index.html`, `default.php`.
4. Pastikan **file tersembunyi terlihat** (File Manager → Settings → *Show Hidden
   Files*, atau FileZilla → *Server → Force showing hidden files*). Cek bahwa
   `.htaccess`, `assets/uploads/.htaccess`, dan `storage/.htaccess` ada.

`base_url` **tidak perlu diubah** — dideteksi otomatis dari domain dan folder.
Installasi di root (`https://domain.com/`) maupun subfolder
(`https://domain.com/toko/`) sama-sama jalan.

---

## 5. Konfigurasi database

### Opsi A — edit satu file (paling andal, disarankan)

Edit `application/config/database.php`, ganti 4 baris ini:

```php
'hostname' => 'localhost',
'username' => 'userku_morphic',
'password' => 'PASSWORD_DB_ANDA',
'database' => 'userku_morphic',
```

### Opsi B — environment variable (tanpa mengubah file)

Tambahkan di `.htaccess` (setelah `RewriteEngine On`):

```apache
SetEnv DB_HOST localhost
SetEnv DB_USER userku_morphic
SetEnv DB_PASS password_db_anda
SetEnv DB_NAME userku_morphic
```

Lalu **hapus/ubah** nilai default di `database.php`? Tidak perlu — nilai env
otomatis menang. Catatan: sebagian hosting berbasis **PHP-FPM** tidak meneruskan
`SetEnv` ke PHP. Kalau situs menampilkan error koneksi database, kembali ke Opsi A.

### Opsi base URL manual (opsional)

Kalau domain memakai proxy/CDN dan URL yang terbentuk salah, paksa lewat `.htaccess`:

```apache
SetEnv APP_BASE_URL https://domain.com/
```

---

## 6. Aktifkan HTTPS

1. cPanel → **SSL/TLS Status** (atau **AutoSSL**) → jalankan **Run AutoSSL** dan
   tunggu sertifikat aktif untuk domain.
2. Buka `.htaccess`, **uncomment** tiga baris ini:

```apache
RewriteCond %{HTTPS} off
RewriteCond %{HTTP_HOST} !^(localhost|127\.0\.0\.1)(:[0-9]+)?$ [NC]
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

3. Buka `https://domain.com/` dan pastikan ikon gembok muncul.

Cookie sesi otomatis diberi flag `Secure` saat request HTTPS, dan `base_url`
otomatis memakai `https`, jadi tidak ada mixed content dari sisi aplikasi.

---

## 7. Permission folder

Lewat File Manager (klik kanan → **Change Permissions**) atau FTP:

| Path | Permission |
|---|---|
| Semua folder | `755` |
| Semua file | `644` |
| `assets/uploads/` (+ `avatars`, `brand`, `thumbnails`) | `755` (harus bisa ditulis) |
| `application/logs/` | `755` (harus bisa ditulis) |
| `application/cache/` | `755` |
| `storage/private_products/` | `755` (harus bisa ditulis saat upload produk) |

Uji dengan login admin lalu upload logo di **Admin → Toko → Pengaturan Situs**.
Kalau gagal, biasanya folder belum writable.

---

## 8. Ganti file produk asli

File `.zip` di `storage/private_products/` saat ini hanya **contoh 160 byte**.
Ganti dengan produk asli lewat File Manager, dan pastikan nama file sama dengan
kolom `products.file_path` di database (bisa diubah dari **Admin → Produk**).

Jangan pernah menautkan file ini langsung — unduhan hanya lewat controller
`Download` dengan token lisensi.

---

## 9. Checklist keamanan setelah online

- [ ] Login `admin@morphic.store` / `password` → **segera ganti password**.
- [ ] Nonaktifkan atau reset akun demo `raka` dan `nadia` (Admin → Users).
- [ ] Password `owner` / `superadmin` memang acak; reset lewat **Admin → Users →
      Ubah → Buat Password Sementara** bila perlu.
- [ ] Pastikan `database.sql`, `database_hosting.sql`, dan folder `.git` **tidak
      ada** di hosting (`.htaccess` sudah memblokir, tetapi lebih aman tidak diupload).
- [ ] `display_errors` mati otomatis di produksi; error masuk ke
      `application/logs/` (lihat troubleshooting).
- [ ] Aktifkan backup otomatis (cPanel → Backup / Backup Wizard).
- [ ] Opsional: isi data kunjungan demo dengan `php tools/seed_demo_traffic.php`
      (butuh SSH/terminal hosting), atau biarkan grafik terisi sendiri.

---

## 10. Troubleshooting

| Gejala | Penyebab & solusi |
|---|---|
| **500 Internal Server Error** | Lihat `error_log` di `public_html` atau cPanel → Errors. Umumnya `.htaccess` memakai direktif yang dilarang (coba komentari blok `Options -Indexes`) atau versi PHP < 8.2 (cPanel → **MultiPHP Manager** → pilih PHP 8.2+). |
| **Halaman putih / blank** | Produksi menyembunyikan error. Buka `application/logs/log-*.php`, atau sementara tambahkan `SetEnv CI_ENV development` di `.htaccess` untuk melihat pesan asli. |
| **Homepage jalan, halaman lain 404** | `mod_rewrite`/`AllowOverride` tidak aktif. Minta hosting mengaktifkannya. Alternatif darurat: set `$config['index_page'] = 'index.php';` dan akses lewat `https://domain.com/index.php/produk` (URL cantik tidak akan berfungsi). |
| **CSS/JS tidak muncul, link mengarah ke localhost** | `APP_BASE_URL` salah, atau file diakses lewat domain lain. Set `APP_BASE_URL` sesuai domain final (termasuk `https://` dan trailing slash opsional). |
| **Redirect loop saat login** | Cookie `Secure` dikirim lewat HTTP, atau sebaliknya. Pastikan situs diakses via `https://` setelah mengaktifkan redirect HTTPS, dan `base_url` memakai skema yang sama. |
| **Database Error: Unable to connect** | Cek host/user/password/nama database. Di banyak hosting, user dan nama DB berprefix (`userku_...`). Pastikan user sudah ditambahkan ke database dengan All Privileges. |
| **Tabel tidak ditemukan (`ci_sessions`, dll.)** | Impor SQL belum jalan atau masuk ke database yang salah. Ulangi langkah 3 dan pastikan database yang dipilih benar. |
| **Upload logo/produk gagal** | Folder tidak writable, atau file melebihi `upload_max_filesize`/`post_max_size`. Minta hosting menaikkan batas (minimal 8M) atau kompres gambar. |
| **Impor SQL terpotong / timeout** | Naikkan batas, atau impor per bagian; gunakan `.sql.gz`. Untuk dump besar minta hosting menjalankan lewat SSH: `mysql -u user -p db < file.sql`. |
| **Gambar tampil tapi produk tidak bisa diunduh** | File produk belum diunggah ke `storage/private_products/`, atau nama file tidak cocok dengan `products.file_path`. |
| **Perubahan tidak terlihat** | Cache browser / OPcache hosting. Hard refresh (Ctrl+F5); tunggu beberapa menit atau minta hosting membersihkan OPcache. |

---

## 11. Update aplikasi (redeploy)

1. Tarik perubahan terbaru di lokal (`git pull`).
2. Jika schema berubah: jalankan `php tools/build_hosting_sql.php`, lalu impor
   bagian yang berubah lewat phpMyAdmin (jangan impor ulang seluruh file di
   database produksi — semua tabel di-`DROP`).
3. Buat zip baru dan upload, timpa file lama (**kecuali** `application/config/*`,
   `assets/uploads/*`, `storage/private_products/*` agar konfigurasi dan file
   pengguna tidak hilang).
4. Kalau ada perubahan `.htaccess`, upload ulang juga file tersembunyinya.

---

## 12. Catatan lingkungan

- PHP **8.2+**, MySQL **5.7+** / MariaDB **10.4+**, Apache dengan `mod_rewrite`.
- Ekstensi PHP yang dipakai: `mysqli`, `mbstring`, `fileinfo`, `curl` (standar cPanel).
- Timezone aplikasi dipin ke `Asia/Jakarta` di `index.php`.
- Environment **production** dipilih otomatis untuk host selain `localhost` /
  `127.0.0.1`; `CI_ENV` tetap bisa memaksa nilai lain.
