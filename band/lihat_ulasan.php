<?php
/**
 * FILE: band/lihat_ulasan.php
 * FUNGSI: Melihat ulasan dan rating dari pelanggan
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

// Statistik rating
$stmt = $db->prepare("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_ulasan,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
    FROM ulasan WHERE id_band = ?
");
$stmt->execute([$bandId]);
$stats = $stmt->fetch();

// Ambil semua ulasan
$stmt = $db->prepare("
    SELECT u.*, 
           us.nama as nama_pelanggan,
           p.tanggal_acara
    FROM ulasan u
    JOIN users us ON u.id_user = us.id
    JOIN pesanan p ON u.id_pesanan = p.id
    WHERE u.id_band = ?
    ORDER BY u.tanggal DESC
");
$stmt->execute([$bandId]);
$ulasanList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan Pelanggan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .rating-bar-fill {
            flex: 1;
            height: 10px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }
        .rating-bar-progress {
            height: 100%;
            background: #fbbf24;
            transition: width 0.3s;
        }
    </style>
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
                <li><a href="jadwal_tampil.php">📅 Jadwal Tampil</a></li>
                <li><a href="lihat_ulasan.php" class="active">⭐ Ulasan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Ulasan & Rating</h1>
            <p class="text-gray mb-3">Lihat feedback dari pelanggan Anda</p>

            <?php showAlert(); ?>

            <!-- Statistik Rating -->
            <div class="row mb-3">
                <div class="col-4">
                    <div class="card text-center" style="background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%); color: white;">
                        <div style="padding: 2rem;">
                            <h1 style="font-size: 4rem; margin: 0;">
                                <?php echo $stats['avg_rating'] > 0 ? number_format($stats['avg_rating'], 1) : '-'; ?>
                            </h1>
                            <div style="font-size: 2rem; margin: 1rem 0;">⭐⭐⭐⭐⭐</div>
                            <p style="margin: 0; font-size: 1.1rem;">
                                <?php echo $stats['total_ulasan']; ?> Ulasan
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Distribusi Rating</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            $totalUlasan = $stats['total_ulasan'] > 0 ? $stats['total_ulasan'] : 1;
                            for ($i = 5; $i >= 1; $i--):
                                $count = $stats["rating_$i"];
                                $percentage = ($count / $totalUlasan) * 100;
                            ?>
                                <div class="rating-bar">
                                    <span style="width: 80px; font-weight: bold;"><?php echo $i; ?> Bintang</span>
                                    <div class="rating-bar-fill">
                                        <div class="rating-bar-progress" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <span style="width: 60px; text-align: right; font-weight: bold;">
                                        <?php echo $count; ?> (<?php echo number_format($percentage, 0); ?>%)
                                    </span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Ulasan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Semua Ulasan</h3>
                </div>
                <div class="card-body">
                    <?php if (count($ulasanList) > 0): ?>
                        <?php foreach ($ulasanList as $ulasan): ?>
                            <div style="border-bottom: 1px solid var(--light); padding: 1.5rem 0;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <h4 style="margin: 0;">
                                            <?php echo htmlspecialchars($ulasan['nama_pelanggan']); ?>
                                        </h4>
                                        <p class="text-gray" style="margin: 0.3rem 0 0 0; font-size: 0.9rem;">
                                            Acara: <?php echo formatTanggal($ulasan['tanggal_acara']); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="color: #fbbf24; font-size: 1.5rem; margin-bottom: 0.3rem;">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $ulasan['rating'] ? '★' : '☆';
                                            }
                                            ?>
                                        </div>
                                        <p class="text-gray" style="margin: 0; font-size: 0.85rem;">
                                            <?php echo date('d/m/Y', strtotime($ulasan['tanggal'])); ?>
                                        </p>
                                    </div>
                                </div>

                                <p style="line-height: 1.6; margin: 0; color: var(--dark);">
                                    <?php echo nl2br(htmlspecialchars($ulasan['komentar'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <div style="font-size: 5rem; margin-bottom: 1rem;">⭐</div>
                            <h3 style="color: var(--gray); margin-bottom: 1rem;">Belum ada ulasan</h3>
                            <p class="text-gray" style="line-height: 1.6;">
                                Ulasan akan muncul setelah pelanggan memberikan rating<br>
                                untuk acara yang sudah selesai
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>