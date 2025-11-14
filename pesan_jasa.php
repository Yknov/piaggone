<?php
// pages/customer/pesan_jasa.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions_jasa.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'customer') {
    header("Location: ../../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'pesan') {
    $id_user = $_SESSION['id_user'];
    $id_jasa = intval($_POST['id_jasa']);
    $id_kendaraan = !empty($_POST['id_kendaraan']) ? intval($_POST['id_kendaraan']) : null;
    $catatan_customer = mysqli_real_escape_string($conn, $_POST['catatan_customer']);
    
    if (requestJasa($conn, $id_user, $id_jasa, $id_kendaraan, $catatan_customer)) {
        $message = "Jasa berhasil dipesan! Mohon tunggu montir mengambil pesanan Anda.";
        $message_type = "success";
    } else {
        $message = "Gagal memesan jasa!";
        $message_type = "danger";
    }
}

// Get data
$data_jasa = getJasaAktif($conn);
$data_kendaraan = getKendaraanByUser($conn, $_SESSION['id_user']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Jasa - Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../../templates/navbar_customer.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-cart-plus"></i> Pesan Jasa Servis</h2>
        <p class="text-muted">Pilih jasa yang Anda butuhkan</p>
        <hr>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (empty($data_jasa)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Belum ada jasa yang tersedia saat ini.
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($data_jasa as $jasa): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-tools"></i> <?= htmlspecialchars($jasa['nama_jasa']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?= htmlspecialchars($jasa['deskripsi']) ?></p>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="bi bi-clock"></i> Estimasi:</span>
                            <strong><?= $jasa['estimasi_waktu'] ?> menit</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-cash"></i> Harga:</span>
                            <strong class="text-success"><?= formatRupiah($jasa['harga']) ?></strong>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary w-100" onclick="showPesanModal(<?= htmlspecialchars(json_encode($jasa)) ?>)">
                            <i class="bi bi-cart-plus"></i> Pesan Jasa Ini
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal Pesan -->
    <div class="modal fade" id="modalPesan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTitle">Pesan Jasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="pesan">
                        <input type="hidden" name="id_jasa" id="pesan_id_jasa">
                        
                        <div class="alert alert-info" id="jasaInfo">
                            <!-- Filled by JS -->
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Kendaraan (Opsional)</label>
                            <select class="form-select" name="id_kendaraan">
                                <option value="">-- Tidak ada kendaraan --</option>
                                <?php foreach ($data_kendaraan as $kendaraan): ?>
                                <option value="<?= $kendaraan['id_kendaraan'] ?>">
                                    <?= htmlspecialchars($kendaraan['merk_kendaraan'] . ' ' . $kendaraan['tipe_kendaraan'] . ' - ' . $kendaraan['nomor_plat']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Pilih kendaraan jika jasa ini untuk kendaraan tertentu</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Catatan / Keluhan</label>
                            <textarea class="form-control" name="catatan_customer" rows="4" 
                                      placeholder="Ceritakan detail keluhan atau permintaan khusus Anda..."></textarea>
                            <small class="text-muted">Contoh: Mesin sering mati mendadak, bunyi aneh dari ban belakang, dll.</small>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i> <strong>Perhatian:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Pastikan Anda berada di lokasi atau dapat dihubungi</li>
                                <li>Montir akan menghubungi Anda setelah mengambil pesanan</li>
                                <li>Biaya dapat berubah jika ada pekerjaan tambahan</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Konfirmasi Pesanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showPesanModal(jasa) {
            document.getElementById('pesan_id_jasa').value = jasa.id_jasa;
            document.getElementById('modalTitle').textContent = 'Pesan: ' + jasa.nama_jasa;
            
            const infoHtml = `
                <strong><i class="bi bi-tools"></i> ${jasa.nama_jasa}</strong><br>
                <small>${jasa.deskripsi}</small><br>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span><i class="bi bi-clock"></i> Estimasi: ${jasa.estimasi_waktu} menit</span>
                    <span><i class="bi bi-cash"></i> Biaya: <strong class="text-success">${formatRupiah(jasa.harga)}</strong></span>
                </div>
            `;
            document.getElementById('jasaInfo').innerHTML = infoHtml;
            
            new bootstrap.Modal(document.getElementById('modalPesan')).show();
        }
        
        function formatRupiah(angka) {
            return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
        }
    </script>
</body>
</html>
