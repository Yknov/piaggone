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
        
        // 1. Cari pengguna berdasarkan email (gunakan fetch associative)
        $sql = "SELECT userID, nama, email, password_hash FROM Pengguna WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $pengguna = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pengguna) {
            return null; // Email tidak ditemukan
        }

        // 2. Verifikasi password
        if (password_verify($password, $pengguna['password_hash'])) {
            
            // 3. Jika password benar, cari perannya di tabel Pegawai
            $sqlRole = "SELECT role FROM Pegawai WHERE userID = ?";
            $stmtRole = $pdo->prepare($sqlRole);
            $stmtRole->execute([$pengguna['userID']]);
            $pegawai = $stmtRole->fetch(PDO::FETCH_ASSOC);

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
    
    // Trim input dasar
    $nama = trim((string)$nama);
    $email = trim((string)$email);
    $nomorHP = $nomorHP === null ? null : trim((string)$nomorHP);
    $role = trim((string)$role);
    $password = (string)$password;

    // Validasi dasar
    if ($nama === '' || $email === '' || $password === '' || $role === '') {
        return ['success' => false, 'message' => 'Nama, Email, Password, dan Role wajib diisi.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email tidak valid.'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
    }

    try {
        // Cek duplikat terlebih dahulu (email / nomorHP) untuk memberikan pesan yang jelas
        $sqlCheck = "SELECT email, nomorHP FROM Pengguna WHERE email = ? OR (nomorHP IS NOT NULL AND nomorHP = ?)";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$email, $nomorHP]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if (isset($existing['email']) && $existing['email'] === $email) {
                return ['success' => false, 'message' => 'Email sudah digunakan'];
            }
            if (!empty($nomorHP) && isset($existing['nomorHP']) && $existing['nomorHP'] === $nomorHP) {
                return ['success' => false, 'message' => 'Nomor HP sudah digunakan'];
            }
        }

        $pdo->beginTransaction();

        // 1. Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 2. Insert ke tabel 'Pengguna'
        // jika nomorHP kosong, insert NULL
        $sqlUser = "INSERT INTO Pengguna (nama, nomorHP, email, password_hash) VALUES (?, ?, ?, ?)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$nama, $nomorHP === '' ? null : $nomorHP, $email, $hashedPassword]);

        $newUserId = $pdo->lastInsertId();

        // 3. Insert ke tabel 'Pegawai'
        $sqlPegawai = "INSERT INTO Pegawai (userID, role) VALUES (?, ?)";
        $stmtPegawai = $pdo->prepare($sqlPegawai);
        $stmtPegawai->execute([$newUserId, $role]);

        // 4. Commit transaksi
        $pdo->commit();
        return ['success' => true, 'message' => 'Registrasi berhasil'];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Jika masih terjadi duplikat karena race-condition, tangani
        $errorMsg = $e->getMessage();
        if ($e->getCode() == 23000) {
            if (stripos($errorMsg, 'email') !== false) {
                return ['success' => false, 'message' => 'Email sudah digunakan'];
            }
            if (stripos($errorMsg, 'nomorHP') !== false || stripos($errorMsg, 'nomor') !== false) {
                return ['success' => false, 'message' => 'Nomor HP sudah digunakan'];
            }
            return ['success' => false, 'message' => 'Data duplikat ditemukan'];
        }
        
        error_log('Register Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Registrasi gagal. Terjadi kesalahan server.'];
    }
}
?>