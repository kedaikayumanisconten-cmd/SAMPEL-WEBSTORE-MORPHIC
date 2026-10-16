# M.O.R.P.H.I.C — Digital Software & Asset Store

Toko digital untuk kreator konten & musisi. Dibuat dengan **CodeIgniter 3**, **PHP 8.2+**,
**MySQL/MariaDB**. Tanpa framework CSS dan tanpa dependensi eksternal — CSS & JavaScript
ditulis sendiri.

---

## 1. Kebutuhan

| Komponen | Versi |
|---|---|
| PHP | 8.2 atau lebih baru |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Web server | Apache (XAMPP) dengan `mod_rewrite` |

## 2. Instalasi

1. Salin folder proyek ke `htdocs` (contoh: `C:\xampp\htdocs\BaseFullstackProject`).
2. Impor skema + data contoh:

   ```bash
   mysql -u root < database.sql
   ```

   Perintah ini membuat database `morphic_store` beserta 5 kategori, 6 produk,
   3 order, dan lisensinya.

3. Sesuaikan koneksi database di `application/config/database.php`
   (default: user `root`, password kosong — konfigurasi bawaan XAMPP).
4. Sesuaikan `base_url` di `application/config/config.php` bila nama foldernya berbeda.
5. Buka <http://localhost/BaseFullstackProject/>.
6. Opsional — isi data kunjungan demo agar grafik dashboard langsung berisi:

   ```bash
   php tools/seed_demo_traffic.php
   ```

### Akun contoh

Login menerima **email atau username**. Password akun contoh admin/customer adalah
`password`. Dua akun istimewa (`owner` dan `superadmin`) memakai password acak yang
**tidak ditampilkan di halaman login** dan tidak ditulis di dokumen ini — lihat
catatan di bawah tabel.

| Username | Email | Peran | Password |
|---|---|---|---|
| `owner` | `owner@morphic.store` | Owner (tingkat tertinggi) | acak, diberikan terpisah |
| `superadmin` | `superadmin@morphic.store` | Super Admin | acak, diberikan terpisah |
| `admin` | `admin@morphic.store` | Admin | `password` |
| `raka` | `raka.creator@example.com` | Customer (video editor) | `password` |
| `nadia` | `nadia.music@example.com` | Customer (musisi) | `password` |

> **Penting.** Halaman login **tidak** lagi menampilkan kredensial akun demo mana pun.
> Password `owner` / `superadmin` dibuat acak saat seeding; jika lupa, reset dari panel
> **Admin → Users → Ubah → Buat Password Sementara** (login sebagai akun berwenang).

#### Hierarki role

| Role | Level | Bisa mengelola | Bisa menetapkan role |
|---|---|---|---|
| `owner` | 4 | semua akun | Owner, Super Admin, Admin, Customer |
| `super_admin` | 3 | Super Admin, Admin, Customer | Super Admin, Admin, Customer |
| `admin` | 2 | Admin, Customer | Admin, Customer |
| `customer` | 1 | — | — |

Satu akun **tidak dapat mengubah role akunnya sendiri** dan tidak dapat mengelola akun
dengan level lebih tinggi (mis. admin tidak bisa menyentuh super_admin/owner).

## 3. Identitas toko

Identitas toko diubah dari **Admin → Toko → Pengaturan Situs** (`admin/settings`), tanpa
menyentuh kode. Yang bisa diatur:

| Grup | Isi |
|---|---|
| **Logo Toko** | Upload logo baru (PNG/JPG/WEBP/GIF/AVIF, maks 2 MB, disimpan di `assets/uploads/brand/`), tempel URL logo, atau hapus logo dan kembali ke lambang huruf. |
| **Identitas Toko** | Nama toko, sub-judul merek di header, dan tagline (dipakai sebagai meta description). |
| **Kontak Owner** | Nama owner, nomor WhatsApp (format internasional tanpa `+`), dan email publik. |
| **Header / Hero Beranda** | Label kecil, judul, dan paragraf pengantar pada hero halaman depan. |
| **Tentang (About)** | Judul dan isi bagian *Tentang* di beranda (kosongkan isi untuk menyembunyikannya). |
| **Footer** | Catatan kecil dan teks hak cipta (tahun ditambahkan otomatis). |

Nilai disimpan pada tabel **`site_settings`** dan menimpa default di
**`application/config/morphic.php`** saat runtime (lihat `MY_Controller::_apply_site_settings()`).
Jadi pengaturan teknis lain — jumlah item per halaman, metode pembayaran, rekening bank,
batas upload, dan sebagainya — tetap diatur lewat file config tersebut.

---

## 4. Foto produk

Foto produk diatur dari **Admin → Produk → Tambah/Ubah Produk**, pada panel *Foto Produk*.
Ada empat pilihan sumber:

| Sumber | Keterangan |
|---|---|
| **Upload File** | Berkas lokal (JPG/PNG/WEBP/GIF/AVIF, maks 4 MB) disimpan di `assets/uploads/thumbnails/`. |
| **Dari URL** | Menempel alamat gambar publik apa pun (termasuk `images.unsplash.com/...`). |
| **Unsplash** | Pencarian foto langsung dari panel admin + kurasi cepat. |
| **Tanpa Gambar** | Kolom dikosongkan; katalog memakai placeholder inisial judul produk. |

Nilai akhirnya selalu disimpan di kolom `thumbnail_url`. Kolom `thumbnail_credit` menyimpan
kredit foto (ditampilkan kecil di bawah foto pada halaman produk).

### Mengaktifkan pencarian Unsplash

Pencarian otomatis memerlukan Access Key gratis:

1. Buat aplikasi di <https://unsplash.com/oauth/applications>.
2. Salin **Access Key**.
3. Tempel ke `application/config/unsplash.php`:

   ```php
   $config['unsplash_access_key'] = 'ACCESS_KEY_ANDA';
   ```

Tanpa key, tab Unsplash **tetap berfungsi**: tersedia 9 foto kurasi (*quick picks*) yang
langsung bisa diklik, tombol untuk membuka halaman pencarian Unsplash, lalu alamat gambar
ditempel pada tab **Dari URL**.

`application/config/unsplash.php` juga menyimpan kata kunci pencarian per kategori agar
hasilnya sesuai audiens (musisi atau kreator video).

> Sesuai Unsplash API Guidelines, setiap foto otomatis dilengkapi atribusi fotografer
> (`thumbnail_credit`) beserta parameter UTM.

---

## 5. Akun admin (username & password)

Buka **Admin → Akun → Username & Password** (atau klik nama Anda di kanan atas panel).
Halaman ini punya dua formulir:

| Formulir | Isi |
|---|---|
| **Ubah Username & Data Akun** | Nama lengkap, username, dan email. |
| **Ubah Password** | Password saat ini, password baru, dan konfirmasinya. |

Aturan yang berlaku:

- **Username bersifat opsional** dan bisa dipakai untuk login sebagai pengganti email.
  Email selalu bisa dipakai. Kosongkan kolom username untuk menghapusnya.
- Karakter username yang diizinkan: huruf, angka, titik, garis bawah, dan strip (minimal 3).
- Username dan email wajib unik — sistem menolak nilai yang sudah dipakai akun lain.
- Mengganti nama/nama pengguna langsung memperbarui sesi dan sapaan di header panel.
- Mengganti password **wajib menyertakan password saat ini**, minimal 8 karakter, dan harus
  berbeda dari password lama. Setelah berhasil, ID sesi diperbarui otomatis.

Customer mengubah datanya sendiri di **Account → Profil** dengan pola yang sama.

### Mengubah akun pengguna lain

Buka **Admin → Users** lalu klik **Ubah** pada baris pengguna. Administrator dapat
mengganti nama, username, dan email pengguna mana pun (termasuk admin lain), serta
me-reset password-nya tanpa perlu tahu password lama:

- Kolom password bersifat **opsional** — biarkan kosong untuk mempertahankan password lama.
- Tombol **Buat Password Sementara** membuat password acak (huruf besar/kecil, angka, simbol)
  dan menampilkannya **sekali** untuk disalin; hanya hash-nya yang disimpan.
- Bila password di-reset, **semua sesi login pengguna tersebut diakhiri** sehingga ia harus
  masuk kembali memakai password baru. Jika yang diubah adalah akun Anda sendiri, sesi Anda
  hanya diperbarui (tetap masuk).
- Username dan email tetap wajib unik antar akun.
- Pengelolaan dibatasi hierarki role — lihat tabel hierarki di bagian **Akun contoh**.

---

## 6. Analitik dashboard

Halaman **Admin → Dashboard** menampilkan ringkasan penjualan dan lalu lintas pengunjung:

- **Kartu KPI** — pendapatan lunas, kunjungan hari ini (dengan tren dibanding kemarin),
  pengunjung unik, dan pesanan yang menunggu verifikasi.
- **Pendapatan 14 hari** — grafik area nilai pesanan lunas per hari.
- **Status pesanan** — diagram donat lunas / menunggu / gagal / dibatalkan.
- **Kunjungan 14 hari** — grafik area page view dengan garis pembanding pengunjung unik.
- **Perangkat pengunjung** — donat desktop / mobile / tablet.
- **Jam sibuk** — diagram batang kunjungan per jam (00–23).
- **Halaman terpopuler** dan **asal kunjungan** (referer eksternal).
- **Riwayat aktivitas** — kunjungan terakhir, pembelian lunas terbaru, pelanggan baru,
  serta tabel pesanan terbaru.

Semua grafik digambar sebagai **SVG inline** oleh `application/helpers/analytics_helper.php`
(area, batang, donat, dan daftar batang horizontal). Tidak ada library chart eksternal dan
tidak ada request tambahan; setiap titik punya tooltip `<title>` bawaan browser, jadi grafik
tetap terbaca walau JavaScript dimatikan.

### Pencatatan kunjungan

`MY_Controller::track_visit()` mencatat setiap kunjungan halaman storefront ke tabel
`page_visits`: tanggal/waktu, halaman, pengunjung (guest atau member), sesi, alamat IP,
referer, dan jenis perangkat. Bots dideteksi dan ditandai agar tidak mengganggu statistik.

Yang **tidak** dicatat: request POST, panggilan AJAX, akses panel admin, unduhan berkas
(`Download`), dan eksekusi CLI. Query string juga tidak pernah disimpan.

- **Riwayat lengkap**: **Admin → Riwayat Kunjungan** (`admin/visits`) — ada filter perangkat,
  pencarian, pilihan *hanya member*, paginasi, dan tombol **Bersihkan riwayat**.
- **Retensi**: baris lama dihapus otomatis setelah `visit_retention_days` (default 180 hari).
- **Pengaturan**: `dashboard_window_days`, `visits_per_page`, dan `visit_retention_days`
  ada di `application/config/morphic.php`.

Karena database contoh belum punya data lalu lintas, tersedia pengisi data demo:

```bash
php tools/seed_demo_traffic.php          # 30 hari terakhir, ~800 kunjungan
php tools/seed_demo_traffic.php 60 1500  # 60 hari, ~1500 kunjungan
php tools/seed_demo_traffic.php --clear  # kosongkan kembali
```

Grafik penjualan terisi sendiri seiring order masuk; data contoh hanya berisi 3 order.

> **Zona waktu.** `index.php` menetapkan `date_default_timezone_set('Asia/Jakarta')` agar
> stempel waktu order, bucket harian analitik, dan penghitung "hari ini" memakai jam yang
> sama dengan MySQL (yang memakai waktu lokal mesin). Ubah bila toko Anda di zona lain.

---

## 7. Struktur singkat

```
application/
  config/           konfigurasi (config, database, routes, morphic, unsplash)
  controllers/      Home, Shop, Auth, Cart, Checkout, Account, Download
    admin/          Dashboard, Products, Categories, Orders, Licenses, Users, Visits, Profile, Settings
  core/             MY_Controller + Frontend/Customer/Admin_Controller
  helpers/          morphic_helper.php (harga, tanggal, URL gambar, dll)
                    analytics_helper.php (deteksi perangkat + grafik SVG)
  libraries/        Unsplash.php (pencarian foto Unsplash)
  models/           User, Category, Product, Order, License, Rate_limit, Visit, Setting
  views/            layouts/, partials/, shop/, cart/, checkout/, account/, admin/
assets/
  css/style.css     seluruh tema
  js/app.js         interaksi umum
  js/thumb-picker.js  pemilih foto produk
  uploads/          thumbnails/, brand/, dan payment_proofs/ (hasil upload admin & customer)
storage/
  private_products/ berkas produk yang diunduh (di luar akses publik)
tools/
  seed_demo_traffic.php  pengisi data kunjungan demo untuk dashboard
```

## 8. Catatan keamanan

- Berkas produk asli berada di `storage/private_products/` dan hanya dilayani melalui
  `Download::index()` memakai token unduhan (`dl_tok_...`) dengan pembatasan jumlah unduhan.
- Perlindungan CSRF aktif dan token diperbarui setiap request POST.
- Nilai keranjang selalu diambil ulang dari katalog, bukan dari session.
- Lisensi baru dibuat saat admin menandai order sebagai **lunas**.
- Password disimpan sebagai hash bcrypt; pergantian password admin wajib menyertakan
  password saat ini dan memperbarui ID sesi. Password asli tidak pernah bisa ditampilkan;
  jika lupa, gunakan **Buat Password Sementara**.
- Role bertingkat (`owner` > `super_admin` > `admin` > `customer`): sebuah akun hanya bisa
  mengelola akun pada level yang sama atau lebih rendah, tidak bisa mengubah role akunnya
  sendiri, dan tidak bisa menetapkan role di atas levelnya.
- Reset password oleh admin (termasuk password sementara) menghapus seluruh sesi akun sasaran.
- Halaman login tidak menampilkan kredensial akun mana pun.
- Log kunjungan hanya menyimpan path (tanpa query string) dan dipangkas otomatis
  sesuai `visit_retention_days`.

## 9. Demo

Foto produk bawaan mengarah ke berkas yang belum ada, sehingga katalog menampilkan
placeholder inisial judul. Buka admin → Produk → Ubah, lalu pilih foto dari salah satu
sumber di atas untuk melihat gambar asli tampil di katalog dan halaman produk.

Data kunjungan yang tampil di dashboard berasal dari `tools/seed_demo_traffic.php`
(±700 baris demo). Gunakan tombol **Bersihkan riwayat** di halaman Riwayat Kunjungan
untuk memulai dari nol dan melihat pencatatan berjalan langsung.
