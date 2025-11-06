<?php
session_start();
require_once 'middleware/auth.php';

// 1. Pastikan user login
FUNGSI_CEK_OTENTIKASI();

// 2. Pastikan hanya role 'Store Manager' yang bisa akses
FUNGSI_CEK_ROLE(['Store Manager']);

// Ambil data user untuk ditampilkan di view
$user = $_SESSION['user'];

// Siapkan variabel untuk layout
$title = 'Dashboard Store Manager';

// Panggil bagian-bagian template
require 'views/layouts/header_internal.php';
require 'views/manager/dashboard.php'; // Ini adalah konten spesifik halaman
require 'views/layouts/footer.php';
?>