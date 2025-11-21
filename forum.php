<?php require_once 'config.php';

// Ambil data dusun & kategori
try {
    $dusun_list = $pdo->query("SELECT * FROM dusun ORDER BY nama_dusun")->fetchAll();
    $kategori_list = $pdo->query("SELECT * FROM kategori_forum ORDER BY nama_kategori")->fetchAll();
} catch(PDOException $e) {
    $error = "Gagal memuat data: " . $e->getMessage();
}

// Handle form kirim pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_kritik'])) {
    $dusun_id = clean_input($_POST['dusun_id'] ?? '');
    $kategori_id = clean_input($_POST['kategori_id'] ?? '');
    $nama = clean_input($_POST['nama'] ?? '');
    $pesan = clean_input($_POST['pesan'] ?? '');
    if ($dusun_id && $kategori_id && $nama && $pesan) {
        try {
            $stmt = $pdo->prepare("INSERT INTO forum_posts (dusun_id, kategori_id, nama, pesan, is_admin_post) VALUES (?, ?, ?, ?, FALSE)");
            $stmt->execute([$dusun_id, $kategori_id, $nama, $pesan]);
            header("Location: forum.php?success=1");
            exit();
        } catch(PDOException $e) {
            $error = "Gagal mengirim pesan: " . $e->getMessage();
        }
    } else {
        $error = "Semua field harus diisi!";
    }
}

// Filter/search
$search = $_GET['search'] ?? '';
$filter_dusun = $_GET['dusun'] ?? '';
$filter_kategori = $_GET['kategori'] ?? '';
try {
    $query = "
        SELECT fp.*, d.nama_dusun, kf.nama_kategori 
        FROM forum_posts fp 
        JOIN dusun d ON fp.dusun_id = d.id 
        JOIN kategori_forum kf ON fp.kategori_id = kf.id WHERE 1=1
    ";
    $params = [];
    if (!empty($search)) {
        $query .= " AND (fp.nama LIKE ? OR fp.pesan LIKE ? OR fp.admin_reply LIKE ?)";
        for($i=0;$i<3;$i++) $params[]="%$search%";
    }
    if (!empty($filter_dusun)) {
        $query .= " AND fp.dusun_id = ?";
        $params[] = $filter_dusun;
    }
    if (!empty($filter_kategori)) {
        $query .= " AND fp.kategori_id = ?";
        $params[] = $filter_kategori;
    }
    $query .= " ORDER BY fp.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch(PDOException $e) {
    $posts = [];
    $error = "Gagal memuat data forum: " . $e->getMessage();
}

function getAvatarClass($name) {
    $firstChar = strtoupper(substr($name,0,1));
    $avatarMap = ['B'=>'avatar-budi','A'=>'avatar-ani','R'=>'avatar-rina','J'=>'avatar-johan','H'=>'avatar-harji','S'=>'avatar-siti','M'=>'avatar-maya'];
    return $avatarMap[$firstChar] ?? 'avatar-budi';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Forum Warga - DesaFunds Minggiran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
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

        .forum-item {
            border: 1.6px solid #222;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 12px;
            background-color: #fff;
            transition: all 0.2s ease;
        }

        .forum-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            user-select: none;
            margin-right: 12px;
            box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.15);
        }

        .avatar-budi { background-color: #e36262; }
        .avatar-ani { background-color: #3a70db; }
        .avatar-rina { background-color: #7ca178; }
        .avatar-johan { background-color: #49b95f; }
        .avatar-harji { background-color: #cade58; color: #222; }
        .avatar-siti { background-color: #e362e3; }
        .avatar-maya { background-color: #8a2be2; }
        .avatar-admin { background-color: #dc3545; }

        .admin-reply {
            margin-top: 8px;
            border-left: 3px solid #dc3545;
            padding-left: 10px;
            font-style: italic;
            color: #dc3545;
            background-color: #f8d7da;
            border-radius: 6px;
            padding: 10px;
        }

        .forum-item.has-reply {
            border-left: 4px solid #28a745;
        }

        .btn-delete, .btn-reply {
            font-size: 0.85rem;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            user-select: none;
        }

        .btn-delete {
            background-color: #dc3545;
            border: none;
            color: white;
        }

        .btn-delete:hover {
            background-color: #b02a37;
            color: white;
        }

        .btn-reply {
            background-color: #0d6efd;
            border: none;
            color: white;
            margin-left: 8px;
        }

        .btn-reply:hover {
            background-color: #084298;
            color: white;
        }

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .reply-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #28a745;
            margin-left: 5px;
        }

        .badge-dusun {
            background-color: #6f42c1;
        }

        .badge-kategori {
            background-color: #20c997;
        }
    </style>
</head>
<body>
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
                <li class="nav-item"><a class="nav-link " href="apbdes.php">APBDes</a></li>
                <li class="nav-item"><a class="nav-link" href="kegiatan.php">Kegiatan</a></li>
                <li class="nav-item"><a class="nav-link active" href="forum.php">Forum</a></li>
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
        <h1 class="text-center mb-4 fw-bold">Forum Kritik dan Saran Desa Minggiran</h1>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Kritik dan saran berhasil dikirim!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['reply_success']) && $_GET['reply_success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Balasan berhasil dikirim!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['delete_success']) && $_GET['delete_success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Pesan berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Form Kritik dan Saran -->
        <div class="form-container">
            <h4 class="mb-3">Berikan Kritik dan Saran</h4>
            <form id="formKritik" method="POST" aria-label="Form kritik dan saran">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="dusun_id" class="form-label">Dusun</label>
                        <select class="form-select" id="dusun_id" name="dusun_id" required>
                            <option value="">Pilih Dusun</option>
                            <?php foreach($dusun_list as $dusun): ?>
                                <option value="<?php echo $dusun['id']; ?>"><?php echo htmlspecialchars($dusun['nama_dusun']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="kategori_id" class="form-label">Topik</label>
                        <select class="form-select" id="kategori_id" name="kategori_id" required>
                            <option value="">Pilih Topik</option>
                            <?php foreach($kategori_list as $kategori): ?>
                                <option value="<?php echo $kategori['id']; ?>"><?php echo htmlspecialchars($kategori['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" autocomplete="off" required
                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="pesan" class="form-label">Pesan</label>
                        <textarea class="form-control" id="pesan" name="pesan" rows="4" required placeholder="Tuliskan kritik, saran, atau pertanyaan Anda..."><?php echo isset($_POST['pesan']) ? htmlspecialchars($_POST['pesan']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-secondary">Hapus</button>
                    <button type="submit" name="submit_kritik" class="btn btn-primary">Kirim Pesan</button>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="filter-container">
            <h5 class="mb-3">Filter dan Pencarian</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="search" name="search" class="form-control" placeholder="Cari pesan..."
                           value="<?php echo htmlspecialchars($search); ?>" aria-label="Cari di forum">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="dusun">
                        <option value="">Semua Dusun</option>
                        <?php foreach($dusun_list as $dusun): ?>
                            <option value="<?php echo $dusun['id']; ?>" <?php echo $filter_dusun == $dusun['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dusun['nama_dusun']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach($kategori_list as $kategori): ?>
                            <option value="<?php echo $kategori['id']; ?>" <?php echo $filter_kategori == $kategori['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                    <?php if ($search || $filter_dusun || $filter_kategori): ?>
                        <a href="forum.php" class="btn btn-outline-secondary w-100 mt-2">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Forum Messages List -->
        <section id="forumList" role="list" aria-label="Daftar pembahasan forum" class="mb-4">
            <?php if (empty($posts)): ?>
                <div class="text-center text-muted py-5">
                    <?php if (!empty($search) || !empty($filter_dusun) || !empty($filter_kategori)): ?>
                        <h5>Tidak ada pesan yang cocok dengan filter</h5>
                        <p>Coba ubah kriteria pencarian atau filter Anda.</p>
                    <?php else: ?>
                        <h5>Belum ada pembahasan</h5>
                        <p>Jadilah yang pertama memberikan kritik dan saran!</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <article class="forum-item d-flex flex-column <?php echo !empty($post['admin_reply']) ? 'has-reply' : ''; ?>" data-id="<?php echo $post['id']; ?>">
                        <div class="d-flex align-items-center mb-2">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="avatar <?php echo getAvatarClass($post['nama']); ?>">
                                    <?php echo strtoupper(substr($post['nama'], 0, 1)); ?>
                                </div>
                                <div class="ms-2">
                                    <div class="fw-semibold d-flex align-items-center">
                                        <span><?php echo htmlspecialchars($post['nama']); ?></span>
                                        <?php if (!empty($post['admin_reply'])): ?>
                                            <span class="reply-indicator" title="Sudah dibalas admin"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <span class="badge badge-dusun"><?php echo htmlspecialchars($post['nama_dusun']); ?></span>
                                        <span class="badge badge-kategori"><?php echo htmlspecialchars($post['nama_kategori']); ?></span>
                                        <span><?php echo date('d M Y H:i', strtotime($post['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($is_admin_logged_in && !$post['is_admin_post']): ?>
                                <div class="ms-auto d-flex align-items-center">
                                    <a href="?delete_id=<?php echo $post['id']; ?>" class="btn btn-delete btn-sm"
                                       onclick="return confirm('Hapus pesan dari <?php echo addslashes($post['nama']); ?>?')">
                                        Hapus
                                    </a>
                                    <button type="button" class="btn btn-reply btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#replyModal<?php echo $post['id']; ?>">
                                        <?php echo empty($post['admin_reply']) ? 'Balas' : 'Edit Balasan'; ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-2">
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($post['pesan'])); ?></p>
                           
                            <?php if (!empty($post['admin_reply'])): ?>
                                <div class="admin-reply">
                                    <strong>Balasan Admin:</strong> <?php echo nl2br(htmlspecialchars($post['admin_reply'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>

                    <!-- Reply Modal -->
                    <?php if ($is_admin_logged_in && !$post['is_admin_post']): ?>
                        <div class="modal fade" id="replyModal<?php echo $post['id']; ?>" tabindex="-1" aria-labelledby="replyModalLabel<?php echo $post['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="replyModalLabel<?php echo $post['id']; ?>">
                                                Balas ke <?php echo htmlspecialchars($post['nama']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Pesan Asli:</label>
                                                <p class="form-control-plaintext border p-2 rounded bg-light"><?php echo nl2br(htmlspecialchars($post['pesan'])); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label for="admin_reply<?php echo $post['id']; ?>" class="form-label">Balasan Admin:</label>
                                                <textarea class="form-control" id="admin_reply<?php echo $post['id']; ?>" name="admin_reply" rows="4" required><?php echo htmlspecialchars($post['admin_reply'] ?? ''); ?></textarea>
                                            </div>
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="submit_reply" class="btn btn-primary">Kirim Balasan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Statistik Forum -->
        <div class="form-container">
            <h5 class="mb-3">Statistik Forum</h5>
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-primary"><?php echo count($posts); ?></h4>
                        <small class="text-muted">Total Pesan</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-success"><?php echo count(array_filter($posts, function($post) { return !empty($post['admin_reply']); })); ?></h4>
                        <small class="text-muted">Pesan Dibalas</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-warning"><?php echo count(array_filter($posts, function($post) { return empty($post['admin_reply']); })); ?></h4>
                        <small class="text-muted">Menunggu Balasan</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-info"><?php echo count($dusun_list); ?></h4>
                        <small class="text-muted">Dusun Terdaftar</small>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
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