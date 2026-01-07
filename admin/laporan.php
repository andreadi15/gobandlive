<?php
/**
 * FILE: admin/laporan.php
 * FUNGSI: Membuat dan melihat laporan transaksi
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_ADMIN);

// Proses generate laporan
if (isset($_POST['generate'])) {
    $periodeAwal = clean($_POST['periode_awal']);
    $periodeAkhir = clean($_POST['periode_akhir']);
    
    if (empty($periodeAwal) || empty($periodeAkhir)) {
        setAlert('error', 'Periode harus diisi');
    } elseif (strtotime($periodeAwal) > strtotime($periodeAkhir)) {
        setAlert('error', 'Periode awal tidak boleh lebih dari periode akhir');
    } else {
        try {
            // Hitung total transaksi dan pendapatan
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_transaksi, SUM(pm.jumlah) as total_pendapatan
                FROM pembayaran pm
                JOIN pesanan p ON pm.id_pesanan = p.id
                WHERE pm.status = ? AND p.tanggal_acara BETWEEN ? AND ?
            ");
            $stmt->execute([STATUS_BAYAR_VERIFIED, $periodeAwal, $periodeAkhir]);
            $result = $stmt->fetch();
            
            // Simpan laporan
            $stmt = $db->prepare("
                INSERT INTO laporan (periode_awal, periode_akhir, total_transaksi, total_pendapatan, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $periodeAwal,
                $periodeAkhir,
                $result['total_transaksi'],
                $result['total_pendapatan'] ?? 0,
                $_SESSION['user_id']
            ]);
            
            setAlert('success', 'Laporan berhasil dibuat!');
            redirect('admin/laporan.php');
            
        } catch (Exception $e) {
            setAlert('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }
}

// Filter periode untuk statistik
$filterAwal = clean($_GET['filter_awal'] ?? date('Y-m-01'));
$filterAkhir = clean($_GET['filter_akhir'] ?? date('Y-m-t'));

// Statistik periode
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT p.id) as total_pesanan,
        COUNT(DISTINCT pm.id) as total_pembayaran,
        SUM(pm.jumlah) as total_pendapatan,
        COUNT(DISTINCT p.id_user) as total_pelanggan_aktif,
        COUNT(DISTINCT p.id_band) as total_band_aktif
    FROM pesanan p
    LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan AND pm.status = ?
    WHERE p.tanggal_acara BETWEEN ? AND ?
");
$stmt->execute([STATUS_BAYAR_VERIFIED, $filterAwal, $filterAkhir]);
$stats = $stmt->fetch();

// Top 5 Band
$stmt = $db->prepare("
    SELECT b.nama_band, COUNT(p.id) as total_pesanan, SUM(pm.jumlah) as total_pendapatan
    FROM band b
    JOIN pesanan p ON b.id = p.id_band
    LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan AND pm.status = ?
    WHERE p.tanggal_acara BETWEEN ? AND ?
    GROUP BY b.id
    ORDER BY total_pesanan DESC
    LIMIT 5
");
$stmt->execute([STATUS_BAYAR_VERIFIED, $filterAwal, $filterAkhir]);
$topBands = $stmt->fetchAll();

// Riwayat laporan
$stmt = $db->prepare("
    SELECT l.*, u.nama as created_by_name
    FROM laporan l
    JOIN users u ON l.created_by = u.id
    ORDER BY l.created_at DESC
    LIMIT 10
");
$stmt->execute();
$laporanList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - <?php echo APP_NAME; ?></title>
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
                <li><a href="verifikasi_bayar.php">✅ Verifikasi Pembayaran</a></li>
                <li><a href="kelola_jadwal.php">📅 Kelola Jadwal</a></li>
                <li><a href="laporan.php" class="active">📈 Laporan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Laporan Transaksi</h1>
            <p class="text-gray mb-3">Generate dan lihat laporan transaksi</p>

            <?php showAlert(); ?>

            <!-- Form Generate Laporan -->
            <div class="card mb-3">
                <div class="card-header" style="background: var(--primary); color: white;">
                    <h3 class="card-title">📝 Buat Laporan Baru</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Periode Awal</label>
                                    <input type="date" name="periode_awal" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Periode Akhir</label>
                                    <input type="date" name="periode_akhir" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" name="generate" class="btn btn-primary" style="width: 100%;">
                                        Generate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filter Statistik -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">📊 Statistik Periode</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row mb-3">
                            <div class="col-4">
                                <input type="date" name="filter_awal" class="form-control" value="<?php echo $filterAwal; ?>">
                            </div>
                            <div class="col-4">
                                <input type="date" name="filter_akhir" class="form-control" value="<?php echo $filterAkhir; ?>">
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    🔍 Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Statistik Cards -->
                    <div class="row">
                        <div class="col-3">
                            <div class="card text-center" style="background: var(--light);">
                                <h3 style="font-size: 2rem; color: var(--primary);"><?php echo $stats['total_pesanan'] ?? 0; ?></h3>
                                <p class="text-gray">Total Pesanan</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card text-center" style="background: var(--light);">
                                <h3 style="font-size: 2rem; color: var(--success);"><?php echo $stats['total_pembayaran'] ?? 0; ?></h3>
                                <p class="text-gray">Pembayaran Verified</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card text-center" style="background: var(--light);">
                                <h3 style="font-size: 1.3rem; color: var(--primary);"><?php echo formatRupiah($stats['total_pendapatan'] ?? 0); ?></h3>
                                <p class="text-gray">Total Pendapatan</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card text-center" style="background: var(--light);">
                                <h3 style="font-size: 2rem; color: var(--info);"><?php echo $stats['total_band_aktif'] ?? 0; ?></h3>
                                <p class="text-gray">Band Aktif</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <!-- Top Band -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🏆 Top 5 Band</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($topBands) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Band</th>
                                            <th>Pesanan</th>
                                            <th>Pendapatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rank = 1; foreach ($topBands as $band): ?>
                                            <tr>
                                                <td><strong>#<?php echo $rank++; ?></strong></td>
                                                <td><?php echo htmlspecialchars($band['nama_band']); ?></td>
                                                <td><?php echo $band['total_pesanan']; ?></td>
                                                <td><?php echo formatRupiah($band['total_pendapatan'] ?? 0); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray text-center">Tidak ada data untuk periode ini</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Laporan -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📜 Riwayat Laporan</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($laporanList) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Periode</th>
                                            <th>Total</th>
                                            <th>Pendapatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($laporanList as $lap): ?>
                                            <tr>
                                                <td>#<?php echo $lap['id']; ?></td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($lap['periode_awal'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($lap['periode_akhir'])); ?>
                                                </td>
                                                <td><?php echo $lap['total_transaksi']; ?></td>
                                                <td><?php echo formatRupiah($lap['total_pendapatan']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray text-center">Belum ada laporan</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>