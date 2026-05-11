<?php
// db.php - koneksi ke database

// 1. Pastikan konfigurasi ini sesuai dengan XAMPP/Environment kamu
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika pakai XAMPP default ('')
define('DB_NAME', 'ecoenzy');   // Pastikan database ini sudah dibuat di phpMyAdmin

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Kita gunakan error yang lebih spesifik untuk debugging
            error_log($e->getMessage());
            die("Wah, koneksi ke database gagal nih. Cek lagi ya! <br> Pesan Error: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Cara ngetesnya:
// $db = getDB();
// echo "Koneksi Berhasil!";
?>