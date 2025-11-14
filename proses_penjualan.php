<?php

session_start();
require_once '../config/database.php';
require_once '../services/PenjualanService.php';
require_once '../middleware/auth.php';

// Set header JSON
header('Content-Type: application/json');

// Cek autentikasi
checkAuth();
checkRole(['kasir']);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validasi input
if (!$input || !isset($input['items']) || empty($input['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data transaksi tidak valid'
    ]);
    exit;
}

// Validasi metode bayar
if (!isset($input['metode_bayar']) || !in_array($input['metode_bayar'], ['Tunai', 'QRIS', 'Transfer'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Metode pembayaran tidak valid'
    ]);
    exit;
}

// Validasi uang diterima untuk metode tunai
if ($input['metode_bayar'] === 'Tunai') {
    if (!isset($input['uang_diterima']) || $input['uang_diterima'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Nominal uang diterima tidak valid'
        ]);
        exit;
    }
}

try {
    // Proses transaksi
    $penjualanService = new PenjualanService($conn);
    $result = $penjualanService->simpanTransaksi($input);
    
    // Log transaksi
    if ($result['success']) {
        $logMessage = sprintf(
            "Transaksi berhasil - Kode: %s, Kasir ID: %s, Total: Rp %s",
            $result['kode_transaksi'],
            $input['id_kasir'],
            number_format($input['items'][0]['harga'] * $input['items'][0]['jumlah'], 0, ',', '.')
        );
        error_log($logMessage);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
    error_log('Error proses penjualan: ' . $e->getMessage());
}
