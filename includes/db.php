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
// Cara ngetesnya:
// $db = getDB();
// echo "Koneksi Berhasil!";
?>