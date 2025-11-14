<?php
// api/jasa_process_actions.php
// API untuk quick actions AJAX pada proses jasa
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/jasa_process.php';

header('Content-Type: application/json');

// Cek apakah user sudah login
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Please login'
    ]);
    exit();
}

$conn = get_db_connection();
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'get_stats':
        // Mendapatkan statistik proses jasa
        $barber_id = ($_SESSION['role'] === 'barber') ? $_SESSION['user_id'] : null;
        $stats = get_jasa_process_stats($conn, $barber_id);
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        break;
    
    case 'get_active_processes':
        // Mendapatkan proses yang sedang aktif (waiting atau in_progress)
        if ($_SESSION['role'] === 'admin') {
            $waiting = get_jasa_process_by_status($conn, 'waiting');
            $in_progress = get_jasa_process_by_status($conn, 'in_progress');
            $processes = array_merge($waiting, $in_progress);
        } else if ($_SESSION['role'] === 'barber') {
            $all = get_jasa_process_by_barber($conn, $_SESSION['user_id']);
            $processes = array_filter($all, function($p) {
                return in_array($p['status'], ['waiting', 'in_progress']);
            });
            $processes = array_values($processes); // Re-index array
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit();
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($processes),
            'data' => $processes
        ]);
        break;
    
    case 'get_ready_bookings':
        // Mendapatkan booking yang siap diproses
        if (!in_array($_SESSION['role'], ['admin', 'barber'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit();
        }
        
        $bookings = get_bookings_ready_to_process($conn);
        
        echo json_encode([
            'success' => true,
            'count' => count($bookings),
            'data' => $bookings
        ]);
        break;
    
    case 'quick_start':
        // Quick start untuk proses tertentu
        $process_id = isset($_POST['process_id']) ? $_POST['process_id'] : '';
        
        if (empty($process_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Process ID required'
            ]);
            exit();
        }
        
        // Validasi akses
        $process = get_jasa_process_by_id($conn, $process_id);
        if (!$process) {
            echo json_encode([
                'success' => false,
                'message' => 'Process not found'
            ]);
            exit();
        }
        
        if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $process['barber_id']) {
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit();
        }
        
        $result = start_jasa_process($conn, $process_id);
        echo json_encode($result);
        break;
    
    case 'quick_complete':
        // Quick complete untuk proses tertentu
        $process_id = isset($_POST['process_id']) ? $_POST['process_id'] : '';
        
        if (empty($process_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Process ID required'
            ]);
            exit();
        }
        
        // Validasi akses
        $process = get_jasa_process_by_id($conn, $process_id);
        if (!$process) {
            echo json_encode([
                'success' => false,
                'message' => 'Process not found'
            ]);
            exit();
        }
        
        if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $process['barber_id']) {
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit();
        }
        
        $result = complete_jasa_process($conn, $process_id);
        echo json_encode($result);
        break;
    
    case 'get_process_duration':
        // Mendapatkan durasi proses yang sedang berjalan
        $process_id = isset($_GET['process_id']) ? $_GET['process_id'] : '';
        
        if (empty($process_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Process ID required'
            ]);
            exit();
        }
        
        $process = get_jasa_process_by_id($conn, $process_id);
        
        if (!$process) {
            echo json_encode([
                'success' => false,
                'message' => 'Process not found'
            ]);
            exit();
        }
        
        if ($process['start_time'] && !$process['end_time']) {
            $start = new DateTime($process['start_time']);
            $now = new DateTime();
            $diff = $start->diff($now);
            
            echo json_encode([
                'success' => true,
                'duration' => [
                    'hours' => $diff->h,
                    'minutes' => $diff->i,
                    'seconds' => $diff->s,
                    'total_minutes' => ($diff->h * 60) + $diff->i,
                    'formatted' => sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s)
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Process not started or already completed'
            ]);
        }
        break;
    
    case 'get_customer_active_process':
        // Mendapatkan proses aktif untuk customer (untuk tracking)
        if ($_SESSION['role'] !== 'customer') {
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit();
        }
        
        $customer_id = $_SESSION['user_id'];
        $sql = "SELECT jp.*, 
                       b.service_id, b.booking_date, b.booking_time,
                       u.name as barber_name,
                       s.name as service_name, s.duration, s.price
                FROM jasa_process jp
                JOIN bookings b ON jp.booking_id = b.id
                JOIN users u ON jp.barber_id = u.id
                JOIN services s ON b.service_id = s.id
                WHERE b.customer_id = '$customer_id'
                AND jp.status IN ('waiting', 'in_progress')
                ORDER BY jp.created_at DESC
                LIMIT 1";
        
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $process = mysqli_fetch_assoc($result);
            
            // Hitung durasi jika sudah dimulai
            if ($process['start_time'] && !$process['end_time']) {
                $start = new DateTime($process['start_time']);
                $now = new DateTime();
                $diff = $start->diff($now);
                $process['current_duration'] = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
            }
            
            echo json_encode([
                'success' => true,
                'has_active' => true,
                'data' => $process
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'has_active' => false,
                'message' => 'No active process'
            ]);
        }
        break;
    
    case 'get_barber_performance':
        // Mendapatkan performa barber
        if (!in_array($_SESSION['role'], ['admin', 'barber'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit();
        }
        
        $barber_id = ($_SESSION['role'] === 'barber') ? $_SESSION['user_id'] : (isset($_GET['barber_id']) ? $_GET['barber_id'] : null);
        
        if (!$barber_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Barber ID required'
            ]);
            exit();
        }
        
        $stats = get_jasa_process_stats($conn, $barber_id);
        $avg_duration = get_average_process_duration($conn, $barber_id);
        
        // Hitung success rate
        $total = $stats['total'];
        $completed = $stats['completed'];
        $success_rate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
        
        // Proses hari ini
        $today = date('Y-m-d');
        $today_sql = "SELECT COUNT(*) as count 
                      FROM jasa_process 
                      WHERE barber_id = '$barber_id' 
                      AND DATE(created_at) = '$today'";
        $today_result = mysqli_query($conn, $today_sql);
        $today_row = mysqli_fetch_assoc($today_result);
        $today_count = $today_row['count'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'avg_duration_minutes' => round($avg_duration, 2),
                'success_rate' => $success_rate,
                'today_processes' => $today_count
            ]
        ]);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

mysqli_close($conn);
?>
