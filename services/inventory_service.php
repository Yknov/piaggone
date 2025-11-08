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

/**
 * Menambahkan produk baru ke database.
 */
function FUNGSI_CREATE_PRODUK($nama, $harga, $stok, $batas_stok) {
    $pdo = get_db_connection();
    
    try {
        $sql = "INSERT INTO Produk (namaProduk, harga, jumlahStok, batasMinimumStok) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $harga, $stok, $batas_stok]);

        return ['success' => true, 'message' => 'Produk baru berhasil ditambahkan.'];

    } catch (PDOException $e) {
        error_log('Error FUNGSI_CREATE_PRODUK: ' . $e->getMessage());
        if ($e->getCode() == 23000) {
            return ['success' => false, 'message' => 'Gagal: Nama produk mungkin sudah ada.'];
        }
        return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
    }
}

/**
 * Membuat permintaan pengadaan baru.
 * Menggunakan transaksi untuk insert ke 2 tabel.
 */
function FUNGSI_CREATE_PENGADAAN($staff_user_id, $items) {
    $pdo = get_db_connection();
    
    try {
        $pdo->beginTransaction();

        // 1. Insert ke tabel master 'PermintaanPengadaan'
        $sqlMaster = "INSERT INTO PermintaanPengadaan (userID_staff, tanggalPermintaan, status) 
                      VALUES (?, NOW(), 'Menunggu')";
        $stmtMaster = $pdo->prepare($sqlMaster);
        $stmtMaster->execute([$staff_user_id]);

        $requestID = $pdo->lastInsertId();

        // 2. Siapkan query untuk insert ke tabel 'DetailPermintaan'
        $sqlDetail = "INSERT INTO DetailPermintaan (requestID, produkID, jumlahDiminta) 
                      VALUES (?, ?, ?)";
        $stmtDetail = $pdo->prepare($sqlDetail);

        // 3. Loop dan insert setiap item ke tabel detail
        foreach ($items as $item) {
            $stmtDetail->execute([
                $requestID,
                $item['produkID'],
                $item['jumlah']
            ]);
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Permintaan pengadaan berhasil dibuat.'];

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Error FUNGSI_CREATE_PENGADAAN: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan database saat membuat permintaan.'];
    }
}

/**
 * Mengambil riwayat permintaan pengadaan berdasarkan ID staff.
 */
function FUNGSI_GET_REQUEST_HISTORY_BY_STAFF($staff_user_id) {
    $pdo = get_db_connection();
    
    try {
        $sql = "SELECT 
                    pp.requestID, 
                    pp.tanggalPermintaan, 
                    pp.status,
                    p_manager.nama AS namaManager
                FROM 
                    PermintaanPengadaan AS pp
                LEFT JOIN 
                    Pegawai AS pg_manager ON pp.userID_manager = pg_manager.userID
                LEFT JOIN
                    Pengguna AS p_manager ON pg_manager.userID = p_manager.userID
                WHERE 
                    pp.userID_staff = ?
                ORDER BY 
                    pp.tanggalPermintaan DESC";
                    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$staff_user_id]);
        
        return $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_REQUEST_HISTORY_BY_STAFF: ' . $e->getMessage());
        return [];
    }
}

/**
 * Mengambil data master dari satu permintaan pengadaan.
 */
function FUNGSI_GET_REQUEST_MASTER($requestID, $staff_user_id) {
    $pdo = get_db_connection();
    
    try {
        $sql = "SELECT 
                    pp.requestID, 
                    pp.tanggalPermintaan, 
                    pp.status,
                    p_staff.nama AS namaStaff,
                    p_manager.nama AS namaManager
                FROM 
                    PermintaanPengadaan AS pp
                JOIN 
                    Pegawai AS pg_staff ON pp.userID_staff = pg_staff.userID
                JOIN
                    Pengguna AS p_staff ON pg_staff.userID = p_staff.userID
                LEFT JOIN 
                    Pegawai AS pg_manager ON pp.userID_manager = pg_manager.userID
                LEFT JOIN
                    Pengguna AS p_manager ON pg_manager.userID = p_manager.userID
                WHERE 
                    pp.requestID = ? AND pp.userID_staff = ?";
                    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requestID, $staff_user_id]);
        
        return $stmt->fetch();

    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_REQUEST_MASTER: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mengambil item-item detail dari satu permintaan pengadaan.
 * (Ini adalah versi yang Anda perbarui, sudah benar)
 */
function FUNGSI_GET_REQUEST_DETAILS_ITEMS($requestID) {
    $pdo = get_db_connection();
    try {
        $sql = "SELECT 
                    dp.produkID, 
                    dp.jumlahDiminta,
                    p.namaProduk,
                    p.harga
                FROM 
                    DetailPermintaan AS dp
                JOIN 
                    Produk AS p ON dp.produkID = p.produkID
                WHERE 
                    dp.requestID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requestID]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_REQUEST_DETAILS_ITEMS: ' . $e->getMessage());
        return [];
    }
}

/**
 * Mengambil semua permintaan pengadaan berdasarkan status.
 * (Digunakan oleh manager)
 */
function FUNGSI_GET_ALL_REQUESTS_BY_STATUS($status) {
    $pdo = get_db_connection();
    
    try {
        $sql = "SELECT 
                    pp.requestID, 
                    pp.tanggalPermintaan, 
                    pp.status,
                    p_staff.nama AS namaStaff
                FROM 
                    PermintaanPengadaan AS pp
                JOIN 
                    Pegawai AS pg_staff ON pp.userID_staff = pg_staff.userID
                JOIN
                    Pengguna AS p_staff ON pg_staff.userID = p_staff.userID
                WHERE 
                    pp.status = ?
                ORDER BY 
                    pp.tanggalPermintaan ASC";
                    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
        
        return $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_ALL_REQUESTS_BY_STATUS: ' . $e->getMessage());
        return [];
    }
}

/**
 * Mengubah status permintaan pengadaan (Approve/Reject).
 * (Digunakan oleh manager)
 */
function FUNGSI_UPDATE_REQUEST_STATUS($requestID, $manager_user_id, $newStatus) {
    $pdo = get_db_connection();
    
    if ($newStatus !== 'Disetujui' && $newStatus !== 'Ditolak') {
        return ['success' => false, 'message' => 'Status tidak valid.'];
    }

    try {
        $sql = "UPDATE PermintaanPengadaan 
                SET 
                    status = ?, 
                    userID_manager = ? 
                WHERE 
                    requestID = ? AND status = 'Menunggu'";
                    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newStatus, $manager_user_id, $requestID]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => "Permintaan #$requestID berhasil di-$newStatus."];
        } else {
            return ['success' => false, 'message' => "Gagal memperbarui: Permintaan mungkin sudah diproses atau tidak ditemukan."];
        }

    } catch (PDOException $e) {
        error_log('Error FUNGSI_UPDATE_REQUEST_STATUS: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
    }
}

/**
 * Memproses penerimaan barang dari permintaan yang sudah disetujui.
 * (Ini fungsi yang Anda sertakan, sudah benar)
 */
function FUNGSI_RECEIVE_STOCK($requestID, $staff_user_id) {
    $pdo = get_db_connection();
    
    try {
        $pdo->beginTransaction();

        $items = FUNGSI_GET_REQUEST_DETAILS_ITEMS($requestID);
        
        if (empty($items)) {
            throw new Exception("Tidak ada item detail ditemukan untuk permintaan ini.");
        }

        $sqlUpdateStok = "UPDATE Produk SET jumlahStok = jumlahStok + ? WHERE produkID = ?";
        $stmtUpdateStok = $pdo->prepare($sqlUpdateStok);

        foreach ($items as $item) {
            $stmtUpdateStok->execute([
                $item['jumlahDiminta'],
                $item['produkID']
            ]);
        }

        $sqlMaster = "UPDATE PermintaanPengadaan SET status = 'Selesai' 
                      WHERE requestID = ? AND status = 'Disetujui'";
        $stmtMaster = $pdo->prepare($sqlMaster);
        $stmtMaster->execute([$requestID]);

        if ($stmtMaster->rowCount() === 0) {
            throw new Exception("Gagal update status permintaan. Mungkin sudah diproses atau statusnya bukan 'Disetujui'.");
        }

        $pdo->commit();
        return ['success' => true, 'message' => "Stok untuk Permintaan #$requestID berhasil diperbarui."];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Error FUNGSI_RECEIVE_STOCK: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Mengambil data master dari satu permintaan (versi Admin/Manager).
 *
 * @param int $requestID ID permintaan yang akan dicari.
 * @return array|false Data master permintaan, atau false jika tidak ditemukan.
 */
function FUNGSI_GET_REQUEST_MASTER_ADMIN($requestID) {
    $pdo = get_db_connection();
    
    try {
        /* * Query ini sama dengan FUNGSI_GET_REQUEST_MASTER,
         * TAPI tanpa klausa "AND pp.userID_staff = ?"
         */
        $sql = "SELECT 
                    pp.requestID, 
                    pp.tanggalPermintaan, 
                    pp.status,
                    p_staff.nama AS namaStaff,
                    p_manager.nama AS namaManager
                FROM 
                    PermintaanPengadaan AS pp
                JOIN 
                    Pegawai AS pg_staff ON pp.userID_staff = pg_staff.userID
                JOIN
                    Pengguna AS p_staff ON pg_staff.userID = p_staff.userID
                LEFT JOIN 
                    Pegawai AS pg_manager ON pp.userID_manager = pg_manager.userID
                LEFT JOIN
                    Pengguna AS p_manager ON pg_manager.userID = p_manager.userID
                WHERE 
                    pp.requestID = ?";
                    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requestID]);
        
        return $stmt->fetch();

    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_REQUEST_MASTER_ADMIN: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mengambil SEMUA riwayat permintaan pengadaan untuk admin/manager.
 *
 * @return array Daftar semua permintaan.
 */
function FUNGSI_GET_ALL_REQUESTS_HISTORY() {
    $pdo = get_db_connection();
    
    try {
        $sql = "SELECT 
                    pp.requestID, 
                    pp.tanggalPermintaan, 
                    pp.status,
                    p_staff.nama AS namaStaff,
                    p_manager.nama AS namaManager
                FROM 
                    PermintaanPengadaan AS pp
                JOIN 
                    Pegawai AS pg_staff ON pp.userID_staff = pg_staff.userID
                JOIN
                    Pengguna AS p_staff ON pg_staff.userID = p_staff.userID
                LEFT JOIN 
                    Pegawai AS pg_manager ON pp.userID_manager = pg_manager.userID
                LEFT JOIN
                    Pengguna AS p_manager ON pg_manager.userID = p_manager.userID
                ORDER BY 
                    pp.requestID DESC"; // Tampilkan yang terbaru di atas
                    
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_ALL_REQUESTS_HISTORY: ' . $e->getMessage());
        return [];
    }
}
?>