<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan: Hanya Manager atau Owner
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Store Manager', 'Owner']);

$user = $_SESSION['user'];

// 2. Ambil SEMUA data riwayat dari service
$daftar_permintaan = FUNGSI_GET_ALL_REQUESTS_HISTORY();

// 3. Siapkan variabel dan panggil template
$title = 'Seluruh Riwayat Pengadaan';
require 'views/layouts/header_internal.php';
require 'views/manager/request_history.php'; // View baru yang akan kita buat
require 'views/layouts/footer.php';
?>