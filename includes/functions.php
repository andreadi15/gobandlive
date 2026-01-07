<?php
/**
 * FILE: includes/functions.php
 * FUNGSI: Helper functions untuk berbagai keperluan
 */

/**
 * Fungsi untuk validasi upload file
 */
function validateUpload($file) {
    $errors = [];
    
    // Cek apakah file ada
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "File tidak ditemukan";
        return $errors;
    }
    
    // Cek ukuran file
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = "Ukuran file terlalu besar (maksimal 2MB)";
    }
    
    // Cek tipe file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        $errors[] = "Tipe file tidak diizinkan (hanya JPG, JPEG, PNG)";
    }
    
    return $errors;
}

/**
 * Fungsi untuk upload file
 */
function uploadFile($file, $targetDir = UPLOAD_DIR) {
    // Buat folder jika belum ada
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Generate nama file unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $fileName;
    
    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    
    return false;
}

/**
 * Fungsi untuk validasi email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Fungsi untuk validasi nomor HP
 */
function validatePhone($phone) {
    // Hanya angka, minimal 10 digit, maksimal 15 digit
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

/**
 * Fungsi untuk cek username sudah ada atau belum
 */
function isUsernameExists($db, $username, $excludeId = null) {
    if ($excludeId) {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $excludeId]);
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }
    return $stmt->fetch() ? true : false;
}

/**
 * Fungsi untuk cek email sudah ada atau belum
 */
function isEmailExists($db, $email, $excludeId = null) {
    if ($excludeId) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $excludeId]);
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }
    return $stmt->fetch() ? true : false;
}

/**
 * Fungsi untuk get user by ID
 */
function getUserById($db, $userId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Fungsi untuk get band by ID
 */
function getBandById($db, $bandId) {
    $stmt = $db->prepare("
        SELECT b.*, u.nama as nama_user, u.email, u.no_hp 
        FROM band b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$bandId]);
    return $stmt->fetch();
}

/**
 * Fungsi untuk get pesanan by ID
 */
function getPesananById($db, $pesananId) {
    $stmt = $db->prepare("
        SELECT p.*, 
               u.nama as nama_pelanggan, 
               b.nama_band, b.tarif,
               pm.status as status_bayar
        FROM pesanan p
        JOIN users u ON p.id_user = u.id
        JOIN band b ON p.id_band = b.id
        LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan
        WHERE p.id = ?
    ");
    $stmt->execute([$pesananId]);
    return $stmt->fetch();
}

/**
 * Fungsi untuk menghitung total pesanan
 */
function countPesanan($db, $status = null) {
    if ($status) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM pesanan WHERE status = ?");
        $stmt->execute([$status]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM pesanan");
    }
    $result = $stmt->fetch();
    return $result['total'];
}

/**
 * Fungsi untuk menghitung total band
 */
function countBand($db) {
    $stmt = $db->query("SELECT COUNT(*) as total FROM band");
    $result = $stmt->fetch();
    return $result['total'];
}

/**
 * Fungsi untuk menghitung total pelanggan
 */
function countPelanggan($db) {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'pelanggan'");
    $result = $stmt->fetch();
    return $result['total'];
}

/**
 * Fungsi untuk menghitung total pendapatan
 */
function countPendapatan($db, $status = 'verified') {
    $stmt = $db->prepare("SELECT SUM(jumlah) as total FROM pembayaran WHERE status = ?");
    $stmt->execute([$status]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Fungsi untuk cek ketersediaan band pada tanggal tertentu
 */
function isBandAvailable($db, $bandId, $tanggal) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM pesanan 
        WHERE id_band = ? 
        AND tanggal_acara = ? 
        AND status IN ('menunggu', 'diterima')
    ");
    $stmt->execute([$bandId, $tanggal]);
    $result = $stmt->fetch();
    return $result['total'] == 0;
}

/**
 * Fungsi untuk logging aktivitas
 */
function logActivity($db, $userId, $activity) {
    $stmt = $db->prepare("
        INSERT INTO activity_log (user_id, activity, created_at) 
        VALUES (?, ?, NOW())
    ");
    try {
        $stmt->execute([$userId, $activity]);
    } catch (Exception $e) {
        // Silent fail untuk logging
    }
}
?>