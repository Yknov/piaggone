<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan: Hanya Manager atau Owner yang bisa mengakses
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Store Manager', 'Owner']);

$user = $_SESSION['user'];
$error = null;
$success = null;

// 2. Logika untuk memproses aksi (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'Disetujui' atau 'Ditolak'
    $manager_id = $user['userID'];

    if ($request_id && $action) {
        $result = FUNGSI_UPDATE_REQUEST_STATUS($request_id, $manager_id, $action);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = "Aksi tidak valid.";
    }
}

// 3. Ambil data untuk ditampilkan di tabel (hanya yang 'Menunggu')
$daftar_permintaan = FUNGSI_GET_ALL_REQUESTS_BY_STATUS('Menunggu');

// 4. Siapkan variabel dan panggil template
$title = 'Tinjau Permintaan Pengadaan';
require 'views/layouts/header_internal.php';
require 'views/manager/request_review.php'; // View baru yang akan kita buat
require 'views/layouts/footer.php';
?>