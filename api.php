<?php
/**
 * File: api.php
 * Deskripsi: Endpoint API terpusat untuk menangani semua permintaan aplikasi.
 * File ini menggabungkan logika dari dua versi sebelumnya, menstandardisasi
 * penanganan respons menggunakan fungsi send_json_response, dan meningkatkan
 * keamanan serta validasi data.
 */

// 1. PENGATURAN AWAL DAN KEAMANAN
// -----------------------------------------------------------------------------

// Mengontrol output dan error reporting untuk memastikan output selalu JSON.
// Ini akan menekan output error HTML dari PHP dan memungkinkan kita mengirim error JSON yang bersih.
error_reporting(0);
ob_start(); // Memulai output buffering

// Memuat file konfigurasi (koneksi database) dan memulai pemeriksaan sesi
require_once 'config.php';
session_check(); // Memanggil fungsi dari config.php untuk memeriksa sesi

// Set header output default ke JSON
header('Content-Type: application/json');


// 2. FUNGSI HELPER UTAMA
// -----------------------------------------------------------------------------

/**
 * Mengirimkan respons dalam format JSON lalu menghentikan eksekusi skrip.
 * Fungsi ini adalah satu-satunya cara untuk mengirim output ke klien.
 *
 * @param array $data Data yang akan di-encode ke JSON.
 */
function send_json_response($data) {
    // Memeriksa apakah status ada di dalam data, jika tidak, default ke 'error'
    if (!isset($data['status'])) {
        $data['status'] = 'error';
    }
    
    ob_clean(); // Membersihkan buffer output untuk memastikan tidak ada output lain
    echo json_encode($data);
    exit();
}

/**
 * Mengoptimalkan gambar dengan mengubah ukuran dan kualitasnya.
 *
 * @param string $source_path Path gambar sumber.
 * @param string $destination_path Path untuk menyimpan gambar hasil optimasi.
 * @param int $max_width Lebar maksimum gambar.
 * @param int $quality Kualitas gambar (1-100).
 * @return bool True jika berhasil, false jika gagal.
 */
function optimizeImage($source_path, $destination_path, $max_width = 800, $quality = 85) {
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng') || !function_exists('imagecreatefromgif')) {
        return false;
    }

    $info = getimagesize($source_path);
    if ($info === false) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($source_path); break;
        case 'image/png':  $image = imagecreatefrompng($source_path);  break;
        case 'image/gif':  $image = imagecreatefromgif($source_path);  break;
        default: return false;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    // Jika gambar sudah cukup kecil, tidak perlu diubah ukurannya
    if ($width <= $max_width) {
        if ($source_path !== $destination_path) {
            copy($source_path, $destination_path);
        }
        imagedestroy($image);
        return true;
    }
    
    // Hitung ukuran baru
    $new_width = $max_width;
    $new_height = floor($height * ($max_width / $width));
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Menjaga transparansi untuk PNG
    if ($mime == "image/png") {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }

    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Simpan gambar baru
    switch ($mime) {
        case 'image/jpeg': imagejpeg($new_image, $destination_path, $quality); break;
        case 'image/png':  imagepng($new_image, $destination_path, 9); break; // Kompresi PNG level 9
        case 'image/gif':  imagegif($new_image, $destination_path); break;
    }

    imagedestroy($image);
    imagedestroy($new_image);
    return true;
}


// 3. ROUTER API
// -----------------------------------------------------------------------------

$action = $_GET['action'] ?? '';
$penjual_id = $_SESSION['penjual_id'] ?? null;

// Daftar aksi yang tidak memerlukan login
$public_actions = ['login_penjual', 'register_penjual', 'update_pilihan_pembayaran', 'verifikasi_pembayaran_wa'];

// Jika aksi memerlukan login tapi pengguna belum login
if (!in_array($action, $public_actions) && !$penjual_id) {
    send_json_response(['status' => 'error', 'message' => 'Akses ditolak. Sesi Anda mungkin telah berakhir.']);
}

// CSRF validation untuk semua aksi POST yang memerlukan login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $public_actions)) {
    if (!validate_csrf_token()) {
        send_json_response(['status' => 'error', 'message' => 'Token keamanan tidak valid. Silakan refresh halaman.']);
    }
}

// Menjalankan fungsi berdasarkan parameter 'action'
try {
    switch ($action) {
        // --- Aksi Publik ---
        case 'login_penjual':       loginPenjual($koneksi); break;
        case 'register_penjual':    registerPenjual($koneksi); break;
        case 'update_pilihan_pembayaran': updatePilihanPembayaran($koneksi); break;
        case 'verifikasi_pembayaran_wa': verifikasiPembayaranWA($koneksi); break;

        // --- Aksi Terotentikasi ---
        case 'get_pelanggan':       getPelanggan($koneksi, $penjual_id); break;
        case 'tambah_pelanggan':    tambahPelanggan($koneksi, $penjual_id); break;
        case 'edit_pelanggan':      editPelanggan($koneksi, $penjual_id); break;
        case 'get_pesanan':         getPesanan($koneksi, $penjual_id); break;
        case 'buat_pesanan':        buatPesanan($koneksi, $penjual_id); break;
        case 'update_status':       updateStatus($koneksi, $penjual_id); break;
        case 'upload_bukti_kirim':  uploadBuktiKirim($koneksi, $penjual_id); break;
        case 'lunasi_pembayaran':   lunasiPembayaran($koneksi, $penjual_id); break;
        case 'verifikasi_dp':       verifikasiDP($koneksi, $penjual_id); break;
        case 'get_dashboard_stats': getDashboardStats($koneksi, $penjual_id); break;
        case 'get_pengaturan':      getPengaturan($koneksi, $penjual_id); break;
        case 'save_pengaturan':     savePengaturan($koneksi, $penjual_id); break;
        case 'kirim_wa':            kirimWa($koneksi, $penjual_id); break;
        case 'check_wa_status':     checkWaStatus(); break;
        
        default:
            send_json_response(['status' => 'error', 'message' => 'Aksi tidak valid atau tidak ditemukan.']);
            break;
    }
} catch (\Throwable $e) {
    // Menangkap error tak terduga dan mengirim respons JSON yang bersih
    send_json_response(['status' => 'error', 'message' => 'Terjadi kesalahan internal pada server: ' . $e->getMessage()]);
} finally {
    // Selalu tutup koneksi database di akhir
    if (isset($koneksi) && $koneksi instanceof mysqli) {
        mysqli_close($koneksi);
    }
    ob_end_flush(); // Mengirim output buffer
}

// --- Fungsi Pengaturan ---
function getPengaturan($koneksi, $penjual_id) {
    $sql = "SELECT qris_statis, detail_pembayaran, opsi_pembayaran_aktif, no_whatsapp_penjual FROM pengaturan WHERE penjual_id = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $penjual_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        send_json_response(['status' => 'success', 'data' => $row]);
    } else {
        // Return default empty settings if not exist
        send_json_response(['status' => 'success', 'data' => ['qris_statis' => '', 'detail_pembayaran' => '', 'opsi_pembayaran_aktif' => 'qris,transfer', 'no_whatsapp_penjual' => '']]);
    }
}

function savePengaturan($koneksi, $penjual_id) {
    $qris_statis = $_POST['qris_statis'] ?? '';
    $detail_pembayaran = $_POST['detail_pembayaran'] ?? '';
    $no_whatsapp_penjual = $_POST['no_whatsapp_penjual'] ?? '';
    
    // Convert array of checkboxes to comma-separated string
    $opsi_pembayaran = isset($_POST['opsi_pembayaran_aktif']) ? implode(',', (array)$_POST['opsi_pembayaran_aktif']) : '';

    $sql = "INSERT INTO pengaturan (penjual_id, qris_statis, detail_pembayaran, opsi_pembayaran_aktif, no_whatsapp_penjual) VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE qris_statis = ?, detail_pembayaran = ?, opsi_pembayaran_aktif = ?, no_whatsapp_penjual = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "issssssss", $penjual_id, $qris_statis, $detail_pembayaran, $opsi_pembayaran, $no_whatsapp_penjual, $qris_statis, $detail_pembayaran, $opsi_pembayaran, $no_whatsapp_penjual);
    if (mysqli_stmt_execute($stmt)) {
        send_json_response(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);
    } else {
        send_json_response(['status' => 'error', 'message' => 'Gagal menyimpan pengaturan: ' . mysqli_error($koneksi)]);
    }
}

function updatePilihanPembayaran($koneksi) {
    $order_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? '';
    $is_dp = isset($_POST['is_dp']) && filter_var($_POST['is_dp'], FILTER_VALIDATE_BOOLEAN);
    $jumlah_dp = isset($_POST['jumlah_dp']) ? (float)$_POST['jumlah_dp'] : 0;
    
    // Generate unique update token based on a hashed secret instead of relying only on ID for public access
    $token = $_POST['token'] ?? '';
    if ($order_id === 0) {
        send_json_response(['status' => 'error', 'message' => 'ID pesanan tidak valid']);
        return;
    }

    // Optimistic Concurrency & Transaction
    mysqli_begin_transaction($koneksi);

    // Mengunci baris agar tidak di-update secara paralel
    $sql_cek = "SELECT total_harga, status_pembayaran, jumlah_dp, status, token FROM pesanan WHERE id = ? FOR UPDATE";
    $stmt_cek = mysqli_prepare($koneksi, $sql_cek);
    mysqli_stmt_bind_param($stmt_cek, "i", $order_id);
    mysqli_stmt_execute($stmt_cek);
    $res = mysqli_stmt_get_result($stmt_cek);
    $pesanan = mysqli_fetch_assoc($res);
    
    if (!$pesanan || $pesanan['status_pembayaran'] === 'Lunas') {
        mysqli_rollback($koneksi);
        send_json_response(['status' => 'error', 'message' => 'Pesanan tidak ditemukan atau sudah lunas']);
        return;
    }
    
    if (!empty($pesanan['token']) && $pesanan['token'] !== $token) {
        mysqli_rollback($koneksi);
        send_json_response(['status' => 'error', 'message' => 'Token akses tidak valid']);
        return;
    }
    
    if ($jumlah_dp < 0 || $jumlah_dp > $pesanan['total_harga']) {
        mysqli_rollback($koneksi);
        send_json_response(['status' => 'error', 'message' => 'Nominal DP tidak valid.']);
        return;
    }

    if ($pesanan['status_pembayaran'] === 'DP') {
        $status_pembayaran = 'Menunggu Verifikasi Lunas';
        $status_pesanan = $pesanan['status']; // Biarkan status pesanan (misal Diproses)
        $final_dp = $pesanan['jumlah_dp'];
    } else {
        $status_pembayaran = $is_dp ? 'Menunggu Verifikasi DP' : 'Menunggu Verifikasi Lunas';
        $status_pesanan = 'Pending';
        $final_dp = $is_dp ? $jumlah_dp : 0;
    }

    $sql = "UPDATE pesanan SET metode_pembayaran = ?, status_pembayaran = ?, status = ?, jumlah_dp = ? WHERE id = ? AND status_pembayaran = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    $current_status_pembayaran = $pesanan['status_pembayaran'];
    mysqli_stmt_bind_param($stmt, "sssdis", $metode_pembayaran, $status_pembayaran, $status_pesanan, $final_dp, $order_id, $current_status_pembayaran);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_commit($koneksi);

        // Generate notif_token dan kirim WA ke penjual
        $notif_token = generate_notif_token($order_id, 0); // penjual_id will be fetched from DB
        $stmt_upnotif = mysqli_prepare($koneksi, "UPDATE pesanan SET notif_token = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_upnotif, "si", $notif_token, $order_id);
        mysqli_stmt_execute($stmt_upnotif);
        mysqli_stmt_close($stmt_upnotif);

        // Ambil data untuk WA ke penjual
        $sql_info = "SELECT p.id, p.total_harga, p.status_pembayaran, p.metode_pembayaran, p.penjual_id,
                     pl.nama AS nama_pelanggan,
                     pg.no_whatsapp_penjual
                     FROM pesanan p
                     JOIN pelanggan pl ON p.pelanggan_id = pl.id
                     JOIN pengaturan pg ON p.penjual_id = pg.penjual_id
                     WHERE p.id = ?";
        $stmt_info = mysqli_prepare($koneksi, $sql_info);
        mysqli_stmt_bind_param($stmt_info, "i", $order_id);
        mysqli_stmt_execute($stmt_info);
        $info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
        mysqli_stmt_close($stmt_info);

        if ($info && !empty($info['no_whatsapp_penjual'])) {
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $jenis = $status_pembayaran; // Tetap gunakan status untuk parameter link (keperluan logika)
            $metode = $metode_pembayaran;

            // Tentukan jumlah yang dibayar dan format tampilan
            if ($status_pembayaran === 'Menunggu Verifikasi DP') {
                $jumlah_bayar = $final_dp;
                $jenis_display = 'Rp ' . number_format($jumlah_bayar, 0, ',', '.') . ' (DP) dari Rp. ' . number_format($info['total_harga'], 0, ',', '.');
            } elseif ($pesanan['status_pembayaran'] === 'DP') {
                // Pelunasan dari status DP
                $jumlah_bayar = $info['total_harga'] - $pesanan['jumlah_dp'];
                $jenis_display = 'Rp ' . number_format($jumlah_bayar, 0, ',', '.') . ' (Pelunasan) dari Rp. ' . number_format($info['total_harga'], 0, ',', '.');
            } else {
                // Lunas langsung
                $jumlah_bayar = $info['total_harga'];
                $jenis_display = 'Rp ' . number_format($jumlah_bayar, 0, ',', '.') . ' (Lunas)';
            }

            $link_terima = $base_url . '/api.php?action=verifikasi_pembayaran_wa&id=' . $order_id . '&token=' . $notif_token . '&jenis=' . urlencode($jenis) . '&metode=' . urlencode($metode) . '&jumlah=' . $jumlah_bayar . '&keputusan=terima';
            $link_tolak = $base_url . '/api.php?action=verifikasi_pembayaran_wa&id=' . $order_id . '&token=' . $notif_token . '&jenis=' . urlencode($jenis) . '&keputusan=tolak';

            $pesan_wa = "🔔 *KONFIRMASI PEMBAYARAN BARU*\n\n";
            $pesan_wa .= "Pelanggan *" . $info['nama_pelanggan'] . "* telah melakukan pembayaran sebesar *" . $jenis_display . "* via *" . $metode . "* untuk pesanan #" . $order_id . ".\n\n";
            $pesan_wa .= "Silakan cek mutasi Anda. Klik link di bawah ini untuk verifikasi otomatis:\n\n";
            $pesan_wa .= "✅ *DITERIMA*: " . $link_terima . "\n\n";
            $pesan_wa .= "❌ *TIDAK VALID*: " . $link_tolak;

            $no_penjual = formatNoWhatsappWA($info['no_whatsapp_penjual']);
            kirim_pesan_wa_gateway($no_penjual, $pesan_wa);
        }

        send_json_response(['status' => 'success', 'message' => 'Pilihan pembayaran diperbarui']);
    } else {
        mysqli_rollback($koneksi);
        send_json_response(['status' => 'error', 'message' => 'Gagal memperbarui.']);
    }
    mysqli_stmt_close($stmt);
}

// 4. IMPLEMENTASI FUNGSI APILOGIKA ENDPOINT
// -----------------------------------------------------------------------------

function registerPenjual($koneksi) {
    // CSRF validation
    if (!validate_csrf_token()) {
        send_json_response(['message' => 'Token keamanan tidak valid.']);
    }
    
    // Rate limiting
    if (!check_rate_limit('register', 3, 3600)) {
        send_json_response(['message' => 'Terlalu banyak percobaan pendaftaran. Silakan tunggu 1 jam.']);
    }
    
    $nama_toko = $_POST['nama_toko'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($nama_toko) || empty($username) || empty($password)) {
        send_json_response(['message' => 'Semua field wajib diisi.']);
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        send_json_response(['message' => 'Password minimal 8 karakter.']);
    }
    
    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        send_json_response(['message' => 'Username hanya boleh huruf, angka, dan underscore (3-20 karakter).']);
    }
    
    // Cek apakah username sudah ada
    $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM penjual WHERE username = ?");
    mysqli_stmt_bind_param($stmt_check, "s", $username);
    mysqli_stmt_execute($stmt_check);
    if (mysqli_stmt_get_result($stmt_check)->num_rows > 0) {
        send_json_response(['message' => 'Username sudah digunakan.']);
    }
    mysqli_stmt_close($stmt_check);

    // Insert data penjual baru
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($koneksi, "INSERT INTO penjual (nama_toko, username, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $nama_toko, $username, $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        reset_rate_limit('register');
        send_json_response(['status' => 'success', 'message' => 'Registrasi berhasil! Silakan login.']);
    } else {
        send_json_response(['message' => 'Gagal mendaftar: ' . mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
}

function loginPenjual($koneksi) {
    // CSRF validation
    if (!validate_csrf_token()) {
        send_json_response(['message' => 'Token keamanan tidak valid.']);
    }
    
    // Rate limiting
    if (!check_rate_limit('login', 5, 900)) {
        send_json_response(['message' => 'Terlalu banyak percobaan login. Silakan tunggu 15 menit.']);
    }
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        send_json_response(['message' => 'Username dan password wajib diisi.']);
    }

    $stmt = mysqli_prepare($koneksi, "SELECT id, password, nama_toko FROM penjual WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($penjual = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $penjual['password'])) {
            // Reset rate limit on successful login
            reset_rate_limit('login');
            
            session_regenerate_id(true); // Mencegah session fixation
            $_SESSION['penjual_id'] = $penjual['id'];
            $_SESSION['nama_toko'] = $penjual['nama_toko'];
            $_SESSION['last_activity'] = time();
            
            // Cek & auto-reconnect session Open-WA
            $wa_result = ensure_wa_session_ready();
            
            send_json_response([
                'status' => 'success', 
                'message' => 'Login berhasil!',
                'wa_status' => $wa_result['ready'] ? 'connected' : 'disconnected',
                'wa_message' => $wa_result['message']
            ]);
        } else {
            send_json_response(['message' => 'Password salah.']);
        }
    } else {
        send_json_response(['message' => 'Username tidak ditemukan.']);
    }
    mysqli_stmt_close($stmt);
}

function getPelanggan($koneksi, $penjual_id) {
    $stmt = mysqli_prepare($koneksi, "SELECT id, nama, no_whatsapp, alamat FROM pelanggan WHERE penjual_id = ? ORDER BY nama ASC");
    mysqli_stmt_bind_param($stmt, "i", $penjual_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pelanggan = mysqli_fetch_all($result, MYSQLI_ASSOC);
    send_json_response(['status' => 'success', 'data' => $pelanggan]);
    mysqli_stmt_close($stmt);
}

function tambahPelanggan($koneksi, $penjual_id) {
    $nama = $_POST['nama'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $no_whatsapp = $_POST['no_whatsapp'] ?? '';

    if (empty($nama) || empty($alamat) || empty($no_whatsapp)) {
        send_json_response(['message' => 'Semua field wajib diisi.']);
    }
    
    $stmt = mysqli_prepare($koneksi, "INSERT INTO pelanggan (nama, alamat, no_whatsapp, penjual_id) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssi", $nama, $alamat, $no_whatsapp, $penjual_id);
    
    if (mysqli_stmt_execute($stmt)) {
        send_json_response(['status' => 'success', 'message' => 'Pelanggan berhasil ditambahkan.']);
    } else {
        send_json_response(['message' => 'Gagal menambahkan pelanggan: ' . mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
}

function editPelanggan($koneksi, $penjual_id) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = $_POST['nama'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $no_whatsapp = $_POST['no_whatsapp'] ?? '';

    if ($id <= 0 || empty($nama) || empty($alamat) || empty($no_whatsapp)) {
        send_json_response(['message' => 'Semua field wajib diisi dan valid.']);
    }
    
    // Pastikan pelanggan ini adalah milik penjual yang sedang login
    $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM pelanggan WHERE id = ? AND penjual_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $id, $penjual_id);
    mysqli_stmt_execute($stmt_check);
    if (mysqli_stmt_get_result($stmt_check)->num_rows == 0) {
        send_json_response(['message' => 'Pelanggan tidak ditemukan atau Anda tidak memiliki izin.']);
    }
    mysqli_stmt_close($stmt_check);

    // Update data pelanggan
    $stmt = mysqli_prepare($koneksi, "UPDATE pelanggan SET nama = ?, alamat = ?, no_whatsapp = ? WHERE id = ? AND penjual_id = ?");
    mysqli_stmt_bind_param($stmt, "sssii", $nama, $alamat, $no_whatsapp, $id, $penjual_id);
    
    if (mysqli_stmt_execute($stmt)) {
        send_json_response(['status' => 'success', 'message' => 'Data pelanggan berhasil diperbarui.']);
    } else {
        send_json_response(['message' => 'Gagal memperbarui pelanggan: ' . mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
}

function getPesanan($koneksi, $penjual_id) {
    $pelanggan_id = isset($_GET['pelanggan_id']) ? (int)$_GET['pelanggan_id'] : 0;
    $sql = "SELECT p.*, pl.nama, pl.no_whatsapp, pl.alamat 
            FROM pesanan p 
            JOIN pelanggan pl ON p.pelanggan_id = pl.id 
            WHERE p.penjual_id = ? ";

    if ($pelanggan_id > 0) {
        $sql .= "AND p.pelanggan_id = ? ";
    }
    $sql .= "ORDER BY p.tanggal_pesan DESC";

    $stmt = mysqli_prepare($koneksi, $sql);
    
    if ($pelanggan_id > 0) {
        mysqli_stmt_bind_param($stmt, "ii", $penjual_id, $pelanggan_id);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $penjual_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pesanan = mysqli_fetch_all($result, MYSQLI_ASSOC);
    send_json_response(['status' => 'success', 'data' => $pesanan]);
    mysqli_stmt_close($stmt);
}

function buatPesanan($koneksi, $penjual_id) {
    $pelanggan_id = isset($_POST['pelanggan_id']) ? (int)$_POST['pelanggan_id'] : 0;
    $deskripsi = $_POST['deskripsi'] ?? '';
    
    if ($pelanggan_id <= 0) send_json_response(['message' => 'Pelanggan harus dipilih.']);
    if (empty($deskripsi)) send_json_response(['message' => 'Deskripsi pesanan tidak boleh kosong.']);

    $estimasi_harga = $_POST['estimasi_harga'] ?? 0;
    $ongkos_kirim = $_POST['ongkos_kirim'] ?? 0;
    $total_harga = $_POST['total_harga'] ?? 0;
    
    // Generate secure token for public link access
    $token = bin2hex(random_bytes(16));
    
    // --- PERBAIKAN FINAL DIMULAI DI SINI ---
    $tanggal_pengambilan = $_POST['tanggal_pengambilan'] ?? '';
    $jam_pengambilan = $_POST['jam_pengambilan'] ?? '';
    $waktu_pengambilan = NULL; // Default ke NULL. Kolom DB harus mengizinkan NULL.

    // Hanya proses jika tanggal diisi. Ini membuat field waktu pengambilan opsional.
    if (!empty($tanggal_pengambilan)) {
        // Jika jam kosong, default ke awal hari (00:00) agar validasi berhasil
        $jam_efektif = !empty($jam_pengambilan) ? $jam_pengambilan : '00:00';
        
        $datetime_string = $tanggal_pengambilan . ' ' . $jam_efektif;
        
        // Coba buat objek DateTime dari format Y-m-d H:i
        $datetime_obj = DateTime::createFromFormat('Y-m-d H:i', $datetime_string);
        
        // Lakukan validasi yang ketat. Cek juga error parsing internal.
        $errors = DateTime::getLastErrors();
        if ($datetime_obj === false || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            // Jika parsing gagal, kirim pesan error yang jelas dan hentikan proses
            send_json_response(['message' => 'Format tanggal pengambilan tidak valid. Harap gunakan format YYYY-MM-DD.']);
        }
        
        // Jika validasi berhasil, format ulang untuk disimpan ke database
        $waktu_pengambilan = $datetime_obj->format('Y-m-d H:i:s');
    }
    // --- AKHIR DARI PERBAIKAN FINAL ---

    $status_pembayaran = $_POST['status_pembayaran'] ?? 'Belum Lunas';
    $metode_pembayaran = !empty($_POST['metode_pembayaran']) ? $_POST['metode_pembayaran'] : NULL;
    $jumlah_dp = !empty($_POST['jumlah_dp']) ? (float)$_POST['jumlah_dp'] : 0;

    $gambar_path = NULL;
    if (isset($_FILES['gambar_sample']) && $_FILES['gambar_sample']['error'] == UPLOAD_ERR_OK) {
        // Validate file upload
        $validation = validate_uploaded_file($_FILES['gambar_sample']);
        if (!$validation['valid']) {
            send_json_response(['message' => $validation['message']]);
        }
        
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        
        // Use random filename to prevent path traversal
        $ext = strtolower(pathinfo($_FILES["gambar_sample"]["name"], PATHINFO_EXTENSION));
        $file_name = "sample_" . $penjual_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["gambar_sample"]["tmp_name"], $target_file)) {
            optimizeImage($target_file, $target_file);
            $gambar_path = $target_file;
        } else {
            send_json_response(['message' => 'Gagal mengupload gambar.']);
        }
    }
    
    $sql = "INSERT INTO pesanan (pelanggan_id, penjual_id, deskripsi_pesanan, gambar_sample, estimasi_harga, ongkos_kirim, total_harga, waktu_pengambilan, status, status_pembayaran, metode_pembayaran, jumlah_dp, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Baru', ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    
    if ($stmt === false) {
        if ($gambar_path && file_exists($gambar_path)) unlink($gambar_path);
        send_json_response(['message' => 'Query error: ' . mysqli_error($koneksi)]);
    }

    mysqli_stmt_bind_param($stmt, "iissdddsssds", $pelanggan_id, $penjual_id, $deskripsi, $gambar_path, $estimasi_harga, $ongkos_kirim, $total_harga, $waktu_pengambilan, $status_pembayaran, $metode_pembayaran, $jumlah_dp, $token);

    if (mysqli_stmt_execute($stmt)) {
        send_json_response(['status' => 'success', 'message' => 'Pesanan berhasil dibuat.']);
    } else {
        if ($gambar_path && file_exists($gambar_path)) unlink($gambar_path); // Hapus gambar menggantung
        send_json_response(['message' => 'Gagal membuat pesanan: ' . mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
}


function uploadBuktiKirim($koneksi, $penjual_id) {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    if ($order_id <= 0 || !isset($_FILES['bukti_kirim']) || $_FILES['bukti_kirim']['error'] != UPLOAD_ERR_OK) {
        send_json_response(['message' => 'Data tidak lengkap atau file tidak terupload.']);
    }

    // Validate file upload
    $validation = validate_uploaded_file($_FILES['bukti_kirim']);
    if (!$validation['valid']) {
        send_json_response(['message' => $validation['message']]);
    }

    // Verifikasi kepemilikan pesanan
    $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM pesanan WHERE id = ? AND penjual_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $order_id, $penjual_id);
    mysqli_stmt_execute($stmt_check);
    if(mysqli_stmt_get_result($stmt_check)->num_rows == 0){
        send_json_response(['message' => 'Anda tidak memiliki akses ke pesanan ini.']);
    }
    mysqli_stmt_close($stmt_check);
    
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
    
    // Use random filename to prevent path traversal
    $ext = strtolower(pathinfo($_FILES["bukti_kirim"]["name"], PATHINFO_EXTENSION));
    $file_name = "bukti_" . $order_id . '_' . bin2hex(random_bytes(8)) . "." . $ext;
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($_FILES["bukti_kirim"]["tmp_name"], $target_file)) {
        optimizeImage($target_file, $target_file);
        
        $stmt = mysqli_prepare($koneksi, "UPDATE pesanan SET gambar_bukti_kirim = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $target_file, $order_id);
        
        if (mysqli_stmt_execute($stmt)) {
            send_json_response(['status' => 'success', 'message' => 'Bukti pengiriman berhasil diupload.']);
        } else {
            send_json_response(['message' => 'Gagal menyimpan path gambar: ' . mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        send_json_response(['message' => 'Gagal mengupload gambar bukti.']);
    }
}

function updateStatus($koneksi, $penjual_id) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = $_POST['status'] ?? '';
    $allowed_statuses = ['Baru', 'Pending', 'Deal', 'Diproses', 'Dikirim', 'Selesai', 'Batal'];

    if ($id <= 0 || !in_array($status, $allowed_statuses)) {
        send_json_response(['message' => 'ID pesanan atau status tidak valid.']);
    }
    
    mysqli_begin_transaction($koneksi);

    $sql_cek = "SELECT status, status_pembayaran FROM pesanan WHERE id = ? AND penjual_id = ? FOR UPDATE";
    $stmt_cek = mysqli_prepare($koneksi, $sql_cek);
    mysqli_stmt_bind_param($stmt_cek, "ii", $id, $penjual_id);
    mysqli_stmt_execute($stmt_cek);
    $res = mysqli_stmt_get_result($stmt_cek);
    $pesanan = mysqli_fetch_assoc($res);

    if (!$pesanan) {
        mysqli_rollback($koneksi);
        send_json_response(['message' => 'Pesanan tidak ditemukan atau Anda tidak memiliki izin.']);
    }

    if ($status === 'Batal') {
        if ($pesanan['status_pembayaran'] === 'Lunas') {
            mysqli_rollback($koneksi);
            send_json_response(['message' => 'Pesanan yang sudah dibayar Lunas tidak dapat dihapus.']);
            return;
        }
        $stmt = mysqli_prepare($koneksi, "DELETE FROM pesanan WHERE id = ? AND penjual_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $penjual_id);
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE pesanan SET status = ? WHERE id = ? AND penjual_id = ? AND status = ?");
        $current_status = $pesanan['status'];
        mysqli_stmt_bind_param($stmt, "siis", $status, $id, $penjual_id, $current_status);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_commit($koneksi);
        send_json_response(['status' => 'success', 'message' => 'Status pesanan berhasil diperbarui.']);
    } else {
        mysqli_rollback($koneksi);
        send_json_response(['message' => 'Gagal memperbarui status: ' . mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
}

function lunasiPembayaran($koneksi, $penjual_id) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $metode = $_POST['metode_pembayaran'] ?? NULL;
    
    if ($id <= 0) {
        send_json_response(['message' => 'ID pesanan tidak valid.']);
    }

    mysqli_begin_transaction($koneksi);
    
    // Pengecekan Optimistic Locking
    $sql_cek = "SELECT status_pembayaran, status FROM pesanan WHERE id = ? AND penjual_id = ? FOR UPDATE";
    $stmt_cek = mysqli_prepare($koneksi, $sql_cek);
    mysqli_stmt_bind_param($stmt_cek, "ii", $id, $penjual_id);
    mysqli_stmt_execute($stmt_cek);
    $res = mysqli_stmt_get_result($stmt_cek);
    $pesanan = mysqli_fetch_assoc($res);

    if (!$pesanan || $pesanan['status_pembayaran'] === 'Lunas' || $pesanan['status'] === 'Batal') {
        mysqli_rollback($koneksi);
        send_json_response(['message' => 'Pesanan tidak ditemukan, sudah lunas, atau dibatalkan.']);
        return;
    }
    
    if ($metode) {
        $stmt = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pembayaran = 'Lunas', metode_pembayaran = ?, jumlah_dp = total_harga WHERE id = ? AND penjual_id = ? AND status_pembayaran != 'Lunas'");
        mysqli_stmt_bind_param($stmt, "sii", $metode, $id, $penjual_id);
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pembayaran = 'Lunas', jumlah_dp = total_harga WHERE id = ? AND penjual_id = ? AND status_pembayaran != 'Lunas'");
        mysqli_stmt_bind_param($stmt, "ii", $id, $penjual_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_commit($koneksi);
        send_json_response(['status' => 'success', 'message' => 'Pembayaran berhasil dilunasi.']);
    } else {
        mysqli_rollback($koneksi);
        send_json_response(['message' => 'Gagal melunasi pembayaran: ' . mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
}

function getDashboardStats($koneksi, $penjual_id) {
    $stats = [];
    
    $queries = [
        'total_pesanan' => "SELECT COUNT(id) as result FROM pesanan WHERE penjual_id = ?",
        'total_pendapatan' => "SELECT SUM(jumlah_dp) as result FROM pesanan WHERE status_pembayaran IN ('DP', 'Lunas') AND status != 'Batal' AND penjual_id = ?",
        'pesanan_baru_hari_ini' => "SELECT COUNT(id) as result FROM pesanan WHERE DATE(tanggal_pesan) = CURDATE() AND penjual_id = ?",
        'perlu_diproses' => "SELECT COUNT(id) as result FROM pesanan WHERE status = 'Deal' AND penjual_id = ?"
    ];
    
    foreach ($queries as $key => $sql) {
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "i", $penjual_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['result'];
        $stats[$key] = $result ?? 0;
        mysqli_stmt_close($stmt);
    }
    
    send_json_response(['status' => 'success', 'data' => $stats]);
}

function verifikasiDP($koneksi, $penjual_id) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        send_json_response(['message' => 'ID pesanan tidak valid.']);
    }
    
    $stmt = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pembayaran = 'DP', status = 'Diproses' WHERE id = ? AND penjual_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $penjual_id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            send_json_response(['status' => 'success', 'message' => 'DP berhasil diverifikasi. Status pesanan menjadi Diproses.']);
        } else {
            send_json_response(['message' => 'Pesanan tidak ditemukan atau Anda tidak memiliki izin.']);
        }
    } else {
        send_json_response(['message' => 'Gagal memverifikasi DP.']);
    }
    mysqli_stmt_close($stmt);
}

function kirimWa($koneksi, $penjual_id) {
    $no_whatsapp = $_POST['no_whatsapp'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    
    if (empty($no_whatsapp) || empty($pesan)) {
        send_json_response(['status' => 'error', 'message' => 'Nomor WhatsApp dan isi pesan harus diisi.']);
    }
    
    // Kirim menggunakan fungsi helper dari config.php
    $result = kirim_pesan_wa_gateway($no_whatsapp, $pesan);
    send_json_response($result);
}

/**
 * Helper: format nomor HP Indonesia ke format internasional (62xxx)
 */
function formatNoWhatsappWA($phone) {
    $clean = preg_replace('/\D/', '', $phone);
    if (strpos($clean, '0') === 0) {
        $clean = '62' . substr($clean, 1);
    } elseif (strpos($clean, '8') === 0) {
        $clean = '62' . $clean;
    }
    return $clean;
}

/**
 * Endpoint publik: Verifikasi pembayaran via link WA.
 * Dipanggil oleh penjual saat klik link DITERIMA / TIDAK VALID.
 * Menampilkan HTML response (bukan JSON).
 */
function verifikasiPembayaranWA($koneksi) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $token = $_GET['token'] ?? '';
    $jenis = $_GET['jenis'] ?? '';
    $metode = $_GET['metode'] ?? '';
    $jumlah = isset($_GET['jumlah']) ? (float)$_GET['jumlah'] : 0;
    $keputusan = $_GET['keputusan'] ?? '';

    header('Content-Type: text/html; charset=utf-8');

    if ($id <= 0 || empty($token) || empty($keputusan)) {
        echo '<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:Inter,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f3f4f6}.card{background:white;padding:2rem;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.1);text-align:center;max-width:400px}.btn{display:inline-block;padding:0.75rem 1.5rem;border-radius:0.5rem;text-decoration:none;font-weight:600;margin-top:1rem}</style></head><body><div class="card"><h1 style="color:#ef4444;font-size:1.5rem">⚠️ Parameter Tidak Valid</h1><p style="color:#6b7280;margin-top:1rem">Link verifikasi tidak lengkap atau tidak valid.</p></div></body></html>';
        return;
    }

    // Validasi token dan ambil data pesanan
    $stmt = mysqli_prepare($koneksi, "SELECT id, status_pembayaran, metode_pembayaran, total_harga, penjual_id, notif_token FROM pesanan WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$pesanan) {
        echo '<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:Inter,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f3f4f6}.card{background:white;padding:2rem;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.1);text-align:center;max-width:400px}</style></head><body><div class="card"><h1 style="color:#ef4444;font-size:1.5rem">❌ Pesanan Tidak Ditemukan</h1><p style="color:#6b7280;margin-top:1rem">Pesanan dengan ID tersebut tidak ditemukan.</p></div></body></html>';
        return;
    }

    if ($pesanan['notif_token'] !== $token) {
        echo '<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:Inter,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f3f4f6}.card{background:white;padding:2rem;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.1);text-align:center;max-width:400px}</style></head><body><div class="card"><h1 style="color:#ef4444;font-size:1.5rem">🔒 Token Tidak Valid</h1><p style="color:#6b7280;margin-top:1rem">Token verifikasi tidak cocok atau sudah digunakan.</p></div></body></html>';
        return;
    }

    // Cek apakah status masih menunggu verifikasi
    if ($pesanan['status_pembayaran'] !== 'Menunggu Verifikasi DP' && $pesanan['status_pembayaran'] !== 'Menunggu Verifikasi Lunas') {
        echo '<!DOCTYPE html><html><head><title>Sudah Diproses</title><style>body{font-family:Inter,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f3f4f6}.card{background:white;padding:2rem;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.1);text-align:center;max-width:400px}.btn{display:inline-block;padding:0.75rem 1.5rem;border-radius:0.5rem;text-decoration:none;font-weight:600;margin-top:1rem}</style></head><body><div class="card"><h1 style="color:#f59e0b;font-size:1.5rem">ℹ️ Sudah Diproses</h1><p style="color:#6b7280;margin-top:1rem">Pembayaran pesanan ini sudah diproses sebelumnya.<br>Status saat ini: <strong>' . htmlspecialchars($pesanan['status_pembayaran']) . '</strong></p></div></body></html>';
        return;
    }

    mysqli_begin_transaction($koneksi);

    if ($keputusan === 'terima') {
        // Update status pembayaran sesuai jenis
        if ($jenis === 'Menunggu Verifikasi DP') {
            $new_status_bayar = 'DP';
            $new_status_order = 'Diproses';
            $sql_up = "UPDATE pesanan SET status_pembayaran = ?, status = ?, metode_pembayaran = ? WHERE id = ?";
            $stmt_up = mysqli_prepare($koneksi, $sql_up);
            mysqli_stmt_bind_param($stmt_up, "sssi", $new_status_bayar, $new_status_order, $metode, $id);
        } else {
            $new_status_bayar = 'Lunas';
            $new_status_order = $pesanan['status']; // Biarkan status pesanan
            $sql_up = "UPDATE pesanan SET status_pembayaran = ?, jumlah_dp = total_harga, metode_pembayaran = ? WHERE id = ?";
            $stmt_up = mysqli_prepare($koneksi, $sql_up);
            mysqli_stmt_bind_param($stmt_up, "ssi", $new_status_bayar, $metode, $id);
        }
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        mysqli_commit($koneksi);

        $msg_title = '✅ Pembayaran Diterima';
        $msg_body = 'Pembayaran pesanan #' . $id . ' telah diverifikasi sebagai <strong>' . htmlspecialchars($new_status_bayar) . '</strong>.';
        $color = '#10b981';
    } else {
        // Tolak: kembalikan ke status sebelumnya
        if ($jenis === 'Menunggu Verifikasi Lunas' && $pesanan['jumlah_dp'] > 0) {
            // Pelunasan dari status DP ditolak → kembali ke DP
            $sql_up = "UPDATE pesanan SET status_pembayaran = 'DP', metode_pembayaran = NULL WHERE id = ?";
            $msg_body = 'Pembayaran pelunasan pesanan #' . $id . ' ditolak. Status kembali ke <strong>DP</strong>.';
        } else {
            // DP ditolak atau pelunasan penuh dari Belum Lunas ditolak → kembali ke Belum Lunas
            $sql_up = "UPDATE pesanan SET status_pembayaran = 'Belum Lunas', metode_pembayaran = NULL WHERE id = ?";
            $msg_body = 'Pembayaran pesanan #' . $id . ' ditolak dan dikembalikan ke status <strong>Belum Lunas</strong>.';
        }
        $stmt_up = mysqli_prepare($koneksi, $sql_up);
        mysqli_stmt_bind_param($stmt_up, "i", $id);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        mysqli_commit($koneksi);

        $msg_title = '❌ Pembayaran Tidak Valid';
        $color = '#ef4444';
    }

    // Invalidasi token agar tidak bisa dipakai lagi
    $stmt_invalidate = mysqli_prepare($koneksi, "UPDATE pesanan SET notif_token = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt_invalidate, "i", $id);
    mysqli_stmt_execute($stmt_invalidate);
    mysqli_stmt_close($stmt_invalidate);

    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran</title>
    <style>
        body { font-family: "Inter", sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f3f4f6; }
        .card { background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 420px; }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; margin: 0.5rem 0; }
        p { color: #6b7280; margin-top: 0.5rem; line-height: 1.6; }
        .detail { background: #f9fafb; border-radius: 0.5rem; padding: 1rem; margin-top: 1rem; text-align: left; font-size: 0.875rem; }
        .detail div { margin: 0.25rem 0; }
        .detail strong { color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">' . ($keputusan === 'terima' ? '✅' : '❌') . '</div>
        <h1 style="color:' . $color . '">' . $msg_title . '</h1>
        <p>' . $msg_body . '</p>
        <div class="detail">
            <div><strong>ID Pesanan:</strong> #' . $id . '</div>
            <div><strong>Jenis:</strong> ' . htmlspecialchars($jenis) . '</div>
            <div><strong>Metode:</strong> ' . htmlspecialchars($metode ?: '-') . '</div>
            <div><strong>Jumlah:</strong> Rp ' . number_format($jumlah, 0, ',', '.') . '</div>
        </div>
        <p style="font-size:0.75rem;color:#9ca3af;margin-top:1.5rem">Halaman ini dapat ditutup.</p>
    </div>
</body>
</html>';
}

function checkWaStatus() {
    $result = ensure_wa_session_ready();
    send_json_response([
        'status' => 'success',
        'wa_status' => $result['ready'] ? 'connected' : 'disconnected',
        'session_status' => $result['session_status'],
        'message' => $result['message']
    ]);
}
?>