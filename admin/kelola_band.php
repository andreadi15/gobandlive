<?php
/**
 * FILE: admin/kelola_band.php
 * FUNGSI: Mengelola data band/vokalis (CRUD)
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_ADMIN);

// Proses hapus band
if (isset($_GET['hapus'])) {
    $id = clean($_GET['hapus']);
    try {
        // Hapus user (akan cascade ke band)
        $stmt = $db->prepare("SELECT user_id FROM band WHERE id = ?");
        $stmt->execute([$id]);
        $band = $stmt->fetch();
        
        if ($band) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$band['user_id']]);
            setAlert('success', 'Band berhasil dihapus');
        }
        redirect('admin/kelola_band.php');
    } catch (Exception $e) {
        setAlert('error', 'Gagal menghapus band');
    }
}

// Proses ubah status ketersediaan
if (isset($_GET['toggle_status'])) {
    $id = clean($_GET['toggle_status']);
    try {
        $stmt = $db->prepare("
            UPDATE band 
            SET status_ketersediaan = CASE 
                WHEN status_ketersediaan = 'tersedia' THEN 'tidak tersedia'
                ELSE 'tersedia'
            END
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        setAlert('success', 'Status ketersediaan berhasil diubah');
        redirect('admin/kelola_band.php');
    } catch (Exception $e) {
        setAlert('error', 'Gagal mengubah status');
    }
}

// Ambil semua band
$search = clean($_GET['search'] ?? '');
$sql = "
    SELECT b.*, u.nama, u.email, u.no_hp,
           (SELECT AVG(rating) FROM ulasan WHERE id_band = b.id) as avg_rating,
           (SELECT COUNT(*) FROM pesanan WHERE id_band = b.id) as total_pesanan
    FROM band b
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (b.nama_band LIKE ? OR b.genre LIKE ? OR u.nama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bandList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Band - <?php echo APP_NAME; ?></title>
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
                <li><a href="kelola_band.php" class="active">🎸 Kelola Band</a></li>
                <li><a href="verifikasi_bayar.php">✅ Verifikasi Pembayaran</a></li>
                <li><a href="kelola_jadwal.php">📅 Kelola Jadwal</a></li>
                <li><a href="laporan.php">📈 Laporan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Kelola Data Band & Vokalis</h1>
            <p class="text-gray mb-3">Manage semua data band dan vokalis</p>

            <?php showAlert(); ?>

            <!-- Search & Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-10">
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="form-control" 
                                    placeholder="Cari berdasarkan nama band, genre, atau nama pemilik..."
                                    value="<?php echo htmlspecialchars($search); ?>"
                                >
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    🔍 Cari
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daftar Band -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Band & Vokalis (<?php echo count($bandList); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (count($bandList) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Band</th>
                                    <th>Genre</th>
                                    <th>Pemilik</th>
                                    <th>Tarif</th>
                                    <th>Rating</th>
                                    <th>Pesanan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bandList as $band): ?>
                                    <tr>
                                        <td><?php echo $band['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($band['nama_band']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars($band['genre']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($band['nama']); ?><br>
                                            <small class="text-gray"><?php echo htmlspecialchars($band['email']); ?></small>
                                        </td>
                                        <td><?php echo formatRupiah($band['tarif']); ?></td>
                                        <td>
                                            <?php if ($band['avg_rating']): ?>
                                                ⭐ <?php echo number_format($band['avg_rating'], 1); ?>
                                            <?php else: ?>
                                                <span class="text-gray">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $band['total_pesanan']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $band['status_ketersediaan'] === 'tersedia' ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo ucfirst($band['status_ketersediaan']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?toggle_status=<?php echo $band['id']; ?>" 
                                               class="btn btn-sm btn-warning"
                                               title="Toggle Status">
                                                🔄
                                            </a>
                                            <a href="?hapus=<?php echo $band['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Yakin ingin menghapus band ini?')"
                                               title="Hapus">
                                                🗑️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <p class="text-gray" style="font-size: 1.2rem;">Tidak ada data band</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>