<?php require_once 'config.php';
if (!$is_admin_logged_in) {
    header("Location: admin_login.php");
    exit();
}

// Handle filter tahun
$selected_year = isset($_GET['tahun']) ? clean_input($_GET['tahun']) : '2025';
$jenis_data = isset($_GET['jenis']) ? clean_input($_GET['jenis']) : 'pengeluaran';

// Ambil data tahun untuk dropdown
try {
    $stmt = $pdo->prepare("SELECT DISTINCT tahun FROM apbdes ORDER BY tahun DESC");
    $stmt->execute();
    $tahun_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Gagal memuat data tahun: " . $e->getMessage();
}

// Handle tambah/edit/hapus APBDes dan data lainnya (proses asli sama seperti punyamu!)
// -- Mulai ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_apbdes'])) {
    $tahun = clean_input($_POST['tahun']);
    $total_anggaran = clean_input($_POST['total_anggaran']);
    $total_pemasukan = clean_input($_POST['total_pemasukan']);
    $total_pengeluaran = clean_input($_POST['total_pengeluaran']);
    $sisa_dana = clean_input($_POST['sisa_dana']);
    try {
        $stmt = $pdo->prepare("INSERT INTO apbdes (tahun, total_anggaran, total_pemasukan, total_pengeluaran, sisa_dana) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tahun, $total_anggaran, $total_pemasukan, $total_pengeluaran, $sisa_dana]);
        header("Location: admin_apbdes.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Gagal menambah APBDes: " . $e->getMessage();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_apbdes'])) {
    $id = clean_input($_POST['id']);
    $tahun = clean_input($_POST['tahun']);
    $total_anggaran = clean_input($_POST['total_anggaran']);
    $total_pemasukan = clean_input($_POST['total_pemasukan']);
    $total_pengeluaran = clean_input($_POST['total_pengeluaran']);
    $sisa_dana = clean_input($_POST['sisa_dana']);
    try {
        $stmt = $pdo->prepare("UPDATE apbdes SET tahun = ?, total_anggaran = ?, total_pemasukan = ?, total_pengeluaran = ?, sisa_dana = ? WHERE id = ?");
        $stmt->execute([$tahun, $total_anggaran, $total_pemasukan, $total_pengeluaran, $sisa_dana, $id]);
        header("Location: admin_apbdes.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Gagal mengedit APBDes: " . $e->getMessage();
    }
}
if (isset($_GET['hapus_apbdes'])) {
    $id = clean_input($_GET['hapus_apbdes']);
    try {
        $stmt = $pdo->prepare("DELETE FROM apbdes WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_apbdes.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Gagal menghapus APBDes: " . $e->getMessage();
    }
}
// ...proses tambah pemasukan/pengeluaran (boleh lanjutkan dari kode asli kamu)
// -- Selesai --

// Ambil data APBDes, pemasukan, dan pengeluaran berdasarkan tahun
try {
    // Data APBDes
    $stmt = $pdo->prepare("SELECT * FROM apbdes WHERE tahun = ?");
    $stmt->execute([$selected_year]);
    $apbdes = $stmt->fetch();

    // Data pemasukan
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

    // Data pengeluaran
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

    // Ambil jenis pemasukan dan pengeluaran untuk dropdown
    $stmt = $pdo->prepare("SELECT * FROM jenis_pemasukan");
    $stmt->execute();
    $jenis_pemasukan = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM jenis_pengeluaran");
    $stmt->execute();
    $jenis_pengeluaran = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Gagal memuat data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola APBDes - Admin DesaFunds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .navbar-custom { background-color: #1966f5; box-shadow: 0 3px 4px rgba(0,0,0,.2);}
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white;}
        .nav-admin .nav-link.active { border-bottom: 3px solid #1966f5; color: #1966f5;}
        .admin-container { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;}
        .btn-action { margin: 2px; }
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
            DesaFunds - Admin
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(1);" aria-hidden="true"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin_apbdes.php">Kelola APBDes</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_kegiatan.php">Kelola Kegiatan</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_forum.php">Kelola Forum</a></li>
                <li class="nav-item">
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <main class="container my-4">
        <h1 class="text-center mb-4 fw-bold">Kelola APBDes Desa Minggiran</h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Data berhasil disimpan!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <!-- Filter Section -->
        <div class="admin-container">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <?php foreach($tahun_list as $tahun): ?>
                        <option value="<?php echo $tahun['tahun']; ?>" <?php echo $selected_year == $tahun['tahun'] ? 'selected' : ''; ?>>
                            <?php echo $tahun['tahun']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Data</label>
                    <select name="jenis" class="form-select" onchange="this.form.submit()">
                        <option value="pengeluaran" <?php echo $jenis_data == 'pengeluaran' ? 'selected' : ''; ?>>Data Pengeluaran</option>
                        <option value="pemasukan" <?php echo $jenis_data == 'pemasukan' ? 'selected' : ''; ?>>Data Pemasukan</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#tambahAPBDesModal">
                        + Tambah APBDes Baru
                    </button>
                </div>
            </form>
        </div>
        <!-- Ringkasan APBDes -->
        <?php if ($apbdes): ?>
        <div class="admin-container">
            <div class="row">
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h5 class="text-primary">Rp <?php echo number_format($apbdes['total_anggaran'], 0, ',', '.'); ?></h5>
                        <small class="text-muted">Total Anggaran</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h5 class="text-success">Rp <?php echo number_format($apbdes['total_pemasukan'], 0, ',', '.'); ?></h5>
                        <small class="text-muted">Total Pemasukan</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h5 class="text-danger">Rp <?php echo number_format($apbdes['total_pengeluaran'], 0, ',', '.'); ?></h5>
                        <small class="text-muted">Total Pengeluaran</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h5 class="text-warning">Rp <?php echo number_format($apbdes['sisa_dana'], 0, ',', '.'); ?></h5>
                        <small class="text-muted">Sisa Dana</small>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editAPBDesModal<?php echo $apbdes['id']; ?>">
                    Edit APBDes
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center my-4">
            Data APBDes tahun <?php echo htmlspecialchars($selected_year); ?> belum tersedia.<br>
            Silakan tambah APBDes baru untuk tahun tersebut.
        </div>
        <?php endif; ?>
        <!-- Data Pemasukan -->
        <?php if ($jenis_data == 'pemasukan'): ?>
        <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Data Pemasukan Tahun <?php echo $selected_year; ?></h4>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahPemasukanModal">
                    + Tambah Pemasukan
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis Pemasukan</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
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
                            <td>
                                <button class="btn btn-warning btn-sm btn-action">Edit</button>
                                <a href="?hapus_pemasukan=<?php echo $item['id']; ?>&tahun=<?php echo $selected_year; ?>&jenis=pemasukan" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Hapus data pemasukan ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada data pemasukan</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        <!-- Data Pengeluaran -->
        <?php if ($jenis_data == 'pengeluaran'): ?>
        <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Data Pengeluaran Tahun <?php echo $selected_year; ?></h4>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahPengeluaranModal">
                    + Tambah Pengeluaran
                </button>
            </div>
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
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pengeluaran)): ?>
                        <?php foreach($pengeluaran as $item): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($item['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_jenis']); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_kegiatan']); ?></td>
                            <td>Rp <?php echo number_format($item['anggaran'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($item['realisasi'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($item['sisa_anggaran'], 0, ',', '.'); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-action">Edit</button>
                                <a href="?hapus_pengeluaran=<?php echo $item['id']; ?>&tahun=<?php echo $selected_year; ?>&jenis=pengeluaran" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Hapus data pengeluaran ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada data pengeluaran</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal Tambah APBDes -->
    <div class="modal fade" id="tambahAPBDesModal" tabindex="-1" aria-labelledby="tambahAPBDesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tambahAPBDesModalLabel">Tambah APBDes Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tahun</label>
                            <input type="number" class="form-control" name="tahun" min="2020" max="2030" value="2025" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Anggaran</label>
                            <input type="number" class="form-control" name="total_anggaran" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Pemasukan</label>
                            <input type="number" class="form-control" name="total_pemasukan" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Pengeluaran</label>
                            <input type="number" class="form-control" name="total_pengeluaran" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sisa Dana</label>
                            <input type="number" class="form-control" name="sisa_dana" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_apbdes" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal-modal pemasukan/pengeluaran (silakan copy dari kode aslimu, logic ditinggalkan sama persis) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>