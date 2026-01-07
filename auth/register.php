<?php
/**
 * FILE: auth/register.php
 * FUNGSI: Halaman registrasi untuk pelanggan dan band/vokalis
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Jika sudah login, redirect
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = '';

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean($_POST['nama'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $no_hp = clean($_POST['no_hp'] ?? '');
    $alamat = clean($_POST['alamat'] ?? '');
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = clean($_POST['role'] ?? '');
    
    // Validasi input
    if (empty($nama)) {
        $errors[] = 'Nama harus diisi';
    }
    
    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (empty($no_hp) || !validatePhone($no_hp)) {
        $errors[] = 'Nomor HP tidak valid (10-15 digit)';
    }
    
    if (empty($username) || strlen($username) < 4) {
        $errors[] = 'Username minimal 4 karakter';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    if (!in_array($role, [ROLE_PELANGGAN, ROLE_BAND])) {
        $errors[] = 'Role tidak valid';
    }
    
    // Cek username dan email sudah ada atau belum
    if (isUsernameExists($db, $username)) {
        $errors[] = 'Username sudah digunakan';
    }
    
    if (isEmailExists($db, $email)) {
        $errors[] = 'Email sudah terdaftar';
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $db->prepare("
                INSERT INTO users (nama, email, no_hp, alamat, username, password, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama, $email, $no_hp, $alamat, $username, $hashedPassword, $role]);
            
            // Jika role band, tambahkan data ke tabel band
            if ($role === ROLE_BAND) {
                $userId = $db->lastInsertId();
                $nama_band = clean($_POST['nama_band'] ?? $nama);
                $genre = clean($_POST['genre'] ?? '');
                $tarif = clean($_POST['tarif'] ?? 0);
                
                $stmt = $db->prepare("
                    INSERT INTO band (user_id, nama_band, genre, tarif, kontak) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $nama_band, $genre, $tarif, $no_hp]);
            }
            
            setAlert('success', 'Registrasi berhasil! Silakan login.');
            redirect('auth/login.php');
            
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 600px;">
            <div class="auth-header">
                <h1 style="color: var(--primary); font-size: 2rem;">🎵 <?php echo APP_NAME; ?></h1>
                <h2>Daftar Akun Baru</h2>
                <p class="text-gray">Lengkapi formulir di bawah untuk mendaftar</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label class="form-label">Daftar Sebagai</label>
                    <select name="role" class="form-select" id="roleSelect" required>
                        <option value="">Pilih Role</option>
                        <option value="<?php echo ROLE_PELANGGAN; ?>" <?php echo (isset($_POST['role']) && $_POST['role'] === ROLE_PELANGGAN) ? 'selected' : ''; ?>>
                            Pelanggan (Pemesan)
                        </option>
                        <option value="<?php echo ROLE_BAND; ?>" <?php echo (isset($_POST['role']) && $_POST['role'] === ROLE_BAND) ? 'selected' : ''; ?>>
                            Band / Vokalis
                        </option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input 
                                type="text" 
                                name="nama" 
                                class="form-control" 
                                placeholder="Nama lengkap Anda"
                                value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="email@example.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Nomor HP</label>
                            <input 
                                type="text" 
                                name="no_hp" 
                                class="form-control" 
                                placeholder="08xxxxxxxxxx"
                                value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input 
                                type="text" 
                                name="username" 
                                class="form-control" 
                                placeholder="Username (min. 4 karakter)"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea 
                        name="alamat" 
                        class="form-control" 
                        placeholder="Alamat lengkap"
                        rows="2"
                    ><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                </div>

                <!-- Form tambahan untuk Band -->
                <div id="bandFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Nama Band / Vokalis</label>
                        <input 
                            type="text" 
                            name="nama_band" 
                            class="form-control" 
                            placeholder="Nama band atau nama panggung"
                            value="<?php echo isset($_POST['nama_band']) ? htmlspecialchars($_POST['nama_band']) : ''; ?>"
                        >
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Genre Musik</label>
                                <input 
                                    type="text" 
                                    name="genre" 
                                    class="form-control" 
                                    placeholder="Contoh: Pop, Rock, Jazz"
                                    value="<?php echo isset($_POST['genre']) ? htmlspecialchars($_POST['genre']) : ''; ?>"
                                >
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Tarif (Rp)</label>
                                <input 
                                    type="number" 
                                    name="tarif" 
                                    class="form-control" 
                                    placeholder="Contoh: 5000000"
                                    value="<?php echo isset($_POST['tarif']) ? htmlspecialchars($_POST['tarif']) : ''; ?>"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input 
                                type="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Min. 6 karakter"
                                required
                            >
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password</label>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                class="form-control" 
                                placeholder="Ulangi password"
                                required
                            >
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Daftar Sekarang
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                <p class="text-gray">
                    Sudah punya akun? 
                    <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                        Login di sini
                    </a>
                </p>
                <p class="text-gray" style="margin-top: 1rem;">
                    <a href="../index.php" style="color: var(--primary); text-decoration: none;">
                        ← Kembali ke Beranda
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Show/hide band fields based on role selection
        document.getElementById('roleSelect').addEventListener('change', function() {
            const bandFields = document.getElementById('bandFields');
            if (this.value === '<?php echo ROLE_BAND; ?>') {
                bandFields.style.display = 'block';
                // Make band fields required
                bandFields.querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else {
                bandFields.style.display = 'none';
                // Make band fields not required
                bandFields.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            }
        });
        
        // Trigger on page load if role already selected
        if (document.getElementById('roleSelect').value) {
            document.getElementById('roleSelect').dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>