<?php
// pages/montir/proses_jasa.php
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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'selesai':
                $id_transaksi_jasa = intval($_POST['id_transaksi_jasa']);
                $catatan_montir = mysqli_real_escape_string($conn, $_POST['catatan_montir']);
                
                if (selesaikanJasa($conn, $id_transaksi_jasa, $catatan_montir)) {
                    $message = "Jasa berhasil diselesaikan!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menyelesaikan jasa!";
                    $message_type = "danger";
                }
                break;
                
            case 'batal':
                $id_transaksi_jasa = intval($_POST['id_transaksi_jasa']);
                $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
                
                if (batalkanJasa($conn, $id_transaksi_jasa, $catatan)) {
                    $message = "Jasa berhasil dibatalkan!";
                    $message_type = "success";
                } else {
                    $message = "Gagal membatalkan jasa!";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get jasa yang sedang dikerjakan
$id_montir = $_SESSION['id_user'];
$data_dikerjakan = getTransaksiJasaByMontir($conn, $id_montir, 'dikerjakan');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Jasa - Montir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../../templates/navbar_montir.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-wrench-adjustable"></i> Jasa Sedang Dikerjakan</h2>
        <p class="text-muted">Jasa yang sedang Anda kerjakan</p>
        <hr>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (empty($data_dikerjakan)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Tidak ada jasa yang sedang dikerjakan. 
            <a href="daftar_jasa_pending.php" class="alert-link">Lihat jasa pending</a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($data_dikerjakan as $jasa): ?>
            <div class="col-md-12 mb-3">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="bi bi-tools"></i> <?= htmlspecialchars($jasa['nama_jasa']) ?>
                                </h5>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-light text-dark">ID: <?= $jasa['id_transaksi_jasa'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2"><i class="bi bi-person-circle"></i> Informasi Customer</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="120">Nama</th>
                                        <td><?= htmlspecialchars($jasa['nama_customer']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>No. Telp</th>
                                        <td><?= htmlspecialchars($jasa['no_telp']) ?></td>
                                    </tr>
                                    <?php if ($jasa['merk_kendaraan']): ?>
                                    <tr>
                                        <th>Kendaraan</th>
                                        <td>
                                            <?= htmlspecialchars($jasa['merk_kendaraan'] . ' ' . $jasa['tipe_kendaraan']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($jasa['nomor_plat']) ?></small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                                
                                <?php if ($jasa['catatan_customer']): ?>
                                <div class="alert alert-light">
                                    <strong><i class="bi bi-chat-left-text"></i> Catatan Customer:</strong><br>
                                    <?= nl2br(htmlspecialchars($jasa['catatan_customer'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2"><i class="bi bi-info-circle"></i> Detail Pekerjaan</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="120">Estimasi</th>
                                        <td><?= $jasa['estimasi_waktu'] ?> menit</td>
                                    </tr>
                                    <tr>
                                        <th>Biaya</th>
                                        <td><span class="text-success fw-bold"><?= formatRupiah($jasa['total_biaya']) ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Mulai Kerja</th>
                                        <td><?= date('d/m/Y H:i', strtotime($jasa['tanggal_mulai'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Durasi</th>
                                        <td>
                                            <?php
                                            $mulai = new DateTime($jasa['tanggal_mulai']);
                                            $sekarang = new DateTime();
                                            $diff = $mulai->diff($sekarang);
                                            echo $diff->format('%h jam %i menit');
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                                
                                <div class="mt-3">
                                    <button class="btn btn-success w-100 mb-2" onclick="showSelesaiModal(<?= $jasa['id_transaksi_jasa'] ?>)">
                                        <i class="bi bi-check-circle"></i> Selesaikan Jasa
                                    </button>
                                    <button class="btn btn-danger w-100" onclick="showBatalModal(<?= $jasa['id_transaksi_jasa'] ?>)">
                                        <i class="bi bi-x-circle"></i> Batalkan Jasa
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal Selesai -->
    <div class="modal fade" id="modalSelesai" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Selesaikan Jasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="selesai">
                        <input type="hidden" name="id_transaksi_jasa" id="selesai_id">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Pastikan pekerjaan sudah selesai dengan baik sebelum menyelesaikan jasa.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Pekerjaan *</label>
                            <textarea class="form-control" name="catatan_montir" rows="4" 
                                      placeholder="Masukkan detail pekerjaan yang sudah dilakukan..." required></textarea>
                            <small class="text-muted">Contoh: Sudah diganti oli mesin, filter oli, dan cek kondisi mesin secara keseluruhan.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Selesaikan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Batal -->
    <div class="modal fade" id="modalBatal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Batalkan Jasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="batal">
                        <input type="hidden" name="id_transaksi_jasa" id="batal_id">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Jasa yang dibatalkan tidak dapat dikembalikan.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alasan Pembatalan *</label>
                            <textarea class="form-control" name="catatan" rows="4" 
                                      placeholder="Masukkan alasan pembatalan..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Ya, Batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSelesaiModal(id) {
            document.getElementById('selesai_id').value = id;
            new bootstrap.Modal(document.getElementById('modalSelesai')).show();
        }
        
        function showBatalModal(id) {
            document.getElementById('batal_id').value = id;
            new bootstrap.Modal(document.getElementById('modalBatal')).show();
        }
    </script>
</body>
</html>
