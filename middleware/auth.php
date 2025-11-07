<?php
// Pastikan session sudah dimulai SEBELUM memanggil fungsi-fungsi ini
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memeriksa apakah user sudah login. Jika belum, tendang ke halaman login.
 */
function FUNGSI_CEK_OTENTIKASI() {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Memeriksa apakah user memiliki salah satu peran yang diizinkan.
 * @param array|string $allowed_roles Daftar peran yang diizinkan, cth: ['Owner','Kasir'] atau "Owner,Kasir"
 */
function FUNGSI_CEK_ROLE($allowed_roles) {
    if (!isset($_SESSION['user'])) {
        http_response_code(403);
        die('Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    // Terima array atau string koma
    if (!is_array($allowed_roles)) {
        $allowed_roles = array_map('trim', explode(',', (string)$allowed_roles));
    }

    if (!in_array($_SESSION['user']['role'], $allowed_roles, true)) {
        http_response_code(403);
        die('Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
?>