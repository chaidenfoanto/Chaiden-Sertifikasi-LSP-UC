# Garage Rental

Aplikasi web penyewaan garasi kendaraan untuk uji kompetensi skema Pemrogram (LSP). Dibangun dengan Laravel dan MySQL. Ada dua role, yaitu `admin` dan `renter` (penyewa).

## Fitur

**Renter**

- Registrasi dengan NIK, nama lengkap, nomor telepon, email (sebagai username), dan password. Akun berstatus `pending` sampai disetujui admin.
- Sewa garasi baru. Penyewa mengisi data kendaraan, memilih tipe sewa harian atau bulanan, lalu memilih garasi yang tersedia. Sistem menghitung tanggal selesai dan total biaya sebelum data disimpan. Penyewa mengunggah bukti pelunasan, dan status garasi menjadi `used`.
- Riwayat sewa beserta bukti pembayaran dan riwayat perpanjangan.

**Admin**

- Membuat garasi secara berkelompok (maksimal 999 per tipe) dengan harga sewa harian dan harga listrik per kWh untuk garasi mobil listrik. ID dibuat otomatis, yaitu `S001`, `C001`, dan `E001`.
- Memverifikasi pendaftar baru dengan menerima atau menolak.
- Memperpanjang sewa yang sedang berjalan dengan mengubah tanggal selesai. Sistem menghitung biaya tambahan dan admin mengunggah bukti bayar.
- Laporan sewa per bulan dalam satu tahun, per hari dalam satu bulan, per tipe garasi, dan per penyewa.

## Aturan bisnis

| Aturan | Implementasi |
|---|---|
| Sepeda dan sepeda motor hanya di garasi sepeda | `Vehicle::compatibleGarageTypes()` |
| Mobil bensin hanya di garasi mobil | `Vehicle::compatibleGarageTypes()` |
| Mobil listrik di garasi mobil atau garasi mobil listrik | `Vehicle::compatibleGarageTypes()` |
| Harga bulanan adalah 25 kali harga harian | `RentalPricing::MONTHLY_MULTIPLIER` |
| Biaya isi listrik adalah (kapasitas penuh dikurangi sisa baterai) dikali harga per kWh, hanya di garasi mobil listrik | `RentalPricing::chargingCost()` |
| Satu garasi hanya menampung satu sewa pada rentang tanggal yang sama | scope `Rental::overlapping()` |
| Bukti bayar berupa gambar jpg, jpeg, atau png, maksimal 5 MB | `StoreRentalRequest`, `ExtendRentalRequest` |
| Nomor plat wajib untuk semua kendaraan kecuali sepeda | `StoreRentalRequest` |

### Asumsi

Bagian soal yang ambigu diputuskan sebagai berikut.

- Satu input sewa maksimal satu bulan (penjelasan dosen). Sewa harian memilih tanggal mulai dan tanggal selesai, maksimal sebanyak jumlah hari pada bulan tanggal mulai dikurangi satu (November 29 hari walaupun mulai tanggal 15). Sewa bulanan hanya memilih tanggal mulai dan berlangsung sebanyak jumlah hari pada bulan tanggal mulai (November 30 hari, Desember 31 hari). Sewa lebih panjang dibuat dengan dua kali input, misalnya 33 hari menjadi 1 bulanan ditambah 3 harian.
- Biaya isi listrik dihitung per input sewa.
- Perpanjangan oleh admin diterapkan pada sewa terakhir dari rangkaian sewa yang sama (penyewa dan garasi sama, tiap sewa mulai sehari setelah sewa sebelumnya berakhir). Hari tambahan dihitung harian, dan satu bulan penuh tambahan dihitung bulanan.
- Setiap input sewa disimpan sebagai satu sewa dengan satu kali upload bukti bayar. Perpanjangan menambah item sewa baru pada sewa yang sama.
- Perpanjangan hanya untuk sewa yang aktif pada hari berjalan, dan tanggal selesai baru harus lebih besar dari tanggal selesai lama.
- Penyewa baru bisa login setelah disetujui. Jika ditolak, tampil pesan yang jelas di halaman login.
- Tanggal mulai sewa terisi hari ini secara bawaan dan boleh dimajukan ke tanggal ke depan (penjelasan dosen saat zoom), tetapi tidak boleh mundur.
- Harga sewa harian dan harga listrik per kWh disimpan per tipe garasi. Menambah garasi baru pada tipe yang sudah ada dengan harga berbeda mengubah harga seluruh garasi tipe itu. Sewa yang sudah tersimpan tidak berubah karena harganya dibekukan di `rental_items`.

## Teknologi dan library

| Library | Fungsi |
|---|---|
| Laravel | Framework MVC, ORM Eloquent, migration, validasi, dan Blade |
| php-open-source-saver/jwt-auth | Autentikasi JWT yang disimpan di cookie HttpOnly dan membawa claim role |
| Tailwind CSS dan Vite | Styling antarmuka (hasil build sudah ada di `public/build`) |
| PHPUnit | Unit test dan feature test |
| MySQL | Basis data relasional |

## Arsitektur

```
app/
  Http/
    Controllers/      Controller tipis, dipisah per role (Admin, Renter)
    Middleware/       AttachJwtFromCookie (baca token dari cookie), EnsureRole (cek role)
    Requests/         Form Request untuk semua validasi input
  Models/             User, Vehicle, GarageType, Garage, Rental, RentalItem, RentalExtension
  Services/           Logika bisnis
    RentalPlanner     Memecah durasi sewa menjadi item bulanan dan harian
    RentalPricing     Harga bulanan, subtotal, dan biaya isi listrik
    RentalService     Quote, pembuatan sewa, dan perpanjangan dalam satu transaksi
    GarageService     Pembuatan garasi berkelompok dan sinkronisasi status
    ReportService     Rekap laporan
    DashboardService  Ringkasan dashboard admin
database/
  migrations/         Skema tabel
  seeders/            Akun admin dan tiga tipe garasi
resources/views/      Template Blade
tests/                Unit test dan feature test
```

### Tabel utama

`users`, `garage_types`, `garages`, `vehicles`, `rentals`, `rental_items`, dan `rental_extensions`. Tabel `garage_types` menyimpan harga harian dan harga listrik per tipe. Tabel `rental_items` menyimpan komponen harga sebuah sewa, yaitu periode awal dan tiap perpanjangan.

## Menjalankan aplikasi

Prasyarat: PHP 8.3 atau lebih baru, Composer, dan MySQL. Buat database kosong bernama `garage_rental`.

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` di `.env` dengan MySQL lokal. Aplikasi berjalan di `http://127.0.0.1:8000`.

Perintah `php artisan storage:link` wajib dijalankan karena bukti pembayaran disajikan lewat `/storage`.

### Akun admin

Akun admin dibuat oleh seeder dan tidak ada pendaftaran admin.

| Email | Password |
|---|---|
| `admin@garage.test` | `admin12345` |

## Alur demo

1. Login sebagai admin, lalu buat garasi untuk tiga tipe beserta harganya. Penyewa belum bisa menyewa sebelum admin mengisi harga tipe garasi.
2. Daftar sebagai penyewa lewat halaman register, lalu kembali ke admin dan setujui di menu verifikasi.
3. Login sebagai penyewa, lalu buat sewa baru dan unggah bukti bayar.
4. Login sebagai admin, lalu perpanjang sewa dan lihat laporan.

## Pengujian

```bash
php artisan test
```

Test memakai SQLite in-memory, jadi ekstensi PHP `pdo_sqlite` harus aktif. Test tidak menyentuh database MySQL.

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