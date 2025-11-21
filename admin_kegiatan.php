<?php
require_once 'config.php';

// Redirect jika bukan admin
if (!$is_admin_logged_in) {
    header("Location: admin_login.php");
    exit();
}

// Handle tambah kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kegiatan'])) {
    $pengeluaran_id = clean_input($_POST['pengeluaran_id']);
    $nama_kegiatan = clean_input($_POST['nama_kegiatan']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $lokasi = clean_input($_POST['lokasi']);
    $tanggal_mulai = clean_input($_POST['tanggal_mulai']);
    $tanggal_selesai = clean_input($_POST['tanggal_selesai']);
    $anggaran = clean_input($_POST['anggaran']);
    $realisasi = clean_input($_POST['realisasi']);
    $sisa_anggaran = clean_input($_POST['sisa_anggaran']);
    $status = clean_input($_POST['status']);
    $dokumentasi = clean_input($_POST['dokumentasi']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO kegiatan (pengeluaran_id, nama_kegiatan, deskripsi, lokasi, tanggal_mulai, tanggal_selesai, anggaran, realisasi, sisa_anggaran, status, dokumentasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$pengeluaran_id, $nama_kegiatan, $deskripsi, $lokasi, $tanggal_mulai, $tanggal_selesai, $anggaran, $realisasi, $sisa_anggaran, $status, $dokumentasi]);
        header("Location: admin_kegiatan.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Gagal menambah kegiatan: " . $e->getMessage();
    }
}

// Handle edit kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_kegiatan'])) {
    $id = clean_input($_POST['id']);
    $pengeluaran_id = clean_input($_POST['pengeluaran_id']);
    $nama_kegiatan = clean_input($_POST['nama_kegiatan']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $lokasi = clean_input($_POST['lokasi']);
    $tanggal_mulai = clean_input($_POST['tanggal_mulai']);
    $tanggal_selesai = clean_input($_POST['tanggal_selesai']);
    $anggaran = clean_input($_POST['anggaran']);
    $realisasi = clean_input($_POST['realisasi']);
    $sisa_anggaran = clean_input($_POST['sisa_anggaran']);
    $status = clean_input($_POST['status']);
    $dokumentasi = clean_input($_POST['dokumentasi']);
    
    try {
        $stmt = $pdo->prepare("UPDATE kegiatan SET pengeluaran_id = ?, nama_kegiatan = ?, deskripsi = ?, lokasi = ?, tanggal_mulai = ?, tanggal_selesai = ?, anggaran = ?, realisasi = ?, sisa_anggaran = ?, status = ?, dokumentasi = ? WHERE id = ?");
        $stmt->execute([$pengeluaran_id, $nama_kegiatan, $deskripsi, $lokasi, $tanggal_mulai, $tanggal_selesai, $anggaran, $realisasi, $sisa_anggaran, $status, $dokumentasi, $id]);
        header("Location: admin_kegiatan.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Gagal mengedit kegiatan: " . $e->getMessage();
    }
}

// Handle hapus kegiatan
if (isset($_GET['hapus_kegiatan'])) {
    $id = clean_input($_GET['hapus_kegiatan']);
    try {
        $stmt = $pdo->prepare("DELETE FROM kegiatan WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_kegiatan.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Gagal menghapus kegiatan: " . $e->getMessage();
    }
}

// Handle tambah detail penggunaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_detail'])) {
    $kegiatan_id = clean_input($_POST['kegiatan_id']);
    $item = clean_input($_POST['item']);
    $jumlah = clean_input($_POST['jumlah']);
    $satuan = clean_input($_POST['satuan']);
    $harga_satuan = clean_input($_POST['harga_satuan']);
    $total = clean_input($_POST['total']);
    $keterangan = clean_input($_POST['keterangan']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO detail_penggunaan_kegiatan (kegiatan_id, item, jumlah, satuan, harga_satuan, total, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kegiatan_id, $item, $jumlah, $satuan, $harga_satuan, $total, $keterangan]);
        header("Location: admin_kegiatan.php?success=1&detail_id=" . $kegiatan_id);
        exit();
    } catch(PDOException $e) {
        $error = "Gagal menambah detail penggunaan: " . $e->getMessage();
    }
}

// Ambil data kegiatan
try {
    $stmt = $pdo->prepare("
        SELECT k.*, p.nama_kegiatan as nama_pengeluaran, p.anggaran as anggaran_pengeluaran
        FROM kegiatan k 
        LEFT JOIN pengeluaran p ON k.pengeluaran_id = p.id 
        ORDER BY k.created_at DESC
    ");
    $stmt->execute();
    $kegiatan = $stmt->fetchAll();
    
    // Ambil data pengeluaran untuk dropdown
    $stmt = $pdo->prepare("SELECT id, nama_kegiatan FROM pengeluaran ORDER BY nama_kegiatan");
    $stmt->execute();
    $pengeluaran_list = $stmt->fetchAll();
    
    // Ambil detail kegiatan jika ada parameter
    $detail_kegiatan = null;
    $detail_penggunaan = [];
    if (isset($_GET['detail_id'])) {
        $detail_id = clean_input($_GET['detail_id']);
        $stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE id = ?");
        $stmt->execute([$detail_id]);
        $detail_kegiatan = $stmt->fetch();
        
        if ($detail_kegiatan) {
            $stmt = $pdo->prepare("SELECT * FROM detail_penggunaan_kegiatan WHERE kegiatan_id = ?");
            $stmt->execute([$detail_id]);
            $detail_penggunaan = $stmt->fetchAll();
        }
    }
    
} catch(PDOException $e) {
    $error = "Gagal memuat data kegiatan: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Kegiatan - Admin DesaFunds</title>
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
        .admin-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .kegiatan-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .progress {
            height: 8px;
        }
        .btn-action {
            margin: 2px;
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
                <li class="nav-item"><a class="nav-link" href="admin_apbdes.php">Kelola APBDes</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin_kegiatan.php">Kelola Kegiatan</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_forum.php">Kelola Forum</a></li>
                <li class="nav-item">
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-4">
        <h1 class="text-center mb-4 fw-bold">Kelola Kegiatan Desa Minggiran</h1>

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

        <!-- Tombol Tambah Kegiatan -->
        <div class="admin-container text-center">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#tambahKegiatanModal">
                + Tambah Kegiatan Baru
            </button>
        </div>

        <!-- Daftar Kegiatan -->
        <div class="admin-container">
            <h4 class="mb-3">Daftar Kegiatan</h4>
            
            <?php if (empty($kegiatan)): ?>
                <div class="text-center text-muted py-4">
                    <h5>Belum ada kegiatan</h5>
                    <p>Klik tombol "Tambah Kegiatan Baru" untuk menambah kegiatan pertama.</p>
                </div>
            <?php else: ?>
                <?php foreach($kegiatan as $item): 
                    $progress = ($item['realisasi'] / $item['anggaran']) * 100;
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
                                           ($item['status'] == 'berjalan' ? 'bg-warning' : 'bg-secondary'); ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <strong>Anggaran: Rp <?php echo number_format($item['anggaran'], 0, ',', '.'); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Sisa: Rp <?php echo number_format($item['sisa_anggaran'], 0, ',', '.'); ?></small>
                            </div>
                            <div class="btn-group">
                                <a href="admin_kegiatan.php?detail_id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm btn-action">
                                    Detail
                                </a>
                                <button class="btn btn-warning btn-sm btn-action" data-bs-toggle="modal" data-bs-target="#editKegiatanModal<?php echo $item['id']; ?>">
                                    Edit
                                </button>
                                <a href="admin_kegiatan.php?hapus_kegiatan=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm btn-action"
                                   onclick="return confirm('Hapus kegiatan <?php echo addslashes($item['nama_kegiatan']); ?>?')">
                                    Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit Kegiatan -->
                <div class="modal fade" id="editKegiatanModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="editKegiatanModalLabel<?php echo $item['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editKegiatanModalLabel<?php echo $item['id']; ?>">Edit Kegiatan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Pengeluaran Terkait</label>
                                                <select class="form-select" name="pengeluaran_id" required>
                                                    <option value="">Pilih Pengeluaran</option>
                                                    <?php foreach($pengeluaran_list as $p): ?>
                                                        <option value="<?php echo $p['id']; ?>" <?php echo $item['pengeluaran_id'] == $p['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($p['nama_kegiatan']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nama Kegiatan</label>
                                                <input type="text" class="form-control" name="nama_kegiatan" value="<?php echo htmlspecialchars($item['nama_kegiatan']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Lokasi</label>
                                                <input type="text" class="form-control" name="lokasi" value="<?php echo htmlspecialchars($item['lokasi']); ?>" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Tanggal Mulai</label>
                                                        <input type="date" class="form-control" name="tanggal_mulai" value="<?php echo $item['tanggal_mulai']; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Tanggal Selesai</label>
                                                        <input type="date" class="form-control" name="tanggal_selesai" value="<?php echo $item['tanggal_selesai']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Anggaran</label>
                                                        <input type="number" class="form-control" name="anggaran" step="0.01" value="<?php echo $item['anggaran']; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Realisasi</label>
                                                        <input type="number" class="form-control" name="realisasi" step="0.01" value="<?php echo $item['realisasi']; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Sisa Anggaran</label>
                                                        <input type="number" class="form-control" name="sisa_anggaran" step="0.01" value="<?php echo $item['sisa_anggaran']; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="rencana" <?php echo $item['status'] == 'rencana' ? 'selected' : ''; ?>>Rencana</option>
                                                    <option value="berjalan" <?php echo $item['status'] == 'berjalan' ? 'selected' : ''; ?>>Berjalan</option>
                                                    <option value="selesai" <?php echo $item['status'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Dokumentasi</label>
                                                <input type="text" class="form-control" name="dokumentasi" value="<?php echo htmlspecialchars($item['dokumentasi']); ?>" placeholder="Nama file foto, dipisahkan koma">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi Kegiatan</label>
                                        <textarea class="form-control" name="deskripsi" rows="4" required><?php echo htmlspecialchars($item['deskripsi']); ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_kegiatan" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Tambah Kegiatan -->
    <div class="modal fade" id="tambahKegiatanModal" tabindex="-1" aria-labelledby="tambahKegiatanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tambahKegiatanModalLabel">Tambah Kegiatan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Pengeluaran Terkait</label>
                                    <select class="form-select" name="pengeluaran_id" required>
                                        <option value="">Pilih Pengeluaran</option>
                                        <?php foreach($pengeluaran_list as $p): ?>
                                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama_kegiatan']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Kegiatan</label>
                                    <input type="text" class="form-control" name="nama_kegiatan" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" name="lokasi" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Mulai</label>
                                            <input type="date" class="form-control" name="tanggal_mulai" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Selesai</label>
                                            <input type="date" class="form-control" name="tanggal_selesai">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Anggaran</label>
                                            <input type="number" class="form-control" name="anggaran" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Realisasi</label>
                                            <input type="number" class="form-control" name="realisasi" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Sisa Anggaran</label>
                                            <input type="number" class="form-control" name="sisa_anggaran" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="rencana">Rencana</option>
                                        <option value="berjalan">Berjalan</option>
                                        <option value="selesai">Selesai</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dokumentasi</label>
                                    <input type="text" class="form-control" name="dokumentasi" placeholder="Nama file foto, dipisahkan koma">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Kegiatan</label>
                            <textarea class="form-control" name="deskripsi" rows="4" required placeholder="Jelaskan detail kegiatan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_kegiatan" class="btn btn-primary">Simpan Kegiatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail Kegiatan -->
    <?php if ($detail_kegiatan): ?>
    <div class="modal fade show" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="false" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Kegiatan: <?php echo htmlspecialchars($detail_kegiatan['nama_kegiatan']); ?></h5>
                    <a href="admin_kegiatan.php" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6>Informasi Umum</h6>
                                </div>
                                <div class="card-body">
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
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6>Informasi Anggaran</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Anggaran:</strong> Rp <?php echo number_format($detail_kegiatan['anggaran'], 0, ',', '.'); ?></p>
                                    <p><strong>Realisasi:</strong> Rp <?php echo number_format($detail_kegiatan['realisasi'], 0, ',', '.'); ?></p>
                                    <p><strong>Sisa Anggaran:</strong> Rp <?php echo number_format($detail_kegiatan['sisa_anggaran'], 0, ',', '.'); ?></p>
                                    <div class="progress mt-2">
                                        <div class="progress-bar 
                                            <?php echo ($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100) >= 100 ? 'bg-success' : 
                                                   (($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100) >= 50 ? 'bg-warning' : 'bg-info'); ?>" 
                                            role="progressbar" 
                                            style="width: <?php echo min(($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100), 100); ?>%"
                                            aria-valuenow="<?php echo ($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100); ?>" 
                                            aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format(($detail_kegiatan['realisasi'] / $detail_kegiatan['anggaran'] * 100), 1); ?>% terealisasi</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6>Deskripsi Kegiatan</h6>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($detail_kegiatan['deskripsi'])); ?></p>
                        </div>
                    </div>

                    <!-- Rincian Penggunaan Dana -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6>Rincian Penggunaan Dana</h6>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#tambahDetailModal">
                                + Tambah Rincian
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($detail_penggunaan)): ?>
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
                                            <th>Aksi</th>
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
                                            <td>
                                                <button class="btn btn-warning btn-sm">Edit</button>
                                                <a href="?hapus_detail=<?php echo $item['id']; ?>&detail_id=<?php echo $detail_kegiatan['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Hapus rincian ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-success fw-bold">
                                            <td colspan="4">Total Penggunaan</td>
                                            <td>Rp <?php echo number_format(array_sum(array_column($detail_penggunaan, 'total')), 0, ',', '.'); ?></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Belum ada rincian penggunaan dana</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_kegiatan.php" class="btn btn-secondary">Tutup</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Detail Penggunaan -->
    <div class="modal fade" id="tambahDetailModal" tabindex="-1" aria-labelledby="tambahDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tambahDetailModalLabel">Tambah Rincian Penggunaan Dana</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="kegiatan_id" value="<?php echo $detail_kegiatan['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Item</label>
                            <input type="text" class="form-control" name="item" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" class="form-control" name="jumlah" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <input type="text" class="form-control" name="satuan" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Harga Satuan</label>
                                    <input type="number" class="form-control" name="harga_satuan" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total</label>
                            <input type="number" class="form-control" name="total" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_detail" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>