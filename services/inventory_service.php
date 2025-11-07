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
 * Membuat permintaan pengadaan baru.
 * Menggunakan transaksi untuk insert ke 2 tabel.
 *
 * @param int $staff_user_id ID dari pegawai yang membuat permintaan.
 * @param array $items Array berisi item yang diminta, cth: [ ['produkID' => 1, 'jumlah' => 10], ... ]
 * @return array Hasil operasi [success => bool, message => string]
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

        // 2. Dapatkan ID dari request yang baru saja kita buat
        $requestID = $pdo->lastInsertId();

        // 3. Siapkan query untuk insert ke tabel 'DetailPermintaan'
        $sqlDetail = "INSERT INTO DetailPermintaan (requestID, produkID, jumlahDiminta) 
                      VALUES (?, ?, ?)";
        $stmtDetail = $pdo->prepare($sqlDetail);

        // 4. Loop dan insert setiap item ke tabel detail
        foreach ($items as $item) {
            $stmtDetail->execute([
                $requestID,
                $item['produkID'],
                $item['jumlah']
            ]);
        }

        // 5. Jika semua berhasil, commit transaksi
        $pdo->commit();
        return ['success' => true, 'message' => 'Permintaan pengadaan berhasil dibuat.'];

    } catch (PDOException $e) {
        // 6. Jika ada error, batalkan semua perubahan
        $pdo->rollBack();
        error_log('Error FUNGSI_CREATE_PENGADAAN: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan database saat membuat permintaan.'];
    }
}

/**
 * Menambahkan produk baru ke database.
 *
 * @param string $nama Nama produk
 * @param float $harga Harga produk
 * @param int $stok Jumlah stok awal
 * @param int $batas_stok Batas minimum stok
 * @return array Hasil operasi [success => bool, message => string]
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
        // Cek jika error-nya adalah duplikat entry
        if ($e->getCode() == 23000) {
            return ['success' => false, 'message' => 'Gagal: Nama produk mungkin sudah ada.'];
        }
        return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
    }
}

/**
 * Mengambil riwayat permintaan pengadaan berdasarkan ID staff.
 *
 * @param int $staff_user_id ID dari pegawai (staff) yang membuat permintaan.
 * @return array Daftar riwayat permintaan.
 */
function FUNGSI_GET_REQUEST_HISTORY_BY_STAFF($staff_user_id) {
    $pdo = get_db_connection();
    
    try {
        /* * Query ini mengambil:
         * 1. Data permintaan (ID, tanggal, status) dari tabel PermintaanPengadaan.
         * 2. Nama manager (jika ada) dari tabel Pengguna, dengan melakukan JOIN melalui tabel Pegawai.
         */
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
 *
 * @param int $requestID ID permintaan yang akan dicari.
 * @param int $staff_user_id ID staff yang login (untuk verifikasi).
 * @return array|false Data master permintaan, atau false jika tidak ditemukan/tidak diizinkan.
 */
function FUNGSI_GET_REQUEST_MASTER($requestID, $staff_user_id) {
    $pdo = get_db_connection();
    
    try {
        /* * Query ini mengambil data permintaan DAN nama staff pembuat DAN nama manager peninjau.
         * Kita juga memvalidasi bahwa requestID ini memang milik staff_user_id yang login.
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
                    pp.requestID = ? AND pp.userID_staff = ?";
                    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requestID, $staff_user_id]);
        
        return $stmt->fetch(); // Mengembalikan satu baris data master

    } catch (PDOException $e) {
        error_log('Error FUNGSI_GET_REQUEST_MASTER: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mengambil item-item detail dari satu permintaan pengadaan.
 *
 * @param int $requestID ID permintaan yang akan dicari.
 * @return array Daftar item produk.
 */
function FUNGSI_GET_REQUEST_DETAILS_ITEMS($requestID) {
    $pdo = get_db_connection();
    
    try {
        /* * Query ini mengambil semua item dari DetailPermintaan
         * dan menggabungkannya dengan tabel Produk untuk mendapatkan nama produk.
         */
        $sql = "SELECT 
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
 * (Digunakan oleh manager untuk melihat permintaan 'Menunggu')
 *
 * @param string $status Status yang dicari ('Menunggu', 'Disetujui', 'Ditolak').
 * @return array Daftar permintaan.
 */
function FUNGSI_GET_ALL_REQUESTS_BY_STATUS($status) {
    $pdo = get_db_connection();
    
    try {
        /* * Query ini mengambil data permintaan DAN nama staff pembuat.
         */
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
                    pp.tanggalPermintaan ASC"; // Tampilkan yang paling lama di atas
                    
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
 *
 * @param int $requestID ID permintaan yang akan diubah.
 * @param int $manager_user_id ID dari manager yang melakukan aksi.
 * @param string $newStatus Status baru ('Disetujui' atau 'Ditolak').
 * @return array Hasil operasi [success => bool, message => string]
 */
function FUNGSI_UPDATE_REQUEST_STATUS($requestID, $manager_user_id, $newStatus) {
    $pdo = get_db_connection();
    
    // Pastikan status yang diinput valid
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
        
        // Cek apakah ada baris yang benar-benar ter-update
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
?>