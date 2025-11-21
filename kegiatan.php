<?php require_once 'config.php';

// Ambil parameter filter
$pengeluaran_id = isset($_GET['pengeluaran_id']) ? clean_input($_GET['pengeluaran_id']) : '';
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Query dasar
$query = "
    SELECT k.*, p.nama_kegiatan as nama_pengeluaran, p.anggaran as anggaran_pengeluaran,
           p.realisasi as realisasi_pengeluaran, p.sisa_anggaran as sisa_pengeluaran
    FROM kegiatan k 
    JOIN pengeluaran p ON k.pengeluaran_id = p.id 
";
$params = [];

// Tambahkan filter jika ada
if (!empty($pengeluaran_id)) {
    $query .= " WHERE k.pengeluaran_id = ?";
    $params[] = $pengeluaran_id;
} elseif (!empty($status)) {
    $query .= " WHERE k.status = ?";
    $params[] = $status;
}
$query .= " ORDER BY k.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $kegiatan = $stmt->fetchAll();

    // Ambil data pengeluaran untuk dropdown filter
    $stmt = $pdo->prepare("SELECT id, nama_kegiatan FROM pengeluaran ORDER BY nama_kegiatan");
    $stmt->execute();
    $pengeluaran_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Gagal memuat data kegiatan: " . $e->getMessage();
    $kegiatan = [];
    $pengeluaran_list = [];
}

// Handle detail kegiatan
$detail_kegiatan = null;
$detail_penggunaan = [];
if (isset($_GET['detail_id'])) {
    $detail_id = clean_input($_GET['detail_id']);
    try {
        $stmt = $pdo->prepare("
            SELECT k.*, p.nama_kegiatan as nama_pengeluaran 
            FROM kegiatan k 
            JOIN pengeluaran p ON k.pengeluaran_id = p.id 
            WHERE k.id = ?
        ");
        $stmt->execute([$detail_id]);
        $detail_kegiatan = $stmt->fetch();

        if ($detail_kegiatan) {
            $stmt = $pdo->prepare("SELECT * FROM detail_penggunaan_kegiatan WHERE kegiatan_id = ?");
            $stmt->execute([$detail_id]);
            $detail_penggunaan = $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        $error = "Gagal memuat detail kegiatan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kegiatan - DesaFunds Minggiran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
        .kegiatan-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 4px solid #1966f5;
        }
        .progress {
            height: 8px;
        }
        .badge-status {
            font-size: 0.8rem;
        }
        .detail-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
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
                </svg>
            </div>
            DesaFunds Minggiran
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(1);" aria-hidden="true"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="apbdes.php">APBDes</a></li>
                <li class="nav-item"><a class="nav-link active" href="kegiatan.php">Kegiatan</a></li>
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
        <h1 class="text-center mb-4 fw-bold">Kegiatan Desa Minggiran</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="kegiatan-card mb-4">
            <h5 class="mb-3">Filter Kegiatan</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label for="pengeluaran_id" class="form-label">Berdasarkan Pengeluaran</label>
                    <select class="form-select" id="pengeluaran_id" name="pengeluaran_id">
                        <option value="">Semua Pengeluaran</option>
                        <?php foreach($pengeluaran_list as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $pengeluaran_id == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nama_kegiatan']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="status" class="form-label">Berdasarkan Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="rencana" <?php echo $status == 'rencana' ? 'selected' : ''; ?>>Rencana</option>
                        <option value="berjalan" <?php echo $status == 'berjalan' ? 'selected' : ''; ?>>Berjalan</option>
                        <option value="selesai" <?php echo $status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <?php if ($pengeluaran_id || $status): ?>
                        <a href="kegiatan.php" class="btn btn-outline-secondary ms-2">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <!-- Modal Detail Kegiatan -->
        <?php if ($detail_kegiatan): ?>
        <div class="modal fade show" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="false" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel">Detail Kegiatan: <?php echo htmlspecialchars($detail_kegiatan['nama_kegiatan']); ?></h5>
                        <a href="kegiatan.php" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Informasi Umum</h6>
                                    <p><strong>Nama Kegiatan:</strong> <?php echo htmlspecialchars($detail_kegiatan['nama_kegiatan']); ?></p>
                                    <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($detail_kegiatan['lokasi']); ?></p>
                                    <p><strong>Periode:</strong> <?php echo date('d M Y', strtotime($detail_kegiatan['tanggal_mulai'])); ?>
                                        <?php if ($detail_kegiatan['tanggal_selesai']): ?>
                                        - <?php echo date('d M Y', strtotime($detail_kegiatan['tanggal_selesai'])); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Status:</strong>
                                        <span class="badge 
                                            <?php echo $detail_kegiatan['status'] == 'selesai' ? 'bg-success' : 
                                                   ($detail_kegiatan['status'] == 'berjalan' ? 'bg-warning' : 'bg-secondary'); ?>">
                                            <?php echo ucfirst($detail_kegiatan['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Informasi Anggaran</h6>
                                    <p><strong>Anggaran:</strong> Rp <?php echo number_format($detail_kegiatan['anggaran'], 0, ',', '.'); ?></p>
                                    <p><strong>Realisasi:</strong> Rp <?php echo number_format($detail_kegiatan['realisasi'], 0, ',', '.'); ?></p>
                                    <p><strong>Sisa Anggaran:</strong> Rp <?php echo number_format($detail_kegiatan['sisa_anggaran'], 0, ',', '.'); ?></p>
                                    <div class="progress mt-2">
                                        <div class="progress-bar 
                                            <?php echo ($detail_kegiatan['anggaran'] > 0 && $detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100 >= 100) ? 'bg-success' : 
                                                   (($detail_kegiatan['anggaran'] > 0 && $detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100 >= 50) ? 'bg-warning' : 'bg-info'); ?>" 
                                            role="progressbar" 
                                            style="width: <?php echo ($detail_kegiatan['anggaran'] > 0) ? min(($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100), 100) : 0; ?>%"
                                            aria-valuenow="<?php echo ($detail_kegiatan['anggaran'] > 0) ? ($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100) : 0; ?>" 
                                            aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo ($detail_kegiatan['anggaran'] > 0) ? number_format(($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100), 1) : '0'; ?>% terealisasi</small>
                                </div>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h6>Deskripsi Kegiatan</h6>
                            <p><?php echo nl2br(htmlspecialchars($detail_kegiatan['deskripsi'])); ?></p>
                        </div>
                        <?php if (!empty($detail_penggunaan)): ?>
                        <div class="detail-section">
                            <h6>Rincian Penggunaan Dana</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Jumlah</th>
                                            <th>Satuan</th>
                                            <th>Harga Satuan</th>
                                            <th>Total</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($detail_penggunaan as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item']); ?></td>
                                            <td><?php echo number_format($item['jumlah'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($item['satuan']); ?></td>
                                            <td>Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                                            <td>Rp <?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($item['keterangan']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-success fw-bold">
                                            <td colspan="4">Total Penggunaan</td>
                                            <td>Rp <?php echo number_format(array_sum(array_column($detail_penggunaan, 'total')), 0, ',', '.'); ?></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <a href="kegiatan.php" class="btn btn-secondary">Tutup</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Daftar Kegiatan -->
        <?php if (empty($kegiatan)): ?>
            <div class="kegiatan-card text-center">
                <h5 class="text-muted">Tidak ada kegiatan</h5>
                <p class="text-muted">Belum ada kegiatan yang tercatat.</p>
            </div>
        <?php else: ?>
            <?php foreach($kegiatan as $item): 
                $progress = ($item['anggaran'] > 0) ? ($item['realisasi'] / $item['anggaran']) * 100 : 0;
            ?>
            <div class="kegiatan-card">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-2"><?php echo htmlspecialchars($item['nama_kegiatan']); ?></h5>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($item['deskripsi']); ?></p>
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <small class="text-muted">
                                    <strong>Lokasi:</strong> <?php echo htmlspecialchars($item['lokasi']); ?>
                                </small>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted">
                                    <strong>Periode:</strong> <?php echo date('d M Y', strtotime($item['tanggal_mulai'])); ?>
                                    <?php if ($item['tanggal_selesai']): ?>
                                        - <?php echo date('d M Y', strtotime($item['tanggal_selesai'])); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <div class="progress mb-2" style="height: 6px;">
                            <div class="progress-bar 
                                <?php echo $progress >= 100 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-info'); ?>" 
                                role="progressbar" 
                                style="width: <?php echo min($progress, 100); ?>%"
                                aria-valuenow="<?php echo $progress; ?>" 
                                aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Realisasi: Rp <?php echo number_format($item['realisasi'], 0, ',', '.'); ?> 
                                dari Rp <?php echo number_format($item['anggaran'], 0, ',', '.'); ?>
                                (<?php echo number_format($progress, 1); ?>%)
                            </small>
                            <span class="badge 
                                <?php echo $item['status'] == 'selesai' ? 'bg-success' : 
                                       ($item['status'] == 'berjalan' ? 'bg-warning' : 'bg-secondary'); ?> badge-status">
                                <?php echo ucfirst($item['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="mb-3">
                            <strong>Anggaran: Rp <?php echo number_format($item['anggaran'], 0, ',', '.'); ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Sisa: Rp <?php echo number_format($item['sisa_anggaran'], 0, ',', '.'); ?></small>
                        </div>
                        <a href="kegiatan.php?detail_id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                            Lihat Detail
                        </a>
                        <?php if ($item['pengeluaran_id']): ?>
                            <a href="apbdes.php" class="btn btn-outline-secondary btn-sm">
                                Lihat APBDes
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
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