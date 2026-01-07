<?php
/**
 * FILE: config/config.php
 * FUNGSI: Konfigurasi umum aplikasi
 */

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi URL Base
define('BASE_URL', 'http://localhost/gobandlive/');

// Konfigurasi Upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/bukti_bayar/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Konfigurasi Aplikasi
define('APP_NAME', 'GOBANDLIVE');
define('APP_VERSION', '1.0.0');

// Role User
define('ROLE_ADMIN', 'admin');
define('ROLE_PELANGGAN', 'pelanggan');
define('ROLE_BAND', 'band');

// Status Pesanan
define('STATUS_MENUNGGU', 'menunggu');
define('STATUS_DITERIMA', 'diterima');
define('STATUS_DIBATALKAN', 'dibatalkan');
define('STATUS_SELESAI', 'selesai');

// Status Pembayaran
define('STATUS_BAYAR_BELUM', 'belum');
define('STATUS_BAYAR_MENUNGGU', 'menunggu');
define('STATUS_BAYAR_VERIFIED', 'verified');

// Timezone
date_default_timezone_set('Asia/Jakarta');

/**
 * Fungsi untuk redirect
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

/**
 * Fungsi untuk cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Fungsi untuk cek role user
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Fungsi untuk proteksi halaman
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

/**
 * Fungsi untuk proteksi berdasarkan role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect('index.php');
    }
}

/**
 * Fungsi untuk sanitasi input
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Fungsi untuk format rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Fungsi untuk format tanggal Indonesia
 */
function formatTanggal($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

/**
 * Fungsi untuk membuat alert
 */
function setAlert($type, $message) {
    $_SESSION['alert_type'] = $type; // success, error, warning, info
    $_SESSION['alert_message'] = $message;
}

/**
 * Fungsi untuk menampilkan alert
 */
function showAlert() {
    if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
        $type = $_SESSION['alert_type'];
        $message = $_SESSION['alert_message'];
        
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        
        unset($_SESSION['alert_type']);
        unset($_SESSION['alert_message']);
    }
}
?>