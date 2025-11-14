<?php
// admin/jasa_process.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/jasa_process.php';

// Cek apakah user adalah admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$conn = get_db_connection();

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get data berdasarkan filter
if ($status_filter === 'all') {
    $processes = get_all_jasa_process($conn);
} else {
    $processes = get_jasa_process_by_status($conn, $status_filter);
}

// Get statistics
$stats = get_jasa_process_stats($conn);

// Get bookings yang siap diproses
$ready_bookings = get_bookings_ready_to_process($conn);

$page_title = 'Kelola Proses Jasa';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Kelola Proses Jasa</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProcessModal">
                    <i class="bi bi-plus-circle"></i> Buat Proses Jasa Baru
                </button>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Menunggu</h5>
                            <h2><?php echo $stats['waiting']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Sedang Proses</h5>
                            <h2><?php echo $stats['in_progress']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Selesai</h5>
                            <h2><?php echo $stats['completed']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Dibatalkan</h5>
                            <h2><?php echo $stats['cancelled']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="?status=all" class="btn btn-outline-primary <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        Semua
                    </a>
                    <a href="?status=waiting" class="btn btn-outline-warning <?php echo $status_filter === 'waiting' ? 'active' : ''; ?>">
                        Menunggu
                    </a>
                    <a href="?status=in_progress" class="btn btn-outline-info <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>">
                        Sedang Proses
                    </a>
                    <a href="?status=completed" class="btn btn-outline-success <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                        Selesai
                    </a>
                    <a href="?status=cancelled" class="btn btn-outline-danger <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                        Dibatalkan
                    </a>
                </div>
            </div>

            <!-- Process Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Proses Jasa</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Barber</th>
                                    <th>Layanan</th>
                                    <th>Tanggal Booking</th>
                                    <th>Status</th>
                                    <th>Durasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($processes)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($processes as $process): ?>
                                        <tr>
                                            <td>#<?php echo $process['id']; ?></td>
                                            <td><?php echo htmlspecialchars($process['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($process['barber_name']); ?></td>
                                            <td><?php echo htmlspecialchars($process['service_name']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($process['booking_date'] . ' ' . $process['booking_time'])); ?></td>
                                            <td>
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
                                                <span class="badge bg-<?php echo $badge_class[$process['status']]; ?>">
                                                    <?php echo $status_text[$process['status']]; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if ($process['start_time'] && $process['end_time']) {
                                                    $start = new DateTime($process['start_time']);
                                                    $end = new DateTime($process['end_time']);
                                                    $diff = $start->diff($end);
                                                    echo $diff->h . 'j ' . $diff->i . 'm';
                                                } else if ($process['start_time']) {
                                                    echo 'Sedang berjalan';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info" onclick="viewDetail(<?php echo $process['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($process['status'] === 'waiting'): ?>
                                                        <button class="btn btn-success" onclick="startProcess(<?php echo $process['id']; ?>)">
                                                            <i class="bi bi-play-fill"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($process['status'] === 'in_progress'): ?>
                                                        <button class="btn btn-primary" onclick="completeProcess(<?php echo $process['id']; ?>)">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (in_array($process['status'], ['waiting', 'in_progress'])): ?>
                                                        <button class="btn btn-danger" onclick="cancelProcess(<?php echo $process['id']; ?>)">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Create Process -->
<div class="modal fade" id="createProcessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Proses Jasa Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../handlers/jasa_process_handler.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Pilih Booking</label>
                        <select name="booking_id" class="form-select" required>
                            <option value="">-- Pilih Booking --</option>
                            <?php foreach ($ready_bookings as $booking): ?>
                                <option value="<?php echo $booking['id']; ?>">
                                    #<?php echo $booking['id']; ?> - 
                                    <?php echo htmlspecialchars($booking['customer_name']); ?> - 
                                    <?php echo htmlspecialchars($booking['service_name']); ?> - 
                                    <?php echo date('d/m/Y H:i', strtotime($booking['booking_date'] . ' ' . $booking['booking_time'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pilih Barber</label>
                        <select name="barber_id" class="form-select" required>
                            <option value="">-- Pilih Barber --</option>
                            <?php
                            $barbers = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'barber'");
                            while ($barber = mysqli_fetch_assoc($barbers)):
                            ?>
                                <option value="<?php echo $barber['id']; ?>">
                                    <?php echo htmlspecialchars($barber['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Proses Jasa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(id) {
    // Load detail via AJAX
    fetch(`../api/get_jasa_process_detail.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('detailContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        });
}

function startProcess(id) {
    if (confirm('Mulai proses jasa ini?')) {
        submitAction('start', id);
    }
}

function completeProcess(id) {
    if (confirm('Selesaikan proses jasa ini?')) {
        submitAction('complete', id);
    }
}

function cancelProcess(id) {
    const notes = prompt('Alasan pembatalan:');
    if (notes !== null) {
        submitAction('cancel', id, notes);
    }
}

function submitAction(action, processId, notes = '') {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('process_id', processId);
    if (notes) {
        formData.append('cancel_notes', notes);
    }
    
    fetch('../handlers/jasa_process_handler.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
