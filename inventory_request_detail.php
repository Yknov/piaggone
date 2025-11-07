<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan: Pastikan user login dan memiliki peran yang sesuai
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Inventory Staff', 'Store Manager', 'Owner']);

$user = $_SESSION['user'];

// 2. Validasi ID dari URL
$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    // Jika tidak ada ID, tendang ke halaman riwayat
    header('Location: inventory_request_history.php');
    exit;
}

// 3. Ambil data dari service
// Catatan: FUNGSI_GET_REQUEST_MASTER memvalidasi bahwa staff ini hanya bisa melihat request-nya sendiri.
$request_master = FUNGSI_GET_REQUEST_MASTER($request_id, $user['userID']);
$request_items = FUNGSI_GET_REQUEST_DETAILS_ITEMS($request_id);

// 4. Jika data request tidak ditemukan (mungkin ID salah atau bukan miliknya)
if (!$request_master) {
    // Kita bisa buat halaman error, tapi untuk sekarang kita redirect
    header('Location: inventory_request_history.php');
    exit;
}

// 5. Siapkan variabel dan panggil template
$title = 'Detail Permintaan #' . htmlspecialchars($request_master['requestID']);
require 'views/layouts/header_internal.php';
require 'views/inventory/request_detail.php'; // View baru yang akan kita buat
require 'views/layouts/footer.php';
?>