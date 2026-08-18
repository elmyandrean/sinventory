# SInventory (Sistem Inventory dengan Forecasting)

Aplikasi manajemen inventaris gudang yang dibangun menggunakan **Laravel 11** dan **Filament PHP**, dilengkapi dengan fitur analitik cerdas (*Simple Moving Average*) untuk estimasi pengadaan barang (Forecasting).

## Fitur Utama
1. **Manajemen Role & User**: Pembatasan hak akses berbasis peran (Manager vs Staff Gudang).
2. **Master Data**: Pengelolaan kategori dan barang (SKU, stok otomatis).
3. **Transaksi Inbound/Outbound**: Pencatatan masuk/keluarnya barang dengan update stok real-time (Observer).
4. **Forecasting**: Prediksi kebutuhan pengadaan barang berdasarkan riwayat transaksi *Outbound* menggunakan metode *Moving Average*.
5. **Dashboard & Laporan**: Ringkasan data operasional gudang.

## Teknologi
- **Framework**: Laravel 11 (PHP 8.2+)
- **Admin Panel**: Filament PHP v3 (TALL Stack)
- **Database**: PostgreSQL (Containerized)
- **Environment**: Docker (via Laravel Sail terpisah)

## Menjalankan Proyek (Lokal)
Karena proyek ini dibangun menggunakan Docker tanpa kontainer database terpisah, pastikan Anda memiliki *instance* PostgreSQL yang berjalan.
1. Salin `.env.example` ke `.env` dan konfigurasikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
2. Jalankan: `composer install`
3. Jalankan: `php artisan key:generate`
4. Jalankan aplikasi via Sail: `./vendor/bin/sail up -d`
5. Lakukan migrasi: `./vendor/bin/sail artisan migrate`
