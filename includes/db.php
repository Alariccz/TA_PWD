<?php
// db.php - koneksi ke database
// pakai PDO supaya lebih aman dari SQL Injection

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'alaric123');
define('DB_NAME', 'ecoenzy');

// fungsi buat dapetin koneksi
// pakai static supaya koneksinya ga bikin baru terus tiap dipanggil
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }

    return $pdo;
}
