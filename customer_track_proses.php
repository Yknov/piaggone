<?php
// customer/track_process.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/jasa_process.php';

// Cek apakah user adalah customer
if (!is_logged_in() || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

$conn = get_db_connection();
$customer_id = $_SESSION['user_id'];

// Get semua proses jasa untuk customer ini
$sql = "SELECT jp.*, 
               b.service_id, b.booking_date, b.booking_time,
               u.name as barber_name,
               s.name as service_name, s.duration, s.price
        FROM jasa_process jp
        JOIN bookings b ON jp.booking_id = b.id
        JOIN users u ON jp.barber_id = u.id
        JOIN services s ON b.service_id = s.id
        WHERE b.customer_id = '$customer_id'
        ORDER BY jp.created_at DESC";

$result = mysqli_query($conn, $sql);
$processes = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $processes[] = $row;
    }
}

// Hitung statistik
$stats = [
    'total' => count($processes),
    'waiting' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach ($processes as $process) {
    $stats[$process['status']]++;
}

$page_title = 'Lacak Proses Jasa';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/customer_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-geo-alt"></i> Lacak Proses Jasa
                </h1>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Total Proses</h6>
                            <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Menunggu</h6>
                            <h2 class="mb-0"><?php echo $stats['waiting']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Sedang Proses</h6>
                            <h2 class="mb-0"><?php echo $stats['in_progress']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Selesai</h6>
                            <h2 class="mb-0"><?php echo $stats['completed']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($processes)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Anda belum memiliki proses jasa. Silakan <a href="booking.php">buat booking</a> terlebih dahulu.
                </div>
            <?php else: ?>
                <!-- Process Timeline -->
                <div class="row">
                    <?php foreach ($processes as $process): ?>
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-0">
                                                <i class="bi bi-scissors"></i> 
                                                <?php echo htmlspecialchars($process['service_name']); ?>
                                            </h5>
                                            <small class="text-muted">
                                                Booking #<?php echo $process['booking_id']; ?> - 
                                                <?php echo date('d/m/Y H:i', strtotime($process['booking_date'] . ' ' . $process['booking_time'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <?php
                                            $badge_class = [
                                                'waiting' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $status_text = [
                                                'waiting' => 'Menunggu',
                                                'in_progress' => 'Sedang Proses',
                                                'completed' => 'Selesai',
                                                'cancelled' => 'Dibatalkan'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class[$process['status']]; ?> fs-6">
                                                <?php echo $status_text[$process['status']]; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Info Section -->
                                        <div class="col-md-4">
                                            <h6 class="text-muted mb-3">Informasi</h6>
                                            <div class="mb-2">
                                                <i class="bi bi-person-fill text-primary"></i>
                                                <strong>Barber:</strong> <?php echo htmlspecialchars($process['barber_name']); ?>
                                            </div>
                                            <div class="mb-2">
                                                <i class="bi bi-clock-fill text-primary"></i>
                                                <strong>Durasi:</strong> <?php echo $process['duration']; ?> menit
                                            </div>
                                            <div class="mb-2">
                                                <i class="bi bi-cash text-primary"></i>
                                                <strong>Harga:</strong> Rp <?php echo number_format($process['price'], 0, ',', '.'); ?>
                                            </div>
                                            
                                            <?php if ($process['start_time'] && $process['end_time']): ?>
                                                <div class="mb-2">
                                                    <i class="bi bi-hourglass text-primary"></i>
                                                    <strong>Durasi Aktual:</strong>
                                                    <?php
                                                    $start = new DateTime($process['start_time']);
                                                    $end = new DateTime($process['end_time']);
                                                    $diff = $start->diff($end);
                                                    echo $diff->h . 'j ' . $diff->i . 'm';
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Timeline Section -->
                                        <div class="col-md-8">
                                            <h6 class="text-muted mb-3">Status Proses</h6>
                                            
                                            <!-- Progress Bar -->
                                            <div class="progress mb-3" style="height: 30px;">
                                                <?php
                                                $progress_width = 25;
                                                $progress_class = 'bg-warning';
                                                
                                                if ($process['status'] === 'in_progress') {
                                                    $progress_width = 50;
                                                    $progress_class = 'bg-info progress-bar-striped progress-bar-animated';
                                                } elseif ($process['status'] === 'completed') {
                                                    $progress_width = 100;
                                                    $progress_class = 'bg-success';
                                                } elseif ($process['status'] === 'cancelled') {
                                                    $progress_width = 100;
                                                    $progress_class = 'bg-danger';
                                                }
                                                ?>
                                                <div class="progress-bar <?php echo $progress_class; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $progress_width; ?>%">
                                                    <?php echo $status_text[$process['status']]; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Timeline Steps -->
                                            <div class="process-timeline">
                                                <div class="timeline-step <?php echo $process['status'] !== 'cancelled' ? 'completed' : 'cancelled'; ?>">
                                                    <div class="timeline-icon">
                                                        <i class="bi bi-check-circle-fill"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <strong>Booking Dikonfirmasi</strong>
                                                        <p class="text-muted mb-0">
                                                            <?php echo date('H:i', strtotime($process['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="timeline-step <?php echo in_array($process['status'], ['in_progress', 'completed']) ? 'completed' : ($process['status'] === 'cancelled' ? 'cancelled' : ''); ?>">
                                                    <div class="timeline-icon">
                                                        <i class="bi bi-<?php echo $process['start_time'] ? 'check-circle-fill' : 'circle'; ?>"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <strong>Proses Dimulai</strong>
                                                        <?php if ($process['start_time']): ?>
                                                            <p class="text-muted mb-0">
                                                                <?php echo date('H:i', strtotime($process['start_time'])); ?>
                                                            </p>
                                                        <?php else: ?>
                                                            <p class="text-muted mb-0">Menunggu...</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="timeline-step <?php echo $process['status'] === 'completed' ? 'completed' : ($process['status'] === 'cancelled' ? 'cancelled' : ''); ?>">
                                                    <div class="timeline-icon">
                                                        <i class="bi bi-<?php echo $process['end_time'] ? 'check-circle-fill' : 'circle'; ?>"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <strong>Selesai</strong>
                                                        <?php if ($process['end_time']): ?>
                                                            <p class="text-muted mb-0">
                                                                <?php echo date('H:i', strtotime($process['end_time'])); ?>
                                                            </p>
                                                        <?php else: ?>
                                                            <p class="text-muted mb-0">
                                                                <?php echo $process['status'] === 'cancelled' ? 'Dibatalkan' : 'Menunggu...'; ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($process['notes']): ?>
                                                <div class="mt-3">
                                                    <div class="alert alert-info mb-0">
                                                        <strong><i class="bi bi-info-circle"></i> Catatan:</strong>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($process['notes'])); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.process-timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    padding: 20px 0;
}

.process-timeline:before {
    content: '';
    position: absolute;
    top: 45px;
    left: 15%;
    right: 15%;
    height: 2px;
    background: #dee2e6;
    z-index: 0;
}

.timeline-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.timeline-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: white;
    border: 3px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 24px;
    color: #6c757d;
}

.timeline-step.completed .timeline-icon {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.timeline-step.cancelled .timeline-icon {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

.timeline-content strong {
    display: block;
    margin-bottom: 5px;
}
</style>

<script>
// Auto refresh untuk update realtime (opsional)
setInterval(() => {
    // Hanya refresh jika ada proses yang sedang berjalan
    const hasActiveProcess = document.querySelector('.badge.bg-info, .badge.bg-warning');
    if (hasActiveProcess) {
        location.reload();
    }
}, 30000); // Refresh setiap 30 detik
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
