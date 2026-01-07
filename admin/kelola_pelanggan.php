<?php
/**
 * FILE: admin/kelola_pelanggan.php
 * FUNGSI: Mengelola data pelanggan (CRUD)
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_ADMIN);

$errors = [];

// Proses hapus pelanggan
if (isset($_GET['hapus'])) {
    $id = clean($_GET['hapus']);
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = ?");
        $stmt->execute([$id, ROLE_PELANGGAN]);
        setAlert('success', 'Pelanggan berhasil dihapus');
        redirect('admin/kelola_pelanggan.php');
    } catch (Exception $e) {
        setAlert('error', 'Gagal menghapus pelanggan');
    }
}

// Ambil semua pelanggan
$search = clean($_GET['search'] ?? '');
$sql = "SELECT * FROM users WHERE role = ?";
$params = [ROLE_PELANGGAN];

if ($search) {
    $sql .= " AND (nama LIKE ? OR email LIKE ? OR username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pelangganList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan - <?php echo APP_NAME; ?></title>
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
                <li><a href="kelola_pelanggan.php" class="active">👥 Kelola Pelanggan</a></li>
                <li><a href="kelola_band.php">🎸 Kelola Band</a></li>
                <li><a href="verifikasi_bayar.php">✅ Verifikasi Pembayaran</a></li>
                <li><a href="kelola_jadwal.php">📅 Kelola Jadwal</a></li>
                <li><a href="laporan.php">📈 Laporan</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Kelola Data Pelanggan</h1>
            <p class="text-gray mb-3">Manage semua data pelanggan</p>

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
                                    placeholder="Cari berdasarkan nama, email, atau username..."
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

            <!-- Daftar Pelanggan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Pelanggan (<?php echo count($pelangganList); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (count($pelangganList) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>No. HP</th>
                                    <th>Username</th>
                                    <th>Terdaftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pelangganList as $pelanggan): ?>
                                    <tr>
                                        <td><?php echo $pelanggan['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($pelanggan['nama']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($pelanggan['email']); ?></td>
                                        <td><?php echo htmlspecialchars($pelanggan['no_hp']); ?></td>
                                        <td><?php echo htmlspecialchars($pelanggan['username']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($pelanggan['created_at'])); ?></td>
                                        <td>
                                            <a href="?hapus=<?php echo $pelanggan['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Yakin ingin menghapus pelanggan ini?')">
                                                🗑️ Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 3rem;">
                            <p class="text-gray" style="font-size: 1.2rem;">Tidak ada data pelanggan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>