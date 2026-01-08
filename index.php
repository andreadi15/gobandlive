<?php
/**
 * FILE: index.php
 * FUNGSI: Landing page utama aplikasi GOBANDLIVE (UPDATED)
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ambil data band untuk ditampilkan
$stmt = $db->query("
    SELECT b.*, 
           (SELECT AVG(rating) FROM ulasan WHERE id_band = b.id) as avg_rating,
           (SELECT COUNT(*) FROM ulasan WHERE id_band = b.id) as total_ulasan
    FROM band b 
    WHERE b.status_ketersediaan = 'tersedia'
    ORDER BY avg_rating DESC 
    LIMIT 6
");
$bands = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sistem Pemesanan Jasa Band & Vokalis</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="index.php" class="navbar-brand">
                🎵 <?php echo APP_NAME; ?>
            </a>
            <ul class="navbar-menu">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="#bands">Band & Vokalis</a></li>
                <li><a href="#about">Tentang</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole(ROLE_PELANGGAN)): ?>
                        <li><a href="pelanggan/dashboard.php">Dashboard</a></li>
                    <?php elseif (hasRole(ROLE_BAND)): ?>
                        <li><a href="band/dashboard.php">Dashboard</a></li>
                    <?php elseif (hasRole(ROLE_ADMIN)): ?>
                        <li><a href="admin/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php" class="btn btn-outline btn-sm login">Login</a></li>
                    <li><a href="auth/register.php" style="color:white" class="btn btn-primary btn-sm">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Temukan Band & Vokalis Terbaik untuk Acara Anda</h1>
            <p>Platform pemesanan jasa hiburan profesional untuk berbagai acara Anda</p>
            <?php if (!isLoggedIn()): ?>
                <a href="auth/register.php" class="btn btn-lg" style="background: white; color: var(--primary);">
                    Mulai Sekarang
                </a>
            <?php else: ?>
                <a href="<?php echo hasRole(ROLE_PELANGGAN) ? 'pelanggan/lihat_band.php' : '#'; ?>" class="btn btn-lg" style="background: white; color: var(--primary);">
                    Lihat Band & Vokalis
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section style="padding: 4rem 0; background: white;">
        <div class="container">
            <h2 class="text-center mb-3" style="font-size: 2rem;">Mengapa Memilih GOBANDLIVE?</h2>
            <div class="row">
                <div class="col-4">
                    <div class="card text-center">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🎸</div>
                        <h3>Band Profesional</h3>
                        <p class="text-gray">Pilihan band dan vokalis profesional dengan berbagai genre musik</p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⚡</div>
                        <h3>Pemesanan Mudah</h3>
                        <p class="text-gray">Proses pemesanan cepat dan praktis secara online</p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💰</div>
                        <h3>Harga Transparan</h3>
                        <p class="text-gray">Harga jelas tanpa biaya tersembunyi</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Band List Section -->
    <section id="bands" style="padding: 4rem 0;">
        <div class="container">
            <h2 class="text-center mb-3" style="font-size: 2rem;">Band & Vokalis Pilihan</h2>
            <div class="row">
                <?php if (count($bands) > 0): ?>
                    <?php foreach ($bands as $band): ?>
                        <div class="col-4">
                            <div class="card">
                                <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); height: 150px; border-radius: var(--radius); margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                    🎤
                                </div>
                                <h3><?php echo htmlspecialchars($band['nama_band']); ?></h3>
                                <p class="text-gray">
                                    <strong>Genre:</strong> <?php echo htmlspecialchars($band['genre']); ?>
                                </p>
                                <p class="text-gray">
                                    <strong>Tarif:</strong> <?php echo formatRupiah($band['tarif']); ?>
                                </p>
                                <?php if ($band['avg_rating']): ?>
                                    <p class="text-gray">
                                        ⭐ <?php echo number_format($band['avg_rating'], 1); ?> 
                                        (<?php echo $band['total_ulasan']; ?> ulasan)
                                    </p>
                                <?php endif; ?>
                                <?php if (isLoggedIn() && hasRole(ROLE_PELANGGAN)): ?>
                                    <a href="pelanggan/pesan_band.php?id=<?php echo $band['id']; ?>" class="btn btn-primary" style="width: 100%;">
                                        Pesan Sekarang
                                    </a>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-outline" style="width: 100%;">
                                        Login untuk Pesan
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-gray">Belum ada band yang tersedia saat ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" style="padding: 4rem 0; background: white;">
        <div class="container">
            <div class="row">
                <div class="col-6">
                    <h2 style="font-size: 2rem; margin-bottom: 1rem;">Tentang GOBANDLIVE</h2>
                    <p style="line-height: 1.8; color: var(--gray);">
                        GOBANDLIVE adalah platform pemesanan jasa band dan vokalis panggilan yang memudahkan 
                        Anda menemukan hiburan profesional untuk berbagai acara seperti pernikahan, 
                        ulang tahun, festival, hingga acara kampus.
                    </p>
                    <p style="line-height: 1.8; color: var(--gray);">
                        Dengan sistem yang terintegrasi, kami memastikan proses pemesanan berjalan lancar, 
                        transparan, dan efisien untuk kepuasan pelanggan dan band/vokalis.
                    </p>
                </div>
                <div class="col-6">
                    <div class="card" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                        <h3>Statistik Kami</h3>
                        <div style="margin-top: 2rem;">
                            <div style="margin-bottom: 1.5rem;">
                                <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                                    <?php echo countBand($db); ?>+
                                </h1>
                                <p>Band & Vokalis Terdaftar</p>
                            </div>
                            <div style="margin-bottom: 1.5rem;">
                                <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                                    <?php echo countPesanan($db, STATUS_SELESAI); ?>+
                                </h1>
                                <p>Acara Sukses Terselenggara</p>
                            </div>
                            <div>
                                <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                                    <?php echo countPelanggan($db); ?>+
                                </h1>
                                <p>Pelanggan Puas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: var(--dark); color: white; padding: 2rem 0; text-align: center;">
        <div class="container">
            <p>&copy; 2025 <?php echo APP_NAME; ?>. All Rights Reserved.</p>
            <p style="margin-top: 0.5rem; opacity: 0.8;">
                Sistem Informasi Pemesanan Jasa Band & Vokalis Panggilan
            </p>
        </div>
    </footer>
</body>
</html>