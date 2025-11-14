<?php
// pages/montir/riwayat_jasa_montir.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions_jasa.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'montir') {
    header("Location: ../../login.php");
    exit();
}

$id_montir = $_SESSION['id_user'];

// Get filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Get riwayat
if ($filter_status) {
    $data_riwayat = getTransaksiJasaByMontir($conn, $id_montir, $filter_status);
} else {
    $data_riwayat = getTransaksiJasaByMontir($conn, $id_montir);
}

// Get statistics
$all_data = getTransaksiJasaByMontir($conn, $id_montir);
$total_selesai = count(array_filter($all_data, fn($t) => $t['status_jasa'] == 'selesai'));
$total_dibatalkan = count(array_filter($all_data, fn($t) => $t['status_jasa'] == 'dibatalkan'));
$total_pendapatan = array_sum(array_map(fn($t) => $t['status_jasa'] == 'selesai' ? $t['total_biaya'] : 0, $all_data));
$rata_rating = 0;
$rated = array_filter($all_data, fn($t) => $t['rating'] !== null);
if (count($rated) > 0) {
    $rata_rating = array_sum(array_map(fn($t) => $t['rating'], $rated)) / count($rated);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Jasa - Montir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../../templates/navbar_montir.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-clock-history"></i> Riwayat Jasa Saya</h2>
        <hr>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3><?= $total_selesai ?></h3>
                        <p class="mb-0">Jasa Selesai</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3><?= $total_dibatalkan ?></h3>
                        <p class="mb-0">Jasa Dibatalkan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5><?= formatRupiah($total_pendapatan) ?></h5>
                        <p class="mb-0">Total Pendapatan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3><?= number_format($rata_rating, 1) ?> ⭐</h3>
                        <p class="mb-0">Rating Rata-rata</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="dikerjakan" <?= $filter_status == 'dikerjakan' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                            <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="dibatalkan" <?= $filter_status == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <?php if ($filter_status): ?>
                        <label class="form-label">&nbsp;</label><br>
                        <a href="riwayat_jasa_montir.php" class="btn btn-secondary">Reset Filter</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabel -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Jasa</th>
                                <th>Customer</th>
                                <th>Kendaraan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Biaya</th>
                                <th>Rating</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_riwayat)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Belum ada riwayat</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($data_riwayat as $jasa): ?>
                            <tr>
                                <td><?= $jasa['id_transaksi_jasa'] ?></td>
                                <td><?= htmlspecialchars($jasa['nama_jasa']) ?></td>
                                <td><?= htmlspecialchars($jasa['nama_customer']) ?></td>
                                <td>
                                    <?php if ($jasa['merk_kendaraan']): ?>
                                        <?= htmlspecialchars($jasa['merk_kendaraan'] . ' ' . $jasa['tipe_kendaraan']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($jasa['nomor_plat']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        Mulai: <?= date('d/m/Y H:i', strtotime($jasa['tanggal_mulai'])) ?><br>
                                        <?php if ($jasa['tanggal_selesai']): ?>
                                        Selesai: <?= date('d/m/Y H:i', strtotime($jasa['tanggal_selesai'])) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td><?= getStatusBadge($jasa['status_jasa']) ?></td>
                                <td><?= formatRupiah($jasa['total_biaya']) ?></td>
                                <td>
                                    <?php if ($jasa['rating']): ?>
                                        <?= str_repeat('⭐', $jasa['rating']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewDetail(<?= htmlspecialchars(json_encode($jasa)) ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail -->
    <div class="modal fade" id="modalDetail" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content filled by JS -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetail(jasa) {
            let html = `
                <table class="table table-bordered">
                    <tr><th width="180">ID Transaksi</th><td>${jasa.id_transaksi_jasa}</td></tr>
                    <tr><th>Jasa</th><td>${jasa.nama_jasa}</td></tr>
                    <tr><th>Customer</th><td>${jasa.nama_customer}</td></tr>
                    <tr><th>No. Telp</th><td>${jasa.no_telp}</td></tr>
                    <tr><th>Kendaraan</th><td>${jasa.merk_kendaraan ? jasa.merk_kendaraan + ' ' + jasa.tipe_kendaraan + ' (' + jasa.nomor_plat + ')' : '-'}</td></tr>
                    <tr><th>Status</th><td>${getStatusBadge(jasa.status_jasa)}</td></tr>
                    <tr><th>Tanggal Mulai</th><td>${new Date(jasa.tanggal_mulai).toLocaleString('id-ID')}</td></tr>
                    <tr><th>Tanggal Selesai</th><td>${jasa.tanggal_selesai ? new Date(jasa.tanggal_selesai).toLocaleString('id-ID') : '-'}</td></tr>
                    <tr><th>Catatan Customer</th><td>${jasa.catatan_customer || '-'}</td></tr>
                    <tr><th>Catatan Montir</th><td>${jasa.catatan_montir || '-'}</td></tr>
                    <tr><th>Biaya</th><td><strong>${formatRupiah(jasa.total_biaya)}</strong></td></tr>
                    ${jasa.rating ? `
                    <tr><th>Rating</th><td>${'⭐'.repeat(jasa.rating)} (${jasa.rating}/5)</td></tr>
                    <tr><th>Review</th><td>${jasa.review || '-'}</td></tr>
                    ` : ''}
                </table>
            `;
            document.getElementById('detailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalDetail')).show();
        }
        
        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning">Pending</span>',
                'dikerjakan': '<span class="badge bg-info">Dikerjakan</span>',
                'selesai': '<span class="badge bg-success">Selesai</span>',
                'dibatalkan': '<span class="badge bg-danger">Dibatalkan</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }
        
        function formatRupiah(angka) {
            return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
        }
    </script>
</body>
</html>
