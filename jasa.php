<?php
// includes/functions_jasa.php

// ==================== MASTER JASA ====================

function getAllJasa($conn) {
    $sql = "SELECT * FROM jasa ORDER BY nama_jasa ASC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getJasaById($conn, $id_jasa) {
    $sql = "SELECT * FROM jasa WHERE id_jasa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_jasa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getJasaAktif($conn) {
    $sql = "SELECT * FROM jasa WHERE status = 'aktif' ORDER BY nama_jasa ASC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function tambahJasa($conn, $nama_jasa, $deskripsi, $harga, $estimasi_waktu) {
    $sql = "INSERT INTO jasa (nama_jasa, deskripsi, harga, estimasi_waktu) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssdi", $nama_jasa, $deskripsi, $harga, $estimasi_waktu);
    return mysqli_stmt_execute($stmt);
}

function updateJasa($conn, $id_jasa, $nama_jasa, $deskripsi, $harga, $estimasi_waktu, $status) {
    $sql = "UPDATE jasa SET nama_jasa = ?, deskripsi = ?, harga = ?, estimasi_waktu = ?, status = ? WHERE id_jasa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssdisi", $nama_jasa, $deskripsi, $harga, $estimasi_waktu, $status, $id_jasa);
    return mysqli_stmt_execute($stmt);
}

function deleteJasa($conn, $id_jasa) {
    $sql = "DELETE FROM jasa WHERE id_jasa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_jasa);
    return mysqli_stmt_execute($stmt);
}

// ==================== TRANSAKSI JASA ====================

function requestJasa($conn, $id_user, $id_jasa, $id_kendaraan, $catatan_customer) {
    // Get harga jasa
    $jasa = getJasaById($conn, $id_jasa);
    $total_biaya = $jasa['harga'];
    
    $sql = "INSERT INTO transaksi_jasa (id_user, id_jasa, id_kendaraan, catatan_customer, total_biaya) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiisd", $id_user, $id_jasa, $id_kendaraan, $catatan_customer, $total_biaya);
    return mysqli_stmt_execute($stmt);
}

function getTransaksiJasaPending($conn) {
    $sql = "SELECT tj.*, j.nama_jasa, j.estimasi_waktu, u.nama as nama_customer, u.no_telp, u.alamat,
            k.merk_kendaraan, k.tipe_kendaraan, k.nomor_plat
            FROM transaksi_jasa tj
            JOIN jasa j ON tj.id_jasa = j.id_jasa
            JOIN users u ON tj.id_user = u.id_user
            LEFT JOIN kendaraan k ON tj.id_kendaraan = k.id_kendaraan
            WHERE tj.status_jasa = 'pending'
            ORDER BY tj.tanggal_request ASC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getTransaksiJasaByMontir($conn, $id_montir, $status = null) {
    if ($status) {
        $sql = "SELECT tj.*, j.nama_jasa, j.estimasi_waktu, u.nama as nama_customer, u.no_telp,
                k.merk_kendaraan, k.tipe_kendaraan, k.nomor_plat
                FROM transaksi_jasa tj
                JOIN jasa j ON tj.id_jasa = j.id_jasa
                JOIN users u ON tj.id_user = u.id_user
                LEFT JOIN kendaraan k ON tj.id_kendaraan = k.id_kendaraan
                WHERE tj.id_montir = ? AND tj.status_jasa = ?
                ORDER BY tj.tanggal_request DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $id_montir, $status);
    } else {
        $sql = "SELECT tj.*, j.nama_jasa, j.estimasi_waktu, u.nama as nama_customer, u.no_telp,
                k.merk_kendaraan, k.tipe_kendaraan, k.nomor_plat
                FROM transaksi_jasa tj
                JOIN jasa j ON tj.id_jasa = j.id_jasa
                JOIN users u ON tj.id_user = u.id_user
                LEFT JOIN kendaraan k ON tj.id_kendaraan = k.id_kendaraan
                WHERE tj.id_montir = ?
                ORDER BY tj.tanggal_request DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_montir);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getTransaksiJasaByCustomer($conn, $id_user, $status = null) {
    if ($status) {
        $sql = "SELECT tj.*, j.nama_jasa, j.estimasi_waktu, m.nama as nama_montir,
                k.merk_kendaraan, k.tipe_kendaraan, k.nomor_plat
                FROM transaksi_jasa tj
                JOIN jasa j ON tj.id_jasa = j.id_jasa
                LEFT JOIN users m ON tj.id_montir = m.id_user
                LEFT JOIN kendaraan k ON tj.id_kendaraan = k.id_kendaraan
                WHERE tj.id_user = ? AND tj.status_jasa = ?
                ORDER BY tj.tanggal_request DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $id_user, $status);
    } else {
        $sql = "SELECT tj.*, j.nama_jasa, j.estimasi_waktu, m.nama as nama_montir,
                k.merk_kendaraan, k.tipe_kendaraan, k.nomor_plat
                FROM transaksi_jasa tj
                JOIN jasa j ON tj.id_jasa = j.id_jasa
                LEFT JOIN users m ON tj.id_montir = m.id_user
                LEFT JOIN kendaraan k ON tj.id_kendaraan = k.id_kendaraan
                WHERE tj.id_user = ?
                ORDER BY tj.tanggal_request DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_user);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getAllTransaksiJasa($conn) {
    $sql = "SELECT tj.*, j.nama_jasa, u.nama as nama_customer, m.nama as nama_montir,
            k.merk_kendaraan, k.tipe_kendaraan, k.nomor_plat
            FROM transaksi_jasa tj
            JOIN jasa j ON tj.id_jasa = j.id_jasa
            JOIN users u ON tj.id_user = u.id_user
            LEFT JOIN users m ON tj.id_montir = m.id_user
            LEFT JOIN kendaraan k ON tj.id_kendaraan = k.id_kendaraan
            ORDER BY tj.tanggal_request DESC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function ambilJasa($conn, $id_transaksi_jasa, $id_montir) {
    $sql = "UPDATE transaksi_jasa 
            SET id_montir = ?, status_jasa = 'dikerjakan', tanggal_mulai = NOW() 
            WHERE id_transaksi_jasa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id_montir, $id_transaksi_jasa);
    return mysqli_stmt_execute($stmt);
}

function selesaikanJasa($conn, $id_transaksi_jasa, $catatan_montir) {
    $sql = "UPDATE transaksi_jasa 
            SET status_jasa = 'selesai', tanggal_selesai = NOW(), catatan_montir = ?
            WHERE id_transaksi_jasa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $catatan_montir, $id_transaksi_jasa);
    return mysqli_stmt_execute($stmt);
}

function batalkanJasa($conn, $id_transaksi_jasa, $catatan) {
    $sql = "UPDATE transaksi_jasa 
            SET status_jasa = 'dibatalkan', catatan_montir = ?
            WHERE id_transaksi_jasa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $catatan, $id_transaksi_jasa);
    return mysqli_stmt_execute($stmt);
}

function berikanRating($conn, $id_transaksi_jasa, $rating, $review) {
    $sql = "UPDATE transaksi_jasa 
            SET rating = ?, review = ?
            WHERE id_transaksi_jasa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isi", $rating, $review, $id_transaksi_jasa);
    return mysqli_stmt_execute($stmt);
}

function getKendaraanByUser($conn, $id_user) {
    $sql = "SELECT * FROM kendaraan WHERE id_user = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'dikerjakan' => '<span class="badge bg-info">Dikerjakan</span>',
        'selesai' => '<span class="badge bg-success">Selesai</span>',
        'dibatalkan' => '<span class="badge bg-danger">Dibatalkan</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}
?>
