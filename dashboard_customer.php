<?php
session_start();
require_once 'middleware/auth.php';

// 1. Pastikan user login
FUNGSI_CEK_OTENTIKASI();

// 2. Pastikan hanya role 'Customer' yang bisa akses
FUNGSI_CEK_ROLE(['Customer']);

// Ambil data user untuk ditampilkan di view
$user = $_SESSION['user'];

// Siapkan variabel untuk layout
$title = 'Dashboard Customer';

// Panggil bagian-bagian template
require 'views/layouts/header_internal.php';
require 'views/customer/dashboard.php'; // Ini adalah konten spesifik halaman
require 'views/layouts/footer.php';
?>