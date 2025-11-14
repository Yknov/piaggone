<?php
// handlers/jasa_process_handler.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/jasa_process.php';
require_once __DIR__ . '/../functions/auth.php';

// Cek apakah user sudah login
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$conn = get_db_connection();
$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = ['success' => false, 'message' => ''];

switch ($action) {
    case 'create':
        // Hanya admin dan barber yang bisa membuat proses jasa
        if (!in_array($_SESSION['role'], ['admin', 'barber'])) {
            $response['message'] = 'Anda tidak memiliki akses untuk membuat proses jasa';
            break;
        }
        
        $booking_id = isset($_POST['booking_id']) ? $_POST['booking_id'] : '';
        $barber_id = isset($_POST['barber_id']) ? $_POST['barber_id'] : $_SESSION['user_id'];
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        if (empty($booking_id)) {
            $response['message'] = 'Booking ID harus diisi';
            break;
        }
        
        $response = create_jasa_process($conn, $booking_id, $barber_id, $notes);
        break;
    
    case 'start':
        // Hanya barber yang assigned yang bisa memulai proses
        $process_id = isset($_POST['process_id']) ? $_POST['process_id'] : '';
        
        if (empty($process_id)) {
            $response['message'] = 'Process ID harus diisi';
            break;
        }
        
        // Cek apakah user adalah barber yang assigned
        $process = get_jasa_process_by_id($conn, $process_id);
        
        if (!$process) {
            $response['message'] = 'Proses jasa tidak ditemukan';
            break;
        }
        
        if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $process['barber_id']) {
            $response['message'] = 'Anda tidak memiliki akses untuk memulai proses ini';
            break;
        }
        
        $response = start_jasa_process($conn, $process_id);
        break;
    
    case 'complete':
        // Hanya barber yang assigned yang bisa menyelesaikan proses
        $process_id = isset($_POST['process_id']) ? $_POST['process_id'] : '';
        
        if (empty($process_id)) {
            $response['message'] = 'Process ID harus diisi';
            break;
        }
        
        // Cek apakah user adalah barber yang assigned
        $process = get_jasa_process_by_id($conn, $process_id);
        
        if (!$process) {
            $response['message'] = 'Proses jasa tidak ditemukan';
            break;
        }
        
        if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $process['barber_id']) {
            $response['message'] = 'Anda tidak memiliki akses untuk menyelesaikan proses ini';
            break;
        }
        
        $response = complete_jasa_process($conn, $process_id);
        break;
    
    case 'cancel':
        // Hanya admin dan barber yang assigned yang bisa membatalkan proses
        $process_id = isset($_POST['process_id']) ? $_POST['process_id'] : '';
        $cancel_notes = isset($_POST['cancel_notes']) ? $_POST['cancel_notes'] : '';
        
        if (empty($process_id)) {
            $response['message'] = 'Process ID harus diisi';
            break;
        }
        
        // Cek apakah user adalah barber yang assigned
        $process = get_jasa_process_by_id($conn, $process_id);
        
        if (!$process) {
            $response['message'] = 'Proses jasa tidak ditemukan';
            break;
        }
        
        if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $process['barber_id']) {
            $response['message'] = 'Anda tidak memiliki akses untuk membatalkan proses ini';
            break;
        }
        
        $response = cancel_jasa_process($conn, $process_id, $cancel_notes);
        break;
    
    case 'update_notes':
        // Hanya barber yang assigned yang bisa update notes
        $process_id = isset($_POST['process_id']) ? $_POST['process_id'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        if (empty($process_id)) {
            $response['message'] = 'Process ID harus diisi';
            break;
        }
        
        // Cek apakah user adalah barber yang assigned
        $process = get_jasa_process_by_id($conn, $process_id);
        
        if (!$process) {
            $response['message'] = 'Proses jasa tidak ditemukan';
            break;
        }
        
        if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $process['barber_id']) {
            $response['message'] = 'Anda tidak memiliki akses untuk update proses ini';
            break;
        }
        
        $response = update_jasa_process_notes($conn, $process_id, $notes);
        break;
    
    default:
        $response['message'] = 'Action tidak valid';
        break;
}

// Return response sebagai JSON jika request adalah AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Jika bukan AJAX, redirect dengan session message
$_SESSION['message'] = $response['message'];
$_SESSION['message_type'] = $response['success'] ? 'success' : 'error';

// Redirect ke halaman sebelumnya atau ke dashboard
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../dashboard.php';
header('Location: ' . $redirect);
exit();
?>
