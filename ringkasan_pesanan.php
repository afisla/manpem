<?php
require_once 'config.php';
require_once 'qris_helper.php';
session_check();

// Ambil ID pesanan dan token dari URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/i', '', $_GET['token']) : '';
if ($order_id === 0) { die("ID Pesanan tidak valid."); }
// Endpoint AJAX untuk QRIS Dinamis On-the-fly
if (isset($_GET['ajax_qris']) && isset($_GET['nominal'])) {
    $nominal = (int)$_GET['nominal'];
    
    $sql = "SELECT pengaturan.qris_statis FROM pesanan JOIN pengaturan ON pesanan.penjual_id = pengaturan.penjual_id WHERE pesanan.id = ? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    
    if ($row && !empty($row['qris_statis'])) {
        $dynamicQris = generateDynamicQris($row['qris_statis'], $nominal);
        echo json_encode(['status' => 'success', 'qris_string' => $dynamicQris]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Query untuk mengambil detail pesanan lengkap beserta info penjual
$sql = "SELECT p.*, pl.nama, pl.alamat, pl.no_whatsapp, penjual.nama_toko 
        FROM pesanan p 
        JOIN pelanggan pl ON p.pelanggan_id = pl.id 
        JOIN penjual ON p.penjual_id = penjual.id
        WHERE p.id = ? AND (p.token = ? OR p.token IS NULL)
        LIMIT 1";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "is", $order_id, $token);
mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}

$penjual_id = $pesanan['penjual_id'];
$nama_toko = htmlspecialchars($pesanan['nama_toko']);

require_once 'qris_helper.php';
$sql_pengaturan = "SELECT * FROM pengaturan WHERE penjual_id = ?";
$stmt_pengaturan = mysqli_prepare($koneksi, $sql_pengaturan);
mysqli_stmt_bind_param($stmt_pengaturan, "i", $penjual_id);
mysqli_stmt_execute($stmt_pengaturan);
$res_pengaturan = mysqli_stmt_get_result($stmt_pengaturan);
$pengaturan = mysqli_fetch_assoc($res_pengaturan) ?: ['qris_statis' => '', 'detail_pembayaran' => ''];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Pesanan #<?php echo htmlspecialchars($pesanan['id']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; }
 </style>
</head>
<body class="bg-white p-3">
    <div class="max-w-2xl mx-auto border border-gray-300 rounded-lg p-4">
        <header class="text-center mb-8">
             <h1 class="text-2xl font-bold text-gray-900"><?php echo $nama_toko; ?></h1>
             <p class="text-gray-500">==========°°==========</p>
            <h1 class="text-2xl font-bold text-gray-900">Ringkasan Pesanan</h1>
            <p class="text-gray-500">ID Pesanan: #<?php echo htmlspecialchars($pesanan['id']); ?></p>
            <div class="mt-2">
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold border border-indigo-200"><?php echo htmlspecialchars($pesanan['status']); ?></span>
            </div>
        </header>

        <div class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div class="font-semibold text-gray-800">Nama Pelanggan:</div>
            <div><?php echo htmlspecialchars($pesanan['nama']); ?></div>

            <div class="font-semibold text-gray-800">No. WhatsApp:</div>
            <div><?php echo htmlspecialchars($pesanan['no_whatsapp']); ?></div>

            <div class="font-semibold text-gray-800">Tanggal Pesan:</div>
            <div><?php echo date('d M Y, H:i', strtotime($pesanan['tanggal_pesan'])); ?></div>

            <div class="font-semibold text-gray-800">Waktu Pengambilan:</div>
            <div><?php echo date('d M Y, H:i', strtotime($pesanan['waktu_pengambilan'])); ?></div>
        </div>

        <hr class="my-6">

        <h2 class="text-xl font-semibold mb-4">Detail Pesanan</h2>
        <p class="bg-gray-50 p-4 rounded-md mb-6"><?php echo nl2br(htmlspecialchars($pesanan['deskripsi_pesanan'])); ?></p>

        <div class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div class="font-semibold text-gray-800">Harga Kue:</div>
            <div class="text-right">Rp <?php echo number_format($pesanan['estimasi_harga'], 0, ',', '.'); ?></div>

            <div class="font-semibold text-gray-800">Ongkos Kirim:</div>
            <div class="text-right">Rp <?php echo number_format($pesanan['ongkos_kirim'], 0, ',', '.'); ?></div>

            <div class="font-bold text-gray-900 text-base border-t pt-2 mt-2">Total Harga:</div>
            <div class="font-bold text-gray-900 text-base text-right border-t pt-2 mt-2">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></div>
        </div>

        <hr class="my-6">

        <h2 class="text-xl font-semibold mb-4">Pembayaran</h2>
        <div class="bg-gray-50 p-4 rounded-md mb-6">
            <div class="mb-2"><strong>Status Pembayaran:</strong> <?php echo htmlspecialchars($pesanan['status_pembayaran']); ?></div>
            <div class="mb-2"><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($pesanan['metode_pembayaran'] ?: 'Belum Dipilih'); ?></div>
            <?php if ($pesanan['status_pembayaran'] == 'DP'): ?>
                <div class="mb-2"><strong>Jumlah DP:</strong> Rp <?php echo number_format($pesanan['jumlah_dp'], 0, ',', '.'); ?></div>
            <?php endif; ?>
            
            <?php 
            $opsiAktif = isset($pengaturan['opsi_pembayaran_aktif']) ? explode(',', $pengaturan['opsi_pembayaran_aktif']) : ['qris', 'transfer'];
            $status_bayar = $pesanan['status_pembayaran'];
            $metode_terpilih = strtolower($pesanan['metode_pembayaran']);
            
            $showTabs = ($status_bayar === 'Belum Lunas' || $status_bayar === 'DP');
            
            $amountToPay = 0;
            if ($status_bayar === 'Belum Lunas') {
                $amountToPay = $pesanan['total_harga'];
            } elseif ($status_bayar === 'DP') {
                $amountToPay = $pesanan['total_harga'] - $pesanan['jumlah_dp'];
            }
            
            if (in_array($status_bayar, ['Menunggu Verifikasi DP', 'Menunggu Verifikasi Lunas'])):
            ?>
                <div class="mt-4 p-4 border border-yellow-200 bg-yellow-50 rounded-lg text-center text-yellow-800">
                    <svg class="w-8 h-8 mx-auto mb-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="font-bold text-lg">Menunggu Verifikasi Admin</h3>
                    <p class="text-sm mt-1">Sistem sedang menunggu admin untuk memverifikasi pembayaran Anda.</p>
                </div>
            <?php elseif ($showTabs && $amountToPay > 0): ?>
                
                <?php if ($status_bayar === 'Belum Lunas'): ?>
                <!-- Pilihan Pembayaran DP / Lunas -->
                <div class="mt-4 mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Tipe Pembayaran:</label>
                    <div class="flex items-center space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="pilihan_dp" value="false" checked onchange="document.getElementById('input-dp-container').classList.add('hidden'); handleModeChange();" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Bayar Lunas (Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>)</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="pilihan_dp" value="true" onchange="document.getElementById('input-dp-container').classList.remove('hidden'); handleModeChange();" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Bayar DP</span>
                        </label>
                    </div>
                    <div id="input-dp-container" class="mt-3 hidden">
                        <label class="block text-sm text-gray-600 mb-1">Nominal DP:</label>
                        <input type="number" id="jumlah_dp" oninput="handleDpChange()" value="<?php echo $pesanan['total_harga'] / 2; ?>" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                </div>
                <?php else: ?>
                <div class="mt-4 mb-4 p-3 bg-blue-50 text-blue-800 rounded-lg border border-blue-200 text-sm">
                    <strong>Pelunasan Pesanan</strong><br>
                    Anda telah membayar DP sebesar Rp <?php echo number_format($pesanan['jumlah_dp'], 0, ',', '.'); ?>. <br>
                    Sisa yang harus dibayar: <strong>Rp <?php echo number_format($amountToPay, 0, ',', '.'); ?></strong>
                </div>
                <div class="hidden">
                    <input type="radio" name="pilihan_dp" value="false" checked>
                    <input type="hidden" id="jumlah_dp" value="<?php echo $pesanan['jumlah_dp']; ?>">
                </div>
                <?php endif; ?>

                <div class="mt-4 border border-gray-200 rounded-lg overflow-hidden bg-white">
                    <div class="flex border-b border-gray-200">
                        <?php if (in_array('qris', $opsiAktif) && !empty($pengaturan['qris_statis'])): ?>
                            <button onclick="switchTab('qris')" id="btn-tab-qris" class="flex-1 py-2 px-4 text-center font-medium text-sm text-indigo-600 bg-indigo-50 border-b-2 border-indigo-600 focus:outline-none">QRIS</button>
                        <?php endif; ?>
                        <?php if (in_array('transfer', $opsiAktif) && !empty($pengaturan['detail_pembayaran'])): ?>
                            <button onclick="switchTab('transfer')" id="btn-tab-transfer" class="flex-1 py-2 px-4 text-center font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none">Transfer</button>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <?php if (in_array('qris', $opsiAktif) && !empty($pengaturan['qris_statis'])): ?>
                            <div id="tab-qris" class="tab-pane">
                                <?php $dynamicQris = generateDynamicQris($pengaturan['qris_statis'], $amountToPay); ?>
                                <div class="text-center">
                                    <h3 class="font-bold text-gray-800 mb-2">Scan QRIS untuk Membayar</h3>
                                    <p class="text-sm text-gray-600 mb-4">Nominal otomatis <span class="nominal-display">Rp <?php echo number_format($amountToPay, 0, ',', '.'); ?></span></p>
                                    <div class="inline-block text-center">
                                        <img id="qris-img" src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($dynamicQris); ?>&size=200x200" alt="QRIS Dinamis" class="mx-auto rounded-lg shadow-sm border p-2 bg-white mb-3">
                                        <a id="qris-download" href="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($dynamicQris); ?>&size=500x500" target="_blank" download="QRIS_Pesanan.png" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded text-sm font-medium hover:bg-indigo-200 inline-flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Download QRIS
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('transfer', $opsiAktif) && !empty($pengaturan['detail_pembayaran'])): ?>
                            <div id="tab-transfer" class="tab-pane <?php echo (in_array('qris', $opsiAktif) && !empty($pengaturan['qris_statis'])) ? 'hidden' : ''; ?>">
                                <div class="p-3 bg-indigo-50 rounded text-sm text-indigo-800 border border-indigo-100">
                                    <h3 class="font-bold text-gray-800 mb-2">Transfer Bank / E-Wallet</h3>
                                    <?php echo nl2br(htmlspecialchars($pengaturan['detail_pembayaran'])); ?>
                                    <p class="mt-3 font-semibold">Total Tagihan: <span class="nominal-display">Rp <?php echo number_format($amountToPay, 0, ',', '.'); ?></span></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-6 mb-4">
                     <button onclick="updatePilihanPembayaran()" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-indigo-700 shadow-md text-center transition-colors">
                         Konfirmasi Saya Telah Membayar
                     </button>
                </div>

                <script>
                    function handleModeChange() {
                        const is_dp = document.querySelector('input[name="pilihan_dp"]:checked').value;
                        let nominal = <?php echo $pesanan['total_harga']; ?>;
                        if (is_dp === 'true') {
                            const dpInput = document.getElementById('jumlah_dp');
                            nominal = dpInput ? dpInput.value : nominal;
                        }
                        updateDisplayAndQris(nominal);
                    }

                    let qrisTimeout = null;
                    function handleDpChange() {
                        clearTimeout(qrisTimeout);
                        const dpInput = document.getElementById('jumlah_dp');
                        if(!dpInput) return;
                        const nominal = dpInput.value;
                        
                        // Update nominal text immediately
                        const formatted = parseInt(nominal || 0).toLocaleString('id-ID');
                        const textElements = document.querySelectorAll('.nominal-display');
                        textElements.forEach(el => el.textContent = 'Rp ' + formatted);
                        
                        qrisTimeout = setTimeout(() => {
                            updateQrisFromServer(nominal);
                        }, 500);
                    }

                    function updateDisplayAndQris(nominal) {
                        const formatted = parseInt(nominal || 0).toLocaleString('id-ID');
                        const textElements = document.querySelectorAll('.nominal-display');
                        textElements.forEach(el => el.textContent = 'Rp ' + formatted);
                        updateQrisFromServer(nominal);
                    }

                    function updateQrisFromServer(nominal) {
                        fetch(`ringkasan_pesanan.php?id=<?php echo $order_id; ?>&token=<?php echo htmlspecialchars($token); ?>&ajax_qris=1&nominal=${nominal}`)
                            .then(res => res.json())
                            .then(data => {
                                if(data.status === 'success') {
                                    const qrisImg = document.getElementById('qris-img');
                                    const qrisDownload = document.getElementById('qris-download');
                                    if (!qrisImg || !qrisDownload) return;
                                    const encodedData = encodeURIComponent(data.qris_string);
                                    qrisImg.src = `https://api.qrserver.com/v1/create-qr-code/?data=${encodedData}&size=200x200`;
                                    qrisDownload.href = `https://api.qrserver.com/v1/create-qr-code/?data=${encodedData}&size=500x500`;
                                }
                            });
                    }

                    function updatePilihanPembayaran() {
                        const is_dp = document.querySelector('input[name="pilihan_dp"]:checked').value;
                        const jumlah_dp = document.getElementById('jumlah_dp').value;
                        
                        let currentTab = 'transfer';
                        const qrisPane = document.getElementById('tab-qris');
                        if (qrisPane && !qrisPane.classList.contains('hidden')) currentTab = 'qris';
                        
                        const formData = new FormData();
                        formData.append('id', <?php echo $order_id; ?>);
                        formData.append('token', '<?php echo htmlspecialchars($token); ?>');
                        formData.append('metode_pembayaran', currentTab);
                        formData.append('is_dp', is_dp);
                        formData.append('jumlah_dp', jumlah_dp);
                        fetch('api.php?action=update_pilihan_pembayaran', {
                            method: 'POST',
                            body: formData
                        }).then(res => res.json()).then(data => {
                            if(data.status === 'success') {
                                window.location.reload(); 
                            } else {
                                alert(data.message || 'Terjadi kesalahan.');
                            }
                        });
                    }

                    function switchTab(tabId) {
                        document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
                        const btnQris = document.getElementById('btn-tab-qris');
                        const btnTransfer = document.getElementById('btn-tab-transfer');
                        
                        if(btnQris) {
                            btnQris.className = 'flex-1 py-2 px-4 text-center font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none';
                        }
                        if(btnTransfer) {
                            btnTransfer.className = 'flex-1 py-2 px-4 text-center font-medium text-sm text-gray-500 hover:text-gray-700 focus:outline-none';
                        }
                        
                        const selectedBtn = document.getElementById('btn-tab-' + tabId);
                        if(selectedBtn) {
                            selectedBtn.className = 'flex-1 py-2 px-4 text-center font-medium text-sm text-indigo-600 bg-indigo-50 border-b-2 border-indigo-600 focus:outline-none';
                        }
                        
                        const targetPane = document.getElementById('tab-' + tabId);
                        if(targetPane) {
                            targetPane.classList.remove('hidden');
                        }
                    }
                </script>

            <?php else: ?>
                <!-- Order has payment method selected or Lunas -->
                <?php if ($pesanan['status_pembayaran'] != 'Lunas'): ?>
                    <?php if (!empty($pengaturan['detail_pembayaran']) && $metode_terpilih !== 'qris'): ?>
                        <div class="mt-4 p-3 border border-indigo-200 bg-indigo-50 rounded text-sm text-indigo-800">
                            <strong>Detail Transfer / Pembayaran:</strong><br>
                            <?php echo nl2br(htmlspecialchars($pengaturan['detail_pembayaran'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php 
                    if ($metode_terpilih === 'qris' && !empty($pengaturan['qris_statis'])): 
                        if ($amountToPay > 0):
                            $dynamicQris = generateDynamicQris($pengaturan['qris_statis'], $amountToPay);
                    ?>
                        <div class="mt-6 text-center border p-4 rounded-lg bg-white">
                            <h3 class="font-bold text-gray-800 mb-2">Scan QRIS untuk Membayar</h3>
                            <p class="text-sm text-gray-600 mb-4">Nominal otomatis Rp <?php echo number_format($amountToPay, 0, ',', '.'); ?></p>
                            <div class="inline-block text-center">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($dynamicQris); ?>&size=200x200" alt="QRIS Dinamis" class="mx-auto rounded-lg shadow-sm border p-2 bg-white mb-3">
                                <a href="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($dynamicQris); ?>&size=500x500" target="_blank" download="QRIS_Pesanan.png" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded text-sm font-medium hover:bg-indigo-200 inline-flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Download QRIS
                                </a>
                            </div>
                        </div>
                    <?php 
                        endif; 
                    endif; 
                    ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <hr class="my-6">

        <h2 class="text-xl font-semibold mb-4">Dokumentasi</h2>
        <div class="grid grid-cols-2 gap-4">
            <?php if (!empty($pesanan['gambar_sample'])): ?>
            <div>
                <h3 class="font-semibold mb-2">Gambar Sample</h3>
                <img src="<?php echo htmlspecialchars($pesanan['gambar_sample']); ?>" alt="Sample" class="rounded-lg border w-full h-auto">
            </div>
            <?php endif; ?>

            <?php if (!empty($pesanan['gambar_bukti_kirim'])): ?>
            <div>
                <h3 class="font-semibold mb-2">Pesanan Selesai</h3>
                <img src="<?php echo htmlspecialchars($pesanan['gambar_bukti_kirim']); ?>" alt="Bukti Kirim" class="rounded-lg border w-full h-auto">
            </div>
            <?php endif; ?>
        </div>

        <footer class="text-center mt-10 text-xs text-gray-400">
            Dokumen ini dibuat secara otomatis oleh Sistem Manajemen Pesanan.
        </footer>
    </div>
</body>
</html>
