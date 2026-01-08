<?php
/**
 * FILE: pelanggan/edit_profil.php
 * FUNGSI: Form edit profil untuk pelanggan
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_PELANGGAN);

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Ambil data user
$user = getUserById($db, $userId);

if (!$user) {
    setAlert('error', 'Data user tidak ditemukan');
    redirect('pelanggan/dashboard.php');
}

// Proses update profil
if (isset($_POST['update_profil'])) {
    $nama = clean($_POST['nama'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $no_hp = clean($_POST['no_hp'] ?? '');
    $alamat = clean($_POST['alamat'] ?? '');
    
    // Validasi
    if (empty($nama)) $errors[] = 'Nama harus diisi';
    if (empty($email) || !validateEmail($email)) $errors[] = 'Email tidak valid';
    if (empty($no_hp) || !validatePhone($no_hp)) $errors[] = 'Nomor HP tidak valid';
    
    // Cek email sudah dipakai user lain
    if (isEmailExists($db, $email, $userId)) {
        $errors[] = 'Email sudah digunakan user lain';
    }
    
    if (empty($errors)) {
        try {
            // Update tabel users
            $stmt = $db->prepare("
                UPDATE users 
                SET nama = ?, email = ?, no_hp = ?, alamat = ?
                WHERE id = ?
            ");
            $stmt->execute([$nama, $email, $no_hp, $alamat, $userId]);
            
            // Update session
            $_SESSION['nama'] = $nama;
            
            $success = true;
            setAlert('success', 'Profil berhasil diperbarui!');
            
            // Refresh data
            $user = getUserById($db, $userId);
            
        } catch (Exception $e) {
            $errors[] = 'Gagal memperbarui profil: ' . $e->getMessage();
        }
    }
}

// Proses ganti password
if (isset($_POST['update_password'])) {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $password_konfirmasi = $_POST['password_konfirmasi'] ?? '';
    
    // Validasi
    if (empty($password_lama)) {
        $errors[] = 'Password lama harus diisi';
    } elseif (!password_verify($password_lama, $user['password'])) {
        $errors[] = 'Password lama tidak sesuai';
    }
    
    if (empty($password_baru) || strlen($password_baru) < 6) {
        $errors[] = 'Password baru minimal 6 karakter';
    }
    
    if ($password_baru !== $password_konfirmasi) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            setAlert('success', 'Password berhasil diubah!');
            redirect('pelanggan/edit_profil.php');
        } catch (Exception $e) {
            $errors[] = 'Gagal mengubah password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="../index.php" class="navbar-brand">
                🎵 <?php echo APP_NAME; ?>
            </a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="lihat_band.php">Lihat Band</a></li>
                <li><a href="status_pesanan.php">Pesanan Saya</a></li>
                <li>
                    <a href="edit_profil.php" class="user-profile-link">
                        Halo, <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>
                    </a>
                </li>
                <li><a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-wrapper">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="lihat_band.php">🎸 Lihat Band</a></li>
                <li><a href="status_pesanan.php">📋 Pesanan Saya</a></li>
                <li><a href="edit_profil.php" class="active">⚙️ Edit Profil</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Edit Profil Pelanggan</h1>
            <p class="text-gray mb-3">Kelola informasi profil dan akun Anda</p>

            <?php if ($success): ?>
                <div class="alert alert-success success-animation">
                    ✅ Profil berhasil diperbarui!
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Profil Header -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        👤
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?> • <?php echo htmlspecialchars($user['no_hp']); ?></p>
                        <p style="margin-top: 0.5rem;">
                            <span class="badge badge-info">Pelanggan</span>
                        </p>
                    </div>
                </div>

                <!-- Form Edit Profil -->
                <form method="POST" action="">
                    <div class="form-section">
                        <h3 class="form-section-title">Informasi Pribadi</h3>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Nomor HP</label>
                                    <input type="text" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($user['no_hp']); ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="text-gray">Username tidak dapat diubah</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap Anda"><?php echo htmlspecialchars($user['alamat']); ?></textarea>
                        </div>
                    </div>

                    <div class="save-buttons">
                        <a href="dashboard.php" class="btn btn-outline" style="flex: 1;">
                            Batal
                        </a>
                        <button type="submit" name="update_profil" class="btn btn-primary" style="flex: 2;">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Form Ganti Password -->
            <div class="profile-card">
                <h3 class="form-section-title">Ganti Password</h3>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Password Lama</label>
                                <input type="password" name="password_lama" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password_baru" class="form-control" id="password_baru" required>
                                <small class="text-gray">Minimal 6 karakter</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="password_konfirmasi" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-warning">
                        🔒 Ubah Password
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>