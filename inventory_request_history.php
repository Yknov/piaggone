<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan: Pastikan user login dan adalah Inventory Staff
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Inventory Staff', 'Store Manager', 'Owner']);

$user = $_SESSION['user'];
$staff_user_id = $user['userID'];

// 2. Ambil data riwayat dari service
$daftar_permintaan = FUNGSI_GET_REQUEST_HISTORY_BY_STAFF($staff_user_id);

// 3. Siapkan variabel dan panggil template
$title = 'Riwayat Permintaan Pengadaan Saya';
require 'views/layouts/header_internal.php';
require 'views/inventory/request_history.php'; // View baru yang akan kita buat
require 'views/layouts/footer.php';
?>