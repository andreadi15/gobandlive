<?php
/**
 * FILE: band/edit_profil.php
 * FUNGSI: Form edit profil untuk band/vokalis
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_BAND);

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Ambil data user dan band
$user = getUserById($db, $userId);
$stmt = $db->prepare("SELECT * FROM band WHERE user_id = ?");
$stmt->execute([$userId]);
$bandData = $stmt->fetch();

if (!$bandData) {
    setAlert('error', 'Data band tidak ditemukan');
    redirect('band/dashboard.php');
}

// Proses update profil
if (isset($_POST['update_profil'])) {
    $nama = clean($_POST['nama'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $no_hp = clean($_POST['no_hp'] ?? '');
    $nama_band = clean($_POST['nama_band'] ?? '');
    $genre = clean($_POST['genre'] ?? '');
    $tarif = clean($_POST['tarif'] ?? '');
    $kontak = clean($_POST['kontak'] ?? '');
    $deskripsi = clean($_POST['deskripsi'] ?? '');
    $status_ketersediaan = isset($_POST['status_ketersediaan']) ? 'tersedia' : 'tidak tersedia';
    
    // Validasi
    if (empty($nama)) $errors[] = 'Nama harus diisi';
    if (empty($email) || !validateEmail($email)) $errors[] = 'Email tidak valid';
    if (empty($no_hp) || !validatePhone($no_hp)) $errors[] = 'Nomor HP tidak valid';
    if (empty($nama_band)) $errors[] = 'Nama band harus diisi';
    if (empty($genre)) $errors[] = 'Genre harus diisi';
    if (empty($tarif) || $tarif < 0) $errors[] = 'Tarif harus valid';
    
    // Cek email sudah dipakai user lain
    if (isEmailExists($db, $email, $userId)) {
        $errors[] = 'Email sudah digunakan user lain';
    }
    
    // Handle upload foto
    $fotoName = $bandData['foto']; // Keep existing photo
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Validasi foto
        $fotoErrors = validateUpload($_FILES['foto']);
        if (!empty($fotoErrors)) {
            $errors = array_merge($errors, $fotoErrors);
        } else {
            // Upload foto baru
            $uploadDir = __DIR__ . '/../uploads/band_photos/';
            $newFoto = uploadFile($_FILES['foto'], $uploadDir);
            if ($newFoto) {
                // Hapus foto lama jika ada
                if ($bandData['foto'] && file_exists($uploadDir . $bandData['foto'])) {
                    unlink($uploadDir . $bandData['foto']);
                }
                $fotoName = $newFoto;
            } else {
                $errors[] = 'Gagal mengupload foto';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update tabel users
            $stmt = $db->prepare("
                UPDATE users 
                SET nama = ?, email = ?, no_hp = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nama, $email, $no_hp, $userId]);
            
            // Update tabel band
            $stmt = $db->prepare("
                UPDATE band 
                SET nama_band = ?, genre = ?, tarif = ?, kontak = ?, deskripsi = ?, status_ketersediaan = ?, foto = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$nama_band, $genre, $tarif, $kontak, $deskripsi, $status_ketersediaan, $fotoName, $userId]);
            
            $db->commit();
            
            // Update session
            $_SESSION['nama'] = $nama;
            
            $success = true;
            setAlert('success', 'Profil berhasil diperbarui!');
            
            // Refresh data
            $user = getUserById($db, $userId);
            $stmt = $db->prepare("SELECT * FROM band WHERE user_id = ?");
            $stmt->execute([$userId]);
            $bandData = $stmt->fetch();
            
        } catch (Exception $e) {
            $db->rollBack();
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
            redirect('band/edit_profil.php');
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
                <li><a href="pesanan_masuk.php">Pesanan</a></li>
                <li><a href="jadwal_tampil.php">Jadwal</a></li>
                <li><a href="lihat_ulasan.php">Ulasan</a></li>
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
                <li><a href="pesanan_masuk.php">📥 Pesanan Masuk</a></li>
                <li><a href="jadwal_tampil.php">📅 Jadwal Tampil</a></li>
                <li><a href="lihat_ulasan.php">⭐ Ulasan</a></li>
                <li><a href="edit_profil.php" class="active">⚙️ Edit Profil</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Edit Profil Band</h1>
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
                        🎤
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($bandData['nama_band']); ?></h2>
                        <p><?php echo htmlspecialchars($bandData['genre']); ?> • <?php echo htmlspecialchars($user['email']); ?></p>
                        <p style="margin-top: 0.5rem;">
                            <span class="badge <?php echo $bandData['status_ketersediaan'] === 'tersedia' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ucfirst($bandData['status_ketersediaan']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Form Edit Profil -->
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3 class="form-section-title">Informasi Akun</h3>
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
                        <div class="form-group">
                            <label class="form-label">Nomor HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($user['no_hp']); ?>" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Informasi Band</h3>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Nama Band/Vokalis</label>
                                    <input type="text" name="nama_band" class="form-control" value="<?php echo htmlspecialchars($bandData['nama_band']); ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Genre Musik</label>
                                    <input type="text" name="genre" class="form-control" value="<?php echo htmlspecialchars($bandData['genre']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Tarif (Rp)</label>
                                    <input type="number" name="tarif" class="form-control" value="<?php echo $bandData['tarif']; ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Kontak Band</label>
                                    <input type="text" name="kontak" class="form-control" value="<?php echo htmlspecialchars($bandData['kontak']); ?>" required>
                                </div>
                            </div>
                        </div>
                         <div class="form-group">
                            <label class="form-label">Foto Band</label>
                            
                            <!-- Preview Foto -->
                            <div style="display: flex; gap: 2rem; align-items: start; flex-wrap: wrap;">
                                <!-- Foto Saat Ini -->
                                <?php if ($bandData['foto'] && file_exists(__DIR__ . '/../uploads/band_photos/' . $bandData['foto'])): ?>
                                    <div>
                                        <p class="text-gray" style="margin-bottom: 0.5rem; font-weight: 500;">📸 Foto Saat Ini</p>
                                        <div style="position: relative; width: 200px; height: 200px;">
                                            <img src="../uploads/band_photos/<?php echo htmlspecialchars($bandData['foto']); ?>" 
                                                alt="Foto Band" 
                                                id="currentPhoto"
                                                style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius); border: 3px solid var(--primary); box-shadow: var(--shadow-lg);">
                                            <div style="position: absolute; top: 10px; right: 10px; background: var(--success); color: white; padding: 0.3rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: bold;">
                                                ✓ Active
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div>
                                        <p class="text-gray" style="margin-bottom: 0.5rem; font-weight: 500;">📸 Foto Saat Ini</p>
                                        <div style="width: 200px; height: 200px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; flex-direction: column; color: white; box-shadow: var(--shadow-lg);">
                                            <div style="font-size: 4rem; margin-bottom: 0.5rem;">🎤</div>
                                            <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;">Belum ada foto</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Preview Upload Baru -->
                                <div id="previewContainer" style="display: none;">
                                    <p class="text-gray" style="margin-bottom: 0.5rem; font-weight: 500;">🔄 Preview Foto Baru</p>
                                    <div style="position: relative; width: 200px; height: 200px;">
                                        <img id="photoPreview" 
                                            src="" 
                                            alt="Preview" 
                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius); border: 3px dashed var(--warning); box-shadow: var(--shadow-lg);">
                                        <div style="position: absolute; top: 10px; right: 10px; background: var(--warning); color: white; padding: 0.3rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: bold;">
                                            ⏳ Preview
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Button Styled -->
                            <div style="margin-top: 1.5rem; padding: 1.5rem; background: var(--light); border-radius: var(--radius); border: 2px dashed var(--gray);">
                                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                    <label for="fotoInput" class="btn btn-primary" style="cursor: pointer; margin: 0;">
                                        📁 Pilih Foto Band
                                    </label>
                                    <input type="file" 
                                        name="foto" 
                                        id="fotoInput"
                                        class="form-control" 
                                        accept="image/jpeg,image/png,image/jpg"
                                        style="display: none;">
                                    <span id="fileName" class="text-gray" style="font-style: italic;">Belum ada file dipilih</span>
                                </div>
                                <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: var(--radius); border-left: 4px solid var(--info);">
                                    <p style="margin: 0; font-size: 0.9rem; color: var(--info); font-weight: 500;">ℹ️ Ketentuan Upload:</p>
                                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0; font-size: 0.85rem; color: var(--gray);">
                                        <li>Format: JPG, JPEG, atau PNG</li>
                                        <li>Ukuran maksimal: 2MB</li>
                                        <li>Resolusi direkomendasikan: minimal 500x500px</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <script>
                        // Preview foto sebelum upload
                        document.getElementById('fotoInput').addEventListener('change', function(e) {
                            const file = e.target.files[0];
                            const fileName = document.getElementById('fileName');
                            const previewContainer = document.getElementById('previewContainer');
                            const photoPreview = document.getElementById('photoPreview');
                            
                            if (file) {
                                // Update file name
                                fileName.textContent = file.name;
                                fileName.style.color = 'var(--success)';
                                fileName.style.fontWeight = '500';
                                
                                // Show preview
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    photoPreview.src = e.target.result;
                                    previewContainer.style.display = 'block';
                                }
                                reader.readAsDataURL(file);
                                
                                // Validasi ukuran
                                if (file.size > 2 * 1024 * 1024) {
                                    alert('⚠️ Ukuran file terlalu besar! Maksimal 2MB');
                                    e.target.value = '';
                                    fileName.textContent = 'Belum ada file dipilih';
                                    fileName.style.color = 'var(--gray)';
                                    fileName.style.fontWeight = 'normal';
                                    previewContainer.style.display = 'none';
                                }
                            } else {
                                fileName.textContent = 'Belum ada file dipilih';
                                fileName.style.color = 'var(--gray)';
                                fileName.style.fontWeight = 'normal';
                                previewContainer.style.display = 'none';
                            }
                        });
                        </script>
                        <div class="form-group">
                            <label class="form-label">Deskripsi Band</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Ceritakan tentang band Anda..."><?php echo htmlspecialchars($bandData['deskripsi']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span style="font-weight: 500;">Status Ketersediaan</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="status_ketersediaan" <?php echo $bandData['status_ketersediaan'] === 'tersedia' ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="text-gray" style="font-size: 0.9rem;">
                                    <?php echo $bandData['status_ketersediaan'] === 'tersedia' ? 'Band Tersedia' : 'Tidak Tersedia'; ?>
                                </span>
                            </label>
                            <small class="text-gray">Nonaktifkan jika Anda sedang tidak menerima pesanan</small>
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

    <script>
        // Toggle status text
        const toggle = document.querySelector('input[name="status_ketersediaan"]');
        const statusText = toggle.parentElement.parentElement.querySelector('.text-gray');
        
        toggle.addEventListener('change', function() {
            if (this.checked) {
                statusText.textContent = 'Band Tersedia';
            } else {
                statusText.textContent = 'Tidak Tersedia';
            }
        });
    </script>
</body>
</html>