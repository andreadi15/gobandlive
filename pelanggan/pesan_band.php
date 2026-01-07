<?php
/**
 * FILE: pelanggan/pesan_band.php
 * FUNGSI: Form pemesanan jasa band/vokalis
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_PELANGGAN);

$bandId = clean($_GET['id'] ?? '');
$errors = [];

// Validasi band ID
if (empty($bandId)) {
    redirect('pelanggan/lihat_band.php');
}

// Ambil data band
$band = getBandById($db, $bandId);
if (!$band) {
    setAlert('error', 'Band tidak ditemukan');
    redirect('pelanggan/lihat_band.php');
}

// Proses pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_acara = clean($_POST['tanggal_acara'] ?? '');
    $lokasi = clean($_POST['lokasi'] ?? '');
    $catatan = clean($_POST['catatan'] ?? '');
    
    // Validasi
    if (empty($tanggal_acara)) {
        $errors[] = 'Tanggal acara harus diisi';
    } else {
        // Cek apakah tanggal di masa depan
        if (strtotime($tanggal_acara) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Tanggal acara harus di masa depan';
        }
        
        // Cek ketersediaan band
        if (!isBandAvailable($db, $bandId, $tanggal_acara)) {
            $errors[] = 'Band tidak tersedia pada tanggal tersebut';
        }
    }
    
    if (empty($lokasi)) {
        $errors[] = 'Lokasi acara harus diisi';
    }
    
    // Jika tidak ada error, simpan pesanan
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Insert pesanan
            $stmt = $db->prepare("
                INSERT INTO pesanan (id_user, id_band, tanggal_acara, lokasi, catatan, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $bandId, 
                $tanggal_acara, 
                $lokasi, 
                $catatan, 
                STATUS_MENUNGGU
            ]);
            
            $pesananId = $db->lastInsertId();
            
            // Insert pembayaran dengan status belum
            $stmt = $db->prepare("
                INSERT INTO pembayaran (id_pesanan, jumlah, status) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pesananId, $band['tarif'], STATUS_BAYAR_BELUM]);
            
            $db->commit();
            
            setAlert('success', 'Pesanan berhasil dibuat! Silakan lakukan pembayaran.');
            redirect('pelanggan/upload_bukti.php?id=' . $pesananId);
            
        } catch (Exception $e) {
            $db->rollBack();
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
    <title>Pesan Band - <?php echo APP_NAME; ?></title>
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
                <li><a href="lihat_band.php" class="active">🎸 Lihat Band</a></li>
                <li><a href="status_pesanan.php">📋 Pesanan Saya</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Form Pemesanan Band</h1>
            <p class="text-gray mb-3">Lengkapi formulir di bawah untuk memesan band</p>

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
                <!-- Detail Band -->
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Detail Band</h3>
                        </div>
                        <div class="card-body">
                            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); height: 150px; border-radius: var(--radius); margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                🎤
                            </div>

                            <h3><?php echo htmlspecialchars($band['nama_band']); ?></h3>
                            
                            <table style="width: 100%; margin-top: 1rem;">
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Genre:</strong></td>
                                    <td><?php echo htmlspecialchars($band['genre']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Kontak:</strong></td>
                                    <td><?php echo htmlspecialchars($band['kontak']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Tarif:</strong></td>
                                    <td style="color: var(--primary); font-weight: bold; font-size: 1.2rem;">
                                        <?php echo formatRupiah($band['tarif']); ?>
                                    </td>
                                </tr>
                            </table>

                            <?php if ($band['deskripsi']): ?>
                                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light);">
                                    <p class="text-gray" style="font-size: 0.9rem; line-height: 1.6;">
                                        <?php echo nl2br(htmlspecialchars($band['deskripsi'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Form Pemesanan -->
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Formulir Pemesanan</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label class="form-label">Nama Pemesan</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_SESSION['nama']); ?>"
                                        disabled
                                    >
                                    <small class="text-gray">Sesuai dengan akun Anda</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Tanggal Acara <span style="color: red;">*</span></label>
                                    <input 
                                        type="date" 
                                        name="tanggal_acara" 
                                        class="form-control" 
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                        value="<?php echo isset($_POST['tanggal_acara']) ? htmlspecialchars($_POST['tanggal_acara']) : ''; ?>"
                                        required
                                    >
                                    <small class="text-gray">Pilih tanggal acara Anda</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Lokasi Acara <span style="color: red;">*</span></label>
                                    <textarea 
                                        name="lokasi" 
                                        class="form-control" 
                                        rows="3"
                                        placeholder="Contoh: Gedung Pertemuan XYZ, Jl. Sudirman No. 123, Jakarta"
                                        required
                                    ><?php echo isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : ''; ?></textarea>
                                    <small class="text-gray">Alamat lengkap lokasi acara</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Catatan Tambahan</label>
                                    <textarea 
                                        name="catatan" 
                                        class="form-control" 
                                        rows="4"
                                        placeholder="Contoh: Acara pernikahan, durasi 3 jam, tema musik romantis"
                                    ><?php echo isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : ''; ?></textarea>
                                    <small class="text-gray">Informasi tambahan untuk band (opsional)</small>
                                </div>

                                <!-- Ringkasan Biaya -->
                                <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius); margin-top: 1.5rem;">
                                    <h4 style="margin-bottom: 1rem;">Ringkasan Biaya</h4>
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="padding: 0.5rem 0;"><strong>Tarif Band:</strong></td>
                                            <td style="text-align: right; font-size: 1.3rem; color: var(--primary); font-weight: bold;">
                                                <?php echo formatRupiah($band['tarif']); ?>
                                            </td>
                                        </tr>
                                    </table>
                                    <p class="text-gray" style="font-size: 0.9rem; margin-top: 1rem;">
                                        Setelah memesan, Anda akan diarahkan untuk upload bukti pembayaran
                                    </p>
                                </div>

                                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                                    <a href="lihat_band.php" class="btn btn-outline" style="flex: 1;">
                                        ← Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary" style="flex: 2;">
                                        📝 Buat Pesanan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>