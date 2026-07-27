<?php
require_once 'config.php';
session_check();

// Jika belum login, tendang ke halaman index
if (!isset($_SESSION['penjual_id'])) {
    header('Location: index.php');
    exit();
}
$nama_toko = htmlspecialchars($_SESSION['nama_toko']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - <?php echo $nama_toko; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-btn { transition: all 0.3s ease-in-out; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: white; background-image: linear-gradient(to right, #4f46e5, #7c3aed); border-bottom-color: #a78bfa; }
        .modal { display: none; }
        .modal.active { display: flex; }
        #notifikasi { transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out; opacity: 0; transform: translateY(-20px); pointer-events: none; }
        #notifikasi.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto p-4 md:p-8 max-w-7xl">
        <header class="mb-6">
            <div class="grid justify-between items-center">
                <div>
                     <h1 class="text-3xl font-bold text-gray-900"><?php echo $nama_toko; ?></h1>
                     <p class="text-gray-600 mt-1">Selamat datang! Kelola Layanan Pesanan Anda dengan lebih mudah.</p>
                </div> <span>----------------------------------------------</span>
                <div class="flex space-x-2 items-center">
                    <span id="wa-status" class="text-xs px-2 py-1 rounded-full bg-gray-200 text-gray-600">WA: ...</span>
                    <button id="btn-pengaturan" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">Pengaturan</button>
                    <a href="logout.php" class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600">Logout</a>
                </div>
            </div>
        </header>

        <nav class="flex justify-center flex-wrap bg-white rounded-lg shadow-md mb-8">
            <button data-tab="dashboard" class="tab-btn py-3 px-4 sm:px-6 font-medium text-gray-600 hover:bg-gray-200 rounded-tl-lg active">Dashboard</button>
            <button data-tab="pelanggan" class="tab-btn py-3 px-4 sm:px-6 font-medium text-gray-600 hover:bg-gray-200">Pelanggan</button>
            <button data-tab="pesanan-baru" class="tab-btn py-3 px-4 sm:px-6 font-medium text-gray-600 hover:bg-gray-200">Pesanan Baru</button>
            <button data-tab="daftar-pesanan" class="tab-btn py-3 px-4 sm:px-6 font-medium text-gray-600 hover:bg-gray-200 rounded-tr-lg">Daftar Pesanan</button>
        </nav>

        <main>
            <!-- Konten Tab Dashboard -->
            <div id="tab-dashboard" class="tab-content active">
                <h2 class="text-2xl font-semibold mb-4 text-center sm:text-left">Ringkasan Bisnis</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-gray-500 text-sm font-medium">Total Pesanan</h3><p id="stat-total-pesanan" class="text-3xl font-bold mt-1">0</p></div>
                    <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-gray-500 text-sm font-medium">Total Pendapatan</h3><p id="stat-total-pendapatan" class="text-3xl font-bold mt-1">Rp 0</p></div>
                    <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-gray-500 text-sm font-medium">Pesanan Baru Hari Ini</h3><p id="stat-pesanan-baru" class="text-3xl font-bold mt-1">0</p></div>
                    <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-gray-500 text-sm font-medium">Perlu Diproses</h3><p id="stat-perlu-diproses" class="text-3xl font-bold mt-1">0</p></div>
                </div>
            </div>

            <!-- Konten Tab Pelanggan -->
            <div id="tab-pelanggan" class="tab-content">
                 <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
                        <h2 class="text-2xl font-semibold mb-4">Daftarkan Pelanggan</h2>
                        <form id="formTambahPelanggan"><div class="space-y-4"><div><label for="nama" class="block text-sm font-medium text-gray-700">Nama</label><input type="text" id="nama" name="nama" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"></div><div><label for="alamat" class="block text-sm font-medium text-gray-700">Alamat</label><textarea id="alamat" name="alamat" rows="3" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"></textarea></div><div><label for="no_whatsapp" class="block text-sm font-medium text-gray-700">No. WhatsApp</label><input type="tel" id="no_whatsapp" name="no_whatsapp" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm" placeholder="Contoh: 628123456789"></div></div><button type="submit" class="mt-6 w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Daftarkan</button></form>
                    </div>
                    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md"><h2 class="text-2xl font-semibold mb-4">Daftar Pelanggan</h2><div id="daftarPelangganContainer" class="space-y-3"></div></div>
                </div>
            </div>

            <!-- Konten Tab Pesanan Baru -->
            <div id="tab-pesanan-baru" class="tab-content">
                <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-semibold mb-4">Buat Pesanan Baru</h2>
                    <form id="formBuatPesanan" enctype="multipart/form-data"><div class="space-y-4"><div><label for="pelanggan_id" class="block text-sm font-medium text-gray-700">Pilih Pelanggan</label><select id="pelanggan_id" name="pelanggan_id" required class="mt-1 block w-full p-2 border bg-white rounded-md shadow-sm"></select></div><div class="grid grid-cols-2 gap-4"><div><label for="tanggal_pengambilan" class="block text-sm font-medium">Tgl Ambil / Diantar :</label><input type="date" id="tanggal_pengambilan" name="tanggal_pengambilan" class="mt-1 block w-full p-2 border rounded-md shadow-sm"></div><div><label for="jam_pengambilan" class="block text-sm font-medium">Jam :</label><input type="time" id="jam_pengambilan" name="jam_pengambilan" class="mt-1 block w-full p-2 border rounded-md shadow-sm"></div></div><div><label for="deskripsi" class="block text-sm font-medium">Deskripsi Pesanan</label><textarea id="deskripsi" name="deskripsi" rows="3" required class="mt-1 block w-full p-2 border rounded-md shadow-sm"></textarea></div><div><label for="gambar_sample" class="block text-sm font-medium">Upload Gambar Sample</label><input type="file" id="gambar_sample" name="gambar_sample" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"></div><hr><div><label for="estimasi_harga" class="block text-sm font-medium">Estimasi Harga (Rp)</label><input type="number" id="estimasi_harga" name="estimasi_harga" required class="mt-1 block w-full p-2 border rounded-md shadow-sm"></div><div><label for="ongkos_kirim" class="block text-sm font-medium">Ongkos Kirim (Rp)</label><div class="flex items-center space-x-2 mt-1"><input type="number" id="ongkos_kirim" name="ongkos_kirim" required placeholder="0" class="block w-full p-2 border rounded-md shadow-sm"><button type="button" id="btnCekOngkir" class="bg-gray-200 px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-300 whitespace-nowrap">Cek Ongkir</button></div></div><div><label for="total_harga" class="block text-sm font-medium">Total Harga (Rp)</label><input type="number" id="total_harga" name="total_harga" readonly class="mt-1 block w-full p-2 border rounded-md shadow-sm bg-gray-100"></div></div><hr class="my-6">
                        <input type="hidden" name="status_pembayaran" id="status_pembayaran" value="Belum Lunas">
                        <input type="hidden" name="metode_pembayaran" id="metode_pembayaran" value="">
                        <input type="hidden" name="jumlah_dp" id="jumlah_dp" value="0">
                        <div class="space-y-3">
                            <button type="button" id="btn-pembayaran" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Atur Pembayaran</button>
                            <div class="text-center text-sm">Status: <span id="display-status-pembayaran" class="font-bold text-gray-800">Belum Lunas</span></div>
                        </div>
                        <button type="submit" id="btnSubmitPesanan" class="mt-6 w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors">Buat Pesanan</button>
                    </form>
                </div>
            </div>
            
            <!-- Konten Tab Daftar Pesanan -->
            <div id="tab-daftar-pesanan" class="tab-content">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex flex-col sm:flex-row justify-between items-center mb-4"><h2 class="text-2xl font-semibold">Daftar Pesanan Masuk</h2><div class="mt-4 sm:mt-0"><label for="filterPelanggan" class="text-sm font-medium mr-2">Filter:</label><select id="filterPelanggan" class="bg-gray-50 border border-gray-300 text-sm rounded-lg p-2"></select></div></div>
                    <div id="daftarPesanan" class="space-y-6"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Elemen Notifikasi Global -->
    <div id="notifikasi" class="fixed top-5 right-5 p-4 rounded-lg text-white z-[1050]"></div>

    <!-- Kumpulan Modal -->
    <div id="modal-ongkir" class="modal fixed inset-0 bg-black bg-opacity-50 z-[1000] items-center justify-center"><div class="bg-white rounded-lg shadow-xl w-11/12 max-w-4xl h-5/6 flex flex-col p-4"><div class="flex justify-between items-center mb-2"><h2 class="text-xl font-bold">Cek Ongkos Kirim</h2><button data-close-modal="modal-ongkir" class="text-2xl">&times;</button></div><iframe class="w-full h-full border-0" src="ongkir.html"></iframe></div></div>
    <div id="modal-pembayaran" class="modal fixed inset-0 bg-black bg-opacity-50 z-[1000] items-center justify-center"><div class="bg-white rounded-lg shadow-xl w-11/12 max-w-lg h-auto flex flex-col p-4"><div class="flex justify-between items-center mb-2"><h2 class="text-xl font-bold">Pengaturan Pembayaran</h2><button data-close-modal="modal-pembayaran" class="text-2xl">&times;</button></div><div class="w-full h-[500px]"><iframe id="pembayaran-iframe" class="w-full h-full border-0" src="pembayaran.html"></iframe></div></div></div>
    <div id="modal-kelola-pengiriman" class="modal fixed inset-0 bg-black bg-opacity-50 z-[1000] items-center justify-center"><div class="bg-white rounded-lg shadow-xl w-11/12 max-w-2xl h-5/6 flex flex-col p-4"><div class="flex justify-between items-center mb-2"><h2 class="text-xl font-bold">Kelola Pengiriman & Penyelesaian</h2><button data-close-modal="modal-kelola-pengiriman" class="text-2xl">&times;</button></div><iframe id="iframe-kelola-pengiriman" class="w-full h-full border-0"></iframe></div></div>
    <div id="modal-ringkasan" class="modal fixed inset-0 bg-black bg-opacity-50 z-[1000] items-center justify-center"><div class="bg-white rounded-lg shadow-xl w-11/12 max-w-4xl h-5/6 flex flex-col p-4"><div class="flex justify-between items-center mb-2"><h2 class="text-xl font-bold">Ringkasan Pesanan Selesai</h2><div><button id="btn-screenshot" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 mr-2">Simpan Screenshot</button><button data-close-modal="modal-ringkasan" class="text-2xl">&times;</button></div></div><iframe id="ringkasan-iframe" class="w-full h-full border-0"></iframe></div></div>

    <!-- Modal Pengaturan -->
    <div id="modal-pengaturan" class="modal fixed inset-0 bg-black bg-opacity-50 z-[1000] items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-5xl h-[85vh] flex flex-col p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Pengaturan Admin & Pembayaran</h2>
                <button data-close-modal="modal-pengaturan" class="text-2xl">&times;</button>
            </div>
            
            <div class="flex border-b mb-4">
                <button id="tab-btn-konfigurasi" class="px-4 py-2 text-indigo-600 border-b-2 border-indigo-600 font-semibold focus:outline-none">Pengaturan Konfigurasi</button>
                <button id="tab-btn-pembayaran" class="px-4 py-2 text-gray-500 border-b-2 border-transparent font-semibold focus:outline-none hover:text-indigo-600">Tabel Pembayaran</button>
            </div>

            <div id="tab-content-konfigurasi" class="flex-1 overflow-auto">
            <form id="formPengaturan">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran Aktif untuk Pelanggan (Belum Lunas)</label>
                        <div class="flex items-center space-x-4 mb-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="opsi_pembayaran_aktif[]" value="qris" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">QRIS Dinamis</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="opsi_pembayaran_aktif[]" value="transfer" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">Transfer Bank / E-Wallet</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="qris_statis" class="block text-sm font-medium text-gray-700">QRIS String Statis</label>
                        <div class="mb-2">
                            <input type="file" id="upload_qris_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                            <p class="text-xs text-gray-500 mt-1">Atau upload gambar QRIS Anda untuk di-decode otomatis menjadi string.</p>
                        </div>
                        <textarea id="qris_statis" name="qris_statis" rows="3" class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm" placeholder="Atau paste string QRIS dari aplikasi qris/merchant Anda di sini..."></textarea>
                    </div>
                    <div>
                        <label for="detail_pembayaran" class="block text-sm font-medium text-gray-700">Detail Transfer Bank / E-Wallet</label>
                        <textarea id="detail_pembayaran" name="detail_pembayaran" rows="3" class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm" placeholder="BCA: 123456 a/n Budi..."></textarea>
                    </div>
                    <div>
                        <label for="no_whatsapp_penjual" class="block text-sm font-medium text-gray-700">No. WhatsApp Penjual (Notifikasi Pembayaran)</label>
                        <input type="tel" id="no_whatsapp_penjual" name="no_whatsapp_penjual" class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm" placeholder="Contoh: 628123456789">
                        <p class="text-xs text-gray-500 mt-1">Nomor ini akan menerima notifikasi WhatsApp saat pelanggan melakukan pembayaran. Pastikan format: 628xxx.</p>
                    </div>
                </div>
                <button type="submit" class="mt-6 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Simpan Pengaturan</button>
            </form>
            </div>
            
            <div id="tab-content-pembayaran" class="hidden flex-1 overflow-auto">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 border">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 border">ID & Pelanggan</th>
                                <th class="px-4 py-3 border">Total & Sisa Tagihan</th>
                                <th class="px-4 py-3 border">Status Bayar</th>
                                <th class="px-4 py-3 border">Status Pesanan</th>
                                <th class="px-4 py-3 border">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-tabel-pembayaran">
                            <!-- Diisi via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pelanggan -->
    <div id="modal-edit-pelanggan" class="modal fixed inset-0 bg-black bg-opacity-50 z-[1000] items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-md h-auto flex flex-col p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Edit Data Pelanggan</h2>
                <button data-close-modal="modal-edit-pelanggan" class="text-2xl">&times;</button>
            </div>
            <form id="formEditPelanggan">
                <input type="hidden" id="edit_pelanggan_id" name="id">
                <div class="space-y-4">
                    <div>
                        <label for="edit_nama" class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" id="edit_nama" name="nama" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="edit_alamat" class="block text-sm font-medium text-gray-700">Alamat</label>
                        <textarea id="edit_alamat" name="alamat" rows="3" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"></textarea>
                    </div>
                    <div>
                        <label for="edit_no_whatsapp" class="block text-sm font-medium text-gray-700">No. WhatsApp</label>
                        <input type="tel" id="edit_no_whatsapp" name="no_whatsapp" required class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm" placeholder="Contoh: 628123456789">
                    </div>
                </div>
                <button type="submit" class="mt-6 w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        // --- SCRIPT UTAMA APLIKASI ---

        // Variabel Global dan Fungsi Helper
        const API_URL = 'api.php';
        const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        const notifikasiEl = document.getElementById('notifikasi');

        const escapeHtml = (text) => {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        const formatNoWhatsapp = (phone) => {
            if (!phone) return '';
            let clean = phone.replace(/\D/g, '');
            if (clean.startsWith('0')) {
                clean = '62' + clean.substring(1);
            } else if (clean.startsWith('8')) {
                clean = '62' + clean;
            }
            return clean;
        };

        const tampilkanNotifikasi = (pesan, tipe = 'success') => {
            notifikasiEl.textContent = pesan;
            notifikasiEl.className = 'fixed top-5 right-5 p-4 rounded-lg text-white z-[1050]'; // Reset class
            let bgClass = 'bg-green-500';
            if (tipe === 'error') {
                bgClass = 'bg-red-500';
            } else if (tipe === 'info') {
                bgClass = 'bg-blue-500';
            }
            notifikasiEl.classList.add(bgClass, 'show');
            setTimeout(() => notifikasiEl.classList.remove('show'), 4000);
        };
        
        const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
        
        const formatPesanWA = (p) => {
            const namaToko = "<?php echo isset($_SESSION['nama_toko']) ? addslashes($_SESSION['nama_toko']) : 'Toko Kami'; ?>";
            
            let statusText = 'telah diterima';
            if (p.status === 'Diproses') {
                statusText = 'sedang diproses';
            } else if (p.status === 'Dikirim') {
                statusText = 'sedang dikirim';
            } else if (p.status === 'Selesai') {
                statusText = 'telah SELESAI dan diterima';
            }
            
            const formatRp = (num) => formatRupiah(parseFloat(num) || 0);
            const harga = parseFloat(p.estimasi_harga) || 0;
            const ongkir = parseFloat(p.ongkos_kirim) || 0;
            const total = parseFloat(p.total_harga) || 0;
            const dp = parseFloat(p.jumlah_dp) || 0;
            const sisa = Math.max(0, total - dp);

            let pesan = `*✨ UPDATE PESANAN - ${namaToko.toUpperCase()} ✨*\n\n`;
            pesan += `Halo Kak *${p.nama}*, pesanan Kakak *${statusText}*! 🎉\n\n`;
            pesan += `*📋 DETAIL PESANAN:*\n`;
            pesan += `▪️ *ID Pesanan:* #${p.id}\n`;
            pesan += `▪️ *Deskripsi:* ${p.deskripsi_pesanan}\n`;
            if (p.waktu_pengambilan) {
                pesan += `▪️ *Rencana Ambil/Antar:* ${p.waktu_pengambilan}\n`;
            }
            pesan += `\n*💰 RINCIAN BIAYA:*\n`;
            pesan += `▪️ *Estimasi Harga:* ${formatRp(harga)}\n`;
            pesan += `▪️ *Ongkos Kirim:* ${formatRp(ongkir)}\n`;
            pesan += `▪️ *Total Biaya:* ${formatRp(total)}\n`;
            
            if (p.status_pembayaran === 'Lunas') {
                pesan += `▪️ *Sudah Dibayar:* *${formatRp(total)}* (Lunas)\n`;
            } else if (dp > 0) {
                pesan += `▪️ *DP Paid:* ${formatRp(dp)}\n`;
                pesan += `▪️ *Sisa Tagihan:* *${formatRp(sisa)}*\n`;
            } else {
                pesan += `▪️ *Total Tagihan:* *${formatRp(total)}* (Belum Dibayar)\n`;
            }
            pesan += `▪️ *Status Pembayaran:* _${p.status_pembayaran}_\n\n`;
            
            if (p.status === 'Selesai') {
			    pesan += `*🧾 BUKTI TRANSAKSI SELESAI:*\n`;
			    pesan += `Kakak dapat mengunduh/melihat bukti transaksi resmi pesanan selesai di sini:\n\n`;
			    pesan += `${window.location.origin}/ringkasan_pesanan.php?id=${p.id}&token=${p.token || ''}\n`;
			    pesan += `\n`; 
			                pesan += `Terima kasih banyak telah berbelanja di toko kami! Semoga Kakak puas dengan layanan kami. 🙏✨`;
            } else if (p.status === 'Diproses') {
                pesan += `Kami akan segera menyiapkan pesanan terbaik untuk Kakak. Mohon ditunggu ya! Jika ada pertanyaan, langsung hubungi kami. 😊\n\n`;
            } else if (p.status === 'Baru') {
                pesan += `Silakan lakukan pengecekan pesanan dan pilih opsi pembayaran melalui link invoice di bawah ini:\n`;
                pesan += `${window.location.origin}/ringkasan_pesanan.php?id=${p.id}&token=${p.token || ''}\n\n`;
                pesan += `Terima kasih telah mempercayakan pesanan Kakak kepada toko kami. 🙏✨`;
            } else if (p.status === 'Dikirim') {
                if (p.status_pembayaran !== 'Lunas') {
                    pesan += `🚚 *PENGIRIMAN & PELUNASAN:*\n`;
                    pesan += `Pesanan Kakak saat ini sedang dalam proses pengiriman. Karena pembayaran belum lunas, mohon kesediaannya untuk melakukan pelunasan *Sisa Tagihan* sebesar *${formatRp(sisa)}* saat barang tiba atau diambil. Terima kasih banyak! 🙏✨\n\n`;
                } else {
                    pesan += `🚚 *PENGIRIMAN:*\n`;
                    pesan += `Pesanan Kakak saat ini sedang dalam proses pengiriman menuju lokasi tujuan. Terima kasih banyak telah melakukan pelunasan pembayaran! 😊🚀\n\n`;
                }
            } else {
                if (p.metode_pembayaran && p.metode_pembayaran.toLowerCase() === 'qris') {
                    pesan += `Lihat detail pesanan dan QRIS Pembayaran di sini:\n${window.location.origin}/ringkasan_pesanan.php?id=${p.id}&token=${p.token || ''}\n\n`;
                }
                pesan += `Terima kasih telah mempercayakan pesanan Kakak kepada toko kami. 🙏✨`;
            }
            
            return pesan;
        };

        const kirimWhatsapp = async (p) => {
            const formattedNomor = formatNoWhatsapp(p.no_whatsapp);
            const pesan = formatPesanWA(p);
            
            tampilkanNotifikasi('Sedang mengirim notifikasi WhatsApp...', 'info');
            
            const response = await fetchData('api.php?action=kirim_wa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    no_whatsapp: formattedNomor,
                    pesan: pesan
                })
            });
            
            if (response && response.status === 'success') {
                tampilkanNotifikasi('Notifikasi WhatsApp berhasil dikirim!', 'success');
            } else {
                tampilkanNotifikasi(response?.message || 'Gagal mengirim notifikasi WhatsApp.', 'error');
            }
        };
        
        const hitungTotal = () => {
            const harga = parseFloat(document.getElementById('estimasi_harga').value) || 0;
            const ongkir = parseFloat(document.getElementById('ongkos_kirim').value) || 0;
            document.getElementById('total_harga').value = harga + ongkir;
        };

        // Fungsi Fetch API Terpusat dengan Penanganan Error yang Lebih Baik
        async function fetchData(url, options = {}) {
            try {
                // Auto-add CSRF token to POST requests
                if (options.method === 'POST') {
                    if (options.body instanceof FormData) {
                        options.body.append('csrf_token', CSRF_TOKEN);
                    } else if (options.body instanceof URLSearchParams) {
                        options.body.append('csrf_token', CSRF_TOKEN);
                    } else if (typeof options.body === 'string') {
                        options.body += '&csrf_token=' + encodeURIComponent(CSRF_TOKEN);
                    }
                    if (!options.headers) options.headers = {};
                    if (!(options.headers instanceof Headers) && !options.headers['Content-Type']) {
                        // Keep existing Content-Type if set (e.g., for URLSearchParams)
                    }
                }
                const response = await fetch(url, options);
                const responseData = await response.json();
                if (!response.ok) {
                    throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
                }
                return responseData;
            } catch (error) {
                console.error('Fetch error:', error);
                tampilkanNotifikasi(error.message || 'Terjadi kesalahan jaringan atau server tidak merespon.', 'error');
                return null;
            }
        }
        
        // --- FUNGSI-FUNGSI UNTUK MEMUAT DATA (LOADERS) ---

        async function loadDashboardData() { 
            const result = await fetchData(`${API_URL}?action=get_dashboard_stats`);
            if (result && result.status === 'success') {
                document.getElementById('stat-total-pesanan').textContent = result.data.total_pesanan;
                document.getElementById('stat-total-pendapatan').textContent = formatRupiah(result.data.total_pendapatan);
                document.getElementById('stat-pesanan-baru').textContent = result.data.pesanan_baru_hari_ini;
                document.getElementById('stat-perlu-diproses').textContent = result.data.perlu_diproses;
            }
        }
        
        async function loadPelanggan() { 
            const result = await fetchData(`${API_URL}?action=get_pelanggan`);
            if (!result || result.status !== 'success') return;
            
            const container = document.getElementById('daftarPelangganContainer');
            const selectPesanan = document.getElementById('pelanggan_id');
            const selectFilter = document.getElementById('filterPelanggan');
            
            container.innerHTML = '';
            selectPesanan.innerHTML = ''; // Kosongkan dulu
            selectFilter.innerHTML = '<option value="0">Semua Pelanggan</option>';
            
            result.data.forEach(p => { 
                const escapedNama = escapeHtml(p.nama);
                const escapedWhatsapp = escapeHtml(p.no_whatsapp);
                const escapedAlamat = escapeHtml(p.alamat || '');

                container.innerHTML += `<div class="flex justify-between items-center p-3 border rounded-md">
                    <div>
                        <p class="font-semibold text-gray-800">${escapedNama}</p>
                        <p class="text-sm text-gray-500">${escapedWhatsapp}</p>
                        ${p.alamat ? `<p class="text-xs text-gray-400 mt-1">${escapedAlamat}</p>` : ''}
                    </div>
                    <button class="btn-edit-pelanggan bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1.5 rounded-md text-xs font-semibold transition" 
                            data-id="${p.id}" 
                            data-nama="${escapedNama}" 
                            data-whatsapp="${escapedWhatsapp}" 
                            data-alamat="${escapedAlamat}">
                        Edit
                    </button>
                </div>`; 
                selectPesanan.innerHTML += `<option value="${p.id}">${escapedNama} - ${escapedAlamat}</option>`; 
                selectFilter.innerHTML += `<option value="${p.id}">${escapedNama}</option>`; 
            });

            // Event listener tombol edit
            container.querySelectorAll('.btn-edit-pelanggan').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_pelanggan_id').value = this.dataset.id;
                    document.getElementById('edit_nama').value = this.dataset.nama;
                    document.getElementById('edit_alamat').value = this.dataset.alamat;
                    document.getElementById('edit_no_whatsapp').value = this.dataset.whatsapp;
                    document.getElementById('modal-edit-pelanggan').classList.add('active');
                });
            });

            // --- [PERBAIKAN UX KRUSIAL] ---
            // Cek jika ada pelanggan, aktifkan tombol submit. Jika tidak, nonaktifkan.
            const submitButton = document.getElementById('btnSubmitPesanan');
            if (result.data.length === 0) {
                selectPesanan.innerHTML = '<option value="">-- Silakan tambah pelanggan dulu --</option>';
                submitButton.disabled = true;
                submitButton.classList.add('bg-gray-400', 'cursor-not-allowed');
                submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
            } else {
                submitButton.disabled = false;
                submitButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                submitButton.classList.add('bg-green-600', 'hover:bg-green-700');
            }
        }

        async function loadPesanan(pelangganId = 0) { 
            const container = document.getElementById('daftarPesanan');
            container.innerHTML = '<p class="text-center text-gray-500">Memuat data...</p>';
            const url = `${API_URL}?action=get_pesanan${pelangganId > 0 ? '&pelanggan_id=' + pelangganId : ''}`;
            const result = await fetchData(url);
            
            container.innerHTML = '';
            if (result && result.status === 'success' && result.data.length > 0) {
                result.data.forEach(p => container.appendChild(createPesananCard(p)));
            } else {
                container.innerHTML = '<p class="text-center text-gray-500">Tidak ada pesanan.</p>';
            }
        }

        function refreshAllData() { 
            loadDashboardData(); 
            loadPelanggan(); 
            loadPesanan(document.getElementById('filterPelanggan').value);
        }

        // --- FUNGSI UNTUK MERENDER KARTU PESANAN ---
        function createPesananCard(p) {
            const card = document.createElement('div');
            card.className = 'border border-gray-200 p-4 rounded-lg';
            const statusColors = { 'Baru': 'bg-blue-100 text-blue-800', 'Pending': 'bg-yellow-100 text-yellow-800', 'Deal': 'bg-green-100 text-green-800', 'Diproses': 'bg-indigo-100 text-indigo-800', 'Dikirim': 'bg-purple-100 text-purple-800', 'Selesai': 'bg-gray-100 text-gray-800', 'Batal': 'bg-red-100 text-red-800' };
            const waktuAmbil = p.waktu_pengambilan ? new Date(p.waktu_pengambilan).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Tidak ditentukan';
            
            // Escape all user data to prevent XSS
            const escapedNama = escapeHtml(p.nama);
            const escapedAlamat = escapeHtml(p.alamat || '-');
            const escapedDeskripsi = escapeHtml(p.deskripsi_pesanan);
            const escapedWhatsapp = escapeHtml(p.no_whatsapp);
            const escapedStatus = escapeHtml(p.status);
            const escapedStatusPembayaran = escapeHtml(p.status_pembayaran);
            const escapedGambarSample = escapeHtml(p.gambar_sample || '');
            const escapedGambarBukti = escapeHtml(p.gambar_bukti_kirim || '');
            const safeToken = escapeHtml(p.token || '');
            
            card.innerHTML = `
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-3">
                    <div><h3 class="text-xl font-bold">${escapedNama} <span class="text-sm font-normal text-gray-600">(${escapedAlamat})</span></h3><p class="text-sm text-gray-500">ID: <a href="ringkasan_pesanan.php?id=${parseInt(p.id)}&token=${safeToken}" target="_blank" class="text-indigo-600 hover:underline">#${parseInt(p.id)}</a> <span class="text-xs ml-1 text-gray-400">(klik untuk buka)</span></p></div>
                    <span class="text-sm font-medium px-2.5 py-0.5 rounded-full mt-2 md:mt-0 ${statusColors[escapedStatus] || ''}">${escapedStatus}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1 space-y-2">
                        ${escapedGambarSample ? `<a href="${escapedGambarSample}" target="_blank"><img src="${escapedGambarSample}" alt="Sample" class="rounded-md w-full h-auto object-cover"></a>` : ''}
                        ${escapedGambarBukti ? `<a href="${escapedGambarBukti}" target="_blank"><img src="${escapedGambarBukti}" alt="Bukti Kirim" class="mt-2 rounded-md w-full h-auto object-cover border-2 border-green-500"></a>` : ''}
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <p class="text-gray-700">${escapedDeskripsi}</p>
                        <p class="text-sm font-semibold text-indigo-700">Waktu Ambil/Diantar: ${escapeHtml(waktuAmbil)}</p>
                        <p class="font-bold text-lg">Total: ${formatRupiah(p.total_harga)}</p>
                        <p class="text-xs font-semibold ${escapedStatusPembayaran === 'Lunas' ? 'text-green-600' : 'text-orange-600'}">Pembayaran: ${escapedStatusPembayaran}</p>
                        <div class="flex flex-wrap gap-2 pt-3">
                            <button class="bg-green-500 text-white px-3 py-1 rounded-md text-sm hover:bg-green-600" data-action="kirim-wa" data-nomor="${escapedWhatsapp}" data-total="${parseFloat(p.total_harga)}" data-harga="${parseFloat(p.estimasi_harga)}" data-ongkir="${parseFloat(p.ongkos_kirim)}">Kirim WA</button>
                            <select class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-auto p-1.5" data-order-id="${parseInt(p.id)}">
                                ${['Baru', 'Pending', 'Deal', 'Diproses', 'Dikirim', 'Selesai', 'Batal'].map(s => `<option value="${s}" ${escapedStatus === s ? 'selected' : ''}>${s}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                </div>`;
            
            card.querySelector('[data-action="kirim-wa"]').addEventListener('click', (e) => {
                kirimWhatsapp(p);
            });

            card.querySelector('select').addEventListener('change', (e) => handleStatusChange(e.target, p));
            return card;
        }

        // --- HANDLER UNTUK AKSI PENGGUNA ---
        
        async function handleStatusChange(selectElement, orderData) {
            const newStatus = selectElement.value;
            const resetSelect = () => { selectElement.value = orderData.status; };
            
            if (newStatus === 'Dikirim') {
                const success = await updateStatus(orderData.id, newStatus);
                if (success) {
                    orderData.status = 'Dikirim';
                    kirimWhatsapp(orderData);
                    document.getElementById('iframe-kelola-pengiriman').src = `kelola_pengiriman.php?id=${orderData.id}`;
                    document.getElementById('modal-kelola-pengiriman').classList.add('active');
                } else {
                    resetSelect();
                }
            } else if (newStatus === 'Selesai') {
                if (orderData.status_pembayaran === 'Lunas' && orderData.gambar_bukti_kirim) {
                    const success = await updateStatus(orderData.id, newStatus);
                    if (success) {
                        orderData.status = 'Selesai';
                        kirimWhatsapp(orderData);
                        document.getElementById('ringkasan-iframe').src = `ringkasan_pesanan.php?id=${orderData.id}&token=${orderData.token || ''}`;
                        document.getElementById('modal-ringkasan').classList.add('active');
                    } else {
                        resetSelect();
                    }
                } else {
                    tampilkanNotifikasi('Pesanan belum lunas atau bukti kirim belum diupload.', 'error');
                    resetSelect();
                }
            } else if (newStatus === 'Diproses') {
                const success = await updateStatus(orderData.id, newStatus);
                if (success) {
                    orderData.status = 'Diproses';
                    kirimWhatsapp(orderData);
                } else {
                    resetSelect();
                }
            } else {
                await updateStatus(orderData.id, newStatus);
            }
        }

        async function updateStatus(id, status) {
            if (status === 'Batal') {
                const konfirmasi = prompt('PERHATIAN: Memilih status Batal akan menghapus pesanan ini secara permanen dari database.\\n\\nKetik "YES" untuk melanjutkan penghapusan:');
                if (konfirmasi !== 'YES') {
                    refreshAllData();
                    if (typeof renderTabelPembayaran === 'function') setTimeout(renderTabelPembayaran, 500);
                    return false;
                }
            }

            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            const result = await fetchData(`${API_URL}?action=update_status`, { method: 'POST', body: formData });
            if (result && result.status === 'success') {
                tampilkanNotifikasi(status === 'Batal' ? 'Pesanan berhasil dihapus permanen.' : result.message, 'success');
                refreshAllData();
                return true;
            }
            return false;
        }

        // --- EVENT LISTENERS UTAMA ---
        document.addEventListener('DOMContentLoaded', () => {
            // Inisialisasi Tab
            document.querySelectorAll('.tab-btn').forEach(tab => { 
                tab.addEventListener('click', () => { 
                    document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
                });
            });

            // Inisialisasi Tombol Tutup Modal
            document.querySelectorAll('[data-close-modal]').forEach(btn => { 
                btn.addEventListener('click', () => document.getElementById(btn.dataset.closeModal).classList.remove('active'));
            });
            
            // Event Listener untuk Tombol dan Form
            document.getElementById('btnCekOngkir').addEventListener('click', () => document.getElementById('modal-ongkir').classList.add('active'));
            document.getElementById('btn-pembayaran').addEventListener('click', () => document.getElementById('modal-pembayaran').classList.add('active'));
            
            document.getElementById('formTambahPelanggan').addEventListener('submit', async function(e) { 
                e.preventDefault();
                const result = await fetchData(`${API_URL}?action=tambah_pelanggan`, { method: 'POST', body: new FormData(this) });
                if (result && result.status === 'success') {
                    tampilkanNotifikasi(result.message, 'success');
                    this.reset();
                    loadPelanggan(); // Cukup load pelanggan, tidak perlu refresh semua
                }
            });
            
            document.getElementById('formEditPelanggan').addEventListener('submit', async function(e) { 
                e.preventDefault();
                const result = await fetchData(`${API_URL}?action=edit_pelanggan`, { method: 'POST', body: new FormData(this) });
                if (result && result.status === 'success') {
                    tampilkanNotifikasi(result.message, 'success');
                    document.getElementById('modal-edit-pelanggan').classList.remove('active');
                    loadPelanggan(); // Reload daftar pelanggan
                }
            });
            
            document.getElementById('formBuatPesanan').addEventListener('submit', async function(e) { 
                e.preventDefault();
                // Kirim form hanya jika tombol tidak disabled
                if(document.getElementById('btnSubmitPesanan').disabled) {
                    tampilkanNotifikasi('Tidak bisa membuat pesanan, belum ada pelanggan terdaftar.', 'error');
                    return;
                }
                const result = await fetchData(`${API_URL}?action=buat_pesanan`, { method: 'POST', body: new FormData(this) });
                if (result && result.status === 'success') {
                    tampilkanNotifikasi(result.message, 'success');
                    this.reset();
                    refreshAllData();
                    document.querySelector('.tab-btn[data-tab="daftar-pesanan"]').click();
                }
            });

            ['estimasi_harga', 'ongkos_kirim'].forEach(id => document.getElementById(id).addEventListener('input', hitungTotal));
            document.getElementById('filterPelanggan').addEventListener('change', function() { loadPesanan(this.value); });
            
            document.getElementById('btn-screenshot').addEventListener('click', () => {
                const iframe = document.getElementById('ringkasan-iframe');
                const iframeBody = iframe.contentWindow.document.body;
                const orderId = new URL(iframe.src).searchParams.get('id');
                tampilkanNotifikasi('Membuat screenshot...', 'success');
                
                html2canvas(iframeBody, { allowTaint: true, useCORS: true, scale: 2 }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = `ringkasan-pesanan-${orderId}.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                }).catch(err => {
                    console.error('Gagal membuat screenshot:', err);
                    tampilkanNotifikasi('Gagal membuat screenshot.', 'error');
                });
            });

            // Event listener untuk komunikasi antar iframe
            window.addEventListener('message', async (event) => {
                if (event.data.type === 'paymentData') {
                    const { status, metode, dp } = event.data.payload;
                    document.getElementById('status_pembayaran').value = status;
                    document.getElementById('metode_pembayaran').value = metode;
                    document.getElementById('jumlah_dp').value = dp || 0;
                    document.getElementById('display-status-pembayaran').textContent = (status === 'Lunas' || status === 'DP') ? `${status} (${metode || 'N/A'})` : 'Belum Lunas';
                    document.getElementById('modal-pembayaran').classList.remove('active');
                    tampilkanNotifikasi('Status pembayaran berhasil diatur!', 'success');
                }

                if (event.data.type === 'orderCompleted') {
                    document.getElementById('modal-kelola-pengiriman').classList.remove('active');
                    if (await updateStatus(event.data.orderId, 'Selesai')) {
                        const res = await fetchData(`${API_URL}?action=get_pesanan`);
                        if (res && res.status === 'success') {
                            const orderData = res.data.find(o => o.id == event.data.orderId);
                            if (orderData) {
                                orderData.status = 'Selesai';
                                kirimWhatsapp(orderData);
                            }
                        }
                        document.getElementById('ringkasan-iframe').src = `ringkasan_pesanan.php?id=${event.data.orderId}&token=${event.data.token || ''}`;
                        document.getElementById('modal-ringkasan').classList.add('active');
                    }
                }
            });
            
            // Pengaturan Admin
            document.getElementById('btn-pengaturan').addEventListener('click', async () => {
                const res = await fetchData(`${API_URL}?action=get_pengaturan`);
                if (res && res.status === 'success') {
                    document.getElementById('qris_statis').value = res.data.qris_statis || '';
                    document.getElementById('detail_pembayaran').value = res.data.detail_pembayaran || '';
                    document.getElementById('no_whatsapp_penjual').value = res.data.no_whatsapp_penjual || '';
                    
                    const opsi = res.data.opsi_pembayaran_aktif ? res.data.opsi_pembayaran_aktif.split(',') : ['qris', 'transfer'];
                    const checkboxes = document.querySelectorAll('input[name="opsi_pembayaran_aktif[]"]');
                    checkboxes.forEach(cb => {
                        cb.checked = opsi.includes(cb.value);
                    });

                    renderTabelPembayaran();
                    document.getElementById('modal-pengaturan').classList.add('active');
                }
            });

            document.getElementById('tab-btn-konfigurasi').addEventListener('click', () => {
                document.getElementById('tab-btn-konfigurasi').className = 'px-4 py-2 text-indigo-600 border-b-2 border-indigo-600 font-semibold focus:outline-none';
                document.getElementById('tab-btn-pembayaran').className = 'px-4 py-2 text-gray-500 border-b-2 border-transparent font-semibold focus:outline-none hover:text-indigo-600';
                document.getElementById('tab-content-konfigurasi').classList.remove('hidden');
                document.getElementById('tab-content-pembayaran').classList.add('hidden');
            });

            document.getElementById('tab-btn-pembayaran').addEventListener('click', () => {
                document.getElementById('tab-btn-pembayaran').className = 'px-4 py-2 text-indigo-600 border-b-2 border-indigo-600 font-semibold focus:outline-none';
                document.getElementById('tab-btn-konfigurasi').className = 'px-4 py-2 text-gray-500 border-b-2 border-transparent font-semibold focus:outline-none hover:text-indigo-600';
                document.getElementById('tab-content-pembayaran').classList.remove('hidden');
                document.getElementById('tab-content-konfigurasi').classList.add('hidden');
                renderTabelPembayaran();
            });

            async function renderTabelPembayaran() {
                const tbody = document.getElementById('tbody-tabel-pembayaran');
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-3 text-center border">Memuat data...</td></tr>';
                
                const result = await fetchData(`${API_URL}?action=get_pesanan`);
                tbody.innerHTML = '';
                
                if (!result || result.status !== 'success' || !result.data || result.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-3 text-center border">Belum ada pesanan</td></tr>';
                    return;
                }

                result.data.forEach(p => {
                    const sudahBayar = p.status_pembayaran === 'Lunas' ? p.total_harga : (p.status_pembayaran === 'DP' ? p.jumlah_dp : 0);
                    const sisa = p.total_harga - sudahBayar;
                    const formatRpLokal = (num) => formatRupiah(parseFloat(num) || 0);
                    
                    const tr = document.createElement('tr');
                    tr.className = 'border-b hover:bg-gray-50';
                    tr.innerHTML = `
                        <td class="px-4 py-3 border">
                            <div class="font-bold"><a href="ringkasan_pesanan.php?id=${p.id}&token=${p.token || ''}" target="_blank" class="text-indigo-600 hover:underline">#${p.id}</a></div>
                            <div class="text-xs text-gray-500">${p.nama} <br><span class="text-gray-400">${p.alamat || '-'}</span></div>
                        </td>
                        <td class="px-4 py-3 border">
                            <div>Total: ${formatRpLokal(p.total_harga)}</div>
                            <div class="text-xs text-red-600 mt-1">Sisa: ${formatRpLokal(sisa)}</div>
                        </td>
                        <td class="px-4 py-3 border">
                            <span class="px-2 py-1 bg-gray-200 text-gray-800 text-xs rounded-full font-semibold">${p.status_pembayaran}</span>
                            <div class="text-xs text-gray-500 mt-1">${p.metode_pembayaran || '-'}</div>
                        </td>
                        <td class="px-4 py-3 border">
                            <select class="bg-gray-50 border border-gray-300 text-xs rounded p-1 w-full" onchange="updateStatusPesananFromTable(${p.id}, this.value)">
                                ${['Baru', 'Pending', 'Deal', 'Diproses', 'Dikirim', 'Selesai', 'Batal'].map(s => `<option value="${s}" ${p.status === s ? 'selected' : ''}>${s}</option>`).join('')}
                            </select>
                        </td>
                        <td class="px-4 py-3 border">
                            ${p.status_pembayaran === 'Lunas' 
                                ? '<span class="text-green-600 text-xs font-semibold">Verified ✓</span>' 
                                : (p.status_pembayaran === 'Menunggu Verifikasi DP' 
                                    ? `<button onclick="verifikasiDP(${p.id})" class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600 mb-1">Verifikasi Terima DP</button><br><button onclick="lunasiDariTabel(${p.id})" class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600 mt-1">Verifikasi Lunas</button>` 
                                    : `<button onclick="lunasiDariTabel(${p.id})" class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600">Verifikasi Lunas</button>`)}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            window.updateStatusPesananFromTable = async function(id, status) {
                if (await updateStatus(id, status)) {
                    refreshAllData();
                    setTimeout(renderTabelPembayaran, 500);
                }
            };

            window.verifikasiDP = async function(id) {
                if (confirm('Yakin memverifikasi penerimaan DP pesanan ini?')) {
                    const res = await fetchData(`${API_URL}?action=verifikasi_dp`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ id: id })
                    });
                    if (res && res.status === 'success') {
                        tampilkanNotifikasi(res.message, 'success');
                        refreshAllData();
                        setTimeout(renderTabelPembayaran, 500);
                    }
                }
            };

            window.lunasiDariTabel = async function(id) {
                if (confirm('Yakin memverifikasi pelunasan pesanan ini?')) {
                    const res = await fetchData(`${API_URL}?action=lunasi_pembayaran`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ id: id })
                    });
                    if (res && res.status === 'success') {
                        tampilkanNotifikasi(res.message, 'success');
                        refreshAllData();
                        setTimeout(renderTabelPembayaran, 500);
                    }
                }
            };

            document.getElementById('formPengaturan').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const res = await fetchData(`${API_URL}?action=save_pengaturan`, {
                    method: 'POST',
                    body: formData
                });
                if (res && res.status === 'success') {
                    tampilkanNotifikasi(res.message, 'success');
                    document.getElementById('modal-pengaturan').classList.remove('active');
                }
            });

            // Decode QRIS Image
            document.getElementById('upload_qris_image').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        context.drawImage(img, 0, 0, img.width, img.height);
                        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: "dontInvert",
                        });
                        if (code) {
                            document.getElementById('qris_statis').value = code.data;
                            tampilkanNotifikasi('Berhasil men-decode QRIS!', 'success');
                        } else {
                            tampilkanNotifikasi('Tidak dapat menemukan QR Code pada gambar.', 'error');
                        }
                    };
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });

            // Muat semua data saat halaman pertama kali dibuka
            refreshAllData();
            
            // Cek status WA
            async function checkWaStatus() {
                const waEl = document.getElementById('wa-status');
                try {
                    const res = await fetchData(`${API_URL}?action=check_wa_status`);
                    if (res && res.wa_status === 'connected') {
                        waEl.textContent = 'WA: Connected';
                        waEl.className = 'text-xs px-2 py-1 rounded-full bg-green-100 text-green-700';
                    } else {
                        waEl.textContent = 'WA: Disconnected';
                        waEl.className = 'text-xs px-2 py-1 rounded-full bg-red-100 text-red-700';
                        waEl.title = res?.message || 'Sesi WA terputus';
                    }
                } catch(e) {
                    waEl.textContent = 'WA: Error';
                    waEl.className = 'text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700';
                }
            }
            checkWaStatus();
        });
    </script>
</body>
</html>

