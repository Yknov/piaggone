<?php
// api/get_jasa_process_detail.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/jasa_process.php';

header('Content-Type: application/json');

// Cek apakah user sudah login
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$conn = get_db_connection();
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID tidak valid'
    ]);
    exit();
}

$process = get_jasa_process_by_id($conn, $id);

if (!$process) {
    echo json_encode([
        'success' => false,
        'message' => 'Proses jasa tidak ditemukan'
    ]);
    exit();
}

// Cek akses: Admin bisa lihat semua, Barber hanya bisa lihat miliknya
if ($_SESSION['role'] === 'barber' && $_SESSION['user_id'] != $process['barber_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses untuk melihat proses ini'
    ]);
    exit();
}

// Status badge
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

// Build HTML
$html = '
<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted">Informasi Booking</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">ID Proses</th>
                <td>#' . $process['id'] . '</td>
            </tr>
            <tr>
                <th>ID Booking</th>
                <td>#' . $process['booking_id'] . '</td>
            </tr>
            <tr>
                <th>Customer</th>
                <td>' . htmlspecialchars($process['customer_name']) . '</td>
            </tr>
            <tr>
                <th>Barber</th>
                <td>' . htmlspecialchars($process['barber_name']) . '</td>
            </tr>
            <tr>
                <th>Layanan</th>
                <td>' . htmlspecialchars($process['service_name']) . '</td>
            </tr>
            <tr>
                <th>Durasi Estimasi</th>
                <td>' . $process['duration'] . ' menit</td>
            </tr>
            <tr>
                <th>Harga</th>
                <td>Rp ' . number_format($process['price'], 0, ',', '.') . '</td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-muted">Informasi Proses</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Status</th>
                <td>
                    <span class="badge bg-' . $badge_class[$process['status']] . '">
                        ' . $status_text[$process['status']] . '
                    </span>
                </td>
            </tr>
            <tr>
                <th>Tanggal Booking</th>
                <td>' . date('d/m/Y', strtotime($process['booking_date'])) . '</td>
            </tr>
            <tr>
                <th>Jam Booking</th>
                <td>' . date('H:i', strtotime($process['booking_time'])) . '</td>
            </tr>';

if ($process['start_time']) {
    $html .= '
            <tr>
                <th>Waktu Mulai</th>
                <td>' . date('d/m/Y H:i:s', strtotime($process['start_time'])) . '</td>
            </tr>';
}

if ($process['end_time']) {
    $html .= '
            <tr>
                <th>Waktu Selesai</th>
                <td>' . date('d/m/Y H:i:s', strtotime($process['end_time'])) . '</td>
            </tr>';
    
    // Hitung durasi aktual
    $start = new DateTime($process['start_time']);
    $end = new DateTime($process['end_time']);
    $diff = $start->diff($end);
    
    $html .= '
            <tr>
                <th>Durasi Aktual</th>
                <td>' . $diff->h . ' jam ' . $diff->i . ' menit ' . $diff->s . ' detik</td>
            </tr>';
}

$html .= '
            <tr>
                <th>Dibuat Pada</th>
                <td>' . date('d/m/Y H:i:s', strtotime($process['created_at'])) . '</td>
            </tr>
            <tr>
                <th>Terakhir Update</th>
                <td>' . date('d/m/Y H:i:s', strtotime($process['updated_at'])) . '</td>
            </tr>
        </table>
    </div>
</div>';

if ($process['notes']) {
    $html .= '
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-muted">Catatan</h6>
        <div class="alert alert-info">
            ' . nl2br(htmlspecialchars($process['notes'])) . '
        </div>
    </div>
</div>';
}

// Timeline
$html .= '
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-muted">Timeline</h6>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-marker bg-primary"></div>
                <div class="timeline-content">
                    <strong>Proses dibuat</strong>
                    <p class="text-muted mb-0">' . date('d/m/Y H:i:s', strtotime($process['created_at'])) . '</p>
                </div>
            </div>';

if ($process['start_time']) {
    $html .= '
            <div class="timeline-item">
                <div class="timeline-marker bg-info"></div>
                <div class="timeline-content">
                    <strong>Proses dimulai</strong>
                    <p class="text-muted mb-0">' . date('d/m/Y H:i:s', strtotime($process['start_time'])) . '</p>
                </div>
            </div>';
}

if ($process['end_time']) {
    $html .= '
            <div class="timeline-item">
                <div class="timeline-marker bg-success"></div>
                <div class="timeline-content">
                    <strong>Proses selesai</strong>
                    <p class="text-muted mb-0">' . date('d/m/Y H:i:s', strtotime($process['end_time'])) . '</p>
                </div>
            </div>';
}

if ($process['status'] === 'cancelled') {
    $html .= '
            <div class="timeline-item">
                <div class="timeline-marker bg-danger"></div>
                <div class="timeline-content">
                    <strong>Proses dibatalkan</strong>
                    <p class="text-muted mb-0">' . date('d/m/Y H:i:s', strtotime($process['updated_at'])) . '</p>
                </div>
            </div>';
}

$html .= '
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child):before {
    content: "";
    position: absolute;
    left: -22px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -27px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    padding-left: 10px;
}
</style>
';

echo json_encode([
    'success' => true,
    'html' => $html,
    'data' => $process
]);
?>
