<?php
/**
 * FILE: pelanggan/status_pesanan.php
 * FUNGSI: Melihat status dan daftar semua pesanan pelanggan
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_PELANGGAN);

$userId = $_SESSION['user_id'];
$tab = clean($_GET['tab'] ?? 'semua');
$detailId = clean($_GET['id'] ?? '');

// Jika ada detail ID, ambil detail pesanan
$detailPesanan = null;
if ($detailId) {
    $detailPesanan = getPesananById($db, $detailId);
    if (!$detailPesanan || $detailPesanan['id_user'] != $userId) {
        $detailPesanan = null;
    }
}
    
// Query pesanan berdasarkan tab
$sql = "
    SELECT p.*, 
           b.nama_band, b.genre, b.tarif, b.kontak,
           pm.status as status_bayar, pm.bukti, pm.metode,
           (SELECT COUNT(*) FROM ulasan WHERE id_pesanan = p.id) as has_ulasan
    FROM pesanan p
    JOIN band b ON p.id_band = b.id
    LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan
    WHERE p.id_user = ?
";

$params = [$userId];

if ($tab === 'menunggu') {
    $sql .= " AND p.status = ?";
    $params[] = STATUS_MENUNGGU;
} elseif ($tab === 'diterima') {
    $sql .= " AND p.status = ?";
    $params[] = STATUS_DITERIMA;
} elseif ($tab === 'selesai') {
    $sql .= " AND p.status = ?";
    $params[] = STATUS_SELESAI;
} elseif ($tab === 'dibatalkan') {
    $sql .= " AND p.status = ?";
    $params[] = STATUS_DIBATALKAN;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pesananList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan - <?php echo APP_NAME; ?></title>
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
                <li><a href="edit_profil.php">⚙️ Edit Profil</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Status Pesanan Saya</h1>
            <p class="text-gray mb-3">Pantau status pesanan Anda</p>

            <?php showAlert(); ?>

            <!-- Tab Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="?tab=semua" class="btn <?php echo $tab === 'semua' ? 'btn-primary' : 'btn-outline'; ?>">
                            Semua
                        </a>
                        <a href="?tab=menunggu" class="btn <?php echo $tab === 'menunggu' ? 'btn-primary' : 'btn-outline'; ?>">
                            Menunggu
                        </a>
                        <a href="?tab=diterima" class="btn <?php echo $tab === 'diterima' ? 'btn-primary' : 'btn-outline'; ?>">
                            Diterima
                        </a>
                        <a href="?tab=selesai" class="btn <?php echo $tab === 'selesai' ? 'btn-primary' : 'btn-outline'; ?>">
                            Selesai
                        </a>
                        <a href="?tab=dibatalkan" class="btn <?php echo $tab === 'dibatalkan' ? 'btn-primary' : 'btn-outline'; ?>">
                            Dibatalkan
                        </a>
                    </div>
                </div>
            </div>

            <!-- Detail Pesanan Modal -->
            <?php if ($detailPesanan): ?>
                <div class="card mb-3" style="border: 2px solid var(--primary);">
                    <div class="card-header" style="background: var(--primary); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">Detail Pesanan #<?php echo $detailPesanan['id']; ?></h3>
                            <a href="status_pesanan.php" style="color: white; text-decoration: none; font-size: 1.5rem;">✕</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <h4 style="margin-bottom: 1rem;">Informasi Pesanan</h4>
                                <table style="width: 100%;">
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0; width: 150px;"><strong>ID Pesanan:</strong></td>
                                        <td>#<?php echo $detailPesanan['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Band/Vokalis:</strong></td>
                                        <td><?php echo htmlspecialchars($detailPesanan['nama_band']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Tanggal Acara:</strong></td>
                                        <td><?php echo formatTanggal($detailPesanan['tanggal_acara']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Lokasi:</strong></td>
                                        <td><?php echo htmlspecialchars($detailPesanan['lokasi']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Tarif:</strong></td>
                                        <td style="color: var(--primary); font-weight: bold;">
                                            <?php echo formatRupiah($detailPesanan['tarif']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-gray" style="padding: 0.5rem 0;"><strong>Status:</strong></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'badge-warning';
                                            if ($detailPesanan['status'] === STATUS_DITERIMA) $badgeClass = 'badge-success';
                                            if ($detailPesanan['status'] === STATUS_DIBATALKAN) $badgeClass = 'badge-danger';
                                            if ($detailPesanan['status'] === STATUS_SELESAI) $badgeClass = 'badge-info';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo ucfirst($detailPesanan['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                                <?php if ($detailPesanan['catatan']): ?>
                                    <div style="margin-top: 1.5rem;">
                                        <strong>Catatan:</strong>
                                        <p style="background: var(--light); padding: 1rem; border-radius: var(--radius); margin-top: 0.5rem;">
                                            <?php echo nl2br(htmlspecialchars($detailPesanan['catatan'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-6">
                                <h4 style="margin-bottom: 1rem;">Status Pembayaran</h4>
                                <?php if ($detailPesanan['status_bayar']): ?>
                                    <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius);">
                                        <p><strong>Status:</strong> 
                                            <?php
                                            $paymentBadge = 'badge-warning';
                                            $paymentText = 'Menunggu';
                                            if ($detailPesanan['status_bayar'] === STATUS_BAYAR_VERIFIED) {
                                                $paymentBadge = 'badge-success';
                                                $paymentText = 'Terverifikasi';
                                            }
                                            ?>
                                            <span class="badge <?php echo $paymentBadge; ?>"><?php echo $paymentText; ?></span>
                                        </p>
                                        <?php if ($detailPesanan['metode']): ?>
                                            <p><strong>Metode:</strong> <?php echo ucfirst(str_replace('_', ' ', $detailPesanan['metode'])); ?></p>
                                        <?php endif; ?>
                                        <?php if ($detailPesanan['bukti']): ?>
                                            <p><strong>Bukti Pembayaran:</strong></p>
                                            <img src="../uploads/bukti_bayar/<?php echo htmlspecialchars($detailPesanan['bukti']); ?>" 
                                                 alt="Bukti Pembayaran" 
                                                 style="width: 100%; max-width: 300px; border-radius: var(--radius); margin-top: 0.5rem; cursor: pointer;"
                                                 onclick="window.open(this.src, '_blank')">
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        Belum ada bukti pembayaran. 
                                        <a href="upload_bukti.php?id=<?php echo $detailPesanan['id']; ?>" class="btn btn-sm btn-primary mt-2" style="display: inline-block;">
                                            Upload Bukti
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- Tombol Aksi -->
                                <div style="margin-top: 2rem;">
                                    <?php if ($detailPesanan['status'] === STATUS_SELESAI): ?>
                                        <a href="beri_ulasan.php?id=<?php echo $detailPesanan['id']; ?>" class="btn btn-success" style="width: 100%;">
                                            ⭐ Beri Ulasan
                                        </a>
                                    <?php elseif ($detailPesanan['status'] === STATUS_MENUNGGU && $detailPesanan['status_bayar'] === STATUS_BAYAR_BELUM): ?>
                                        <a href="upload_bukti.php?id=<?php echo $detailPesanan['id']; ?>" class="btn btn-primary" style="width: 100%;">
                                            📤 Upload Bukti Pembayaran
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Daftar Pesanan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Pesanan</h3>
                </div>
                <div class="card-body">
                    <?php if (count($pesananList) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Band/Vokalis</th>
                                    <th>Tanggal Acara</th>
                                    <th>Tarif</th>
                                    <th>Status Pesanan</th>
                                    <th>Status Bayar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pesananList as $pesanan): ?>
                                    <tr>
                                        <td>#<?php echo $pesanan['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pesanan['nama_band']); ?></strong><br>
                                            <small class="text-gray"><?php echo htmlspecialchars($pesanan['genre']); ?></small>
                                        </td>
                                        <td><?php echo formatTanggal($pesanan['tanggal_acara']); ?></td>
                                        <td><?php echo formatRupiah($pesanan['tarif']); ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'badge-warning';
                                            if ($pesanan['status'] === STATUS_DITERIMA) $badgeClass = 'badge-success';
                                            if ($pesanan['status'] === STATUS_DIBATALKAN) $badgeClass = 'badge-danger';
                                            if ($pesanan['status'] === STATUS_SELESAI) $badgeClass = 'badge-info';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo ucfirst($pesanan['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($pesanan['status_bayar']): ?>
                                                <?php
                                                $payBadge = 'badge-warning';
                                                if ($pesanan['status_bayar'] === STATUS_BAYAR_VERIFIED) $payBadge = 'badge-success';
                                                ?>
                                                <span class="badge <?php echo $payBadge; ?>">
                                                    <?php echo $pesanan['status_bayar'] === STATUS_BAYAR_VERIFIED ? 'Verified' : 'Menunggu'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Belum Bayar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?id=<?php echo $pesanan['id']; ?>&tab=<?php echo $tab; ?>" class="btn btn-sm btn-primary">
                                                Detail
                                            </a>
                                            <?php if ($pesanan['status'] === STATUS_SELESAI): ?>
                                                <a href="beri_ulasan.php?id=<?php echo $pesanan['id']; ?>" class="btn btn-sm btn-success">
                                                    Ulasan
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <p class="text-gray" style="font-size: 1.2rem;">📋 Tidak ada pesanan</p>
                            <a href="lihat_band.php" class="btn btn-primary mt-2">
                                Pesan Band Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>