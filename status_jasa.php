<?php
// pages/customer/status_jasa.php
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

// Handle rating
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'rating') {
    $id_transaksi_jasa = intval($_POST['id_transaksi_jasa']);
    $rating = intval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    
    if (berikanRating($conn, $id_transaksi_jasa, $rating, $review)) {
        $message = "Terima kasih atas rating dan review Anda!";
        $message_type = "success";
    } else {
        $message = "Gagal memberikan rating!";
        $message_type = "danger";
    }
}

// Get jasa aktif (pending & dikerjakan)
$id_user = $_SESSION['id_user'];
$pending = getTransaksiJasaByCustomer($conn, $id_user, 'pending');
$dikerjakan = getTransaksiJasaByCustomer($conn, $id_user, 'dikerjakan');
$selesai_belum_rating = array_filter(
    getTransaksiJasaByCustomer($conn, $id_user, 'selesai'),
    fn($t) => $t['rating'] === null
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Jasa - Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #6c757d;
        }
        .timeline-item.active::before {
            border-color: #0d6efd;
            background: #0d6efd;
        }
        .timeline-item.success::before {
            border-color: #198754;
            background: #198754;
        }
    </style>
</head>
<body>
    <?php include '../../templates/navbar_customer.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-speedometer2"></i> Status Jasa Saya</h2>
        <p class="text-muted">Lacak status jasa yang sedang berjalan</p>
        <hr>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Pending -->
        <?php if (!empty($pending)): ?>
        <h4 class="mt-4"><i class="bi bi-hourglass-split"></i> Menunggu Montir</h4>
        <div class="row">
            <?php foreach ($pending as $jasa): ?>
            <div class="col-md-6 mb-3">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="bi bi-tools"></i> <?= htmlspecialchars($jasa['nama_jasa']) ?>
                            <span class="badge bg-dark float-end">ID: <?= $jasa['id_transaksi_jasa'] ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item success">
                                <strong>Pesanan Dibuat</strong><br>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($jasa['tanggal_request'])) ?></small>
                            </div>
                            <div class="timeline-item active">
                                <strong>Menunggu Montir...</strong><br>
                                <small class="text-muted">Pesanan Anda akan segera diambil montir</small>
                            </div>
                        </div>
                        
                        <?php if ($jasa['merk_kendaraan']): ?>
                        <div class="alert alert-light mt-3 mb-0">
                            <strong>Kendaraan:</strong> <?= htmlspecialchars($jasa['merk_kendaraan'] . ' ' . $jasa['tipe_kendaraan']) ?>
                            <br><small><?= htmlspecialchars($jasa['nomor_plat']) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Dikerjakan -->
        <?php if (!empty($dikerjakan)): ?>
        <h4 class="mt-4"><i class="bi bi-wrench-adjustable"></i> Sedang Dikerjakan</h4>
        <div class="row">
            <?php foreach ($dikerjakan as $jasa): ?>
            <div class="col-md-6 mb-3">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-tools"></i> <?= htmlspecialchars($jasa['nama_jasa']) ?>
                            <span class="badge bg-dark float-end">ID: <?= $jasa['id_transaksi_jasa'] ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong><i class="bi bi-person-gear"></i> Montir:</strong> <?= htmlspecialchars($jasa['nama_montir']) ?>
                        </div>
                        
                        <div class="timeline">
                            <div class="timeline-item success">
                                <strong>Pesanan Dibuat</strong><br>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($jasa['tanggal_request'])) ?></small>
                            </div>
                            <div class="timeline-item success">
                                <strong>Diambil Montir</strong><br>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($jasa['tanggal_mulai'])) ?></small>
                            </div>
                            <div class="timeline-item active">
                                <strong>Sedang Dikerjakan...</strong><br>
                                <small class="text-muted">
                                    Durasi: 
                                    <?php
                                    $mulai = new DateTime($jasa['tanggal_mulai']);
                                    $sekarang = new DateTime();
                                    $diff = $mulai->diff($sekarang);
                                    echo $diff->format('%h jam %i menit');
                                    ?>
                                </small>
                            </div>
                        </div>
                        
                        <?php if ($jasa['merk_kendaraan']): ?>
                        <div class="alert alert-light mt-3 mb-0">
                            <strong>Kendaraan:</strong> <?= htmlspecialchars($jasa['merk_kendaraan'] . ' ' . $jasa['tipe_kendaraan']) ?>
                            <br><small><?= htmlspecialchars($jasa['nomor_plat']) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Selesai Belum Rating -->
        <?php if (!empty($selesai_belum_rating)): ?>
        <h4 class="mt-4"><i class="bi bi-star"></i> Berikan Rating</h4>
        <div class="row">
            <?php foreach ($selesai_belum_rating as $jasa): ?>
            <div class="col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($jasa['nama_jasa']) ?>
                            <span class="badge bg-dark float-end">ID: <?= $jasa['id_transaksi_jasa'] ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <strong><i class="bi bi-person-gear"></i> Montir:</strong> <?= htmlspecialchars($jasa['nama_montir']) ?><br>
                            <strong><i class="bi bi-calendar-check"></i> Selesai:</strong> <?= date('d/m/Y H:i', strtotime($jasa['tanggal_selesai'])) ?>
                        </div>
                        
                        <?php if ($jasa['catatan_montir']): ?>
                        <div class="alert alert-light">
                            <strong>Catatan Montir:</strong><br>
                            <?= nl2br(htmlspecialchars($jasa['catatan_montir'])) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <h5 class="text-success"><?= formatRupiah($jasa['total_biaya']) ?></h5>
                        </div>
                        
                        <button class="btn btn-warning w-100 mt-3" onclick="showRatingModal(<?= $jasa['id_transaksi_jasa'] ?>, '<?= htmlspecialchars($jasa['nama_montir']) ?>')">
                            <i class="bi bi-star"></i> Berikan Rating & Review
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($pending) && empty($dikerjakan) && empty($selesai_belum_rating)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Tidak ada jasa yang sedang berjalan. 
            <a href="pesan_jasa.php" class="alert-link">Pesan jasa sekarang</a>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="riwayat_jasa_customer.php" class="btn btn-outline-primary">
                <i class="bi bi-clock-history"></i> Lihat Riwayat Lengkap
            </a>
        </div>
    </div>
    
    <!-- Modal Rating -->
    <div class="modal fade" id="modalRating" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Berikan Rating</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="rating">
                        <input type="hidden" name="id_transaksi_jasa" id="rating_id">
                        
                        <div class="text-center mb-3">
                            <h6>Bagaimana pengalaman Anda dengan montir <strong id="nama_montir"></strong>?</h6>
                        </div>
                        
                        <div class="mb-3 text-center">
                            <label class="form-label">Rating *</label><br>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="rating" id="star1" value="1" required>
                                <label class="btn btn-outline-warning" for="star1">⭐ 1</label>
                                
                                <input type="radio" class="btn-check" name="rating" id="star2" value="2">
                                <label class="btn btn-outline-warning" for="star2">⭐ 2</label>
                                
                                <input type="radio" class="btn-check" name="rating" id="star3" value="3">
                                <label class="btn btn-outline-warning" for="star3">⭐ 3</label>
                                
                                <input type="radio" class="btn-check" name="rating" id="star4" value="4">
                                <label class="btn btn-outline-warning" for="star4">⭐ 4</label>
                                
                                <input type="radio" class="btn-check" name="rating" id="star5" value="5">
                                <label class="btn btn-outline-warning" for="star5">⭐ 5</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Review (Opsional)</label>
                            <textarea class="form-control" name="review" rows="3" 
                                      placeholder="Ceritakan pengalaman Anda..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Nanti Saja</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-star"></i> Kirim Rating
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showRatingModal(id, namaMontir) {
            document.getElementById('rating_id').value = id;
            document.getElementById('nama_montir').textContent = namaMontir;
            new bootstrap.Modal(document.getElementById('modalRating')).show();
        }
    </script>
</body>
</html>
