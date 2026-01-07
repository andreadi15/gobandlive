<?php
/**
 * FILE: admin/kelola_jadwal.php
 * FUNGSI: Mengelola jadwal tampil band
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_ADMIN);

// Proses update status jadwal
if (isset($_POST['update_status'])) {
    $jadwalId = clean($_POST['jadwal_id']);
    $status = clean($_POST['status']);
    
    try {
        $stmt = $db->prepare("UPDATE jadwal_tampil SET status = ? WHERE id = ?");
        $stmt->execute([$status, $jadwalId]);
        
        // Jika sudah tampil, update status pesanan jadi selesai
        if ($status === 'sudah tampil') {
            $stmt = $db->prepare("
                UPDATE pesanan p
                JOIN jadwal_tampil jt ON p.id = jt.id_pesanan
                SET p.status = ?
                WHERE jt.id = ?
            ");
            $stmt->execute([STATUS_SELESAI, $jadwalId]);
        }
        
        setAlert('success', 'Status jadwal berhasil diupdate');
        redirect('admin/kelola_jadwal.php');
    } catch (Exception $e) {
        setAlert('error', 'Gagal update status');
    }
}

// Ambil semua jadwal
$tab = clean($_GET['tab'] ?? 'mendatang');
$sql = "
    SELECT jt.*, 
           p.lokasi, p.catatan,
           u.nama as nama_pelanggan, u.no_hp,
           b.nama_band
    FROM jadwal_tampil jt
    JOIN pesanan p ON jt.id_pesanan = p.id
    JOIN users u ON p.id_user = u.id
    JOIN band b ON jt.id_band = b.id
    WHERE 1=1
";

if ($tab === 'mendatang') {
    $sql .= " AND jt.tanggal >= CURDATE() AND jt.status = 'belum tampil'";
} elseif ($tab === 'selesai') {
    $sql .= " AND jt.status = 'sudah tampil'";
}

$sql .= " ORDER BY jt.tanggal " . ($tab === 'mendatang' ? 'ASC' : 'DESC');

$stmt = $db->query($sql);
$jadwalList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - <?php echo APP_NAME; ?></title>
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
                <li><a href="kelola_jadwal.php" class="active">📅 Kelola Jadwal</a></li>
                <li><a href="laporan.php">📈 Laporan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Kelola Jadwal Tampil</h1>
            <p class="text-gray mb-3">Manage jadwal tampil semua band</p>

            <?php showAlert(); ?>

            <!-- Tab Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <div style="display: flex; gap: 1rem;">
                        <a href="?tab=mendatang" class="btn <?php echo $tab === 'mendatang' ? 'btn-primary' : 'btn-outline'; ?>">
                            📅 Jadwal Mendatang
                        </a>
                        <a href="?tab=selesai" class="btn <?php echo $tab === 'selesai' ? 'btn-primary' : 'btn-outline'; ?>">
                            ✅ Jadwal Selesai
                        </a>
                    </div>
                </div>
            </div>

            <!-- Daftar Jadwal -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $tab === 'mendatang' ? 'Jadwal Mendatang' : 'Jadwal Selesai'; ?>
                        (<?php echo count($jadwalList); ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (count($jadwalList) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Band</th>
                                    <th>Pelanggan</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jadwalList as $jadwal): ?>
                                    <tr>
                                        <td>#<?php echo $jadwal['id']; ?></td>
                                        <td><?php echo formatTanggal($jadwal['tanggal']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($jadwal['nama_band']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($jadwal['nama_pelanggan']); ?><br>
                                            <small class="text-gray"><?php echo htmlspecialchars($jadwal['no_hp']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($jadwal['lokasi'], 0, 40)); ?>...</td>
                                        <td>
                                            <span class="badge <?php echo $jadwal['status'] === 'sudah tampil' ? 'badge-info' : 'badge-success'; ?>">
                                                <?php echo $jadwal['status'] === 'sudah tampil' ? 'Selesai' : 'Belum Tampil'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($jadwal['status'] === 'belum tampil'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="jadwal_id" value="<?php echo $jadwal['id']; ?>">
                                                    <input type="hidden" name="status" value="sudah tampil">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success"
                                                            onclick="return confirm('Tandai jadwal ini sebagai selesai?')">
                                                        ✅ Selesai
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <p class="text-gray" style="font-size: 1.2rem;">
                                <?php echo $tab === 'mendatang' ? '📅 Tidak ada jadwal mendatang' : '✅ Tidak ada jadwal selesai'; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>