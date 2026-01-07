<?php
/**
 * FILE: pelanggan/upload_bukti.php
 * FUNGSI: Upload bukti pembayaran untuk pesanan
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_PELANGGAN);

$pesananId = clean($_GET['id'] ?? '');
$errors = [];

// Validasi pesanan
if (empty($pesananId)) {
    setAlert('error', 'ID Pesanan tidak valid');
    redirect('pelanggan/status_pesanan.php');
}

// Ambil data pesanan
$pesanan = getPesananById($db, $pesananId);

if (!$pesanan || $pesanan['id_user'] != $_SESSION['user_id']) {
    setAlert('error', 'Pesanan tidak ditemukan');
    redirect('pelanggan/status_pesanan.php');
}

// Cek apakah sudah upload bukti
$stmt = $db->prepare("SELECT * FROM pembayaran WHERE id_pesanan = ?");
$stmt->execute([$pesananId]);
$pembayaran = $stmt->fetch();

// Proses upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = clean($_POST['metode'] ?? '');
    
    // Validasi metode
    if (empty($metode)) {
        $errors[] = 'Metode pembayaran harus dipilih';
    }
    
    // Validasi file
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileErrors = validateUpload($_FILES['bukti']);
        if (!empty($fileErrors)) {
            $errors = array_merge($errors, $fileErrors);
        }
    } else {
        $errors[] = 'Bukti pembayaran harus diupload';
    }
    
    // Jika tidak ada error, proses upload
    if (empty($errors)) {
        $fileName = uploadFile($_FILES['bukti']);
        
        if ($fileName) {
            try {
                // Update data pembayaran
                $stmt = $db->prepare("
                    UPDATE pembayaran 
                    SET metode = ?, bukti = ?, status = ?, tanggal_bayar = NOW() 
                    WHERE id_pesanan = ?
                ");
                $stmt->execute([$metode, $fileName, STATUS_BAYAR_MENUNGGU, $pesananId]);
                
                setAlert('success', 'Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.');
                redirect('pelanggan/status_pesanan.php?id=' . $pesananId);
                
            } catch (Exception $e) {
                $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Gagal mengupload file';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bukti Pembayaran - <?php echo APP_NAME; ?></title>
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
                    <span style="color: var(--gray);">
                        Halo, <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>
                    </span>
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
                <li><a href="status_pesanan.php" class="active">📋 Pesanan Saya</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Upload Bukti Pembayaran</h1>
            <p class="text-gray mb-3">Upload bukti transfer untuk pesanan #<?php echo $pesananId; ?></p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Detail Pesanan -->
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Detail Pesanan</h3>
                        </div>
                        <div class="card-body">
                            <table style="width: 100%;">
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>ID Pesanan:</strong></td>
                                    <td>#<?php echo $pesanan['id']; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Band:</strong></td>
                                    <td><?php echo htmlspecialchars($pesanan['nama_band']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Tanggal Acara:</strong></td>
                                    <td><?php echo formatTanggal($pesanan['tanggal_acara']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Lokasi:</strong></td>
                                    <td><?php echo htmlspecialchars($pesanan['lokasi']); ?></td>
                                </tr>
                            </table>

                            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--light);">
                                <h4 style="margin-bottom: 1rem;">Total Pembayaran</h4>
                                <p style="font-size: 2rem; color: var(--primary); font-weight: bold; margin: 0;">
                                    <?php echo formatRupiah($pesanan['tarif']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Rekening -->
                    <div class="card mt-2">
                        <div class="card-header" style="background: var(--primary); color: white;">
                            <h3 class="card-title">Informasi Rekening</h3>
                        </div>
                        <div class="card-body">
                            <div style="background: var(--light); padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
                                <p style="margin: 0; font-weight: bold;">Bank BCA</p>
                                <p style="margin: 0.5rem 0 0 0; font-size: 1.3rem; font-weight: bold; color: var(--primary);">
                                    1234567890
                                </p>
                                <p style="margin: 0.5rem 0 0 0; color: var(--gray);">a.n. GOBANDLIVE</p>
                            </div>
                            <div style="background: var(--light); padding: 1rem; border-radius: var(--radius);">
                                <p style="margin: 0; font-weight: bold;">Bank Mandiri</p>
                                <p style="margin: 0.5rem 0 0 0; font-size: 1.3rem; font-weight: bold; color: var(--primary);">
                                    0987654321
                                </p>
                                <p style="margin: 0.5rem 0 0 0; color: var(--gray);">a.n. GOBANDLIVE</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Upload -->
                <div class="col-8">
                    <?php if ($pembayaran && $pembayaran['status'] === STATUS_BAYAR_MENUNGGU): ?>
                        <!-- Jika sudah upload, tampilkan status -->
                        <div class="card">
                            <div class="card-body text-center" style="padding: 3rem;">
                                <div style="font-size: 5rem; margin-bottom: 1rem;">⏳</div>
                                <h2 style="color: var(--warning); margin-bottom: 1rem;">Menunggu Verifikasi</h2>
                                <p class="text-gray" style="font-size: 1.1rem; line-height: 1.8;">
                                    Bukti pembayaran Anda sedang diverifikasi oleh admin.<br>
                                    Kami akan memberitahu Anda setelah pembayaran diverifikasi.
                                </p>

                                <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius); margin-top: 2rem; text-align: left;">
                                    <h4 style="margin-bottom: 1rem;">Bukti Pembayaran Anda:</h4>
                                    <p><strong>Metode:</strong> <?php echo ucfirst($pembayaran['metode']); ?></p>
                                    <p><strong>Tanggal Upload:</strong> <?php echo date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])); ?></p>
                                    <?php if ($pembayaran['bukti']): ?>
                                        <img src="../uploads/bukti_bayar/<?php echo htmlspecialchars($pembayaran['bukti']); ?>" 
                                             alt="Bukti Pembayaran" 
                                             style="width: 100%; max-width: 400px; border-radius: var(--radius); margin-top: 1rem;">
                                    <?php endif; ?>
                                </div>

                                <a href="status_pesanan.php" class="btn btn-primary mt-3">
                                    Lihat Status Pesanan
                                </a>
                            </div>
                        </div>
                    <?php elseif ($pembayaran && $pembayaran['status'] === STATUS_BAYAR_VERIFIED): ?>
                        <!-- Jika sudah diverifikasi -->
                        <div class="card">
                            <div class="card-body text-center" style="padding: 3rem;">
                                <div style="font-size: 5rem; margin-bottom: 1rem;">✅</div>
                                <h2 style="color: var(--success); margin-bottom: 1rem;">Pembayaran Terverifikasi</h2>
                                <p class="text-gray" style="font-size: 1.1rem;">
                                    Pembayaran Anda telah diverifikasi. Pesanan sedang diproses.
                                </p>
                                <a href="status_pesanan.php" class="btn btn-success mt-3">
                                    Lihat Status Pesanan
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Form upload bukti -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Form Upload Bukti Pembayaran</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <strong>📌 Panduan Upload:</strong>
                                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                                        <li>Transfer sesuai nominal yang tertera</li>
                                        <li>Upload bukti transfer (JPG/PNG, max 2MB)</li>
                                        <li>Pastikan foto jelas dan terbaca</li>
                                        <li>Verifikasi akan dilakukan dalam 1x24 jam</li>
                                    </ul>
                                </div>

                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label class="form-label">Metode Pembayaran <span style="color: red;">*</span></label>
                                        <select name="metode" class="form-select" required>
                                            <option value="">Pilih Metode Pembayaran</option>
                                            <option value="transfer_bca">Transfer Bank BCA</option>
                                            <option value="transfer_mandiri">Transfer Bank Mandiri</option>
                                            <option value="transfer_bri">Transfer Bank BRI</option>
                                            <option value="transfer_bni">Transfer Bank BNI</option>
                                            <option value="ewallet">E-Wallet (OVO/GoPay/Dana)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Bukti Pembayaran <span style="color: red;">*</span></label>
                                        <input 
                                            type="file" 
                                            name="bukti" 
                                            class="form-control" 
                                            accept="image/jpeg,image/png,image/jpg"
                                            required
                                            id="fileInput"
                                        >
                                        <small class="text-gray">Format: JPG, PNG (Maksimal 2MB)</small>
                                    </div>

                                    <!-- Preview Image -->
                                    <div id="imagePreview" style="display: none; margin-top: 1rem;">
                                        <label class="form-label">Preview:</label>
                                        <img id="preview" src="" alt="Preview" style="max-width: 100%; max-height: 300px; border-radius: var(--radius); border: 2px solid var(--light);">
                                    </div>

                                    <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius); margin-top: 1.5rem;">
                                        <p style="margin: 0; color: var(--gray); font-size: 0.9rem; line-height: 1.6;">
                                            <strong>Catatan:</strong> Dengan mengupload bukti pembayaran, Anda menyatakan bahwa 
                                            pembayaran telah dilakukan sesuai dengan nominal yang tertera. Pembayaran palsu atau 
                                            manipulasi akan dikenakan sanksi sesuai hukum yang berlaku.
                                        </p>
                                    </div>

                                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                                        <a href="status_pesanan.php" class="btn btn-outline" style="flex: 1;">
                                            ← Kembali
                                        </a>
                                        <button type="submit" class="btn btn-primary" style="flex: 2;">
                                            📤 Upload Bukti Pembayaran
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Preview image sebelum upload
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>