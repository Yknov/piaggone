<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan: Hanya role tertentu yang boleh menambah produk
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Inventory Staff', 'Store Manager', 'Owner']);

$user = $_SESSION['user'];
$error = null;
$success = null;

// 2. Logika untuk memproses formulir saat disubmit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 3. Ambil dan validasi data
    $namaProduk = trim($_POST['namaProduk'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $jumlahStok = (int)($_POST['jumlahStok'] ?? 0);
    $batasMinimumStok = (int)($_POST['batasMinimumStok'] ?? 0);

    if (empty($namaProduk) || $harga <= 0) {
        $error = "Nama Produk dan Harga (harus > 0) wajib diisi.";
    } else if ($jumlahStok < 0 || $batasMinimumStok < 0) {
        $error = "Stok dan Batas Stok tidak boleh negatif.";
    } else {
        // 4. Panggil service untuk menyimpan data
        $result = FUNGSI_CREATE_PRODUK($namaProduk, $harga, $jumlahStok, $batasMinimumStok);

        if ($result['success']) {
            $success = $result['message'] . ' <a href="inventory_stok.php">Lihat daftar stok.</a>';
        } else {
            $error = $result['message'];
        }
    }
}

// 5. Logika untuk menampilkan halaman (GET)
$title = 'Tambah Produk Baru';
require 'views/layouts/header_internal.php';
require 'views/inventory/produk_create.php'; // Panggil view baru
require 'views/layouts/footer.php';
?>