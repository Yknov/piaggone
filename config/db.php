<?php
// Pengaturan Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '12345678'); // Sesuaikan dengan password database Anda
define('DB_NAME', 'piaggone');

/**
 * Membuat koneksi ke database menggunakan PDO.
 * @return PDO Objek koneksi PDO.
 */
function get_db_connection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // Di aplikasi nyata, jangan tampilkan error detail ke user
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>