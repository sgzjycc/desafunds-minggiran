<?php require_once 'config.php';

// Ambil data APBDes tahun 2025
try {
    $stmt = $pdo->prepare("SELECT * FROM apbdes WHERE tahun = 2025");
    $stmt->execute();
    $apbdes = $stmt->fetch();
} catch(PDOException $e) {
    $apbdes = null;
    $error = "Gagal memuat data APBDes: " . $e->getMessage();
}

// Ambil data pemasukan dan pengeluaran untuk chart
try {
    $stmt = $pdo->prepare("
        SELECT jp.nama_jenis, SUM(p.jumlah) as total 
        FROM pemasukan p 
        JOIN jenis_pemasukan jp ON p.jenis_pemasukan_id = jp.id 
        WHERE YEAR(p.tanggal) = 2025 
        GROUP BY jp.id
    ");
    $stmt->execute();
    $pemasukan_per_jenis = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT jp.nama_jenis, SUM(pe.anggaran) as total_anggaran, SUM(pe.realisasi) as total_realisasi
        FROM pengeluaran pe 
        JOIN jenis_pengeluaran jp ON pe.jenis_pengeluaran_id = jp.id 
        WHERE YEAR(pe.tanggal) = 2025 
        GROUP BY jp.id
    ");
    $stmt->execute();
    $pengeluaran_per_jenis = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT MONTH(tanggal) as bulan, SUM(anggaran) as anggaran, SUM(realisasi) as realisasi
        FROM pengeluaran 
        WHERE YEAR(tanggal) = 2025 
        GROUP BY MONTH(tanggal)
        ORDER BY bulan
    ");
    $stmt->execute();
    $pengeluaran_bulanan = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT k.*, p.nama_kegiatan as nama_pengeluaran 
        FROM kegiatan k 
        JOIN pengeluaran p ON k.pengeluaran_id = p.id 
        ORDER BY k.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $kegiatan_terbaru = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Gagal memuat data chart: " . $e->getMessage();
    $pemasukan_per_jenis = [];
    $pengeluaran_per_jenis = [];
    $pengeluaran_bulanan = [];
    $kegiatan_terbaru = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Beranda - DesaFunds Minggiran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .navbar-custom {
            background-color: #1966f5;
            box-shadow: 0 3px 4px rgba(0, 0, 0, 0.2);
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
        .hero-section {
            background: linear-gradient(135deg, #1966f5 0%, #14b8a6 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 4px solid #1966f5;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1966f5;
        }
        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 3rem;
            color: #1966f5;
            margin-bottom: 15px;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-md navbar-custom px-3">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
            <div style="width:44px;height:44px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-right:8px;">
                    <img src="gambar/logo.png" height="50" width="50">
                    <circle cx="32" cy="32" r="30" fill="white" />
                    <path d="M19 43 L32 29 45 43" stroke="#57266a" stroke-width="3" fill="none" stroke-linejoin="round" />
                    <rect x="27" y="29" width="10" height="12" fill="#57266a" />
                    <ellipse cx="23" cy="18" rx="6" ry="8" fill="#57266a" />
                </img>
            </div>
            DesaFunds Minggiran
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(1);" aria-hidden="true"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link active" href="index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="apbdes.php">APBDes</a></li>
                <li class="nav-item"><a class="nav-link" href="kegiatan.php">Kegiatan</a></li>
                <li class="nav-item"><a class="nav-link" href="forum.php">Forum</a></li>
                <li class="nav-item">
                    <?php if (!$is_admin_logged_in): ?>
                        <a href="admin_login.php" class="btn btn-outline-light btn-sm ms-3">Login Admin</a>
                    <?php else: ?>
                        <a href="admin_logout.php" class="btn btn-outline-light btn-sm ms-3">Logout Admin</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Error Message -->
    <?php if (isset($error)): ?>
        <div class="container mt-4">
            <div class="error-message">
                <strong>Error:</strong> <?php echo $error; ?>
                <br><small>Website akan tetap berjalan dengan data terbatas.</small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">DesaFunds Minggiran</h1>
                    <p class="lead mb-4">Aplikasi Transparansi dan Akuntabilitas Dana Desa Minggiran</p>
                    <p class="mb-4">Platform digital untuk memantau pengelolaan keuangan desa secara transparan dan akuntabel. Warga dapat melihat langsung alokasi dana, realisasi kegiatan, dan memberikan masukan.</p>
                    <a href="#dashboard" class="btn btn-light btn-lg">Lihat Dashboard</a>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="gambar/logo.png" width="400" alt="dana desa">
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Section -->
    <section id="dashboard" class="container mb-5">
        <h2 class="text-center mb-4 fw-bold">Dashboard APBDes 2025</h2>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['total_anggaran'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="text-muted">Total Anggaran</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['total_pemasukan'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="text-muted">Total Pemasukan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="text-muted">Total Pengeluaran</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['sisa_dana'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="text-muted">Sisa Dana</div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>Pemasukan per Jenis</h5>
                    <?php if (!empty($pemasukan_per_jenis)): ?>
                        <canvas id="pemasukanChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center">Data pemasukan tidak tersedia</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>Pengeluaran per Jenis</h5>
                    <?php if (!empty($pengeluaran_per_jenis)): ?>
                        <canvas id="pengeluaranChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center">Data pengeluaran tidak tersedia</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-container">
                    <h5>Realisasi Pengeluaran Bulanan 2025</h5>
                    <?php if (!empty($pengeluaran_bulanan)): ?>
                        <canvas id="pengeluaranBulananChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center">Data pengeluaran bulanan tidak tersedia</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-container">
                    <h5>Kegiatan Terbaru</h5>
                    <div class="row">
                        <?php if (!empty($kegiatan_terbaru)): ?>
                            <?php foreach($kegiatan_terbaru as $kegiatan): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></h6>
                                        <p class="card-text small"><?php echo substr(htmlspecialchars($kegiatan['deskripsi']), 0, 100); ?>...</p>
                                        <div class="d-flex justify-content-between">
                                            <span class="badge bg-<?php 
                                                switch($kegiatan['status']) {
                                                    case 'selesai': echo 'success'; break;
                                                    case 'berjalan': echo 'warning'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo ucfirst($kegiatan['status']); ?></span>
                                            <small class="text-muted">Rp <?php echo number_format($kegiatan['realisasi'], 0, ',', '.'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center text-muted">
                                <p>Belum ada kegiatan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="kegiatan.php" class="btn btn-primary">Lihat Semua Kegiatan</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container mb-5">
        <h2 class="text-center mb-4 fw-bold">Fitur Utama DesaFunds</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-card text-center">
                    <div class="feature-icon">📊</div>
                    <h4>APBDes</h4>
                    <p>Pantau Anggaran Pendapatan dan Belanja Desa secara detail dan real-time. Lihat alokasi dana, realisasi, dan sisa anggaran untuk setiap program.</p>
                    <a href="apbdes.php" class="btn btn-outline-primary">Lihat APBDes</a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card text-center">
                    <div class="feature-icon">🏗️</div>
                    <h4>Kegiatan</h4>
                    <p>Ikuti perkembangan berbagai kegiatan pembangunan desa. Lihat detail penggunaan dana, progress pelaksanaan, dan dokumentasi kegiatan.</p>
                    <a href="kegiatan.php" class="btn btn-outline-primary">Lihat Kegiatan</a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card text-center">
                    <div class="feature-icon">💬</div>
                    <h4>Forum</h4>
                    <p>Sampaikan kritik, saran, dan aspirasi untuk pembangunan desa. Admin akan merespon dan menindaklanjuti setiap masukan dari warga.</p>
                    <a href="forum.php" class="btn btn-outline-primary">Lihat Forum</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
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
    <script>
        <?php if (!empty($pemasukan_per_jenis)): ?>
        const pemasukanCtx = document.getElementById('pemasukanChart').getContext('2d');
        const pemasukanChart = new Chart(pemasukanCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['nama_jenis'] . "'"; }, $pemasukan_per_jenis)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $pemasukan_per_jenis)); ?>],
                    backgroundColor: ['#1966f5', '#14b8a6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        <?php endif; ?>

        <?php if (!empty($pengeluaran_per_jenis)): ?>
        const pengeluaranCtx = document.getElementById('pengeluaranChart').getContext('2d');
        const pengeluaranChart = new Chart(pengeluaranCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['nama_jenis'] . "'"; }, $pengeluaran_per_jenis)); ?>],
                datasets: [{
                    label: 'Anggaran',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total_anggaran']; }, $pengeluaran_per_jenis)); ?>],
                    backgroundColor: '#1966f5'
                }, {
                    label: 'Realisasi',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total_realisasi']; }, $pengeluaran_per_jenis)); ?>],
                    backgroundColor: '#14b8a6'
                }]
            },
            options: {
                responsive: true, scales: { y: { beginAtZero: true } }
            }
        });
        <?php endif; ?>

        <?php if (!empty($pengeluaran_bulanan)): ?>
        const pengeluaranBulananCtx = document.getElementById('pengeluaranBulananChart').getContext('2d');
        const pengeluaranBulananChart = new Chart(pengeluaranBulananCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Anggaran',
                    data: [<?php
                        $data = array_fill(0, 12, 0);
                        foreach($pengeluaran_bulanan as $item) {
                            $data[$item['bulan'] - 1] = $item['anggaran'];
                        }
                        echo implode(',', $data);
                    ?>],
                    borderColor: '#1966f5',
                    backgroundColor: 'rgba(25, 102, 245, 0.1)',
                    fill: true
                }, {
                    label: 'Realisasi',
                    data: [<?php
                        $data = array_fill(0, 12, 0);
                        foreach($pengeluaran_bulanan as $item) {
                            $data[$item['bulan'] - 1] = $item['realisasi'];
                        }
                        echo implode(',', $data);
                    ?>],
                    borderColor: '#14b8a6',
                    backgroundColor: 'rgba(20, 184, 166, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true, scales: { y: { beginAtZero: true } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>