<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php'; // Panggil service yang baru kita buat

// 1. Pastikan user login
FUNGSI_CEK_OTENTIKASI();

// 2. Pastikan hanya role 'Inventory Staff' (atau manajer/owner) yang bisa akses
// Kita tambahkan Store Manager dan Owner karena mereka mungkin perlu melihat stok juga
FUNGSI_CEK_ROLE(['Inventory Staff', 'Store Manager', 'Owner']);

// Ambil data user dari session
$user = $_SESSION['user'];

// Ambil semua data produk dari service
$daftar_produk = FUNGSI_GET_ALL_PRODUK();

// Siapkan variabel untuk layout
$title = 'Daftar Stok Produk';

// Panggil bagian-bagian template
require 'views/layouts/header_internal.php';

// --- WRAPPER MAIN CONTENT supaya tidak tertutup oleh navbar/sidebar ---
echo '<main class="main-content">';

require 'views/inventory/stok.php'; // Panggil view baru yang akan kita buat

echo '</main>';

require 'views/layouts/footer.php';
?>