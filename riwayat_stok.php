<?php

session_start();
require_once '../config/database.php';
require_once '../middleware/auth.php';

checkAuth();
checkRole(['inventory', 'manager', 'owner']);

$user = $_SESSION['user'];

// Filter
$produkFilter = isset($_GET['produk']) ? intval($_GET['produk']) : 0;
$jenisFilter = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Build query
$query = "SELECT 
    rs.*,
    p.nama_produk,
    p.kategori,
    u.nama as nama_user,
    tp.kode_transaksi
FROM riwayat_stok rs
INNER JOIN produk p ON rs.id_produk = p.id_produk
INNER JOIN users u ON rs.id_user = u.id_user
LEFT JOIN transaksi_penjualan tp ON rs.id_transaksi = tp.id_transaksi
WHERE DATE(rs.tanggal) BETWEEN ? AND ?";

$params = [$startDate, $endDate];
$types = "ss";

if ($produkFilter > 0) {
    $query .= " AND rs.id_produk = ?";
    $params[] = $produkFilter;
    $types .= "i";
}

if ($jenisFilter !== '') {
    $query .= " AND rs.jenis_transaksi = ?";
    $params[] = $jenisFilter;
    $types .= "s";
}

$query .= " ORDER BY rs.tanggal DESC LIMIT 100";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get daftar produk untuk filter
$queryProduk = "SELECT id_produk, nama_produk FROM produk ORDER BY nama_produk";
$produkList = $conn->query($queryProduk)->fetch_all(MYSQLI_ASSOC);

// Get statistik
$queryStats = "SELECT 
    COUNT(*) as total_transaksi,
    SUM(CASE WHEN jenis_transaksi = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN jenis_transaksi IN ('keluar', 'penjualan') THEN jumlah ELSE 0 END) as total_keluar
FROM riwayat_stok
WHERE DATE(tanggal) BETWEEN ? AND ?";
$stmtStats = $conn->prepare($queryStats);
$stmtStats->bind_param("ss", $startDate, $endDate);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();

include '../layouts/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-history"></i> Riwayat Pergerakan Stok
                    </h4>
                </div>
                <div class="card-body">
                    
                    <!-- Statistik -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Total Transaksi</h6>
                                    <h3><?= number_format($stats['total_transaksi']) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6>Stok Masuk</h6>
                                    <h3><?= number_format($stats['total_masuk']) ?> pcs</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h6>Stok Keluar</h6>
                                    <h3><?= number_format($stats['total_keluar']) ?> pcs</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Produk</label>
                                    <select name="produk" class="form-control">
                                        <option value="0">Semua Produk</option>
                                        <?php foreach ($produkList as $prod): ?>
                                        <option value="<?= $prod['id_produk'] ?>" 
                                                <?= $produkFilter == $prod['id_produk'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($prod['nama_produk']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Jenis</label>
                                    <select name="jenis" class="form-control">
                                        <option value="">Semua Jenis</option>
                                        <option value="masuk" <?= $jenisFilter === 'masuk' ? 'selected' : '' ?>>Masuk</option>
                                        <option value="keluar" <?= $jenisFilter === 'keluar' ? 'selected' : '' ?>>Keluar</option>
                                        <option value="penjualan" <?= $jenisFilter === 'penjualan' ? 'selected' : '' ?>>Penjualan</option>
                                        <option value="retur" <?= $jenisFilter === 'retur' ? 'selected' : '' ?>>Retur</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Dari Tanggal</label>
                                    <input type="date" name="start" class="form-control" value="<?= $startDate ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sampai Tanggal</label>
                                    <input type="date" name="end" class="form-control" value="<?= $endDate ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <a href="riwayat_stok.php" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Table Riwayat -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-info">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Stok Sebelum</th>
                                    <th>Stok Sesudah</th>
                                    <th>Keterangan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($riwayat)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <div class="py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Tidak ada riwayat stok pada periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($riwayat as $row): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['nama_produk']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $row['kategori'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = [
                                            'masuk' => 'success',
                                            'keluar' => 'warning',
                                            'penjualan' => 'danger',
                                            'retur' => 'secondary'
                                        ];
                                        $class = $badgeClass[$row['jenis_transaksi']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $class ?>">
                                            <?= ucfirst($row['jenis_transaksi']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="<?= in_array($row['jenis_transaksi'], ['masuk', 'retur']) ? 'text-success' : 'text-danger' ?>">
                                            <?= in_array($row['jenis_transaksi'], ['masuk', 'retur']) ? '+' : '-' ?>
                                            <?= $row['jumlah'] ?> pcs
                                        </strong>
                                    </td>
                                    <td><?= $row['stok_sebelum'] ?> pcs</td>
                                    <td>
                                        <strong class="<?= $row['stok_sesudah'] < $row['stok_sebelum'] ? 'text-danger' : 'text-success' ?>">
                                            <?= $row['stok_sesudah'] ?> pcs
                                        </strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['keterangan']) ?>
                                        <?php if ($row['kode_transaksi']): ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-receipt"></i> <?= $row['kode_transaksi'] ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Info -->
                    <?php if (count($riwayat) >= 100): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> 
                        Menampilkan 100 data terbaru. Gunakan filter untuk melihat data spesifik.
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="mt-4">
                        <a href="kelola_produk.php" class="btn btn-primary">
                            <i class="fas fa-box"></i> Kelola Produk
                        </a>
                        <button class="btn btn-success" onclick="exportExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportExcel() {
    // Simple export to CSV
    const table = document.querySelector('table');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
            // Remove HTML tags and clean text
            let text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(','));
    }
    
    // Download
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = 'riwayat_stok_<?= date('Y-m-d') ?>.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php include '../layouts/footer.php'; ?>
