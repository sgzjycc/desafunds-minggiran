<?php
require_once 'config.php';

if (!$is_admin_logged_in) {
    header("Location: admin_login.php");
    exit();
}

// Ambil data dusun & kategori
$dusun_list = $pdo->query("SELECT * FROM dusun ORDER BY nama_dusun")->fetchAll();
$kategori_list = $pdo->query("SELECT * FROM kategori_forum ORDER BY nama_kategori")->fetchAll();

// Balasan admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $post_id = $_POST['post_id'];
    $admin_reply = $_POST['admin_reply'];

    $stmt = $pdo->prepare("UPDATE forum_posts SET admin_reply = ? WHERE id = ?");
    $stmt->execute([$admin_reply, $post_id]);

    header("Location: admin_forum.php?reply_success=1");
    exit();
}

// Delete pesan
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = ?");
    $stmt->execute([$delete_id]);

    header("Location: admin_forum.php?delete_success=1");
    exit();
}

// Ambil postingan
$stmt = $pdo->query("
    SELECT fp.*, d.nama_dusun, kf.nama_kategori
    FROM forum_posts fp
    JOIN dusun d ON fp.dusun_id = d.id
    JOIN kategori_forum kf ON fp.kategori_id = kf.id
    ORDER BY fp.created_at DESC
");
$posts = $stmt->fetchAll();

function getAvatarClass($name) {
    $firstChar = strtoupper(substr($name,0,1));
    $map = [
        'B'=>'avatar-budi','A'=>'avatar-ani','R'=>'avatar-rina','J'=>'avatar-johan',
        'H'=>'avatar-harji','S'=>'avatar-siti','M'=>'avatar-maya'
    ];
    return $map[$firstChar] ?? 'avatar-budi';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Forum Admin - DesaFunds</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            DesaFunds - Admin
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(1);" aria-hidden="true"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_apbdes.php">Kelola APBDes</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_kegiatan.php">Kelola Kegiatan</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin_forum.php">Kelola Forum</a></li>
                <li class="nav-item">
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
<div class="container mt-5 mb-5">
    <h2 class="text-center fw-bold mb-4">Kelola Forum Warga</h2>

    <?php if(isset($_GET['reply_success'])): ?>
        <div class="alert alert-success">Balasan berhasil disimpan!</div>
    <?php endif; ?>

    <?php if(isset($_GET['delete_success'])): ?>
        <div class="alert alert-success">Pesan berhasil dihapus!</div>
    <?php endif; ?>

    <!-- Statistik (ditampilkan sesuai permintaan) -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title">Total Pesan</h5>
                    <p class="stats-number"><?php echo count($posts); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title">Pesan Dibalas Admin</h5>
                    <p class="stats-number"><?php echo array_sum(array_map(fn($p)=>!empty($p['admin_reply'])?1:0,$posts)); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title">Belum Dibalas</h5>
                    <p class="stats-number"><?php echo array_sum(array_map(fn($p)=>empty($p['admin_reply'])?1:0,$posts)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- LIST PESAN -->
    <?php foreach ($posts as $post): ?>
        <div class="forum-item mb-3 p-3 rounded shadow-sm border">
            <div class="row">
                <div class="col-auto">
                    <div class="avatar <?php echo getAvatarClass($post['nama']); ?>">
                        <?php echo strtoupper($post['nama'][0]); ?>
                    </div>
                </div>

                <div class="col">
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($post['nama']); ?></h5>
                    <p class="text-secondary small mb-1"><?php echo $post['nama_dusun']; ?> - <?php echo $post['nama_kategori']; ?></p>

                    <p><?php echo nl2br(htmlspecialchars($post['pesan'])); ?></p>
                    <small class="text-muted"><?php echo $post['created_at']; ?></small>

                    <div class="mt-3">
                        <a href="admin_forum.php?delete_id=<?php echo $post['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus pesan ini?')">Hapus</a>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#reply<?php echo $post['id']; ?>">Balas / Edit</button>
                    </div>

                    <div class="collapse mt-3" id="reply<?php echo $post['id']; ?>">
                        <form method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <textarea name="admin_reply" rows="3" class="form-control mb-2" placeholder="Balasan admin..."><?php echo htmlspecialchars($post['admin_reply']); ?></textarea>
                            <button type="submit" class="btn btn-success btn-sm" name="submit_reply">Simpan Balasan</button>
                        </form>
                    </div>

                    <?php if(!empty($post['admin_reply'])): ?>
                        <div class="admin-reply-box p-3 bg-light border rounded mt-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar avatar-admin me-2">A</div>
                                <strong>Admin:</strong>
                            </div>

                            <p><?php echo nl2br(htmlspecialchars($post['admin_reply'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>