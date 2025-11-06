<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Memverifikasi login pegawai.
 * @param string $email
 * @param string $password
 * @return array|null Data user jika valid, null jika tidak.
 */
function FUNGSI_VERIFIKASI_LOGIN($email, $password) {
    try {
        $pdo = get_db_connection();
        
        // 1. Cari pengguna berdasarkan email
        $sql = "SELECT userID, nama, email, password_hash FROM Pengguna WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $pengguna = $stmt->fetch();

        if (!$pengguna) {
            return null; // Email tidak ditemukan
        }

        // 2. Verifikasi password
        if (password_verify($password, $pengguna['password_hash'])) {
            
            // 3. Jika password benar, cari perannya di tabel Pegawai
            $sqlRole = "SELECT role FROM Pegawai WHERE userID = ?";
            $stmtRole = $pdo->prepare($sqlRole);
            $stmtRole->execute([$pengguna['userID']]);
            $pegawai = $stmtRole->fetch();

            if ($pegawai) {
                // Sukses! User adalah pegawai. Gabungkan data.
                $user_data = [
                    'userID' => $pengguna['userID'],
                    'nama' => $pengguna['nama'],
                    'email' => $pengguna['email'],
                    'role' => $pegawai['role']
                ];
                return $user_data;
            } else {
                // User ada, tapi bukan pegawai (mungkin pelanggan)
                return null;
            }
        }

        return null; // Password salah
    } catch (PDOException $e) {
        error_log('Login Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Mendaftarkan pegawai baru.
 * @return array Hasil operasi [success => bool, message => string]
 */
function FUNGSI_REGISTER_PEGAWAI($nama, $nomorHP, $email, $password, $role) {
    $pdo = get_db_connection();
    
    try {
        $pdo->beginTransaction();

        // 1. Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 2. Insert ke tabel 'Pengguna'
        $sqlUser = "INSERT INTO Pengguna (nama, nomorHP, email, password_hash) VALUES (?, ?, ?, ?)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$nama, $nomorHP, $email, $hashedPassword]);

        $newUserId = $pdo->lastInsertId();

        // 3. Insert ke tabel 'Pegawai'
        $sqlPegawai = "INSERT INTO Pegawai (userID, role) VALUES (?, ?)";
        $stmtPegawai = $pdo->prepare($sqlPegawai);
        $stmtPegawai->execute([$newUserId, $role]);

        // 4. Commit transaksi
        $pdo->commit();
        return ['success' => true, 'message' => 'Registrasi berhasil'];

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // Cek error duplikat (error code 23000, 1062)
        if ($e->getCode() == 23000 && strpos($e->getMessage(), '1062') !== false) {
             if (strpos($e->getMessage(), 'email') !== false) {
                return ['success' => false, 'message' => 'Email sudah digunakan'];
             }
             if (strpos($e->getMessage(), 'nomorHP') !== false) {
                return ['success' => false, 'message' => 'Nomor HP sudah digunakan'];
             }
        }
        
        error_log('Register Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Registrasi gagal. Terjadi kesalahan server.'];
    }
}
?>