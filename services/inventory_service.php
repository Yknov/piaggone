<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Mengambil semua data produk dari database untuk ditampilkan.
 * @return array Daftar semua produk.
 */
function FUNGSI_GET_ALL_PRODUK() {
    try {
        $pdo = get_db_connection();
        // Ambil semua kolom yang relevan dari tabel Produk
        $sql = "SELECT produkID, namaProduk, harga, jumlahStok, batasMinimumStok 
                FROM Produk 
                ORDER BY namaProduk ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_ALL_PRODUK: ' . $e->getMessage());
        return []; // Kembalikan array kosong jika gagal
    }
}

/**
 * Mengambil data ringkasan stok untuk dashboard.
 * @return array [total_produk, stok_menipis, stok_habis]
 */
function FUNGSI_GET_SUMMARY_STOK() {
    try {
        $pdo = get_db_connection();
        // Query ini menghitung 3 angka sekaligus
        $sql = "SELECT 
                    COUNT(produkID) AS total_produk,
                    SUM(CASE WHEN jumlahStok > 0 AND jumlahStok <= batasMinimumStok THEN 1 ELSE 0 END) AS stok_menipis,
                    SUM(CASE WHEN jumlahStok = 0 THEN 1 ELSE 0 END) AS stok_habis
                FROM Produk";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(); // Mengembalikan 1 baris berisi 3 angka
    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_SUMMARY_STOK: ' . $e->getMessage());
        // Kembalikan nilai default jika error
        return ['total_produk' => 0, 'stok_menipis' => 0, 'stok_habis' => 0];
    }
}
?>