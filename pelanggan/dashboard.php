<?php
/**
 * FILE: pelanggan/dashboard.php
 * FUNGSI: Dashboard utama untuk pelanggan
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Proteksi halaman - hanya pelanggan yang bisa akses
requireRole(ROLE_PELANGGAN);

$userId = $_SESSION['user_id'];

// Ambil data statistik pesanan pelanggan
$stmt = $db->prepare("SELECT COUNT(*) as total FROM pesanan WHERE id_user = ?");
$stmt->execute([$userId]);
$totalPesanan = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM pesanan WHERE id_user = ? AND status = ?");
$stmt->execute([$userId, STATUS_MENUNGGU]);
$pesananMenunggu = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM pesanan WHERE id_user = ? AND status = ?");
$stmt->execute([$userId, STATUS_DITERIMA]);
$pesananDiterima = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM pesanan WHERE id_user = ? AND status = ?");
$stmt->execute([$userId, STATUS_SELESAI]);
$pesananSelesai = $stmt->fetch()['total'];

// Ambil pesanan terbaru
$stmt = $db->prepare("
    SELECT p.*, b.nama_band, b.genre, b.tarif, pm.status as status_bayar
    FROM pesanan p
    JOIN band b ON p.id_band = b.id
    LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan
    WHERE p.id_user = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$pesananTerbaru = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pelanggan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
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
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="lihat_band.php">🎸 Lihat Band</a></li>
                <li><a href="status_pesanan.php">📋 Pesanan Saya</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h1>Dashboard Pelanggan</h1>
            <p class="text-gray mb-3">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</p>

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
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $pesananMenunggu; ?></h3>
                        <p>Menunggu Verifikasi</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $pesananDiterima; ?></h3>
                        <p>Pesanan Diterima</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $pesananSelesai; ?></h3>
                        <p>Pesanan Selesai</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Menu Cepat</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4">
                            <a href="lihat_band.php" class="btn btn-primary" style="width: 100%;">
                                🎸 Lihat Daftar Band
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="status_pesanan.php" class="btn btn-info" style="width: 100%;">
                                📋 Cek Status Pesanan
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="status_pesanan.php?tab=selesai" class="btn btn-success" style="width: 100%;">
                                ⭐ Beri Ulasan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pesanan Terbaru -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pesanan Terbaru</h3>
                </div>
                <div class="card-body">
                    <?php if (count($pesananTerbaru) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Band/Vokalis</th>
                                    <th>Tanggal Acara</th>
                                    <th>Lokasi</th>
                                    <th>Tarif</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pesananTerbaru as $pesanan): ?>
                                    <tr>
                                        <td>#<?php echo $pesanan['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pesanan['nama_band']); ?></strong><br>
                                            <small class="text-gray"><?php echo htmlspecialchars($pesanan['genre']); ?></small>
                                        </td>
                                        <td><?php echo formatTanggal($pesanan['tanggal_acara']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($pesanan['lokasi'], 0, 30)); ?>...</td>
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
                                            <a href="status_pesanan.php?id=<?php echo $pesanan['id']; ?>" class="btn btn-sm btn-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-center mt-2">
                            <a href="status_pesanan.php" class="btn btn-outline">
                                Lihat Semua Pesanan
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 2rem;">
                            <p class="text-gray" style="font-size: 1.2rem;">📋 Belum ada pesanan</p>
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