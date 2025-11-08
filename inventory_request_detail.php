<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Inventory Staff', 'Store Manager', 'Owner']);

$user = $_SESSION['user'];
$user_role = $user['role']; // Ambil role

// 2. Validasi ID dari URL
$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    header('Location: inventory_request_history.php');
    exit;
}

// 3. Ambil data dari service (LOGIKA DIPERBARUI)
$request_master = null;
if ($user_role === 'Inventory Staff') {
    // Staff hanya bisa lihat request miliknya
    $request_master = FUNGSI_GET_REQUEST_MASTER($request_id, $user['userID']);
} else if ($user_role === 'Store Manager' || $user_role === 'Owner') {
    // Manager/Owner bisa lihat semua
    $request_master = FUNGSI_GET_REQUEST_MASTER_ADMIN($request_id);
}

$request_items = FUNGSI_GET_REQUEST_DETAILS_ITEMS($request_id);

// 4. Jika data request tidak ditemukan
if (!$request_master) {
    // Arahkan ke halaman riwayat yang sesuai
    if ($user_role === 'Inventory Staff') {
        header('Location: inventory_request_history.php');
    } else {
        header('Location: manager_request_review.php');
    }
    exit;
}

// 5. Siapkan variabel dan panggil template
$title = 'Detail Permintaan #' . htmlspecialchars($request_master['requestID']);
require 'views/layouts/header_internal.php';
require 'views/inventory/request_detail.php';
require 'views/layouts/footer.php';
?>