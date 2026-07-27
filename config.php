<?php
// File: config.php

// Session Security Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' https://cdn.tailwindcss.com https://fonts.googleapis.com https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://static.cloudflareinsights.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://static.cloudflareinsights.com; img-src 'self' data: https://api.qrserver.com;");

// Pengaturan Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_mpem');

// Pengaturan zona waktu
date_default_timezone_set('Asia/Jakarta');

// Membuat koneksi ke database
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Periksa koneksi
if (!$koneksi) {
    // Log error ke file daripada menampilkan ke pengguna
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Terjadi kesalahan sistem. Silakan coba lagi nanti.");
}

// Auto-migrate: Pastikan kolom token ada di tabel pesanan untuk menghindari error pada pengguna yang belum menjalankan ulang migration.sql
$check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'token'");
if ($check_col && mysqli_num_rows($check_col) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN token VARCHAR(64) DEFAULT NULL");
}

// Auto-migrate: kolom notif_token di tabel pesanan
$check_col2 = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'notif_token'");
if ($check_col2 && mysqli_num_rows($check_col2) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN notif_token VARCHAR(64) DEFAULT NULL");
}

// Auto-migrate: kolom no_whatsapp_penjual di tabel pengaturan
$check_col3 = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'no_whatsapp_penjual'");
if ($check_col3 && mysqli_num_rows($check_col3) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN no_whatsapp_penjual VARCHAR(50) DEFAULT NULL");
}

// Secret key untuk generate notif_token verifikasi (gunakan env var di produksi)
define('NOTIF_SECRET', getenv('NOTIF_SECRET') ?: 'f8a3b2c1d4e5f6a7b8c9d0e1f2a3b4c5');

/**
 * Fungsi untuk memeriksa sesi login dan menangani logout otomatis karena tidak aktif.
 * Durasi diatur ke 30 menit (1800 detik).
 */
function session_check() {
    session_start();

    // Jika pengguna belum login (tidak ada penjual_id), tidak ada yang perlu dilakukan.
    if (!isset($_SESSION['penjual_id'])) {
        return;
    }

    // Logout otomatis jika tidak aktif selama 30 menit
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        // Redirect ke halaman login dengan pesan
        header('Location: index.php?reason=inactive');
        exit();
    }

    // Perbarui waktu aktivitas terakhir pada setiap pemuatan halaman yang aman
    $_SESSION['last_activity'] = time();
    
    // Generate CSRF token jika belum ada
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Pengaturan WA Gateway (OpenWA) - gunakan env var di produksi
define('WA_API_URL', getenv('WA_API_URL') ?: 'http://127.0.0.1:2785/api');
define('WA_API_KEY', getenv('WA_API_KEY') ?: 'owa_k1_5a8e5cd2f23f719b53dba03a1fd8fd8169925f9b4954025f07df18b1eb5592cd');
define('WA_SESSION_NAME', getenv('WA_SESSION_NAME') ?: 'marketing-zahra');

/**
 * Mengirim pesan WhatsApp melalui API Gateway OpenWA.
 * 
 * @param string $no_whatsapp Nomor WhatsApp tujuan (format e.164 tanpa +, misal 628123456789).
 * @param string $pesan Isi pesan teks.
 * @return array Array berisi status dan pesan log/error.
 */
function kirim_pesan_wa_gateway($no_whatsapp, $pesan) {
    // 1. Dapatkan daftar sesi untuk mencari UUID dari sesi marketing-zahra
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "X-API-Key: " . WA_API_KEY . "\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    // Gunakan @ untuk suppress warnings, lalu periksa respon
    $response = @file_get_contents(WA_API_URL . '/sessions', false, $context);
    
    if ($response === false) {
        return [
            'status' => 'error',
            'message' => 'Gagal terhubung ke WA Gateway API via file_get_contents. Pastikan port 2785 aktif.'
        ];
    }
    
    $sessions = json_decode($response, true);
    if (!is_array($sessions)) {
        return [
            'status' => 'error',
            'message' => 'Format respon WA Gateway tidak valid.'
        ];
    }
    
    $session_id = null;
    $session_ready = false;
    foreach ($sessions as $session) {
        if (isset($session['name']) && $session['name'] === WA_SESSION_NAME) {
            $session_id = $session['id'] ?? null;
            $session_ready = (isset($session['status']) && $session['status'] === 'ready');
            break;
        }
    }
    
    if (!$session_id) {
        return [
            'status' => 'error',
            'message' => 'Sesi WA "' . WA_SESSION_NAME . '" tidak ditemukan di gateway.'
        ];
    }
    
    if (!$session_ready) {
        return [
            'status' => 'error',
            'message' => 'Sesi WA "' . WA_SESSION_NAME . '" sedang tidak aktif/ready.'
        ];
    }
    
    // 2. Format nomor penerima dengan @c.us jika belum ada
    $chat_id = $no_whatsapp;
    if (strpos($chat_id, '@') === false) {
        $chat_id .= '@c.us';
    }
    
    // 3. Kirim pesan ke sesi tersebut
    $post_data = json_encode([
        'chatId' => $chat_id,
        'text' => $pesan
    ]);
    
    $send_options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                        "X-API-Key: " . WA_API_KEY . "\r\n",
            'content' => $post_data,
            'ignore_errors' => true // Membiarkan kita membaca body respons bahkan pada error 4xx/5xx
        ]
    ];
    $send_context = stream_context_create($send_options);
    $send_url = WA_API_URL . '/sessions/' . $session_id . '/messages/send-text';
    
    $send_response = @file_get_contents($send_url, false, $send_context);
    
    // Dapatkan status code dari response headers
    $status_code = 0;
    if (isset($http_response_header)) {
        $status_line = isset($http_response_header[0]) ? $http_response_header[0] : '';
        preg_match('{HTTP\/\S+\s+(\d+)}', $status_line, $matches);
        $status_code = isset($matches[1]) ? (int)$matches[1] : 0;
    }
    
    if ($status_code === 200 || $status_code === 201) {
        return [
            'status' => 'success',
            'message' => 'Pesan berhasil dikirim via WA Gateway.'
        ];
    } else {
        $error_desc = json_decode($send_response, true);
        $error_msg = $error_desc['message'] ?? 'Gagal mengirim pesan.';
        return [
            'status' => 'error',
            'message' => 'Gagal mengirim pesan via Gateway. HTTP Code: ' . $status_code . '. Detail: ' . $error_msg
        ];
    }
}

/**
 * Generate notif_token untuk link verifikasi WA penjual.
 * Token ini berbeda dari token akses customer.
 */
function generate_notif_token($order_id, $penjual_id) {
    return hash_hmac('sha256', $order_id . '-' . $penjual_id . '-' . bin2hex(random_bytes(8)), NOTIF_SECRET);
}

// --- CSRF Protection Functions ---
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function get_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// --- Rate Limiting Functions ---
function check_rate_limit($action, $max_attempts = 5, $window_seconds = 900) {
    $key = 'rate_limit_' . $action;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $rate_data = &$_SESSION[$key];
    
    // Reset if window has passed
    if (time() - $rate_data['first_attempt'] > $window_seconds) {
        $rate_data = ['count' => 0, 'first_attempt' => time()];
        return true;
    }
    
    // Check if rate limit exceeded
    if ($rate_data['count'] >= $max_attempts) {
        return false;
    }
    
    $rate_data['count']++;
    return true;
}

function reset_rate_limit($action) {
    $key = 'rate_limit_' . $action;
    unset($_SESSION[$key]);
}

// --- File Upload Validation ---
function validate_uploaded_file($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $max_size = 5242880) {
    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'message' => 'Upload gagal. Error code: ' . $file['error']];
    }
    
    // Check file size (default 5MB)
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'message' => 'Ukuran file terlalu besar. Maksimal: ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    // Check MIME type using finfo (more reliable than $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'message' => 'Tipe file tidak diizinkan. Hanya: ' . implode(', ', $allowed_types)];
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_extensions)) {
        return ['valid' => false, 'message' => 'Ekstensi file tidak diizinkan.'];
    }
    
    return ['valid' => true, 'mime' => $mime_type];
}

// --- Open-WA Session Management ---

/**
 * Helper untuk melakukan HTTP request ke Open-WA API.
 */
function owa_request($method, $endpoint, $body = null) {
    $url = WA_API_URL . $endpoint;
    $options = [
        'http' => [
            'method' => $method,
            'header' => "X-API-Key: " . WA_API_KEY . "\r\nContent-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    if ($body !== null) {
        $options['http']['content'] = json_encode($body);
    }
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    $status_code = 0;
    if (isset($http_response_header)) {
        $status_line = isset($http_response_header[0]) ? $http_response_header[0] : '';
        preg_match('{HTTP\/\S+\s+(\d+)}', $status_line, $matches);
        $status_code = isset($matches[1]) ? (int)$matches[1] : 0;
    }
    
    return [
        'status' => $status_code,
        'body' => json_decode($response, true)
    ];
}

/**
 * Memastikan session Open-WA dalam status ready.
 * Jika disconnected, auto-reconnect (stop → start).
 * 
 * @return array ['ready' => bool, 'message' => string, 'session_status' => string|null]
 */
function ensure_wa_session_ready() {
    // 1. Ambil semua sessions
    $res = owa_request('GET', '/sessions');
    
    if ($res['status'] === 0) {
        return ['ready' => false, 'message' => 'Gagal terhubung ke WA Gateway. Pastikan port 2785 aktif.', 'session_status' => null];
    }
    
    if (!is_array($res['body'])) {
        return ['ready' => false, 'message' => 'Format respon WA Gateway tidak valid.', 'session_status' => null];
    }
    
    // 2. Cari session marketing-zahra
    $session = null;
    foreach ($res['body'] as $s) {
        if (isset($s['name']) && $s['name'] === WA_SESSION_NAME) {
            $session = $s;
            break;
        }
    }
    
    if (!$session) {
        return ['ready' => false, 'message' => 'Sesi WA "' . WA_SESSION_NAME . '" tidak ditemukan di gateway.', 'session_status' => null];
    }
    
    $session_id = $session['id'];
    $session_status = $session['status'] ?? 'unknown';
    
    // 3. Jika sudah ready, langsung return
    if ($session_status === 'ready') {
        return ['ready' => true, 'message' => 'Sesi WA terhubung.', 'session_status' => 'ready'];
    }
    
    // 4. Session tidak ready → auto-reconnect
    error_log("[WA] Session " . WA_SESSION_NAME . " status: {$session_status}. Memulai auto-reconnect...");
    
    // Stop dulu
    owa_request('POST', '/sessions/' . $session_id . '/stop');
    sleep(2);
    
    // Start
    owa_request('POST', '/sessions/' . $session_id . '/start');
    
    // Tunggu max 15 detik untuk reconnect
    for ($i = 0; $i < 15; $i++) {
        sleep(1);
        $check = owa_request('GET', '/sessions/' . $session_id);
        if (isset($check['body']['status']) && $check['body']['status'] === 'ready') {
            error_log("[WA] Session " . WA_SESSION_NAME . " berhasil reconnect setelah {$i} detik.");
            return ['ready' => true, 'message' => 'Sesi WA berhasil reconnect.', 'session_status' => 'ready'];
        }
    }
    
    // 5. Gagal reconnect
    $final_status = 'unknown';
    $final_check = owa_request('GET', '/sessions/' . $session_id);
    if (isset($final_check['body']['status'])) {
        $final_status = $final_check['body']['status'];
    }
    
    error_log("[WA] Session " . WA_SESSION_NAME . " gagal reconnect. Status akhir: {$final_status}");
    return [
        'ready' => false,
        'message' => 'Sesi WA gagal reconnect. Status: ' . $final_status . '. Silakan cek WhatsApp di HP Anda.',
        'session_status' => $final_status
    ];
}
?>
