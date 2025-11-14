<?php
// pages/montir/daftar_jasa_pending.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions_jasa.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'montir') {
    header("Location: ../../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle ambil jasa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'ambil') {
    $id_transaksi_jasa = intval($_POST['id_transaksi_jasa']);
    $id_montir = $_SESSION['id_user'];
    
    if (ambilJasa($conn, $id_transaksi_jasa, $id_montir)) {
        $message = "Jasa berhasil diambil! Silakan kerjakan di menu Proses Jasa.";
        $message_type = "success";
    } else {
        $message = "Gagal mengambil jasa!";
        $message_type = "danger";
    }
}

// Get pending jasa
$data_pending = getTransaksiJasaPending($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Jasa Pending - Montir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../../templates/navbar_montir.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-list-task"></i> Daftar Jasa Pending</h2>
        <p class="text-muted">Jasa yang belum diambil oleh montir manapun</p>
        <hr>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (empty($data_pending)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Tidak ada jasa pending saat ini.
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($data_pending as $jasa): ?>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-tools"></i> <?= htmlspecialchars($jasa['nama_jasa']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6">
                                <strong><i class="bi bi-person"></i> Customer:</strong>
                            </div>
                            <div class="col-6">
                                <?= htmlspecialchars($jasa['nama_customer']) ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <strong><i class="bi bi-telephone"></i> No. Telp:</strong>
                            </div>
                            <div class="col-6">
                                <?= htmlspecialchars($jasa['no_telp']) ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <strong><i class="bi bi-geo-alt"></i> Alamat:</strong>
                            </div>
                            <div class="col-6">
                                <?= htmlspecialchars($jasa['alamat']) ?>
                            </div>
                        </div>
                        <hr>
                        <?php if ($jasa['merk_kendaraan']): ?>
                        <div class="row mb-2">
                            <div class="col-6">
                                <strong><i class="bi bi-car-front"></i> Kendaraan:</strong>
                            </div>
                            <div class="col-6">
                                <?= htmlspecialchars($jasa['merk_kendaraan'] . ' ' . $jasa['tipe_kendaraan']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($jasa['nomor_plat']) ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="row mb-2">
                            <div class="col-6">
                                <strong><i class="bi bi-clock"></i> Estimasi:</strong>
                            </div>
                            <div class="col-6">
                                <?= $jasa['estimasi_waktu'] ?> menit
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <strong><i class="bi bi-cash"></i> Biaya:</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-success fw-bold"><?= formatRupiah($jasa['total_biaya']) ?></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <strong><i class="bi bi-calendar"></i> Request:</strong>
                            </div>
                            <div class="col-6">
                                <?= date('d/m/Y H:i', strtotime($jasa['tanggal_request'])) ?>
                            </div>
                        </div>
                        <?php if ($jasa['catatan_customer']): ?>
                        <hr>
                        <div class="alert alert-light mb-0">
                            <strong><i class="bi bi-chat-left-text"></i> Catatan Customer:</strong><br>
                            <?= nl2br(htmlspecialchars($jasa['catatan_customer'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary w-100" onclick="confirmAmbil(<?= $jasa['id_transaksi_jasa'] ?>, '<?= htmlspecialchars($jasa['nama_jasa']) ?>')">
                            <i class="bi bi-check-circle"></i> Ambil Jasa Ini
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Form Ambil (hidden) -->
    <form method="POST" id="formAmbil">
        <input type="hidden" name="action" value="ambil">
        <input type="hidden" name="id_transaksi_jasa" id="ambil_id_transaksi">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmAmbil(id, nama) {
            if (confirm('Yakin ingin mengambil jasa "' + nama + '"?\n\nSetelah diambil, Anda bertanggung jawab menyelesaikan jasa ini.')) {
                document.getElementById('ambil_id_transaksi').value = id;
                document.getElementById('formAmbil').submit();
            }
        }
    </script>
</body>
</html>
