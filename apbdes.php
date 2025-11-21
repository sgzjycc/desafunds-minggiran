<?php require_once 'config.php';

// Handle filter tahun
$selected_year = isset($_GET['tahun']) ? clean_input($_GET['tahun']) : '2025';

// Ambil data tahun untuk dropdown
try {
    $stmt = $pdo->prepare("SELECT DISTINCT tahun FROM apbdes ORDER BY tahun DESC");
    $stmt->execute();
    $tahun_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Gagal memuat data tahun: " . $e->getMessage();
}

// Ambil data APBDes tahun yang dipilih
try {
    $stmt = $pdo->prepare("SELECT * FROM apbdes WHERE tahun = ?");
    $stmt->execute([$selected_year]);
    $apbdes = $stmt->fetch();

    // Ambil data pemasukan
    $stmt = $pdo->prepare("
        SELECT p.*, jp.nama_jenis 
        FROM pemasukan p 
        JOIN jenis_pemasukan jp ON p.jenis_pemasukan_id = jp.id 
        JOIN apbdes a ON p.apbdes_id = a.id 
        WHERE a.tahun = ? 
        ORDER BY p.tanggal DESC
    ");
    $stmt->execute([$selected_year]);
    $pemasukan = $stmt->fetchAll();

    // Ambil data pengeluaran
    $stmt = $pdo->prepare("
        SELECT pe.*, jp.nama_jenis 
        FROM pengeluaran pe 
        JOIN jenis_pengeluaran jp ON pe.jenis_pengeluaran_id = jp.id 
        JOIN apbdes a ON pe.apbdes_id = a.id 
        WHERE a.tahun = ? 
        ORDER BY pe.tanggal DESC
    ");
    $stmt->execute([$selected_year]);
    $pengeluaran = $stmt->fetchAll();

    // Hitung total pemasukan dan pengeluaran
    $total_pemasukan = array_sum(array_column($pemasukan, 'jumlah'));
    $total_pengeluaran = array_sum(array_column($pengeluaran, 'realisasi'));
    
} catch(PDOException $e) {
    $error = "Gagal memuat data APBDes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>APBDes - DesaFunds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .navbar-custom { background-color: #1966f5; box-shadow: 0 3px 4px rgba(0,0,0,0.2);}
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; user-select: none;}
        .navbar-custom .nav-link.active { border-bottom: 2px solid white; color: white;}
        .stat-card {
            background: white; border-radius: 10px; padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; border-left: 4px solid #1966f5;
        }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: #1966f5; }
        .table-container {
            background: white; border-radius: 10px; padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .progress { height: 8px; }
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
            DesaFunds
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link active" href="apbdes.php">APBDes</a></li>
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
    <main class="container my-4">
        <h1 class="text-center mb-4 fw-bold">Anggaran Pendapatan dan Belanja Desa (APBDes) Desa Minggiran</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="table-container">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pilih Tahun</label>
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <?php foreach($tahun_list as $tahun): ?>
                            <option value="<?php echo $tahun['tahun']; ?>" <?php echo $selected_year == $tahun['tahun'] ? 'selected' : ''; ?>>
                                Tahun <?php echo $tahun['tahun']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <?php if ($is_admin_logged_in): ?>
                        <a href="admin_apbdes.php" class="btn btn-primary w-100">Kelola APBDes</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php if ($apbdes): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['total_anggaran'], 0, ',', '.'); ?></div>
                    <div class="text-muted">Total Anggaran</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['total_pemasukan'], 0, ',', '.'); ?></div>
                    <div class="text-muted">Total Pemasukan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['total_pengeluaran'], 0, ',', '.'); ?></div>
                    <div class="text-muted">Total Pengeluaran</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($apbdes['sisa_dana'], 0, ',', '.'); ?></div>
                    <div class="text-muted">Sisa Dana</div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <h4 class="mb-3">Progress Realisasi Anggaran Tahun <?php echo $selected_year; ?></h4>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Realisasi Pengeluaran</span>
                    <span>
                        <?php echo number_format(
                            $apbdes['total_anggaran'] > 0 
                            ? ($apbdes['total_pengeluaran'] / $apbdes['total_anggaran'] * 100) : 0, 1
                        ); ?>%
                    </span>
                </div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar"
                        style="width: <?php echo $apbdes['total_anggaran'] > 0 ? ($apbdes['total_pengeluaran'] / $apbdes['total_anggaran'] * 100) : 0; ?>%"
                        aria-valuenow="<?php echo $apbdes['total_anggaran'] > 0 ? ($apbdes['total_pengeluaran'] / $apbdes['total_anggaran'] * 100) : 0; ?>"
                        aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <h4 class="mb-3">Rincian Pemasukan Tahun <?php echo $selected_year; ?></h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis Pemasukan</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($pemasukan)): ?>
                        <?php foreach($pemasukan as $item): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($item['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_jenis']); ?></td>
                            <td>Rp <?php echo number_format($item['jumlah'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($item['keterangan']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-success fw-bold">
                            <td colspan="2">Total Pemasukan</td>
                            <td>Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Belum ada data pemasukan</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="table-container">
            <h4 class="mb-3">Rincian Pengeluaran Tahun <?php echo $selected_year; ?></h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis Pengeluaran</th>
                            <th>Kegiatan</th>
                            <th>Anggaran</th>
                            <th>Realisasi</th>
                            <th>Sisa</th>
                            <th>Progress</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($pengeluaran)): ?>
                        <?php foreach($pengeluaran as $item): 
                            $progress = ($item['anggaran'] > 0) ? ($item['realisasi'] / $item['anggaran']) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($item['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_jenis']); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_kegiatan']); ?></td>
                            <td>Rp <?php echo number_format($item['anggaran'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($item['realisasi'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($item['sisa_anggaran'], 0, ',', '.'); ?></td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                        <div class="progress-bar 
                                            <?php echo $progress >= 100 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-info'); ?>"
                                            role="progressbar" style="width: <?php echo min($progress, 100); ?>%"
                                            aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small><?php echo number_format($progress, 1); ?>%</small>
                                </div>
                            </td>
                            <td>
                                <a href="kegiatan.php?pengeluaran_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-danger fw-bold">
                            <td colspan="3">Total Pengeluaran</td>
                            <td>Rp <?php echo number_format(array_sum(array_column($pengeluaran, 'anggaran')), 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format(array_sum(array_column($pengeluaran, 'sisa_anggaran')), 0, ',', '.'); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada data pengeluaran</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
            <div class="table-container text-center">
                <h4 class="text-muted">Data APBDes Tahun <?php echo htmlspecialchars($selected_year); ?> Belum Tersedia</h4>
                <p class="text-muted">Silakan pilih tahun lain atau hubungi admin.</p>
            </div>
        <?php endif; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>