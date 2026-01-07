<?php
/**
 * FILE: admin/verifikasi_bayar.php
 * FUNGSI: Verifikasi pembayaran dari pelanggan
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_ADMIN);

$detailId = clean($_GET['id'] ?? '');

// Proses verifikasi
if (isset($_POST['verifikasi'])) {
    $pembayaranId = clean($_POST['pembayaran_id']);
    $pesananId = clean($_POST['pesanan_id']);
    $bandId = clean($_POST['band_id']);
    $tanggalAcara = clean($_POST['tanggal_acara']);
    $lokasi = clean($_POST['lokasi']);
    
    try {
        $db->beginTransaction();
        
        // Update status pembayaran
        $stmt = $db->prepare("
            UPDATE pembayaran 
            SET status = ?, verified_at = NOW(), verified_by = ?
            WHERE id = ?
        ");
        $stmt->execute([STATUS_BAYAR_VERIFIED, $_SESSION['user_id'], $pembayaranId]);
        
        // Update status pesanan
        $stmt = $db->prepare("
            UPDATE pesanan 
            SET status = ?
            WHERE id = ?
        ");
        $stmt->execute([STATUS_DITERIMA, $pesananId]);
        
        // Buat jadwal tampil
        $stmt = $db->prepare("
            INSERT INTO jadwal_tampil (id_band, id_pesanan, tanggal, lokasi, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$bandId, $pesananId, $tanggalAcara, $lokasi, 'belum tampil']);
        
        $db->commit();
        
        setAlert('success', 'Pembayaran berhasil diverifikasi dan jadwal telah dibuat!');
        redirect('admin/verifikasi_bayar.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        setAlert('error', 'Gagal verifikasi: ' . $e->getMessage());
    }
}

// Proses tolak
if (isset($_POST['tolak'])) {
    $pembayaranId = clean($_POST['pembayaran_id']);
    $pesananId = clean($_POST['pesanan_id']);
    
    try {
        $db->beginTransaction();
        
        // Update status pembayaran kembali ke belum
        $stmt = $db->prepare("
            UPDATE pembayaran 
            SET status = ?, bukti = NULL
            WHERE id = ?
        ");
        $stmt->execute([STATUS_BAYAR_BELUM, $pembayaranId]);
        
        // Update status pesanan
        $stmt = $db->prepare("
            UPDATE pesanan 
            SET status = ?
            WHERE id = ?
        ");
        $stmt->execute([STATUS_DIBATALKAN, $pesananId]);
        
        $db->commit();
        
        setAlert('warning', 'Pembayaran ditolak dan pesanan dibatalkan');
        redirect('admin/verifikasi_bayar.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        setAlert('error', 'Gagal menolak: ' . $e->getMessage());
    }
}

// Ambil detail pembayaran jika ada
$detailPembayaran = null;
if ($detailId) {
    $stmt = $db->prepare("
        SELECT pm.*, 
               p.id as pesanan_id, p.tanggal_acara, p.lokasi, p.catatan,
               u.nama as nama_pelanggan, u.email, u.no_hp,
               b.id as band_id, b.nama_band, b.tarif
        FROM pembayaran pm
        JOIN pesanan p ON pm.id_pesanan = p.id
        JOIN users u ON p.id_user = u.id
        JOIN band b ON p.id_band = b.id
        WHERE pm.id = ?
    ");
    $stmt->execute([$detailId]);
    $detailPembayaran = $stmt->fetch();
}

// Ambil daftar pembayaran menunggu verifikasi
$stmt = $db->prepare("
    SELECT pm.*, 
           p.id as pesanan_id, p.tanggal_acara,
           u.nama as nama_pelanggan,
           b.nama_band, b.tarif
    FROM pembayaran pm
    JOIN pesanan p ON pm.id_pesanan = p.id
    JOIN users u ON p.id_user = u.id
    JOIN band b ON p.id_band = b.id
    WHERE pm.status = ?
    ORDER BY pm.tanggal_bayar DESC
");
$stmt->execute([STATUS_BAYAR_MENUNGGU]);
$pembayaranList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - <?php echo APP_NAME; ?></title>
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
                <li><a href="kelola_pelanggan.php">Pelanggan</a></li>
                <li><a href="kelola_band.php">Band</a></li>
                <li><a href="verifikasi_bayar.php">Verifikasi</a></li>
                <li><a href="laporan.php">Laporan</a></li>
                <li>
                    <span style="color: var(--gray);">
                        Admin: <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>
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
                <li><a href="kelola_pelanggan.php">👥 Kelola Pelanggan</a></li>
                <li><a href="kelola_band.php">🎸 Kelola Band</a></li>
                <li><a href="verifikasi_bayar.php" class="active">✅ Verifikasi Pembayaran</a></li>
                <li><a href="kelola_jadwal.php">📅 Kelola Jadwal</a></li>
                <li><a href="laporan.php">📈 Laporan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Verifikasi Pembayaran</h1>
            <p class="text-gray mb-3">Verifikasi bukti pembayaran dari pelanggan</p>

            <?php showAlert(); ?>

            <!-- Detail Verifikasi -->
            <?php if ($detailPembayaran): ?>
                <div class="card mb-3" style="border: 2px solid var(--warning);">
                    <div class="card-header" style="background: var(--warning); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">Detail Pembayaran #<?php echo $detailPembayaran['id']; ?></h3>
                            <a href="verifikasi_bayar.php" style="color: white; text-decoration: none; font-size: 1.5rem;">✕</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="pembayaran_id" value="<?php echo $detailPembayaran['id']; ?>">
                            <input type="hidden" name="pesanan_id" value="<?php echo $detailPembayaran['pesanan_id']; ?>">
                            <input type="hidden" name="band_id" value="<?php echo $detailPembayaran['band_id']; ?>">
                            <input type="hidden" name="tanggal_acara" value="<?php echo $detailPembayaran['tanggal_acara']; ?>">
                            <input type="hidden" name="lokasi" value="<?php echo $detailPembayaran['lokasi']; ?>">
                            
                            <div class="row">
                                <div class="col-6">
                                    <h4 style="margin-bottom: 1rem;">Informasi Pesanan</h4>
                                    <table style="width: 100%;">
                                        <tr>
                                            <td class="text-gray" style="padding: 0.5rem 0; width: 150px;"><strong>ID Pesanan:</strong></td>
                                            <td>#<?php echo $detailPembayaran['pesanan_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray" style="padding: 0.5rem 0;"><strong>Pelanggan:</strong></td>
                                            <td><?php echo htmlspecialchars($detailPembayaran['nama_pelanggan']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray" style="padding: 0.5rem 0;"><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($detailPembayaran['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray" style="padding: 0.5rem 0;"><strong>No. HP:</strong></td>
                                            <td><?php echo htmlspecialchars($detailPembayaran['no_hp']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray" style="padding: 0.5rem 0;"><strong>Band:</strong></td>
                                            <td><?php echo htmlspecialchars($detailPembayaran['nama_band']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray" style="padding: 0.5rem 0;"><strong>Tanggal Acara:</strong></td>
                                            <td><?php echo formatTanggal($detailPembayaran['tanggal_acara']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray" style="padding: 0.5rem 0;"><strong>Jumlah Bayar:</strong></td>
                                            <td style="color: var(--primary); font-weight: bold; font-size: 1.3rem;">
                                                <?php echo formatRupiah($detailPembayaran['jumlah']); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-6">
                                    <h4 style="margin-bottom: 1rem;">Bukti Pembayaran</h4>
                                    <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius);">
                                        <p><strong>Metode:</strong> <?php echo ucfirst(str_replace('_', ' ', $detailPembayaran['metode'])); ?></p>
                                        <p><strong>Tanggal Upload:</strong> <?php echo date('d/m/Y H:i', strtotime($detailPembayaran['tanggal_bayar'])); ?></p>
                                        
                                        <?php if ($detailPembayaran['bukti']): ?>
                                            <p style="margin-top: 1rem;"><strong>Bukti Transfer:</strong></p>
                                            <img src="../uploads/bukti_bayar/<?php echo htmlspecialchars($detailPembayaran['bukti']); ?>" 
                                                 alt="Bukti Pembayaran" 
                                                 style="width: 100%; border-radius: var(--radius); cursor: pointer;"
                                                 onclick="window.open(this.src, '_blank')">
                                            <p class="text-gray" style="font-size: 0.9rem; margin-top: 0.5rem; text-align: center;">
                                                Klik gambar untuk memperbesar
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                                        <button type="submit" name="verifikasi" class="btn btn-success" style="flex: 1;">
                                            ✅ Verifikasi & Setujui
                                        </button>
                                        <button type="submit" name="tolak" class="btn btn-danger" style="flex: 1;"
                                                onclick="return confirm('Yakin ingin menolak pembayaran ini?')">
                                            ❌ Tolak
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Daftar Pembayaran Menunggu -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pembayaran Menunggu Verifikasi (<?php echo count($pembayaranList); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (count($pembayaranList) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pelanggan</th>
                                    <th>Band</th>
                                    <th>Tanggal Acara</th>
                                    <th>Jumlah</th>
                                    <th>Upload</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pembayaranList as $bayar): ?>
                                    <tr>
                                        <td>#<?php echo $bayar['id']; ?></td>
                                        <td><?php echo htmlspecialchars($bayar['nama_pelanggan']); ?></td>
                                        <td><?php echo htmlspecialchars($bayar['nama_band']); ?></td>
                                        <td><?php echo formatTanggal($bayar['tanggal_acara']); ?></td>
                                        <td><?php echo formatRupiah($bayar['jumlah']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($bayar['tanggal_bayar'])); ?></td>
                                        <td>
                                            <a href="?id=<?php echo $bayar['id']; ?>" class="btn btn-sm btn-primary">
                                                🔍 Verifikasi
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <div style="font-size: 5rem; margin-bottom: 1rem;">✅</div>
                            <h3 style="color: var(--success);">Semua pembayaran sudah diverifikasi!</h3>
                            <p class="text-gray">Tidak ada pembayaran yang menunggu verifikasi saat ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>