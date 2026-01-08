<?php
/**
 * FILE: admin/dashboard.php
 * FUNGSI: Dashboard utama untuk admin (UPDATED)
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_ADMIN);

// Statistik keseluruhan
$totalPelanggan = countPelanggan($db);
$totalBand = countBand($db);
$totalPesanan = countPesanan($db);
$totalPendapatan = countPendapatan($db, STATUS_BAYAR_VERIFIED);

// Pesanan menunggu verifikasi
$stmt = $db->prepare("
    SELECT COUNT(*) as total FROM pembayaran 
    WHERE status = ?
");
$stmt->execute([STATUS_BAYAR_MENUNGGU]);
$menungguVerifikasi = $stmt->fetch()['total'];

// Statistik per status
$pesananMenunggu = countPesanan($db, STATUS_MENUNGGU);
$pesananDiterima = countPesanan($db, STATUS_DITERIMA);
$pesananSelesai = countPesanan($db, STATUS_SELESAI);

// Pesanan terbaru
$stmt = $db->query("
    SELECT p.*, 
           u.nama as nama_pelanggan,
           b.nama_band,
           pm.status as status_bayar
    FROM pesanan p
    JOIN users u ON p.id_user = u.id
    JOIN band b ON p.id_band = b.id
    LEFT JOIN pembayaran pm ON p.id = pm.id_pesanan
    ORDER BY p.created_at DESC
    LIMIT 5
");
$pesananTerbaru = $stmt->fetchAll();

// Pembayaran menunggu verifikasi
$stmt = $db->prepare("
    SELECT pm.*, p.id as pesanan_id, p.tanggal_acara,
           u.nama as nama_pelanggan,
           b.nama_band
    FROM pembayaran pm
    JOIN pesanan p ON pm.id_pesanan = p.id
    JOIN users u ON p.id_user = u.id
    JOIN band b ON p.id_band = b.id
    WHERE pm.status = ?
    ORDER BY pm.tanggal_bayar DESC
    LIMIT 5
");
$stmt->execute([STATUS_BAYAR_MENUNGGU]);
$pembayaranMenunggu = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo APP_NAME; ?></title>
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
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="kelola_pelanggan.php">👥 Kelola Pelanggan</a></li>
                <li><a href="kelola_band.php">🎸 Kelola Band</a></li>
                <li><a href="verifikasi_bayar.php">✅ Verifikasi Pembayaran</a></li>
                <li><a href="kelola_jadwal.php">📅 Kelola Jadwal</a></li>
                <li><a href="laporan.php">📈 Laporan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Dashboard Admin</h1>
            <p class="text-gray mb-3">Selamat datang di panel administrasi GOBANDLIVE</p>

            <?php showAlert(); ?>

            <!-- Alert Notifikasi -->
            <?php if ($menungguVerifikasi > 0): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Perhatian!</strong> Ada <?php echo $menungguVerifikasi; ?> pembayaran menunggu verifikasi.
                    <a href="verifikasi_bayar.php" style="color: var(--primary); font-weight: bold; margin-left: 1rem;">
                        Verifikasi Sekarang →
                    </a>
                </div>
            <?php endif; ?>

            <!-- Statistik Cards -->
            <div class="row mb-3">
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; min-height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $totalPelanggan; ?></h3>
                        <p style="margin: 0;">Total Pelanggan</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; min-height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $totalBand; ?></h3>
                        <p style="margin: 0;">Total Band</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; min-height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $totalPesanan; ?></h3>
                        <p style="margin: 0;">Total Pesanan</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; min-height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem; word-break: break-word; text-align: center;"><?php echo formatRupiah($totalPendapatan); ?></h3>
                        <p style="margin: 0;">Total Pendapatan</p>
                    </div>
                </div>
            </div>

            <!-- Status Pesanan -->
            <div class="row mb-3">
                <div class="col-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 style="font-size: 2rem; color: var(--warning);"><?php echo $pesananMenunggu; ?></h3>
                            <p class="text-gray">Pesanan Menunggu</p>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 style="font-size: 2rem; color: var(--success);"><?php echo $pesananDiterima; ?></h3>
                            <p class="text-gray">Pesanan Diterima</p>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 style="font-size: 2rem; color: var(--info);"><?php echo $pesananSelesai; ?></h3>
                            <p class="text-gray">Pesanan Selesai</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Pembayaran Menunggu Verifikasi -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header" style="background: var(--warning); color: white; border-radius: var(--radius) var(--radius) 0 0;">
                            <h3 class="card-title">⏳ Menunggu Verifikasi (<?php echo count($pembayaranMenunggu); ?>)</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($pembayaranMenunggu) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Pesanan</th>
                                            <th>Pelanggan</th>
                                            <th>Band</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($pembayaranMenunggu as $bayar): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td>#<?php echo $bayar['pesanan_id']; ?></td>
                                                <td><?php echo htmlspecialchars($bayar['nama_pelanggan']); ?></td>
                                                <td><?php echo htmlspecialchars($bayar['nama_band']); ?></td>
                                                <td>
                                                    <a href="verifikasi_bayar.php?id=<?php echo $bayar['id']; ?>" class="btn btn-sm btn-primary">
                                                        Verifikasi
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="text-center mt-2">
                                    <a href="verifikasi_bayar.php" class="btn btn-warning">
                                        Lihat Semua
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center" style="padding: 2rem;">
                                    <p class="text-gray">✅ Tidak ada pembayaran menunggu verifikasi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Pesanan Terbaru -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📋 Pesanan Terbaru</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($pesananTerbaru) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>ID</th>
                                            <th>Pelanggan</th>
                                            <th>Band</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($pesananTerbaru as $pesanan): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td>#<?php echo $pesanan['id']; ?></td>
                                                <td><?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?></td>
                                                <td><?php echo htmlspecialchars($pesanan['nama_band']); ?></td>
                                                <td>
                                                    <?php
                                                    $badgeClass = 'badge-warning';
                                                    if ($pesanan['status'] === STATUS_DITERIMA) $badgeClass = 'badge-success';
                                                    if ($pesanan['status'] === STATUS_SELESAI) $badgeClass = 'badge-info';
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst($pesanan['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-center" style="padding: 2rem;">
                                    <p class="text-gray">Belum ada pesanan</p>
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