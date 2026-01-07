<?php
/**
 * FILE: config/database.php
 * FUNGSI: Koneksi database menggunakan PDO (PHP Data Objects)
 * KEAMANAN: Menggunakan PDO untuk prepared statement
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'gobandlive');
define('DB_USER', 'root');
define('DB_PASS', '');

// Fungsi untuk membuat koneksi database
function getDBConnection() {
    try {
        // Buat koneksi PDO
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        // Jika gagal koneksi, tampilkan error
        die("Koneksi Database Gagal: " . $e->getMessage());
    }
}

// Inisialisasi koneksi global
$db = getDBConnection();

/**
 * CATATAN PENGGUNAAN:
 * 
 * 1. Include file ini di setiap file yang membutuhkan database:
 *    require_once '../config/database.php';
 * 
 * 2. Gunakan prepared statement untuk keamanan:
 *    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
 *    $stmt->execute([$email]);
 * 
 * 3. Jangan pernah langsung concat variable ke query (SQL Injection!)
 */
?>