<?php
// barber/my_processes.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/jasa_process.php';

// Cek apakah user adalah barber
if (!is_logged_in() || $_SESSION['role'] !== 'barber') {
    header('Location: ../login.php');
    exit();
}

$conn = get_db_connection();
$barber_id = $_SESSION['user_id'];

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get data berdasarkan filter
if ($status_filter === 'all') {
    $processes = get_jasa_process_by_barber($conn, $barber_id);
} else {
    $all_processes = get_jasa_process_by_barber($conn, $barber_id);
    $processes = array_filter($all_processes, function($p) use ($status_filter) {
        return $p['status'] === $status_filter;
    });
}

// Get statistics untuk barber ini
$stats = get_jasa_process_stats($conn, $barber_id);
$avg_duration = get_average_process_duration($conn, $barber_id);

$page_title = 'Proses Jasa Saya';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/barber_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Proses Jasa Saya</h1>
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
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Total Proses</h6>
                            <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Menunggu</h6>
                            <h3 class="mb-0"><?php echo $stats['waiting']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Sedang Proses</h6>
                            <h3 class="mb-0"><?php echo $stats['in_progress']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Selesai</h6>
                            <h3 class="mb-0"><?php echo $stats['completed']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-title">Dibatalkan</h6>
                            <h3 class="mb-0"><?php echo $stats['cancelled']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Rata-rata Durasi</h6>
                            <h3 class="mb-0"><?php echo round($avg_duration); ?> mnt</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="?status=all" class="btn btn-outline-primary <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        Semua (<?php echo $stats['total']; ?>)
                    </a>
                    <a href="?status=waiting" class="btn btn-outline-warning <?php echo $status_filter === 'waiting' ? 'active' : ''; ?>">
                        Menunggu (<?php echo $stats['waiting']; ?>)
                    </a>
                    <a href="?status=in_progress" class="btn btn-outline-info <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>">
                        Sedang Proses (<?php echo $stats['in_progress']; ?>)
                    </a>
                    <a href="?status=completed" class="btn btn-outline-success <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                        Selesai (<?php echo $stats['completed']; ?>)
                    </a>
                    <a href="?status=cancelled" class="btn btn-outline-danger <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                        Dibatalkan (<?php echo $stats['cancelled']; ?>)
                    </a>
                </div>
            </div>

            <!-- Process Cards -->
            <div class="row">
                <?php if (empty($processes)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Tidak ada proses jasa
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($processes as $process): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <strong>#<?php echo $process['id']; ?> - <?php echo htmlspecialchars($process['service_name']); ?></strong>
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
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Customer:</strong></div>
                                        <div class="col-8"><?php echo htmlspecialchars($process['customer_name']); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Tanggal:</strong></div>
                                        <div class="col-8"><?php echo date('d/m/Y', strtotime($process['booking_date'])); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Jam:</strong></div>
                                        <div class="col-8"><?php echo date('H:i', strtotime($process['booking_time'])); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Durasi:</strong></div>
                                        <div class="col-8"><?php echo $process['duration']; ?> menit</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Harga:</strong></div>
                                        <div class="col-8">Rp <?php echo number_format($process['price'], 0, ',', '.'); ?></div>
                                    </div>
                                    
                                    <?php if ($process['start_time']): ?>
                                        <div class="row mb-2">
                                            <div class="col-4"><strong>Mulai:</strong></div>
                                            <div class="col-8"><?php echo date('H:i', strtotime($process['start_time'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($process['end_time']): ?>
                                        <div class="row mb-2">
                                            <div class="col-4"><strong>Selesai:</strong></div>
                                            <div class="col-8"><?php echo date('H:i', strtotime($process['end_time'])); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4"><strong>Total Durasi:</strong></div>
                                            <div class="col-8">
                                                <?php
                                                $start = new DateTime($process['start_time']);
                                                $end = new DateTime($process['end_time']);
                                                $diff = $start->diff($end);
                                                echo $diff->h . ' jam ' . $diff->i . ' menit';
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($process['notes']): ?>
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <strong>Catatan:</strong>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($process['notes'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewDetail(<?php echo $process['id']; ?>)">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                        
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($process['status'] === 'waiting'): ?>
                                                <button class="btn btn-success" onclick="startProcess(<?php echo $process['id']; ?>)">
                                                    <i class="bi bi-play-fill"></i> Mulai
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($process['status'] === 'in_progress'): ?>
                                                <button class="btn btn-primary" onclick="completeProcess(<?php echo $process['id']; ?>)">
                                                    <i class="bi bi-check-circle"></i> Selesaikan
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($process['status'], ['waiting', 'in_progress'])): ?>
                                                <button class="btn btn-warning" onclick="updateNotes(<?php echo $process['id']; ?>)">
                                                    <i class="bi bi-pencil"></i> Catatan
                                                </button>
                                                <button class="btn btn-danger" onclick="cancelProcess(<?php echo $process['id']; ?>)">
                                                    <i class="bi bi-x-circle"></i> Batalkan
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
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
    if (confirm('Mulai proses jasa ini sekarang?')) {
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
    if (notes !== null && notes.trim() !== '') {
        submitAction('cancel', id, notes);
    } else if (notes !== null) {
        alert('Alasan pembatalan harus diisi');
    }
}

function updateNotes(id) {
    const notes = prompt('Update catatan:');
    if (notes !== null) {
        const formData = new FormData();
        formData.append('action', 'update_notes');
        formData.append('process_id', id);
        formData.append('notes', notes);
        
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

// Auto refresh untuk update status realtime (opsional)
// setInterval(() => {
//     location.reload();
// }, 60000); // Refresh setiap 1 menit
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
