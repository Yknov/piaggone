<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php'; // Panggil service

// 1. Pastikan user login
FUNGSI_CEK_OTENTIKASI();

// 2. Pastikan hanya role 'Inventory Staff' yang bisa akses
FUNGSI_CEK_ROLE(['Inventory Staff']);

// Ambil data user untuk ditampilkan di view
$user = $_SESSION['user'];

// === PERUBAHAN UTAMA ===
// 3. Ambil data ringkasan dari service
$summary = FUNGSI_GET_SUMMARY_STOK();
$total_produk = $summary['total_produk'];
$stok_menipis = $summary['stok_menipis'];
$stok_habis = $summary['stok_habis'];
// =======================

// Siapkan variabel untuk layout
$title = 'Dashboard Inventory';

// Panggil bagian-bagian template
require 'views/layouts/header_internal.php';
require 'views/inventory/dashboard.php'; // Ini adalah konten
require 'views/layouts/footer.php';
?>