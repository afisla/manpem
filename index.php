<?php
require_once 'config.php';
session_start();

// Jika sudah login, langsung arahkan ke halaman pemesanan
if (isset($_SESSION['penjual_id'])) {
    header('Location: pemesanan.php');
    exit();
}

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Pesanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        /* Style untuk transisi fade-in/out */
        .opacity-0 {
            opacity: 0;
        }
        .transition-opacity {
            transition: opacity 1s ease-in-out;
        }
        .duration-1000 {
            transition-duration: 1s;
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Splash Screen -->
    <!-- Pastikan file 'ss.jpg' ada di direktori yang sama dan sudah dikompresi. -->
    <div id="splash-screen" class="fixed inset-0 z-50 flex items-center justify-center bg-white transition-opacity duration-1000">
        <img src="ss.jpg" alt="Logo" class="w-full h-full object-cover">
    </div>

    <!-- Konten Utama (Login/Daftar) - Awalnya disembunyikan -->
    <div id="main-content" class="min-h-screen flex items-center justify-center opacity-0 transition-opacity duration-1000">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <div id="login-view">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Login Penjual</h2>
                <form id="formLogin">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="space-y-4">
                        <div>
                            <label for="login_username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" id="login_username" name="username" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="login_password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="login_password" name="password" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <button type="submit" class="mt-6 w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Login</button>
                </form>
                <p class="text-center text-sm text-gray-600 mt-4">
                    Belum punya akun? <a href="#" id="showRegister" class="font-medium text-indigo-600 hover:text-indigo-500">Daftar di sini</a>
                </p>
            </div>

            <div id="register-view" class="hidden">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Daftar Akun Baru</h2>
                <form id="formRegister">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="space-y-4">
                         <div>
                            <label for="nama_toko" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                            <input type="text" id="nama_toko" name="nama_toko" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label for="register_username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" id="register_username" name="username" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label for="register_password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="register_password" name="password" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                    <button type="submit" class="mt-6 w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Daftar</button>
                </form>
                <p class="text-center text-sm text-gray-600 mt-4">
                    Sudah punya akun? <a href="#" id="showLogin" class="font-medium text-indigo-600 hover:text-indigo-500">Login di sini</a>
                </p>
            </div>
             <div id="notifikasi" class="mt-4 p-3 rounded-lg text-white text-center hidden"></div>
        </div>
    </div>

    <script>
        // --- LOGIKA SPLASH SCREEN ---
        window.addEventListener('load', () => {
            const splashScreen = document.getElementById('splash-screen');
            const mainContent = document.getElementById('main-content');

            // Tampilkan splash screen selama 3 detik
            setTimeout(() => {
                // Mulai transisi fade-out untuk splash screen
                splashScreen.classList.add('opacity-0');

                // Setelah transisi selesai, sembunyikan splash screen dan tampilkan konten utama
                setTimeout(() => {
                    splashScreen.style.display = 'none';
                    // Mulai transisi fade-in untuk konten utama
                    mainContent.classList.remove('opacity-0');
                }, 1000); // Durasi ini harus sama dengan durasi transisi CSS
            }, 3000); // Waktu tampilan splash screen dalam milidetik (3 detik)
        });

        // --- LOGIKA FORM YANG SUDAH ADA ---
        const API_URL = 'api.php';
        const notifikasiEl = document.getElementById('notifikasi');

        const tampilkanNotifikasi = (pesan, tipe = 'success') => {
            notifikasiEl.textContent = pesan;
            notifikasiEl.className = 'mt-4 p-3 rounded-lg text-white text-center'; // Reset classes
            notifikasiEl.classList.add(tipe === 'success' ? 'bg-green-500' : 'bg-red-500');
            notifikasiEl.classList.remove('hidden');
        };

        const toggleView = (show) => {
            document.getElementById('login-view').classList.toggle('hidden', show !== 'login');
            document.getElementById('register-view').classList.toggle('hidden', show !== 'register');
            if (!notifikasiEl.classList.contains('hidden')) {
                notifikasiEl.classList.add('hidden');
            }
        };

        document.getElementById('showRegister').addEventListener('click', (e) => {
            e.preventDefault();
            toggleView('register');
        });

        document.getElementById('showLogin').addEventListener('click', (e) => {
            e.preventDefault();
            toggleView('login');
        });
        
        async function handleFormSubmit(url, formData) {
            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Network response was not ok.');
                return await response.json();
            } catch (error) {
                console.error("Fetch error:", error);
                tampilkanNotifikasi('Terjadi kesalahan jaringan.', 'error');
                return null;
            }
        }

        document.getElementById('formLogin').addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await handleFormSubmit(`${API_URL}?action=login_penjual`, new FormData(e.target));
            if (result) {
                let msg = result.message;
                if (result.status === 'success' && result.wa_status === 'disconnected') {
                    msg += '\n⚠️ WhatsApp: ' + (result.wa_message || 'Sesi terputus');
                }
                tampilkanNotifikasi(msg, result.status);
                if (result.status === 'success') {
                    setTimeout(() => { window.location.href = 'pemesanan.php'; }, result.wa_status === 'disconnected' ? 2000 : 500);
                }
            }
        });

        document.getElementById('formRegister').addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await handleFormSubmit(`${API_URL}?action=register_penjual`, new FormData(e.target));
            if (result) {
                tampilkanNotifikasi(result.message, result.status);
                if (result.status === 'success') {
                    e.target.reset();
                    setTimeout(() => toggleView('login'), 2000);
                }
            }
        });

        // Cek jika ada pesan dari redirect logout
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('reason') === 'inactive') {
            // Tunda notifikasi hingga konten utama muncul
            setTimeout(() => {
                tampilkanNotifikasi('Anda telah logout otomatis karena tidak aktif.', 'error');
            }, 4100); // Sedikit lebih lama dari total animasi splash
        }
    </script>
</body>
</html>

