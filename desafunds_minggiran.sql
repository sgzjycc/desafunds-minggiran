-- Buat database
CREATE DATABASE IF NOT EXISTS desafunds_minggiran;
USE desafunds_minggiran;

-- Tabel admin
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel dusun (Desa Minggiran)
CREATE TABLE IF NOT EXISTS dusun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_dusun VARCHAR(100) NOT NULL UNIQUE
);

-- Tabel kategori forum
CREATE TABLE IF NOT EXISTS kategori_forum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE
);

-- Tabel forum posts
CREATE TABLE IF NOT EXISTS forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dusun_id INT NOT NULL,
    kategori_id INT NOT NULL,
    nama VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    admin_reply TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin_post BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (dusun_id) REFERENCES dusun(id),
    FOREIGN KEY (kategori_id) REFERENCES kategori_forum(id)
);

-- Tabel APBDes
CREATE TABLE IF NOT EXISTS apbdes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun YEAR NOT NULL,
    total_anggaran DECIMAL(15,2) NOT NULL,
    total_pemasukan DECIMAL(15,2) NOT NULL,
    total_pengeluaran DECIMAL(15,2) NOT NULL,
    sisa_dana DECIMAL(15,2) NOT NULL
);

-- Tabel jenis pemasukan
CREATE TABLE IF NOT EXISTS jenis_pemasukan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jenis VARCHAR(100) NOT NULL UNIQUE
);

-- Tabel pemasukan
CREATE TABLE IF NOT EXISTS pemasukan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apbdes_id INT NOT NULL,
    jenis_pemasukan_id INT NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    tanggal DATE NOT NULL,
    FOREIGN KEY (apbdes_id) REFERENCES apbdes(id),
    FOREIGN KEY (jenis_pemasukan_id) REFERENCES jenis_pemasukan(id)
);

-- Tabel jenis pengeluaran
CREATE TABLE IF NOT EXISTS jenis_pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jenis VARCHAR(100) NOT NULL UNIQUE
);

-- Tabel pengeluaran
CREATE TABLE IF NOT EXISTS pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apbdes_id INT NOT NULL,
    jenis_pengeluaran_id INT NOT NULL,
    nama_kegiatan VARCHAR(255) NOT NULL,
    anggaran DECIMAL(15,2) NOT NULL,
    realisasi DECIMAL(15,2) NOT NULL DEFAULT 0,
    sisa_anggaran DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    tanggal DATE NOT NULL,
    FOREIGN KEY (apbdes_id) REFERENCES apbdes(id),
    FOREIGN KEY (jenis_pengeluaran_id) REFERENCES jenis_pengeluaran(id)
);

-- Tabel kegiatan
CREATE TABLE IF NOT EXISTS kegiatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengeluaran_id INT NOT NULL,
    nama_kegiatan VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    lokasi VARCHAR(255) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NULL,
    anggaran DECIMAL(15,2) NOT NULL,
    realisasi DECIMAL(15,2) NOT NULL DEFAULT 0,
    sisa_anggaran DECIMAL(15,2) NOT NULL,
    status ENUM('rencana', 'berjalan', 'selesai') DEFAULT 'rencana',
    dokumentasi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengeluaran_id) REFERENCES pengeluaran(id) ON DELETE CASCADE
);

-- Tabel detail penggunaan dana kegiatan
CREATE TABLE IF NOT EXISTS detail_penggunaan_kegiatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kegiatan_id INT NOT NULL,
    item VARCHAR(255) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    satuan VARCHAR(50) NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    FOREIGN KEY (kegiatan_id) REFERENCES kegiatan(id) ON DELETE CASCADE
);

-- DATA DEFAULT

-- Admin default: username = admin, password = password (bcrypt hash)
INSERT INTO admin (username, password, nama_lengkap) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Desa Minggiran');

-- Dusun
INSERT INTO dusun (nama_dusun) VALUES 
('Minggiran'),
('Moranggan'),
('Rejowinangun');

-- Kategori forum
INSERT INTO kategori_forum (nama_kategori) VALUES 
('Infrastruktur'),
('Pendidikan'),
('Kesehatan'),
('Kesejahteraan Ekonomi'),
('Lainnya');

-- Jenis pemasukan
INSERT INTO jenis_pemasukan (nama_jenis) VALUES 
('Dana Desa'),
('Bagi Hasil Pajak'),
('Bantuan Provinsi'),
('Bantuan Kabupaten'),
('Pendapatan Asli Desa'),
('Hibah');

-- Jenis pengeluaran
INSERT INTO jenis_pengeluaran (nama_jenis) VALUES 
('Pembangunan Infrastruktur'),
('Pemberdayaan Masyarakat'),
('Pelayanan Publik'),
('Administrasi Desa'),
('Bantuan Sosial'),
('Lainnya');

-- Data APBDes beberapa tahun
INSERT INTO apbdes (tahun, total_anggaran, total_pemasukan, total_pengeluaran, sisa_dana) VALUES 
(2024, 750000000.00, 720000000.00, 600000000.00, 120000000.00),
(2025, 850000000.00, 820000000.00, 650000000.00, 170000000.00);

-- Data pemasukan 2025
INSERT INTO pemasukan (apbdes_id, jenis_pemasukan_id, jumlah, keterangan, tanggal) VALUES 
(2, 1, 720000000.00, 'Dana Desa dari Pemerintah Pusat', '2025-01-15'),
(2, 2, 50000000.00, 'Bagi Hasil Pajak Kabupaten', '2025-02-10'),
(2, 3, 30000000.00, 'Bantuan Provinsi Jawa Timur', '2025-03-05'),
(2, 5, 20000000.00, 'Hasil BUMDes', '2025-04-20');

-- Data pengeluaran 2025
INSERT INTO pengeluaran (apbdes_id, jenis_pengeluaran_id, nama_kegiatan, anggaran, realisasi, sisa_anggaran, keterangan, tanggal) VALUES 
(2, 1, 'Perbaikan Jalan Dusun Minggiran', 120000000.00, 95000000.00, 25000000.00, 'Perbaikan jalan sepanjang 2 km', '2025-01-20'),
(2, 1, 'Pembangunan Balai Desa', 200000000.00, 150000000.00, 50000000.00, 'Pembangunan balai desa 2 lantai', '2025-02-15'),
(2, 2, 'Pelatihan UMKM', 50000000.00, 35000000.00, 15000000.00, 'Pelatihan untuk 50 pelaku UMKM', '2025-03-10');

-- Data kegiatan 2025
INSERT INTO kegiatan (pengeluaran_id, nama_kegiatan, deskripsi, lokasi, tanggal_mulai, tanggal_selesai, anggaran, realisasi, sisa_anggaran, status, dokumentasi) VALUES 
(1, 'Perbaikan Jalan Dusun Minggiran', 'Pengerasan dan pengaspalan jalan sepanjang 2 km di Dusun Minggiran yang rusak akibat hujan', 'Dusun Minggiran', '2025-01-25', '2025-03-15', 120000000.00, 95000000.00, 25000000.00, 'selesai', NULL),
(2, 'Pembangunan Balai Desa', 'Pembangunan balai desa 2 lantai dengan fasilitas ruang pertemuan, perpustakaan, dan ruang administrasi', 'Lingkungan Balai Desa', '2025-02-20', '2025-06-30', 200000000.00, 150000000.00, 50000000.00, 'berjalan', NULL);

-- Contoh detail penggunaan dana kegiatan
INSERT INTO detail_penggunaan_kegiatan (kegiatan_id, item, jumlah, satuan, harga_satuan, total, keterangan) VALUES 
(1, 'Aspal', 200, 'ton', 450000.00, 90000000.00, 'Aspal hotmix kualitas terbaik'),
(1, 'Tenaga Kerja', 150, 'HOK', 30000.00, 4500000.00, 'Tenaga kerja lokal'),
(2, 'Semen', 500, 'sak', 70000.00, 35000000.00, 'Semen Portland');

-- Contoh forum posts
INSERT INTO forum_posts (dusun_id, kategori_id, nama, pesan, admin_reply, created_at) VALUES 
(1, 1, 'Budi Santoso', 'Mohon perbaikan jalan di depan balai desa, banyak lubang yang membahayakan pengendara', 'Terima kasih atas masukannya Pak Budi. Sudah kami catat dan akan kami prioritaskan dalam APBDes berikutnya.', NOW()),
(2, 4, 'Siti Rahayu', 'Apakah ada program bantuan modal UMKM untuk ibu-ibu di Dusun Moranggan?', 'Iya Bu Siti, ada program bantuan modal UMKM. Silakan datang ke kantor desa dengan membawa proposal usaha.', NOW()),
(3, 2, 'Ahmad Fauzi', 'Anak-anak di Dusun Rejowinangun kesulitan mengikuti pembelajaran online karena sinyal internet buruk', NULL, NOW());