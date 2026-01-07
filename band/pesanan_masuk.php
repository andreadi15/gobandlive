<?php
/**
 * FILE: band/pesanan_masuk.php
 * FUNGSI: Melihat daftar pesanan yang masuk untuk band
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_BAND);

$userId = $_SESSION['user_id'];

// Ambil data band
$stmt = $db->prepare("SELECT * FROM band WHERE user_id = ?");
$stmt->execute([$userId]);
$bandData = $stmt->fetch();

if (!$bandData) {
    setAlert('error', 'Data band tidak ditemukan');
    redirect('auth/logout.php');
}

$bandId = $bandData['id'];
$tab = clean($_GET['tab'] ?? 'diterima');
$detailId = clean($_GET['id'] ?? '');

// Jika ada detail ID, ambil detail pesanan
$detailPesanan = null;
if ($detailId) {
    $stmt = $db->prepare("
        SELECT p.*, 
               u.nama as nama_pelanggan, u.email, u.no_hp, u.alamat,
               pm.status as status_bayar, pm.metode, pm.bukti
        FROM pesanan p
        JOIN users u ON p.id_user = u.id
        LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan
        WHERE p.id = ? AND p.id_band = ?
    ");
    $stmt->execute([$detailId, $bandId]);
    $detailPesanan = $stmt->fetch();
}

// Query pesanan berdasarkan tab
$sql = "
    SELECT p.*, 
           u.nama as nama_pelanggan, u.no_hp,
           pm.status as status_bayar
    FROM pesanan p
    JOIN users u ON p.id_user = u.id
    LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan
    WHERE p.id_band = ? AND pm.status = ?
";

$params = [$bandId, STATUS_BAYAR_VERIFIED];

if ($tab === 'diterima') {
    $sql .= " AND p.status = ?";
    $params[] = STATUS_DITERIMA;
} elseif ($tab === 'selesai') {
    $sql .= " AND p.status = ?";
    $params[] = STATUS_SELESAI;
}

$sql .= " ORDER BY p.tanggal_acara ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pesananList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - <?php echo APP_NAME; ?></title>
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
                <li><a href="pesanan_masuk.php" class="active">📥 Pesanan Masuk</a></li>
                <li><a href="jadwal_tampil.php">📅 Jadwal Tampil</a></li>
                <li><a href="lihat_ulasan.php">⭐ Ulasan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Pesanan Masuk</h1>
            <p class="text-gray mb-3">Daftar pesanan yang sudah diverifikasi admin</p>

            <?php showAlert(); ?>

            <!-- Tab Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <div style="display: flex; gap: 1rem;">
                        <a href="?tab=diterima" class="btn <?php echo $tab === 'diterima' ? 'btn-primary' : 'btn-outline'; ?>">
                            Pesanan Aktif
                        </a>
                        <a href="?tab=selesai" class="btn <?php echo $tab === 'selesai' ? 'btn-primary' : 'btn-outline'; ?>">
                            Pesanan Selesai
                        </a>
                    </div>
                </div>
            </div>

            <!-- Detail Pesanan -->
            <?php if ($detailPesanan): ?>
                <div class="card mb-3" style="border: 2px solid var(--primary);">
                    <div class="card-header" style="background: var(--primary); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">Detail Pesanan #<?php echo $detailPesanan['id']; ?></h3>
                            <a href="pesanan_masuk.php?tab=<?php echo $tab; ?>" style="color: white; text-decoration: none; font-size: 1.5rem;">✕</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <h4 style="margin-bottom: 1rem;">Informasi Pelanggan</h4>
                                <table style="width: 100%;">
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0; width: 150px;"><strong>Nama:</strong></td>
                                        <td><?php echo htmlspecialchars($detailPesanan['nama_pelanggan']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($detailPesanan['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>No. HP:</strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($detailPesanan['no_hp']); ?>
                                            <a href="tel:<?php echo $detailPesanan['no_hp']; ?>" class="btn btn-sm btn-success" style="margin-left: 0.5rem;">
                                                📞 Hubungi
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Alamat:</strong></td>
                                        <td><?php echo htmlspecialchars($detailPesanan['alamat']); ?></td>
                                    </tr>
                                </table>

                                <h4 style="margin-top: 2rem; margin-bottom: 1rem;">Detail Acara</h4>
                                <table style="width: 100%;">
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0; width: 150px;"><strong>Tanggal Acara:</strong></td>
                                        <td><?php echo formatTanggal($detailPesanan['tanggal_acara']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Lokasi:</strong></td>
                                        <td><?php echo nl2br(htmlspecialchars($detailPesanan['lokasi'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Tarif:</strong></td>
                                        <td style="color: var(--primary); font-weight: bold; font-size: 1.2rem;">
                                            <?php echo formatRupiah($bandData['tarif']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge <?php echo $detailPesanan['status'] === STATUS_SELESAI ? 'badge-info' : 'badge-success'; ?>">
                                                <?php echo ucfirst($detailPesanan['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                                <?php if ($detailPesanan['catatan']): ?>
                                    <div style="margin-top: 1.5rem;">
                                        <strong>Catatan dari Pelanggan:</strong>
                                        <p style="background: var(--light); padding: 1rem; border-radius: var(--radius); margin-top: 0.5rem; line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($detailPesanan['catatan'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-6">
                                <h4 style="margin-bottom: 1rem;">Bukti Pembayaran</h4>
                                <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius);">
                                    <p><strong>Status:</strong> <span class="badge badge-success">Terverifikasi</span></p>
                                    <?php if ($detailPesanan['metode']): ?>
                                        <p><strong>Metode:</strong> <?php echo ucfirst(str_replace('_', ' ', $detailPesanan['metode'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($detailPesanan['bukti']): ?>
                                        <p style="margin-top: 1rem;"><strong>Bukti Transfer:</strong></p>
                                        <img src="../uploads/bukti_bayar/<?php echo htmlspecialchars($detailPesanan['bukti']); ?>" 
                                             alt="Bukti Pembayaran" 
                                             style="width: 100%; max-width: 400px; border-radius: var(--radius); margin-top: 0.5rem; cursor: pointer;"
                                             onclick="window.open(this.src, '_blank')">
                                        <p class="text-gray" style="font-size: 0.9rem; margin-top: 0.5rem;">Klik untuk memperbesar</p>
                                    <?php endif; ?>
                                </div>

                                <div class="alert alert-info" style="margin-top: 1.5rem;">
                                    <strong>📌 Informasi:</strong>
                                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                                        <li>Pesanan ini sudah diverifikasi oleh admin</li>
                                        <li>Hubungi pelanggan untuk konfirmasi detail acara</li>
                                        <li>Pastikan Anda hadir tepat waktu</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Daftar Pesanan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $tab === 'diterima' ? 'Pesanan Aktif' : 'Pesanan Selesai'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (count($pesananList) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal Acara</th>
                                    <th>Lokasi</th>
                                    <th>Tarif</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pesananList as $pesanan): ?>
                                    <tr>
                                        <td>#<?php echo $pesanan['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?></strong><br>
                                            <small class="text-gray"><?php echo htmlspecialchars($pesanan['no_hp']); ?></small>
                                        </td>
                                        <td><?php echo formatTanggal($pesanan['tanggal_acara']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($pesanan['lokasi'], 0, 30)); ?>...</td>
                                        <td><?php echo formatRupiah($bandData['tarif']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $pesanan['status'] === STATUS_SELESAI ? 'badge-info' : 'badge-success'; ?>">
                                                <?php echo ucfirst($pesanan['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?id=<?php echo $pesanan['id']; ?>&tab=<?php echo $tab; ?>" class="btn btn-sm btn-primary">
                                                Detail
                                            </a>
                                            <a href="tel:<?php echo $pesanan['no_hp']; ?>" class="btn btn-sm btn-success">
                                                📞
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <p class="text-gray" style="font-size: 1.2rem;">
                                📋 Tidak ada pesanan <?php echo $tab === 'diterima' ? 'aktif' : 'selesai'; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>