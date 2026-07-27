# Manajemen Pemesanan (ManPem)

Sistem manajemen pesanan berbasis web untuk penjual/usaha kecil. Dibangun dengan PHP native + MySQL/MariaDB, mendukung integrasi WhatsApp Gateway (Open-WA) untuk notifikasi dan verifikasi pembayaran otomatis via link.

## Fitur

- Manajemen pelanggan (tambah, edit, daftar)
- Manajemen pesanan (buat, update status, upload gambar sample & bukti kirim)
- Pembayaran DP/Lunas dengan dukungan QRIS & Transfer Bank
- Link verifikasi pembayaran via WhatsApp untuk penjual (terima/tolak)
- Auto-reconnect session WhatsApp Gateway
- Keamanan: CSRF token, rate limiting, session timeout, file upload validation, prepared statements
- Dashboard statistik
- Splash screen & UI responsif dengan Tailwind CSS

## Kebutuhan Sistem

- PHP 8.0+ (dengan ekstensi: `mysqli`, `gd`, `mbstring`, `json`, `fileinfo`)
- MariaDB 10.4+ / MySQL 5.7+ (wajib mendukung `ALTER USER ... IDENTIFIED VIA mysql_native_password`)
- Open-WA (WA Gateway) - opsional, untuk fitur notifikasi WhatsApp
- Composer / Git - opsional, hanya untuk pengembangan

## Dependensi

### PHP Ekstensi

```bash
sudo apt update
sudo apt install -y php php-cli php-mysqli php-gd php-mbstring php-json php-fileinfo
```

### Database

```bash
sudo apt install -y mariadb-server mariadb-client
sudo service mariadb start
```

### WA Gateway (Open-WA) - Opsional

Unduh dan jalankan Open-WA dari https://github.com/open-wa/wa-automate-nodejs, atau gunakan layanan WA Gateway lain yang kompatibel dengan API Open-WA.

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/afisla/manpem.git
cd manpem
```

### 2. Konfigurasi Database

Jalankan migrasi database:

```bash
mysql -u root < migration.sql
```

Atau login ke MySQL dan jalankan:

```sql
SOURCE /path/to/migration.sql;
```

> **Catatan:** Script `migration.sql` akan membuat database `db_mpem` beserta tabel-tabel yang diperlukan, serta menyesuaikan autentikasi root agar dapat diakses tanpa password.

### 3. Konfigurasi Environment

Copy file `.env.example` menjadi `.env` (atau sesuaikan langsung di `config.php`):

```bash
cp .env.example .env
```

Sesuaikan nilai-nilai di `.env`:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=db_mpem

WA_API_URL=http://127.0.0.1:2785/api
WA_API_KEY=your_wa_api_key_here
WA_SESSION_NAME=marketing-zahra

NOTIF_SECRET=your_random_secret_key_here
```

### 4. Jalankan Server

Gunakan script `server.sh` untuk menjalankan server:

```bash
chmod +x server.sh
./server.sh
```

Script ini akan:
- Menghentikan server PHP sebelumnya (jika ada)
- Menjalankan MariaDB
- Menjalankan PHP built-in server di `http://127.0.0.1:8080`

Atau jalankan manual:

```bash
php -S 127.0.0.1:8080 -t /path/to/project
```

### 5. Akses Aplikasi

Buka browser: **http://127.0.0.1:8080**

Daftar akun penjual baru, lalu login.

## Struktur Direktori

```
manpem/
├── api.php              # REST API endpoint terpusat
├── config.php           # Konfigurasi database & helper functions
├── index.php            # Halaman login/register dengan splash screen
├── pemesanan.php        # Halaman utama manajemen pesanan
├── kelola_pengiriman.php # Manajemen pengiriman
├── ringkasan_pesanan.php # Ringkasan pesanan (link publik)
├── pembayaran.html       # Halaman pembayaran (link publik)
├── ongkir.html           # Halaman cek ongkir
├── compress.html         # Tools kompresi gambar
├── logout.php            # Logout
├── qris_helper.php       # Helper QRIS
├── migration.sql         # Script migrasi database
├── server.sh             # Script menjalankan server
├── .env.example          # Contoh konfigurasi environment
├── .htaccess             # Konfigurasi Apache (security headers)
├── uploads/              # Direktori upload gambar
└── test_e2e.sh           # Script testing
```

## Migrasi Database

Jika telah memiliki versi sebelumnya, jalankan ulang `migration.sql` untuk mendapat struktur terbaru:

```bash
mysql -u root db_mpem < migration.sql
```

Atau jika hanya perlu menambahkan kolom baru tanpa mengulang seluruh migrasi, jalankan query berikut di MySQL:

```sql
ALTER TABLE pesanan ADD COLUMN token VARCHAR(64) DEFAULT NULL;
ALTER TABLE pesanan ADD COLUMN notif_token VARCHAR(64) DEFAULT NULL;
ALTER TABLE pengaturan ADD COLUMN no_whatsapp_penjual VARCHAR(50) DEFAULT NULL;
```

> **Catatan:** `config.php` sudah memiliki auto-migrasi untuk kolom-kolom di atas, sehingga akan otomatis menambahkan kolom yang kurang saat aplikasi diakses.

## Tata Cara Penggunaan

### Alur Kerja

1. **Daftar/Login** - Buka `index.php`, daftar akun penjual baru atau login
2. **Tambah Pelanggan** - Masukkan data nama, alamat, nomor WhatsApp
3. **Buat Pesanan** - Pilih pelanggan, isi deskripsi, upload gambar sample, tentukan harga & ongkos kirim
4. **Customer Memilih Pembayaran** - Customer menerima link pembayaran via WhatsApp, memilih metode (QRIS/Transfer) dan jenis pembayaran (DP/Lunas)
5. **Verifikasi Pembayaran** - Penjual menerima notifikasi WhatsApp dengan link terima/tolak
6. **Proses Pesanan** - Update status pesanan (Diproses → Dikirim → Selesai)
7. **Upload Bukti Kirim** - Upload gambar bukti pengiriman

## Verifikasi Pembayaran via WhatsApp

Saat customer memilih metode pembayaran, sistem akan:
1. Mengirim notifikasi WhatsApp ke nomor penjual
2. Menyertakan link **DITERIMA** dan **TIDAK VALID** untuk verifikasi satu klik
3. Link hanya bisa digunakan sekali (token di-invalidasi setelah dipakai)

Konfigurasi nomor WhatsApp penjual ada di menu **Pengaturan** setelah login.

## Keamanan

- CSRF token pada semua form
- Rate limiting (login: 5x/15 menit, register: 3x/jam)
- Session timeout otomatis (30 menit)
- Prepared statements (anti SQL injection)
- File upload validation (MIME type, ekstensi, ukuran)
- Random filename untuk upload
- XSS protection headers
- Content Security Policy headers
- Session fixation protection

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `Database connection failed` | Pastikan MariaDB berjalan: `sudo service mariadb start` |
| WA Gateway tidak terhubung | Pastikan Open-WA berjalan di port 2785 |
| Gambar tidak muncul | Periksa folder `uploads/` dan permission-nya |
| Login gagal | Cek username & password, atau register akun baru |
