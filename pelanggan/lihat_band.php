<?php
/**
 * FILE: pelanggan/lihat_band.php
 * FUNGSI: Menampilkan daftar band/vokalis yang tersedia (UPDATED)
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_PELANGGAN);

// Filter pencarian
$search = clean($_GET['search'] ?? '');
$genre = clean($_GET['genre'] ?? '');

// Query band dengan filter
$sql = "
    SELECT b.*, 
           (SELECT AVG(rating) FROM ulasan WHERE id_band = b.id) as avg_rating,
           (SELECT COUNT(*) FROM ulasan WHERE id_band = b.id) as total_ulasan
    FROM band b 
    WHERE b.status_ketersediaan = 'tersedia'
";

$params = [];

if ($search) {
    $sql .= " AND (b.nama_band LIKE ? OR b.genre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($genre) {
    $sql .= " AND b.genre = ?";
    $params[] = $genre;
}

$sql .= " ORDER BY avg_rating DESC, b.nama_band ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bands = $stmt->fetchAll();

// Ambil daftar genre untuk filter
$genreStmt = $db->query("SELECT DISTINCT genre FROM band ORDER BY genre");
$genres = $genreStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Band - <?php echo APP_NAME; ?></title>
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
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="lihat_band.php" class="active">🎸 Lihat Band</a></li>
                <li><a href="status_pesanan.php">📋 Pesanan Saya</a></li>
                <li><a href="edit_profil.php">⚙️ Edit Profil</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Daftar Band & Vokalis</h1>
            <p class="text-gray mb-3">Pilih band atau vokalis untuk acara Anda</p>

            <?php showAlert(); ?>

            <!-- Filter & Search -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-6">
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="form-control" 
                                    placeholder="Cari berdasarkan nama atau genre..."
                                    value="<?php echo htmlspecialchars($search); ?>"
                                >
                            </div>
                            <div class="col-4">
                                <select name="genre" class="form-select">
                                    <option value="">Semua Genre</option>
                                    <?php foreach ($genres as $g): ?>
                                        <option value="<?php echo htmlspecialchars($g['genre']); ?>" 
                                                <?php echo $genre === $g['genre'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($g['genre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
            <div class="row">
                <?php if (count($bands) > 0): ?>
                    <?php foreach ($bands as $band): ?>
                        <div class="col-4">
                            <div class="card" style="min-height: 600px; display: flex; flex-direction: column;">
                                <!-- Icon Band -->
                                <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); height: 180px; border-radius: var(--radius); margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem; flex-shrink: 0;">
                                    🎤
                                </div>

                                <h3 style="margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($band['nama_band']); ?>
                                </h3>

                                <p class="text-gray" style="margin-bottom: 0.5rem;">
                                    <strong>Genre:</strong> 
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($band['genre']); ?>
                                    </span>
                                </p>

                                <p class="text-gray" style="margin-bottom: 0.5rem;">
                                    <strong>Tarif:</strong> 
                                    <span style="color: var(--primary); font-weight: bold; font-size: 1.2rem;">
                                        <?php echo formatRupiah($band['tarif']); ?>
                                    </span>
                                </p>

                                <p class="text-gray" style="margin-bottom: 0.5rem;">
                                    <strong>Kontak:</strong> <?php echo htmlspecialchars($band['kontak']); ?>
                                </p>

                                <!-- Deskripsi dengan Truncate -->
                                <?php if ($band['deskripsi']): ?>
                                    <div style="margin-bottom: 1rem; flex-grow: 1;">
                                        <p class="text-gray" style="font-size: 0.9rem; line-height: 1.5; margin: 0;">
                                            <span id="desc-short-<?php echo $band['id']; ?>">
                                                <?php 
                                                $desc = htmlspecialchars($band['deskripsi']);
                                                if (strlen($desc) > 100) {
                                                    echo substr($desc, 0, 100) . '...';
                                                } else {
                                                    echo $desc;
                                                }
                                                ?>
                                            </span>
                                            <?php if (strlen($band['deskripsi']) > 100): ?>
                                                <span id="desc-full-<?php echo $band['id']; ?>" style="display: none;">
                                                    <?php echo nl2br(htmlspecialchars($band['deskripsi'])); ?>
                                                </span>
                                                <a href="javascript:void(0)" 
                                                   onclick="toggleDesc(<?php echo $band['id']; ?>)"
                                                   id="desc-toggle-<?php echo $band['id']; ?>"
                                                   style="color: var(--primary); font-weight: 500; cursor: pointer; display: block; margin-top: 0.5rem;">
                                                    Lihat selengkapnya
                                                </a>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-bottom: 1rem; flex-grow: 1;"></div>
                                <?php endif; ?>

                                <!-- Rating -->
                                <?php if ($band['avg_rating']): ?>
                                    <p style="margin-bottom: 1rem;">
                                        <span style="color: #fbbf24; font-size: 1.2rem;">
                                            ⭐ <?php echo number_format($band['avg_rating'], 1); ?>
                                        </span>
                                        <span class="text-gray" style="font-size: 0.9rem;">
                                            (<?php echo $band['total_ulasan']; ?> ulasan)
                                        </span>
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray" style="margin-bottom: 1rem; font-size: 0.9rem;">
                                        Belum ada ulasan
                                    </p>
                                <?php endif; ?>

                                <a href="pesan_band.php?id=<?php echo $band['id']; ?>" class="btn btn-primary" style="width: 100%; margin-top: auto;">
                                    📅 Pesan Sekarang
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card text-center" style="padding: 3rem;">
                            <h3 style="color: var(--gray); margin-bottom: 1rem;">🔍 Band tidak ditemukan</h3>
                            <p class="text-gray">Coba ubah kata kunci pencarian atau filter Anda</p>
                            <a href="lihat_band.php" class="btn btn-primary mt-2">
                                Reset Pencarian
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleDesc(bandId) {
            const shortDesc = document.getElementById('desc-short-' + bandId);
            const fullDesc = document.getElementById('desc-full-' + bandId);
            const toggle = document.getElementById('desc-toggle-' + bandId);
            
            if (fullDesc.style.display === 'none') {
                shortDesc.style.display = 'none';
                fullDesc.style.display = 'inline';
                toggle.textContent = 'Lihat lebih sedikit';
            } else {
                shortDesc.style.display = 'inline';
                fullDesc.style.display = 'none';
                toggle.textContent = 'Lihat selengkapnya';
            }
        }
    </script>
</body>
</html>