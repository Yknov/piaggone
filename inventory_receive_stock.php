<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan: Hanya Inventory Staff atau lebih tinggi
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Inventory Staff', 'Store Manager', 'Owner']);

$user = $_SESSION['user'];
$error = null;
$success = null;

// 2. Logika untuk memproses penerimaan barang (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_request_id'])) {
    $request_id = $_POST['receive_request_id'];
    $staff_id = $user['userID'];

    $result = FUNGSI_RECEIVE_STOCK($request_id, $staff_id);

    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// 3. Ambil data untuk ditampilkan di tabel (hanya yang 'Disetujui')
// Kita bisa gunakan lagi fungsi FUNGSI_GET_ALL_REQUESTS_BY_STATUS
$daftar_permintaan_disetujui = FUNGSI_GET_ALL_REQUESTS_BY_STATUS('Disetujui');

// 4. Siapkan variabel dan panggil template
$title = 'Penerimaan Barang';
require 'views/layouts/header_internal.php';
require 'views/inventory/receive_stock.php'; // View baru yang akan kita buat
require 'views/layouts/footer.php';
?>