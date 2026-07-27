<?php
require_once 'config.php';
session_check();

if (!isset($_SESSION['penjual_id'])) {
    http_response_code(403);
    die("Akses ditolak. Silakan login.");
}
$penjual_id = $_SESSION['penjual_id'];

$pesanan = null;
$kekurangan_bayar = 0;
$is_lunas = false;
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id > 0) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM pesanan WHERE id = ? AND penjual_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $penjual_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pesanan = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // REVISI: Logika baru untuk menghitung kekurangan dan status lunas
    if ($pesanan) {
        $status_pembayaran = $pesanan['status_pembayaran'];
        $is_lunas = ($status_pembayaran == 'Lunas');

        switch ($status_pembayaran) {
            case 'DP':
                $kekurangan_bayar = $pesanan['total_harga'] - $pesanan['jumlah_dp'];
                break;
            case 'belum_lunas':
                $kekurangan_bayar = $pesanan['total_harga'];
                $pesanan['jumlah_dp'] = 0; // Pastikan jumlah DP adalah 0 untuk tampilan
                break;
            case 'Lunas':
                $kekurangan_bayar = 0;
                $pesanan['jumlah_dp'] = $pesanan['total_harga']; // Jika lunas, yg dibayar = total
                break;
            default:
                $kekurangan_bayar = $pesanan['total_harga'];
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengiriman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 p-4">
    <?php if ($pesanan): ?>
    <div class="max-w-lg mx-auto space-y-6">
        <h1 class="text-2xl font-bold text-center text-gray-800">Kelola Pengiriman Pesanan #<?php echo $pesanan['id']; ?></h1>

        <div class="bg-white p-5 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-3">Bukti Pengiriman</h2>
            <div id="bukti-container">
                <?php if (!empty($pesanan['gambar_bukti_kirim'])): ?>
                    <img src="<?php echo htmlspecialchars($pesanan['gambar_bukti_kirim']); ?>" alt="Bukti Kirim" class="rounded-md w-full h-auto object-cover border-2 border-green-500">
                    <p class="text-center text-sm text-green-600 mt-2">Bukti sudah diupload.</p>
                <?php else: ?>
                    <form id="formUploadBukti" class="space-y-3">
                        <input type="hidden" name="order_id" value="<?php echo $pesanan['id']; ?>">
                        <input type="file" name="bukti_kirim" required accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                        <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Upload Bukti</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
	
        <div id="pembayaran-container" class="bg-white p-5 rounded-lg shadow">
           <h2 class="text-lg font-semibold mb-3">Status Pembayaran</h2>
           <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span>Total Harga:</span> <span class="font-medium"><?php echo 'Rp ' . number_format($pesanan['total_harga'], 0, ',', '.'); ?></span></div>
                <div class="flex justify-between"><span>Sudah Dibayar:</span> <span class="font-medium text-green-600"><?php echo 'Rp ' . number_format($pesanan['jumlah_dp'], 0, ',', '.'); ?></span></div>
                <hr>
                <div class="flex justify-between text-base font-bold">
                    <span>KEKURANGAN:</span> 
                    <span class="<?php echo $is_lunas ? 'text-green-600' : 'text-red-600'; ?>"><?php echo 'Rp ' . number_format($kekurangan_bayar, 0, ',', '.'); ?></span>
                </div>
            </div>
            <?php if (!$is_lunas): ?>
            <div class="mt-4">
                <label for="metode_pembayaran" class="block text-sm font-medium text-gray-700">Metode Pembayaran Pelunasan</label>
                <select id="metode_pembayaran" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="Cash">Cash</option>
                    <option value="E-wallet">E-wallet</option>
                    <option value="Transfer">Transfer</option>
                    <option value="Qris">Qris</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <?php endif; ?>
            <button id="btn-lunasi" 
                    class="mt-4 w-full text-white py-2 px-4 rounded-md transition-colors <?php echo $is_lunas ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'; ?>" 
                    <?php echo $is_lunas ? 'disabled' : ''; ?>>
                <?php echo $is_lunas ? 'Pembayaran Sudah Lunas' : 'Tandai Sudah Lunas'; ?>
            </button>
       </div>

        <div class="bg-white p-5 rounded-lg shadow">
             <h2 class="text-lg font-semibold mb-3">Konfirmasi Pesanan Selesai</h2>
             <p class="text-sm text-gray-600 mb-3">Tombol ini akan aktif jika bukti kirim sudah diupload dan pembayaran sudah lunas.</p>
            <button id="btn-selesai" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                 Barang Diterima Pelanggan & Selesaikan Pesanan
            </button>
        </div>
        <div id="notifikasi-iframe" class="fixed top-2 right-2 p-3 rounded-md text-white z-50 opacity-0 transition-opacity duration-300"></div>
    </div>
    <?php else: ?>
        <p class="text-center text-red-500">Gagal memuat data pesanan. ID tidak valid atau Anda tidak memiliki akses.</p>
    <?php endif; ?>

<script>
    const API_URL = 'api.php';
    const orderId = <?php echo $order_id; ?>;
    const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
    let isLunas = <?php echo $is_lunas ? 'true' : 'false'; ?>;
    let buktiUploaded = <?php echo !empty($pesanan["gambar_bukti_kirim"]) ? 'true' : 'false'; ?>;

    const notifikasi = (pesan, tipe = 'success') => {
        const el = document.getElementById('notifikasi-iframe');
        el.textContent = pesan;
        el.className = `fixed top-2 right-2 p-3 rounded-md text-white z-50 transition-opacity duration-300 ${tipe === 'success' ? 'bg-green-500' : 'bg-red-500'} opacity-100`;
        setTimeout(() => el.classList.remove('opacity-100'), 3000);
    };

    function checkSelesaiButton() {
        const btn = document.getElementById('btn-selesai');
        if (btn) {
            btn.disabled = !(isLunas && buktiUploaded);
        }
    }

    /**
     * Fungsi untuk mengompres gambar menggunakan Canvas.
     * @param {File} file - File gambar yang akan dikompres.
     * @param {number} quality - Kualitas gambar (0 sampai 1). 0.2 untuk penghematan ~80%.
     * @returns {Promise<Blob>} Promise yang akan resolve dengan Blob gambar terkompres.
     */
    function compressImage(file, quality = 0.2) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = (event) => {
                const img = new Image();
                img.src = event.target.result;
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    canvas.toBlob((blob) => {
                        if (blob) {
                            console.log(`Original size: ${(file.size / 1024).toFixed(2)} KB`);
                            console.log(`Compressed size: ${(blob.size / 1024).toFixed(2)} KB`);
                            resolve(blob);
                        } else {
                            reject(new Error('Gagal membuat blob dari canvas.'));
                        }
                    }, 'image/jpeg', quality);
                };
                img.onerror = (error) => reject(error);
            };
            reader.onerror = (error) => reject(error);
        });
    }


    document.addEventListener('DOMContentLoaded', () => {
        checkSelesaiButton();

        const formUpload = document.getElementById('formUploadBukti');
        if (formUpload) {
            formUpload.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitButton = formUpload.querySelector('button[type="submit"]');
                const fileInput = formUpload.querySelector('input[type="file"]');
                const file = fileInput.files[0];

                if (!file) {
                    notifikasi('Silakan pilih gambar terlebih dahulu.', 'error');
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Mengompres gambar...';

                try {
                    // Kompres gambar dengan kualitas 20% (untuk penghematan ~80%)
                    const compressedBlob = await compressImage(file, 0.2);

                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('csrf_token', CSRF_TOKEN);
                    // Kirim blob yang sudah dikompres dengan nama file asli
                    formData.append('bukti_kirim', compressedBlob, file.name);

                    submitButton.textContent = 'Mengupload...';

                    const response = await fetch(`${API_URL}?action=upload_bukti_kirim`, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    notifikasi(result.message, result.status);

                    if (result.status === 'success') {
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        // Aktifkan kembali tombol jika upload gagal
                        submitButton.disabled = false;
                        submitButton.textContent = 'Upload Bukti';
                    }

                } catch (error) {
                    notifikasi('Gagal mengompres atau mengupload gambar.', 'error');
                    console.error('Compression/Upload Error:', error);
                    submitButton.disabled = false;
                    submitButton.textContent = 'Upload Bukti';
                }
            });
        }


        const btnLunasi = document.getElementById('btn-lunasi');
        if (btnLunasi && !btnLunasi.disabled) {
            btnLunasi.addEventListener('click', async () => {
                const formData = new FormData();
                formData.append('id', orderId);
                formData.append('csrf_token', CSRF_TOKEN);
                
                const metodeSelect = document.getElementById('metode_pembayaran');
                if (metodeSelect) {
                    formData.append('metode_pembayaran', metodeSelect.value);
                }
                
                try {
                    const response = await fetch(`${API_URL}?action=lunasi_pembayaran`, { method: 'POST', body: formData });
                    const result = await response.json();
                    notifikasi(result.message, result.status);
                    if (result.status === 'success') {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } catch (error) {
                    notifikasi('Terjadi kesalahan jaringan.', 'error');
                }
            });
        }

        const btnSelesai = document.getElementById('btn-selesai');
        if (btnSelesai) {
            btnSelesai.addEventListener('click', () => {
                window.parent.postMessage({ type: 'orderCompleted', orderId: orderId, token: '<?php echo $pesanan["token"] ?? ""; ?>' }, '*');
            });
        }
    });
</script>
</body>
</html>