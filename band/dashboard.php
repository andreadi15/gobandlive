<?php
/**
 * FILE: band/dashboard.php
 * FUNGSI: Dashboard utama untuk band/vokalis (UPDATED)
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

// Statistik pesanan
$stmt = $db->prepare("
    SELECT COUNT(*) as total FROM pesanan 
    WHERE id_band = ? AND status IN (?, ?)
");
$stmt->execute([$bandId, STATUS_DITERIMA, STATUS_SELESAI]);
$totalPesanan = $stmt->fetch()['total'];

$stmt = $db->prepare("
    SELECT COUNT(*) as total FROM pesanan 
    WHERE id_band = ? AND status = ?
");
$stmt->execute([$bandId, STATUS_DITERIMA]);
$pesananAktif = $stmt->fetch()['total'];

$stmt = $db->prepare("
    SELECT COUNT(*) as total FROM pesanan 
    WHERE id_band = ? AND status = ?
");
$stmt->execute([$bandId, STATUS_SELESAI]);
$pesananSelesai = $stmt->fetch()['total'];

// Rating rata-rata
$stmt = $db->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_ulasan 
    FROM ulasan WHERE id_band = ?
");
$stmt->execute([$bandId]);
$ratingData = $stmt->fetch();
$avgRating = $ratingData['avg_rating'] ?? 0;
$totalUlasan = $ratingData['total_ulasan'] ?? 0;

// Pesanan terbaru yang sudah verified
$stmt = $db->prepare("
    SELECT p.*, u.nama as nama_pelanggan, u.no_hp, pm.status as status_bayar
    FROM pesanan p
    JOIN users u ON p.id_user = u.id
    JOIN pembayaran pm ON p.id = pm.id_pesanan
    WHERE p.id_band = ? AND pm.status = ? AND p.status = ?
    ORDER BY p.tanggal_acara ASC
    LIMIT 5
");
$stmt->execute([$bandId, STATUS_BAYAR_VERIFIED, STATUS_DITERIMA]);
$pesananTerbaru = $stmt->fetchAll();

// Jadwal tampil mendatang
$stmt = $db->prepare("
    SELECT jt.*, p.lokasi, u.nama as nama_pelanggan
    FROM jadwal_tampil jt
    JOIN pesanan p ON jt.id_pesanan = p.id
    JOIN users u ON p.id_user = u.id
    WHERE jt.id_band = ? AND jt.tanggal >= CURDATE() AND jt.status = ?
    ORDER BY jt.tanggal ASC
    LIMIT 5
");
$stmt->execute([$bandId, 'belum tampil']);
$jadwalMendatang = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Band - <?php echo APP_NAME; ?></title>
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
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="pesanan_masuk.php">📥 Pesanan Masuk</a></li>
                <li><a href="jadwal_tampil.php">📅 Jadwal Tampil</a></li>
                <li><a href="lihat_ulasan.php">⭐ Ulasan</a></li>
                <li><a href="edit_profil.php">👤 Edit Profil</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Dashboard Band</h1>
            <p class="text-gray mb-3">Selamat datang, <?php echo htmlspecialchars($bandData['nama_band']); ?>!</p>

            <?php showAlert(); ?>

            <!-- Statistik Cards -->
            <div class="row mb-3">
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $totalPesanan; ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $pesananAktif; ?></h3>
                        <p>Pesanan Aktif</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $pesananSelesai; ?></h3>
                        <p>Pesanan Selesai</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%); color: white;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;">
                            <?php echo $avgRating > 0 ? number_format($avgRating, 1) : '-'; ?>
                        </h3>
                        <p>Rating (<?php echo $totalUlasan; ?> ulasan)</p>
                    </div>
                </div>
            </div>

            <!-- Profil Band -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Profil Band</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-2" style="padding-right: 2rem;">
                            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); height: 120px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                🎤
                            </div>
                        </div>
                        <div class="col-10">
                            <h3><?php echo htmlspecialchars($bandData['nama_band']); ?></h3>
                            <div style="display: flex; gap: 2rem; margin-top: 1rem;">
                                <div>
                                    <p class="text-gray"><strong>Genre:</strong> <?php echo htmlspecialchars($bandData['genre']); ?></p>
                                    <p class="text-gray"><strong>Kontak:</strong> <?php echo htmlspecialchars($bandData['kontak']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray"><strong>Tarif:</strong> <span style="color: var(--primary); font-weight: bold;"><?php echo formatRupiah($bandData['tarif']); ?></span></p>
                                    <p class="text-gray">
                                        <strong>Status:</strong> 
                                        <span class="badge <?php echo $bandData['status_ketersediaan'] === 'tersedia' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst($bandData['status_ketersediaan']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <?php if ($bandData['deskripsi']): ?>
                                <p class="text-gray" style="margin-top: 1rem; line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($bandData['deskripsi'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Pesanan Terbaru -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pesanan Aktif</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($pesananTerbaru) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>ID</th>
                                            <th>Pelanggan</th>
                                            <th>Tanggal</th>
                                            <th>Lokasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($pesananTerbaru as $pesanan): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td>#<?php echo $pesanan['id']; ?></td>
                                                <td><?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?></td>
                                                <td><?php echo formatTanggal($pesanan['tanggal_acara']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($pesanan['lokasi'], 0, 20)); ?>...</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="text-center mt-2">
                                    <a href="pesanan_masuk.php" class="btn btn-outline">
                                        Lihat Semua Pesanan
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center" style="padding: 2rem;">
                                    <p class="text-gray">📋 Belum ada pesanan aktif</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Mendatang -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Jadwal Mendatang</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($jadwalMendatang) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Pelanggan</th>
                                            <th>Lokasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($jadwalMendatang as $jadwal): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo formatTanggal($jadwal['tanggal']); ?></td>
                                                <td><?php echo htmlspecialchars($jadwal['nama_pelanggan']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($jadwal['lokasi'], 0, 20)); ?>...</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="text-center mt-2">
                                    <a href="jadwal_tampil.php" class="btn btn-outline">
                                        Lihat Semua Jadwal
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center" style="padding: 2rem;">
                                    <p class="text-gray">📅 Belum ada jadwal mendatang</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>