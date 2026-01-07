<?php
/**
 * FILE: auth/login.php
 * FUNGSI: Halaman login untuk semua user (admin, pelanggan, band)
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Jika sudah login, redirect ke dashboard sesuai role
if (isLoggedIn()) {
    if (hasRole(ROLE_ADMIN)) {
        redirect('admin/dashboard.php');
    } elseif (hasRole(ROLE_PELANGGAN)) {
        redirect('pelanggan/dashboard.php');
    } elseif (hasRole(ROLE_BAND)) {
        redirect('band/dashboard.php');
    }
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Cari user berdasarkan username
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil, set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            setAlert('success', 'Login berhasil! Selamat datang, ' . $user['nama']);
            
            // Redirect berdasarkan role
            if ($user['role'] === ROLE_ADMIN) {
                redirect('admin/dashboard.php');
            } elseif ($user['role'] === ROLE_PELANGGAN) {
                redirect('pelanggan/dashboard.php');
            } elseif ($user['role'] === ROLE_BAND) {
                redirect('band/dashboard.php');
            }
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 style="color: var(--primary); font-size: 2rem;">🎵 <?php echo APP_NAME; ?></h1>
                <h2>Login ke Akun Anda</h2>
                <p class="text-gray">Masukkan username dan password Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        class="form-control" 
                        placeholder="Masukkan username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Login
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                <p class="text-gray">
                    Belum punya akun? 
                    <a href="register.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                        Daftar sekarang
                    </a>
                </p>
                <p class="text-gray" style="margin-top: 1rem;">
                    <a href="../index.php" style="color: var(--primary); text-decoration: none;">
                        ← Kembali ke Beranda
                    </a>
                </p>
            </div>

            <!-- Info Akun Demo (untuk testing) -->
            <div style="margin-top: 2rem; padding: 1rem; background: var(--light); border-radius: var(--radius); font-size: 0.875rem;">
                <strong>Akun Demo:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem; color: var(--gray);">
                    <li>Admin: username: <strong>admin</strong>, password: <strong>admin123</strong></li>
                    <li>Band: username: <strong>rizkyband</strong>, password: <strong>admin123</strong></li>
                    <li>Pelanggan: username: <strong>johndoe</strong>, password: <strong>admin123</strong></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>