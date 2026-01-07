<?php
/**
 * FILE: pelanggan/beri_ulasan.php
 * FUNGSI: Form untuk memberikan rating dan ulasan setelah acara selesai
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(ROLE_PELANGGAN);

$pesananId = clean($_GET['id'] ?? '');
$errors = [];

// Validasi pesanan
if (empty($pesananId)) {
    setAlert('error', 'ID Pesanan tidak valid');
    redirect('pelanggan/status_pesanan.php');
}

// Ambil data pesanan
$pesanan = getPesananById($db, $pesananId);

if (!$pesanan || $pesanan['id_user'] != $_SESSION['user_id']) {
    setAlert('error', 'Pesanan tidak ditemukan');
    redirect('pelanggan/status_pesanan.php');
}

// Cek apakah pesanan sudah selesai
if ($pesanan['status'] !== STATUS_SELESAI) {
    setAlert('error', 'Hanya pesanan yang sudah selesai yang bisa diulas');
    redirect('pelanggan/status_pesanan.php');
}

// Cek apakah sudah pernah memberi ulasan
$stmt = $db->prepare("SELECT * FROM ulasan WHERE id_pesanan = ?");
$stmt->execute([$pesananId]);
$existingUlasan = $stmt->fetch();

if ($existingUlasan) {
    setAlert('info', 'Anda sudah memberikan ulasan untuk pesanan ini');
    redirect('pelanggan/status_pesanan.php?id=' . $pesananId);
}

// Proses submit ulasan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = clean($_POST['rating'] ?? '');
    $komentar = clean($_POST['komentar'] ?? '');
    
    // Validasi
    if (empty($rating) || $rating < 1 || $rating > 5) {
        $errors[] = 'Rating harus antara 1-5 bintang';
    }
    
    if (empty($komentar)) {
        $errors[] = 'Komentar harus diisi';
    } elseif (strlen($komentar) < 10) {
        $errors[] = 'Komentar minimal 10 karakter';
    }
    
    // Jika tidak ada error, simpan ulasan
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO ulasan (id_pesanan, id_user, id_band, rating, komentar) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $pesananId,
                $_SESSION['user_id'],
                $pesanan['id_band'],
                $rating,
                $komentar
            ]);
            
            setAlert('success', 'Terima kasih! Ulasan Anda berhasil disimpan.');
            redirect('pelanggan/status_pesanan.php?id=' . $pesananId);
            
        } catch (Exception $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Ulasan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .rating-stars {
            display: flex;
            gap: 0.5rem;
            font-size: 3rem;
            justify-content: center;
            margin: 2rem 0;
        }
        .rating-stars input[type="radio"] {
            display: none;
        }
        .rating-stars label {
            cursor: pointer;
            color: #d1d5db;
            transition: var(--transition);
        }
        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #fbbf24;
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
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="lihat_band.php">🎸 Lihat Band</a></li>
                <li><a href="status_pesanan.php" class="active">📋 Pesanan Saya</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Beri Ulasan & Rating</h1>
            <p class="text-gray mb-3">Bagaimana pengalaman Anda dengan band ini?</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Detail Pesanan -->
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Detail Pesanan</h3>
                        </div>
                        <div class="card-body">
                            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); height: 150px; border-radius: var(--radius); margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                🎤
                            </div>

                            <table style="width: 100%;">
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>ID:</strong></td>
                                    <td>#<?php echo $pesanan['id']; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Band:</strong></td>
                                    <td><?php echo htmlspecialchars($pesanan['nama_band']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Tanggal:</strong></td>
                                    <td><?php echo formatTanggal($pesanan['tanggal_acara']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Lokasi:</strong></td>
                                    <td><?php echo htmlspecialchars(substr($pesanan['lokasi'], 0, 30)); ?>...</td>
                                </tr>
                                <tr>
                                    <td class="text-gray" style="padding: 0.5rem 0;"><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo ucfirst($pesanan['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card mt-2" style="background: var(--light);">
                        <div class="card-body">
                            <p style="font-size: 0.9rem; line-height: 1.6; margin: 0;">
                                💡 <strong>Tips:</strong> Berikan ulasan yang jujur dan konstruktif. 
                                Ulasan Anda akan membantu pelanggan lain dalam memilih band yang tepat.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Ulasan -->
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Form Ulasan</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="ulasanForm">
                                <!-- Rating Stars -->
                                <div class="form-group">
                                    <label class="form-label text-center" style="display: block;">
                                        Berikan Rating <span style="color: red;">*</span>
                                    </label>
                                    <div class="rating-stars">
                                        <input type="radio" name="rating" id="star5" value="5">
                                        <label for="star5">★</label>
                                        
                                        <input type="radio" name="rating" id="star4" value="4">
                                        <label for="star4">★</label>
                                        
                                        <input type="radio" name="rating" id="star3" value="3">
                                        <label for="star3">★</label>
                                        
                                        <input type="radio" name="rating" id="star2" value="2">
                                        <label for="star2">★</label>
                                        
                                        <input type="radio" name="rating" id="star1" value="1">
                                        <label for="star1">★</label>
                                    </div>
                                    <p id="ratingText" class="text-center text-gray" style="font-size: 1.1rem; font-weight: bold;">
                                        Pilih rating
                                    </p>
                                </div>

                                <!-- Komentar -->
                                <div class="form-group">
                                    <label class="form-label">Tulis Ulasan Anda <span style="color: red;">*</span></label>
                                    <textarea 
                                        name="komentar" 
                                        class="form-control" 
                                        rows="8"
                                        placeholder="Ceritakan pengalaman Anda dengan band ini... (minimal 10 karakter)"
                                        required
                                    ><?php echo isset($_POST['komentar']) ? htmlspecialchars($_POST['komentar']) : ''; ?></textarea>
                                    <small class="text-gray">
                                        <span id="charCount">0</span> karakter (minimal 10)
                                    </small>
                                </div>

                                <!-- Panduan Ulasan -->
                                <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
                                    <h4 style="margin-bottom: 1rem;">Panduan Menulis Ulasan:</h4>
                                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--gray); line-height: 1.8;">
                                        <li>Jelaskan kualitas penampilan band</li>
                                        <li>Sebutkan profesionalitas dan ketepatan waktu</li>
                                        <li>Ceritakan interaksi dengan tamu/audience</li>
                                        <li>Berikan masukan yang membangun</li>
                                    </ul>
                                </div>

                                <div style="display: flex; gap: 1rem;">
                                    <a href="status_pesanan.php" class="btn btn-outline" style="flex: 1;">
                                        ← Kembali
                                    </a>
                                    <button type="submit" class="btn btn-success" style="flex: 2;" id="submitBtn" disabled>
                                        ⭐ Kirim Ulasan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Rating system
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        const ratingText = document.getElementById('ratingText');
        const ratingLabels = {
            '5': 'Sangat Baik ⭐⭐⭐⭐⭐',
            '4': 'Baik ⭐⭐⭐⭐',
            '3': 'Cukup ⭐⭐⭐',
            '2': 'Kurang ⭐⭐',
            '1': 'Buruk ⭐'
        };

        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingText.textContent = ratingLabels[this.value];
                ratingText.style.color = 'var(--primary)';
                validateForm();
            });
        });

        // Character counter
        const komentarInput = document.querySelector('textarea[name="komentar"]');
        const charCount = document.getElementById('charCount');
        const submitBtn = document.getElementById('submitBtn');

        komentarInput.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            validateForm();
        });

        // Validate form
        function validateForm() {
            const rating = document.querySelector('input[name="rating"]:checked');
            const komentarLength = komentarInput.value.length;
            
            if (rating && komentarLength >= 10) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
            }
        }

        // Initial validation
        validateForm();
    </script>
</body>
</html>