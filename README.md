# Garage Rental

Aplikasi web penyewaan garasi kendaraan. Perusahaan menyewakan garasi untuk sepeda, mobil bensin, dan mobil listrik secara harian atau bulanan. Aplikasi ini dibuat untuk uji kompetensi skema Pemrogram (LSP) dengan Laravel dan basis data relasional MySQL.

Ada dua role pengguna.

- **Admin** membuat garasi, memverifikasi pendaftar, memperpanjang sewa, dan melihat laporan.
- **Penyewa** mendaftar, menyewa garasi, dan melihat riwayat sewanya.

## Fitur

### Penyewa

- **Registrasi** dengan NIK (16 digit), nama lengkap (tampil di navbar), nomor telepon dengan awalan +62 yang tetap, email (dipakai untuk login), dan password. Akun baru berstatus `pending` dan baru bisa login setelah disetujui admin. Jika ditolak, halaman login menampilkan pesan penolakan.
- **Sewa baru.** Penyewa mengisi data kendaraan (tipe, nomor plat kecuali sepeda, merek, model, dan kapasitas baterai untuk mobil listrik), memilih sewa harian atau bulanan, lalu mengisi tanggal mulai dan jumlah hari. Tanggal selesai terisi otomatis. Aplikasi menampilkan garasi yang tersedia sesuai tipe kendaraan beserta total biaya. Penyewa memilih garasi dan mengunggah bukti pelunasan berupa gambar.
- **Riwayat sewa** beserta bukti pembayaran dan riwayat perpanjangan.
- Seorang penyewa boleh menyewa lebih dari satu garasi pada waktu yang sama, selama kendaraannya berbeda.

### Admin

- **Buat garasi berkelompok.** Admin memilih tipe garasi, jumlah (maksimal 999 per tipe), harga sewa harian, dan harga isi listrik per kWh untuk garasi mobil listrik. ID dibuat otomatis dengan awalan `S` untuk sepeda, `C` untuk mobil, dan `E` untuk mobil listrik, diikuti tiga digit yang terus bertambah, misalnya `S001` atau `C010`.
- **Verifikasi pendaftar.** Admin memeriksa NIK lalu menerima atau menolak pendaftaran.
- **Perpanjang sewa.** Aplikasi menampilkan sewa yang sedang berjalan hari ini. Admin memilih satu sewa, mengubah tanggal selesai, melihat biaya tambahan yang dihitung sistem, lalu mengunggah bukti bayar.
- **Laporan sewa** per bulan dalam tahun tertentu, per hari dalam bulan tertentu, per tipe garasi, dan per penyewa.
- **Dashboard** berisi pendaftar yang menunggu persetujuan, sewa aktif, garasi kosong hari ini, pendapatan bulan ini, dan sewa yang akan berakhir dalam 7 hari.

## Cara kerja sewa

### Tipe garasi dan kendaraan

| Kendaraan | Garasi yang boleh dipakai |
|---|---|
| Sepeda | Garasi sepeda |
| Sepeda motor | Garasi sepeda |
| Mobil bensin | Garasi mobil |
| Mobil listrik | Garasi mobil atau garasi mobil listrik |

Aturan ini ada di `Vehicle::compatibleGarageTypes()`. Pilihan tipe garasi di form sewa menyesuaikan tipe kendaraan yang dipilih.

### Harga

- Harga harian disimpan **per tipe garasi**, bukan per garasi. Menambah garasi baru pada tipe yang sudah ada dengan harga berbeda akan mengubah harga seluruh garasi tipe itu.
- Harga bulanan selalu **25 kali** harga harian.
- Sewa yang sudah tersimpan tidak ikut berubah, karena harga satuannya dibekukan di tabel `rental_items`.
- Tipe garasi yang harganya belum diisi admin belum bisa disewa.

### Biaya isi listrik

Mobil listrik di garasi mobil listrik wajib mengisi baterai sampai penuh. Biayanya `(kapasitas baterai - sisa baterai) x harga per kWh`, dibulatkan ke rupiah terdekat. Penyewa mengisi kapasitas dan sisa baterai di form. Mobil listrik yang berada di garasi mobil biasa tidak dikenai biaya isi listrik. Biaya ini dihitung sekali untuk setiap input sewa.

### Durasi dan tanggal

- **Tanggal mulai** terisi hari ini secara bawaan. Penyewa boleh memajukannya ke tanggal ke depan, tetapi tidak boleh mundur.
- **Sewa harian.** Penyewa mengisi jumlah hari, dan tanggal selesai terisi otomatis. Satu input maksimal sebanyak jumlah hari pada bulan tanggal mulai dikurangi satu. Contohnya, sewa yang mulai di bulan November maksimal 29 hari.
- **Sewa bulanan.** Penyewa hanya memilih tanggal mulai. Sewa berlangsung selama jumlah hari pada bulan tanggal mulai (November 30 hari, Desember 31 hari), dan tanggal selesai terisi otomatis.
- **Sewa lebih dari satu bulan** dibuat dengan dua kali input. Contohnya untuk 33 hari, input pertama adalah sewa bulanan dan input kedua adalah sewa harian 3 hari yang dimulai sehari setelah input pertama berakhir. Input kedua inilah yang membutuhkan tanggal mulai ke depan.

### Ketersediaan garasi dan status

Sebuah garasi tersedia jika tidak ada sewa lain yang bentrok dengan rentang tanggal yang dipilih (scope `Rental::overlapping()`). Karena yang dihitung adalah rentang tanggal, garasi yang sudah disewa mulai tanggal 27 masih bisa disewa oleh orang lain pada tanggal 22 sampai 26.

Kolom `status` pada garasi bernilai `available` atau `used`. Status berubah menjadi `used` begitu sebuah sewa berhasil disimpan, dan kembali `available` setelah semua sewanya berakhir. Status ini disegarkan setiap kali admin membuka dashboard atau halaman pembuatan garasi. Penentu ketersediaan untuk sewa baru tetap rentang tanggal, bukan kolom `status`.

Satu kendaraan tidak bisa memiliki dua sewa pada rentang tanggal yang sama. Nomor plat bersifat unik di seluruh sistem dan hanya boleh dipakai oleh satu akun.

### Perpanjangan

- Hanya sewa yang aktif hari ini yang bisa diperpanjang. Tanggal selesai baru harus lebih besar dari tanggal selesai lama.
- Sewa yang lebih panjang dari satu bulan disimpan sebagai beberapa sewa berurutan (penyewa dan garasi sama, tiap sewa mulai sehari setelah sewa sebelumnya berakhir). Perpanjangan selalu diterapkan pada sewa terakhir dari rangkaian itu.
- Hari tambahan dihitung dengan harga harian. Bagian tambahan yang sudah sepanjang satu bulan penuh dihitung dengan harga bulanan.
- Perpanjangan ditolak jika garasi sudah dipakai sewa lain pada hari tambahan tersebut.

### Laporan

Sebuah sewa dihitung pada periode tanggal mulainya. Total sewa sudah termasuk biaya perpanjangannya. Laporan per bulan dan per hari selalu memakai tahun tertentu (bawaannya tahun berjalan). Laporan per tipe dan per penyewa mencakup semua tahun jika tahun dikosongkan.

## Teknologi dan library

| Teknologi | Fungsi |
|---|---|
| PHP 8.3 dan Laravel 13 | Framework MVC, Eloquent ORM, migration, validasi, dan Blade |
| MySQL | Basis data relasional |
| php-open-source-saver/jwt-auth | Autentikasi JWT. Token disimpan di cookie HttpOnly bernama `access_token` dan membawa claim role |
| Tailwind CSS 4 dan Vite | Styling antarmuka dan bundling aset |
| PHPUnit | Unit test dan feature test |

Font yang dipakai adalah Poppins dan Montserrat.

## Arsitektur

```
app/
  Http/
    Controllers/      Controller tipis, dipisah per role (Admin, Renter)
    Middleware/       AttachJwtFromCookie (baca token dari cookie), EnsureRole (cek role)
    Requests/         Form Request untuk semua validasi input
  Models/             User, Vehicle, GarageType, Garage, Rental, RentalItem, RentalExtension
  Services/           Logika bisnis
    RentalPlanner     Aturan tanggal, batas durasi, pemecahan sewa bulanan dan harian
    RentalPricing     Harga bulanan, subtotal, dan biaya isi listrik
    RentalService     Quote, pembuatan sewa, dan perpanjangan dalam satu transaksi
    GarageService     Pembuatan garasi berkelompok dan sinkronisasi status
    ReportService     Rekap laporan
    DashboardService  Ringkasan dashboard admin
database/
  migrations/         Skema tabel
  seeders/            Akun admin dan tiga tipe garasi
resources/views/      Template Blade (layouts, components, admin, renter, auth)
tests/                Unit test dan feature test
```

Kode dipisah per tanggung jawab. Controller hanya menerima request dan memanggil service. Validasi ada di Form Request, dan aturan bisnis ada di service. Setiap kelas dan method diberi docblock.

## Basis data

| Tabel | Isi |
|---|---|
| `users` | NIK, nama lengkap, telepon, email, password, role (`admin` atau `renter`), status (`pending`, `approved`, `rejected`) |
| `garage_types` | Tiga tipe (`bicycle`, `car`, `electric_car`) dengan kode huruf, harga harian, dan harga listrik per kWh |
| `garages` | Garasi dengan kode (misalnya `C001`), nomor urut per tipe, dan status |
| `vehicles` | Kendaraan milik penyewa: tipe, nomor plat (unik), merek, model, kapasitas baterai |
| `rentals` | Satu sewa: penyewa, kendaraan, garasi, rentang tanggal, sisa baterai, biaya isi listrik, total biaya, bukti bayar |
| `rental_items` | Komponen harga sebuah sewa (bulanan atau harian), lengkap dengan harga satuan yang dibekukan |
| `rental_extensions` | Riwayat perpanjangan: tanggal selesai lama dan baru, biaya tambahan, bukti bayar |

Relasi utama: satu penyewa memiliki banyak kendaraan dan banyak sewa. Satu tipe garasi memiliki banyak garasi. Satu garasi dan satu kendaraan masing-masing bisa punya banyak sewa. Satu sewa memiliki banyak `rental_items` dan banyak `rental_extensions`. Laravel juga membuat tabel sessions, cache, cache_locks, dan migrations.

## Menjalankan aplikasi

### Prasyarat

- PHP 8.3 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, dan `xml`
- Composer 2
- Node.js 20.19 atau lebih baru (disarankan versi LTS terbaru) dan npm
- MySQL 8 atau MariaDB

### Langkah instalasi

1. Clone repository dan masuk ke foldernya.
   ```bash
   git clone <url-repository>
   cd <nama-folder>
   ```

2. Buat database kosong.
   ```sql
   CREATE DATABASE garage_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Salin file environment, lalu sesuaikan `DB_USERNAME` dan `DB_PASSWORD` dengan MySQL lokal.
   ```bash
   cp .env.example .env
   ```

4. Pasang dependensi PHP dan JavaScript.
   ```bash
   composer install
   npm install
   ```

5. Buat kunci aplikasi dan kunci JWT. Keduanya ditulis otomatis ke file `.env`.
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```

6. Buat tabel dan isi data awal (tiga tipe garasi dan akun admin).
   ```bash
   php artisan migrate --seed
   ```

7. Buat symbolic link agar bukti pembayaran bisa dibuka lewat `/storage`.
   ```bash
   php artisan storage:link
   ```

### Menjalankan server

Jalankan dengan perintah berikut. Cara ini yang disarankan karena tampilan memakai Tailwind CSS yang dibangun oleh Vite.

```bash
composer run dev
```

Perintah ini menjalankan server Laravel dan Vite bersamaan, sehingga perubahan pada CSS dan Blade langsung terlihat di browser. Buka alamat yang muncul di terminal, biasanya `http://127.0.0.1:8000`. Jika di terminal tidak muncul proses Vite, jalankan dua perintah ini di dua terminal terpisah.

```bash
php artisan serve
npm run dev
```

**Tanpa Node.js.** Hasil build aset sudah ada di `public/build`, jadi aplikasi bisa dijalankan cukup dengan `php artisan serve`. Pastikan file `public/hot` tidak ada, karena file itu membuat Laravel mencari aset ke server Vite. Untuk membuat ulang hasil build setelah mengubah tampilan, jalankan `npm run build`.

### Akun admin

Akun admin dibuat oleh seeder. Tidak ada pendaftaran admin.

| Email | Password |
|---|---|
| `admin@garage.test` | `admin12345` |

Penyewa mendaftar sendiri lewat halaman Register, lalu disetujui admin sebelum bisa login.

## Alur demo

1. Login sebagai admin, buka menu pembuatan garasi, lalu buat garasi untuk tiga tipe beserta harganya. Penyewa belum bisa menyewa sebelum admin mengisi harga tipe garasi.
2. Daftar sebagai penyewa lewat halaman Register. Kembali sebagai admin dan setujui pendaftar di menu Verify Renters.
3. Login sebagai penyewa, buka Sewa Baru, isi data kendaraan dan durasi, pilih garasi, lalu unggah bukti bayar.
4. Login sebagai admin, buka menu Rentals untuk memperpanjang sewa yang sedang berjalan.
5. Buka menu Reports untuk melihat laporan per bulan, per hari, per tipe garasi, dan per penyewa.

## Pengujian

```bash
composer run test
```

Perintah ini setara dengan `php artisan test`. Test memakai SQLite in-memory, jadi ekstensi PHP `pdo_sqlite` harus aktif. Test tidak menyentuh database MySQL.

| Berkas | Cakupan |
|---|---|
| `tests/Unit/RentalPlannerTest.php` | Batas sewa harian, pemecahan bulanan dan harian, input tidak valid |
| `tests/Unit/RentalPricingTest.php` | Harga bulanan, harga item pecahan, biaya isi listrik |
| `tests/Feature/RentalRequestValidationTest.php` | Plat nomor, data baterai, bukti bayar, tanggal mulai, batas jumlah |
| `tests/Feature/RentalServiceTest.php` | Pembuatan sewa, status garasi, bentrok tanggal, biaya listrik |
| `tests/Feature/GarageBatchRequestTest.php` | Validasi pembuatan garasi berkelompok |
| `tests/Feature/GarageServiceTest.php` | Pembuatan kode garasi, batas 999, sinkronisasi status |
| `tests/Feature/AuthFlowTest.php` | Registrasi, login, logout, pembatasan akses per role |
| `tests/Feature/RenterVerificationTest.php` | Persetujuan dan penolakan penyewa |
| `tests/Feature/ReportServiceTest.php` | Rekap laporan per bulan, tipe, dan penyewa |
| `tests/Feature/AdminDashboardTest.php` | Ringkasan dashboard admin |
| `tests/Feature/PublicPagesTest.php` | Halaman publik |

## Pemecahan masalah

| Gejala | Penyebab dan solusi |
|---|---|
| Halaman tampil tanpa styling | Ada file `public/hot` tetapi Vite tidak berjalan. Jalankan `composer run dev`, atau hapus `public/hot` lalu pakai `php artisan serve`. |
| Tanda `*` merah atau tampilan baru tidak muncul | Hasil build lama. Jalankan `npm run build`, lalu hard refresh di browser. |
| `No application encryption key has been specified` | Jalankan `php artisan key:generate`. |
| Error terkait JWT secret saat login | Jalankan `php artisan jwt:secret`, lalu `php artisan config:clear`. |
| `Access denied for user` atau `Unknown database` | Periksa `DB_USERNAME`, `DB_PASSWORD`, dan `DB_DATABASE` di `.env`, lalu pastikan database `garage_rental` sudah dibuat. |
| `The [public/storage] link already exists` | Hapus link lama dengan `rm public/storage`, lalu jalankan `php artisan storage:link`. |
| Bukti pembayaran tidak bisa dibuka | Jalankan `php artisan storage:link`. |
| Perubahan `.env` tidak terbaca | Jalankan `php artisan config:clear`. |