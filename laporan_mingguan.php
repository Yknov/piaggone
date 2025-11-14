<?php


session_start();
require_once '../config/database.php';
require_once '../services/PenjualanService.php';
require_once '../middleware/auth.php';

checkAuth();
checkRole(['manager', 'owner']);

$user = $_SESSION['user'];
$penjualanService = new PenjualanService($conn);

// Get periode (default minggu ini)
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('monday this week'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('sunday this week'));

// Get laporan
$laporan = $penjualanService->getLaporanPenjualan($startDate, $endDate);

// Get statistik
$queryStats = "SELECT 
    COUNT(tp.id_transaksi) as total_transaksi,
    SUM(tp.total_bayar) as total_pendapatan,
    AVG(tp.total_bayar) as rata_rata_transaksi,
    SUM(dtp.jumlah) as total_item_terjual
FROM transaksi_penjualan tp
LEFT JOIN detail_transaksi_penjualan dtp ON tp.id_transaksi = dtp.id_transaksi
WHERE tp.status = 'selesai' 
AND DATE(tp.tanggal_transaksi) BETWEEN ? AND ?";
$stmtStats = $conn->prepare($queryStats);
$stmtStats->bind_param("ss", $startDate, $endDate);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();

// Get produk terlaris periode ini
$queryTerlaris = "SELECT 
    p.nama_produk,
    p.kategori,
    SUM(dtp.jumlah) as total_terjual,
    SUM(dtp.subtotal) as total_pendapatan
FROM produk p
INNER JOIN detail_transaksi_penjualan dtp ON p.id_produk = dtp.id_produk
INNER JOIN transaksi_penjualan tp ON dtp.id_transaksi = tp.id_transaksi
WHERE tp.status = 'selesai'
AND DATE(tp.tanggal_transaksi) BETWEEN ? AND ?
GROUP BY p.id_produk
ORDER BY total_terjual DESC
LIMIT 5";
$stmtTerlaris = $conn->prepare($queryTerlaris);
$stmtTerlaris->bind_param("ss", $startDate, $endDate);
$stmtTerlaris->execute();
$produkTerlaris = $stmtTerlaris->get_result()->fetch_all(MYSQLI_ASSOC);

// Get metode pembayaran
$queryMetode = "SELECT 
    metode_bayar,
    COUNT(*) as jumlah_transaksi,
    SUM(total_bayar) as total_nilai
FROM transaksi_penjualan
WHERE status = 'selesai'
AND DATE(tanggal_transaksi) BETWEEN ? AND ?
GROUP BY metode_bayar";
$stmtMetode = $conn->prepare($queryMetode);
$stmtMetode->bind_param("ss", $startDate, $endDate);
$stmtMetode->execute();
$metodeBayar = $stmtMetode->get_result()->fetch_all(MYSQLI_ASSOC);

include '../layouts/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0">
                        <i class="fas fa-chart-line"></i> Laporan Penjualan Mingguan
                    </h3>
                    <p class="mb-0">Periode: <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?></p>
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
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start" class="form-control" value="<?= $startDate ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date" name="end" class="form-control" value="<?= $endDate ?>" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                            <button type="button" class="btn btn-success" onclick="exportPDF()">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </form>
                    
                    <!-- Quick Filters -->
                    <div class="mt-3">
                        <strong>Quick Filter:</strong>
                        <button class="btn btn-sm btn-outline-primary" onclick="setWeek('this')">Minggu Ini</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="setWeek('last')">Minggu Lalu</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Total Transaksi</h6>
                    <h3><?= number_format($stats['total_transaksi'] ?? 0) ?></h3>
                    <small>transaksi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Pendapatan</h6>
                    <h3>Rp <?= number_format($stats['total_pendapatan'] ?? 0, 0, ',', '.') ?></h3>
                    <small>rupiah</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Rata-rata Transaksi</h6>
                    <h3>Rp <?= number_format($stats['rata_rata_transaksi'] ?? 0, 0, ',', '.') ?></h3>
                    <small>per transaksi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Total Item Terjual</h6>
                    <h3><?= number_format($stats['total_item_terjual'] ?? 0) ?></h3>
                    <small>item</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Laporan Harian -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> Laporan Per Hari
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($laporan)): ?>
                    <div class="alert alert-info">
                        Tidak ada data penjualan pada periode ini.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Transaksi</th>
                                    <th>Total Item</th>
                                    <th>Total Penjualan</th>
                                    <th>Metode Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($laporan as $row): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= $row['total_transaksi'] ?></td>
                                    <td><?= $row['total_item'] ?? 0 ?></td>
                                    <td>
                                        <strong class="text-success">
                                            Rp <?= number_format($row['total_penjualan'], 0, ',', '.') ?>
                                        </strong>
                                    </td>
                                    <td><?= $row['metode_bayar'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>TOTAL</th>
                                    <th><?= number_format($stats['total_transaksi']) ?></th>
                                    <th><?= number_format($stats['total_item_terjual']) ?></th>
                                    <th colspan="2">
                                        <strong class="text-success">
                                            Rp <?= number_format($stats['total_pendapatan'], 0, ',', '.') ?>
                                        </strong>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Produk Terlaris -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star"></i> Top 5 Produk Terlaris
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($produkTerlaris)): ?>
                    <p class="text-muted">Tidak ada data</p>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($produkTerlaris as $idx => $produk): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary">#<?= $idx + 1 ?></span>
                                    <strong><?= htmlspecialchars($produk['nama_produk']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= $produk['kategori'] ?></small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-success"><?= $produk['total_terjual'] ?> pcs</strong>
                                    <br>
                                    <small>Rp <?= number_format($produk['total_pendapatan'], 0, ',', '.') ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Metode Pembayaran -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card"></i> Metode Pembayaran
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($metodeBayar)): ?>
                    <p class="text-muted">Tidak ada data</p>
                    <?php else: ?>
                    <?php foreach ($metodeBayar as $metode): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong><?= $metode['metode_bayar'] ?></strong>
                            <span><?= $metode['jumlah_transaksi'] ?> transaksi</span>
                        </div>
                        <div class="progress">
                            <?php 
                            $persen = ($metode['total_nilai'] / $stats['total_pendapatan']) * 100;
                            ?>
                            <div class="progress-bar bg-info" style="width: <?= $persen ?>%">
                                <?= number_format($persen, 1) ?>%
                            </div>
                        </div>
                        <small class="text-muted">Rp <?= number_format($metode['total_nilai'], 0, ',', '.') ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setWeek(type) {
    const today = new Date();
    let start, end;
    
    if (type === 'this') {
        // Minggu ini (Senin - Minggu)
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
        start = new Date(today.setDate(diff));
        end = new Date(start);
        end.setDate(end.getDate() + 6);
    } else if (type === 'last') {
        // Minggu lalu
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1) - 7;
        start = new Date(today.setDate(diff));
        end = new Date(start);
        end.setDate(end.getDate() + 6);
    }
    
    const formatDate = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };
    
    window.location.href = `?start=${formatDate(start)}&end=${formatDate(end)}`;
}

function exportPDF() {
    alert('Fitur export PDF akan segera hadir!');
    // TODO: Implementasi export PDF
}
</script>

<?php include '../layouts/footer.php'; ?>
