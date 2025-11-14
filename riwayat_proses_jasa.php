<?php
// pages/admin/riwayat_proses_jasa.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions_jasa.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Get filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Get all transaksi
$data_transaksi = getAllTransaksiJasa($conn);

// Filter if needed
if ($filter_status) {
    $data_transaksi = array_filter($data_transaksi, function($t) use ($filter_status) {
        return $t['status_jasa'] == $filter_status;
    });
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Proses Jasa - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../../templates/navbar_admin.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-clock-history"></i> Riwayat Proses Jasa</h2>
        <hr>
        
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="dikerjakan" <?= $filter_status == 'dikerjakan' ? 'selected' : '' ?>>Dikerjakan</option>
                            <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="dibatalkan" <?= $filter_status == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <?php if ($filter_status): ?>
                        <label class="form-label">&nbsp;</label><br>
                        <a href="riwayat_proses_jasa.php" class="btn btn-secondary">Reset Filter</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-3">
            <?php
            $all = getAllTransaksiJasa($conn);
            $total_pending = count(array_filter($all, fn($t) => $t['status_jasa'] == 'pending'));
            $total_dikerjakan = count(array_filter($all, fn($t) => $t['status_jasa'] == 'dikerjakan'));
            $total_selesai = count(array_filter($all, fn($t) => $t['status_jasa'] == 'selesai'));
            $total_revenue = array_sum(array_map(fn($t) => $t['status_jasa'] == 'selesai' ? $t['total_biaya'] : 0, $all));
            ?>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Pending</h5>
                        <h3><?= $total_pending ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Dikerjakan</h5>
                        <h3><?= $total_dikerjakan ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Selesai</h5>
                        <h3><?= $total_selesai ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <h5>Total Revenue</h5>
                        <h6><?= formatRupiah($total_revenue) ?></h6>
                    </div>
                </div>
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
                                <th>Customer</th>
                                <th>Jasa</th>
                                <th>Kendaraan</th>
                                <th>Montir</th>
                                <th>Tanggal Request</th>
                                <th>Status</th>
                                <th>Biaya</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_transaksi)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Belum ada data transaksi</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($data_transaksi as $transaksi): ?>
                            <tr>
                                <td><?= $transaksi['id_transaksi_jasa'] ?></td>
                                <td><?= htmlspecialchars($transaksi['nama_customer']) ?></td>
                                <td><?= htmlspecialchars($transaksi['nama_jasa']) ?></td>
                                <td>
                                    <?php if ($transaksi['merk_kendaraan']): ?>
                                        <?= htmlspecialchars($transaksi['merk_kendaraan'] . ' ' . $transaksi['tipe_kendaraan']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($transaksi['nomor_plat']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($transaksi['nama_montir'] ?? '-') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($transaksi['tanggal_request'])) ?></td>
                                <td><?= getStatusBadge($transaksi['status_jasa']) ?></td>
                                <td><?= formatRupiah($transaksi['total_biaya']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewDetail(<?= htmlspecialchars(json_encode($transaksi)) ?>)">
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
                    <h5 class="modal-title">Detail Transaksi Jasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be filled by JS -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetail(transaksi) {
            let html = `
                <table class="table table-bordered">
                    <tr>
                        <th width="200">ID Transaksi</th>
                        <td>${transaksi.id_transaksi_jasa}</td>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <td>${transaksi.nama_customer}</td>
                    </tr>
                    <tr>
                        <th>Jasa</th>
                        <td>${transaksi.nama_jasa}</td>
                    </tr>
                    <tr>
                        <th>Kendaraan</th>
                        <td>${transaksi.merk_kendaraan ? transaksi.merk_kendaraan + ' ' + transaksi.tipe_kendaraan + ' (' + transaksi.nomor_plat + ')' : '-'}</td>
                    </tr>
                    <tr>
                        <th>Montir</th>
                        <td>${transaksi.nama_montir || '-'}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>${getStatusBadge(transaksi.status_jasa)}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Request</th>
                        <td>${new Date(transaksi.tanggal_request).toLocaleString('id-ID')}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Mulai</th>
                        <td>${transaksi.tanggal_mulai ? new Date(transaksi.tanggal_mulai).toLocaleString('id-ID') : '-'}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Selesai</th>
                        <td>${transaksi.tanggal_selesai ? new Date(transaksi.tanggal_selesai).toLocaleString('id-ID') : '-'}</td>
                    </tr>
                    <tr>
                        <th>Catatan Customer</th>
                        <td>${transaksi.catatan_customer || '-'}</td>
                    </tr>
                    <tr>
                        <th>Catatan Montir</th>
                        <td>${transaksi.catatan_montir || '-'}</td>
                    </tr>
                    <tr>
                        <th>Total Biaya</th>
                        <td><strong>${formatRupiah(transaksi.total_biaya)}</strong></td>
                    </tr>
                    ${transaksi.rating ? `
                    <tr>
                        <th>Rating</th>
                        <td>${'⭐'.repeat(transaksi.rating)} (${transaksi.rating}/5)</td>
                    </tr>
                    <tr>
                        <th>Review</th>
                        <td>${transaksi.review || '-'}</td>
                    </tr>
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
