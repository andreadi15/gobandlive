<?php
/**
 * FILE: band/jadwal_tampil.php
 * FUNGSI: Melihat jadwal tampil band/vokalis
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

// Query jadwal tampil
$stmt = $db->prepare("
    SELECT jt.*, 
           p.lokasi, p.catatan,
           u.nama as nama_pelanggan, u.no_hp
    FROM jadwal_tampil jt
    JOIN pesanan p ON jt.id_pesanan = p.id
    JOIN users u ON p.id_user = u.id
    WHERE jt.id_band = ?
    ORDER BY jt.tanggal DESC
");
$stmt->execute([$bandId]);
$jadwalList = $stmt->fetchAll();

// Pisahkan jadwal berdasarkan status
$jadwalMendatang = [];
$jadwalSelesai = [];

foreach ($jadwalList as $jadwal) {
    if ($jadwal['status'] === 'belum tampil' && strtotime($jadwal['tanggal']) >= strtotime(date('Y-m-d'))) {
        $jadwalMendatang[] = $jadwal;
    } else {
        $jadwalSelesai[] = $jadwal;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Tampil - <?php echo APP_NAME; ?></title>
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
                <li><a href="pesanan_masuk.php">📥 Pesanan Masuk</a></li>
                <li><a href="jadwal_tampil.php" class="active">📅 Jadwal Tampil</a></li>
                <li><a href="lihat_ulasan.php">⭐ Ulasan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Jadwal Tampil</h1>
            <p class="text-gray mb-3">Daftar jadwal penampilan Anda</p>

            <?php showAlert(); ?>

            <!-- Jadwal Mendatang -->
            <div class="card mb-3">
                <div class="card-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h3 class="card-title">📅 Jadwal Mendatang</h3>
                </div>
                <div class="card-body">
                    <?php if (count($jadwalMendatang) > 0): ?>
                        <div class="row">
                            <?php foreach ($jadwalMendatang as $jadwal): ?>
                                <div class="col-6">
                                    <div class="card" style="border-left: 4px solid var(--primary);">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h4 style="margin: 0; color: var(--primary);">
                                                    <?php echo formatTanggal($jadwal['tanggal']); ?>
                                                </h4>
                                                <p class="text-gray" style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                                                    ID Jadwal: #<?php echo $jadwal['id']; ?>
                                                </p>
                                            </div>
                                            <span class="badge badge-success">Mendatang</span>
                                        </div>

                                        <table style="width: 100%;">
                                            <tr>
                                                <td class="text-gray" style="padding: 0.3rem 0; width: 100px;"><strong>Pelanggan:</strong></td>
                                                <td><?php echo htmlspecialchars($jadwal['nama_pelanggan']); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-gray" style="padding: 0.3rem 0;"><strong>Kontak:</strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($jadwal['no_hp']); ?>
                                                    <a href="tel:<?php echo $jadwal['no_hp']; ?>" class="btn btn-sm btn-success" style="margin-left: 0.5rem; padding: 0.2rem 0.5rem;">
                                                        📞
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-gray" style="padding: 0.3rem 0;"><strong>Lokasi:</strong></td>
                                                <td><?php echo htmlspecialchars($jadwal['lokasi']); ?></td>
                                            </tr>
                                        </table>

                                        <?php if ($jadwal['catatan']): ?>
                                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light);">
                                                <p class="text-gray" style="font-size: 0.9rem; margin: 0;"><strong>Catatan:</strong></p>
                                                <p style="font-size: 0.9rem; margin: 0.5rem 0 0 0; line-height: 1.5;">
                                                    <?php echo nl2br(htmlspecialchars($jadwal['catatan'])); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>

                                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light); text-align: center;">
                                            <p class="text-gray" style="font-size: 0.9rem; margin: 0;">
                                                <?php
                                                $selisih = floor((strtotime($jadwal['tanggal']) - strtotime(date('Y-m-d'))) / (60*60*24));
                                                if ($selisih == 0) {
                                                    echo "🔥 <strong>Hari ini!</strong>";
                                                } elseif ($selisih == 1) {
                                                    echo "⏰ <strong>Besok</strong>";
                                                } else {
                                                    echo "📆 <strong>$selisih hari lagi</strong>";
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 2rem;">
                            <p class="text-gray" style="font-size: 1.2rem;">📅 Tidak ada jadwal mendatang</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Riwayat Jadwal -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📜 Riwayat Jadwal</h3>
                </div>
                <div class="card-body">
                    <?php if (count($jadwalSelesai) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Pelanggan</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jadwalSelesai as $jadwal): ?>
                                    <tr>
                                        <td>#<?php echo $jadwal['id']; ?></td>
                                        <td><?php echo formatTanggal($jadwal['tanggal']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($jadwal['nama_pelanggan']); ?></strong><br>
                                            <small class="text-gray"><?php echo htmlspecialchars($jadwal['no_hp']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($jadwal['lokasi'], 0, 40)); ?>...</td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $jadwal['status'] === 'sudah tampil' ? 'Selesai' : 'Lewat'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 2rem;">
                            <p class="text-gray">Belum ada riwayat jadwal</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>