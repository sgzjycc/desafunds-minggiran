<?php require_once 'config.php';
if (!$is_admin_logged_in) {
    header("Location: admin_login.php");
    exit();
}

// Ambil statistik untuk dashboard admin
try {
    // Total pesan forum
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM forum_posts");
    $stmt->execute();
    $total_posts = $stmt->fetch()['total'];

    // Pesan belum dibalas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM forum_posts WHERE admin_reply IS NULL OR admin_reply = ''");
    $stmt->execute();
    $unreplied_posts = $stmt->fetch()['total'];

    // Total kegiatan
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM kegiatan");
    $stmt->execute();
    $total_kegiatan = $stmt->fetch()['total'];

    // Total pengeluaran
    $stmt = $pdo->prepare("SELECT SUM(realisasi) as total FROM pengeluaran WHERE YEAR(tanggal) = 2025");
    $stmt->execute();
    $total_pengeluaran = $stmt->fetch()['total'];

    // Kegiatan terbaru
    $stmt = $pdo->prepare("SELECT k.*, p.nama_kegiatan as nama_pengeluaran FROM kegiatan k JOIN pengeluaran p ON k.pengeluaran_id = p.id ORDER BY k.created_at DESC LIMIT 5");
    $stmt->execute();
    $kegiatan_terbaru = $stmt->fetchAll();

    // Forum posts terbaru
    $stmt = $pdo->prepare("SELECT fp.*, d.nama_dusun, kf.nama_kategori FROM forum_posts fp JOIN dusun d ON fp.dusun_id = d.id JOIN kategori_forum kf ON fp.kategori_id = kf.id ORDER BY fp.created_at DESC LIMIT 5");
    $stmt->execute();
    $posts_terbaru = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Gagal memuat data dashboard: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Admin - DesaFunds Minggiran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .navbar-custom { background-color: #1966f5; box-shadow: 0 3px 4px rgba(0,0,0,0.2);}
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; user-select: none;}
        .stat-card {
            background: white; border-radius: 10px; padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; border-left: 4px solid #1966f5;
        }
        .stat-number { font-size: 2rem; font-weight: bold; color: #1966f5; }
        .dashboard-section {
            background: white; border-radius: 10px; padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;
        }

                .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white;
            user-select: none;
        }

        .navbar-custom .nav-link.active {
            border-bottom: 2px solid white;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-md navbar-custom px-3">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
            <div style="width:44px;height:44px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-right:8px;">
                     <img src="gambar/logo.png" height="50" width="50">
                    <circle cx="32" cy="32" r="30" fill="white" />
                    <path d="M19 43 L32 29 45 43" stroke="#57266a" stroke-width="3" fill="none" stroke-linejoin="round" />
                    <rect x="27" y="29" width="10" height="12" fill="#57266a" />
                    <ellipse cx="23" cy="18" rx="6" ry="8" fill="#57266a" />
                </svg>
            </div>
            DesaFunds - Admin
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(1);" aria-hidden="true"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_apbdes.php">Kelola APBDes</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_kegiatan.php">Kelola Kegiatan</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_forum.php">Kelola Forum</a></li>
                <li class="nav-item">
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <main class="container my-4">
        <h1 class="text-center mb-4 fw-bold">Dashboard Admin Desa Minggiran</h1>
        <p class="text-center text-muted mb-5">Selamat datang, <?php echo $_SESSION['admin_nama'] ?? 'Admin'; ?>! Kelola data DesaFunds dari sini.</p>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_posts; ?></div>
                    <div class="text-muted">Total Pesan Forum</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $unreplied_posts; ?></div>
                    <div class="text-muted">Pesan Belum Dibalas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_kegiatan; ?></div>
                    <div class="text-muted">Total Kegiatan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($total_pengeluaran ?? 0, 0, ',', '.'); ?></div>
                    <div class="text-muted">Total Pengeluaran 2025</div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="dashboard-section">
                    <h5 class="mb-3">Kegiatan Terbaru</h5>
                    <?php if (!empty($kegiatan_terbaru)): ?>
                        <?php foreach($kegiatan_terbaru as $kegiatan): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <h6 class="mb-1"><?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></h6>
                            <small class="text-muted d-block"><?php echo date('d M Y', strtotime($kegiatan['created_at'])); ?></small>
                            <span class="badge 
                                <?php echo $kegiatan['status'] == 'selesai' ? 'bg-success' : 
                                       ($kegiatan['status'] == 'berjalan' ? 'bg-warning' : 'bg-secondary'); ?>">
                                <?php echo ucfirst($kegiatan['status']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Belum ada kegiatan</p>
                    <?php endif; ?>
                    <div class="text-center mt-3">
                        <a href="admin_kegiatan.php" class="btn btn-outline-primary btn-sm">Kelola Kegiatan</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-section">
                    <h5 class="mb-3">Pesan Forum Terbaru</h5>
                    <?php if (!empty($posts_terbaru)): ?>
                        <?php foreach($posts_terbaru as $post): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <h6 class="mb-1"><?php echo htmlspecialchars($post['nama']); ?></h6>
                            <p class="mb-1 small"><?php echo substr(htmlspecialchars($post['pesan']), 0, 100); ?>...</p>
                            <small class="text-muted d-block">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($post['nama_dusun']); ?></span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($post['nama_kategori']); ?></span>
                                <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                            </small>
                            <?php if (empty($post['admin_reply'])): ?>
                                <span class="badge bg-warning mt-1">Perlu Balasan</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Belum ada pesan forum</p>
                    <?php endif; ?>
                    <div class="text-center mt-3">
                        <a href="admin_forum.php" class="btn btn-outline-primary btn-sm">Kelola Forum</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-section">
            <h5 class="mb-3">Aksi Cepat</h5>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <a href="admin_forum.php" class="btn btn-primary w-100">Balas Pesan Forum</a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="admin_apbdes.php" class="btn btn-success w-100">Kelola APBDes</a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="admin_kegiatan.php" class="btn btn-info w-100">Kelola Kegiatan</a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="admin_logout.php" class="btn btn-danger w-100">Logout</a>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>DesaFunds Minggiran</h5>
                    <p>Aplikasi Transparansi dan Akuntabilitas Dana Desa Minggiran</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; 2025 DesaFunds Minggiran. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>