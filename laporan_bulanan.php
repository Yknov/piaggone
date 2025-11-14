<?php

session_start();
require_once '../config/database.php';
require_once '../services/PenjualanService.php';
require_once '../middleware/auth.php';

checkAuth();
checkRole(['owner']);

$user = $_SESSION['user'];
$penjualanService = new PenjualanService($conn);

// Get periode (default bulan ini)
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$startDate = "$tahun-$bulan-01";
$endDate = date('Y-m-t', strtotime($startDate));

// Get laporan
$laporan = $penjualanService->getLaporanPenjualan($startDate, $endDate);

// Get statistik bulanan
$queryStats = "SELECT 
    COUNT(tp.id_transaksi) as total_transaksi,
    SUM(tp.total_bayar) as total_pendapatan,
    SUM(tp.diskon) as total_diskon,
    AVG(tp.total_bayar) as rata_rata_transaksi,
    SUM(dtp.jumlah) as total_item_terjual,
    MIN(tp.total_bayar) as transaksi_terendah,
    MAX(tp.total_bayar) as transaksi_tertinggi
FROM transaksi_penjualan tp
LEFT JOIN detail_transaksi_penjualan dtp ON tp.id_transaksi = dtp.id_transaksi
WHERE tp.status = 'selesai' 
AND YEAR(tp.tanggal_transaksi) = ? 
AND MONTH(tp.tanggal_transaksi) = ?";
$stmtStats = $conn->prepare($queryStats);
$stmtStats->bind_param("ii", $tahun, $bulan);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();

// Get produk terlaris
$produkTerlaris = $penjualanService->getProdukTerlaris(10);

// Get performa per kategori
$queryKategori = "SELECT 
    p.kategori,
    COUNT(DISTINCT dtp.id_transaksi) as jumlah_transaksi,
    SUM(dtp.jumlah) as total_terjual,
    SUM(dtp.subtotal) as total_pendapatan
FROM produk p
INNER JOIN detail_transaksi_penjualan dtp ON p.id_produk = dtp.id_produk
INNER JOIN transaksi_penjualan tp ON dtp.id_transaksi = tp.id_transaksi
WHERE tp.status = 'selesai'
AND YEAR(tp.tanggal_transaksi) = ?
AND MONTH(tp.tanggal_transaksi) = ?
GROUP BY p.kategori
ORDER BY total_pendapatan DESC";
$stmtKategori = $conn->prepare($queryKategori);
$stmtKategori->bind_param("ii", $tahun, $bulan);
$stmtKategori->execute();
$kategori = $stmtKategori->get_result()->fetch_all(MYSQLI_ASSOC);

// Get tren penjualan (perbandingan dengan bulan lalu)
$bulanLalu = $bulan - 1;
$tahunLalu = $tahun;
if ($bulanLalu < 1) {
    $bulanLalu = 12;
    $tahunLalu--;
}
$queryTren = "SELECT 
    SUM(total_bayar) as total_bulan_lalu
FROM transaksi_penjualan
WHERE status = 'selesai'
AND YEAR(tanggal_transaksi) = ?
AND MONTH(tanggal_transaksi) = ?";
$stmtTren = $conn->prepare($queryTren);
$stmtTren->bind_param("ii", $tahunLalu, $bulanLalu);
$stmtTren->execute();
$tren = $stmtTren->get_result()->fetch_assoc();

$pertumbuhan = 0;
if ($tren['total_bulan_lalu'] > 0) {
    $pertumbuhan = (($stats['total_pendapatan'] - $tren['total_bulan_lalu']) / $tren['total_bulan_lalu']) * 100;
}

// Nama bulan
$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

include '../layouts/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Rekap Penjualan Bulanan
                    </h3>
                    <h5><?= $namaBulan[(int)$bulan] ?> <?= $tahun ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Periode -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Bulan</label>
                            <select name="bulan" class="form-control" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" 
                                        <?= $m == $bulan ? 'selected' : '' ?>>
                                    <?= $namaBulan[$m] ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tahun</label>
                            <select name="tahun" class="form-control" required>
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                            <button type="button" class="btn btn-success me-2" onclick="exportExcel()">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-danger" onclick="exportPDF()">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Utama -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Transaksi</small>
                            <h3 class="mb-0"><?= number_format($stats['total_transaksi'] ?? 0) ?></h3>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Pendapatan</small>
                            <h4 class="mb-0 text-success">Rp <?= number_format($stats['total_pendapatan'] ?? 0, 0, ',', '.') ?></h4>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x text-success opacity-25"></i>
                    </div>
                    <?php if ($pertumbuhan != 0): ?>
                    <small class="<?= $pertumbuhan > 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="fas fa-arrow-<?= $pertumbuhan > 0 ? 'up' : 'down' ?>"></i>
                        <?= number_format(abs($pertumbuhan), 1) ?>% vs bulan lalu
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Rata-rata Transaksi</small>
                            <h4 class="mb-0 text-warning">Rp <?= number_format($stats['rata_rata_transaksi'] ?? 0, 0, ',', '.') ?></h4>
                        </div>
                        <i class="fas fa-calculator fa-2x text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Item Terjual</small>
                            <h3 class="mb-0 text-info"><?= number_format($stats['total_item_terjual'] ?? 0) ?></h3>
                        </div>
                        <i class="fas fa-box fa-2x text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Statistik -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Transaksi Terendah</h6>
                    <h4 class="text-danger">Rp <?= number_format($stats['transaksi_terendah'] ?? 0, 0, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Transaksi Tertinggi</h6>
                    <h4 class="text-success">Rp <?= number_format($stats['transaksi_tertinggi'] ?? 0, 0, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Diskon Diberikan</h6>
                    <h4 class="text-warning">Rp <?= number_format($stats['total_diskon'] ?? 0, 0, ',', '.') ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Performa Per Kategori -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group"></i> Performa Per Kategori
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($kategori)): ?>
                    <p class="text-muted">Tidak ada data</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Transaksi</th>
                                    <th>Item Terjual</th>
                                    <th>Pendapatan</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kategori as $kat): ?>
                                <?php $persen = ($kat['total_pendapatan'] / $stats['total_pendapatan']) * 100; ?>
                                <tr>
                                    <td><strong><?= $kat['kategori'] ?></strong></td>
                                    <td><?= $kat['jumlah_transaksi'] ?></td>
                                    <td><?= $kat['total_terjual'] ?> pcs</td>
                                    <td>Rp <?= number_format($kat['total_pendapatan'], 0, ',', '.') ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" style="width: <?= $persen ?>%">
                                                <?= number_format($persen, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top 10 Produk Terlaris -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-medal"></i> Top 10 Produk Terlaris (All Time)
                    </h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($produkTerlaris)): ?>
                    <p class="text-muted">Tidak ada data</p>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($produkTerlaris as $idx => $produk): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($idx < 3): ?>
                                    <i class="fas fa-trophy fa-2x <?= ['text-warning', 'text-secondary', 'text-danger'][$idx] ?>"></i>
                                    <?php else: ?>
                                    <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; line-height: 20px;">
                                        <?= $idx + 1 ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($produk['nama_produk']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <span class="badge bg-info"><?= $produk['kategori'] ?></span>
                                        Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-success"><?= number_format($produk['total_terjual']) ?> pcs</strong>
                                    <br>
                                    <small class="text-muted">
                                        Rp <?= number_format($produk['total_pendapatan'], 0, ',', '.') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tren Penjualan Harian -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Tren Penjualan Harian
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($laporan)): ?>
                    <div class="alert alert-info">
                        Tidak ada data penjualan pada periode ini.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Hari</th>
                                    <th>Transaksi</th>
                                    <th>Item Terjual</th>
                                    <th>Total Penjualan</th>
                                    <th>Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($laporan as $row): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= strftime('%A', strtotime($row['tanggal'])) ?></td>
                                    <td><?= $row['total_transaksi'] ?></td>
                                    <td><?= $row['total_item'] ?? 0 ?> pcs</td>
                                    <td>
                                        <strong class="text-success">
                                            Rp <?= number_format($row['total_penjualan'], 0, ',', '.') ?>
                                        </strong>
                                    </td>
                                    <td>
                                        Rp <?= number_format($row['total_penjualan'] / $row['total_transaksi'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2">TOTAL</th>
                                    <th><?= number_format($stats['total_transaksi']) ?></th>
                                    <th><?= number_format($stats['total_item_terjual']) ?> pcs</th>
                                    <th>
                                        <strong class="text-success">
                                            Rp <?= number_format($stats['total_pendapatan'], 0, ',', '.') ?>
                                        </strong>
                                    </th>
                                    <th>
                                        Rp <?= number_format($stats['rata_rata_transaksi'], 0, ',', '.') ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.opacity-25 {
    opacity: 0.25;
}
</style>

<script>
function exportExcel() {
    alert('Fitur export Excel akan segera hadir!');
    // TODO: Implementasi export Excel
}

function exportPDF() {
    alert('Fitur export PDF akan segera hadir!');
    // TODO: Implementasi export PDF
}
</script>

<?php include '../layouts/footer.php'; ?>
