<?php
// 1. Mulai Session untuk autentikasi
session_start();

// 2. Koneksi Database SQLite
$db_file = __DIR__ . '/database_perpustakaan.sqlite';
try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// 3. Otomatis Buat Tabel (Migrasi)
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, 
    password TEXT, nama_lengkap TEXT, role TEXT
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT, 
    pengarang TEXT, penerbit TEXT, stok INTEGER
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS loans (
    id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, 
    book_id INTEGER, tanggal_pinjam TEXT, status TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(book_id) REFERENCES books(id)
)");

// 4. Seeding Data Awal (Akun Default & Buku)
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $passAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    $passPustakawan = password_hash('pustaka123', PASSWORD_DEFAULT);
    $passAnggota = password_hash('anggota123', PASSWORD_DEFAULT);
    
    $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('admin', '$passAdmin', 'Administrator Utama', 'Admin')");
    $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('pustakawan', '$passPustakawan', 'Petugas Perpus', 'Pustakawan')");
    $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('budi', '$passAnggota', 'Budi Santoso', 'Anggota')");
    
    $pdo->exec("INSERT INTO books (judul, pengarang, penerbit, stok) VALUES ('Belajar PHP Native Modular', 'John Doe', 'Tech Press', 5)");
    $pdo->exec("INSERT INTO books (judul, pengarang, penerbit, stok) VALUES ('Struktur Basis Data', 'Jane Smith', 'Edu Media', 2)");
}

// 5. Fungsi Bantuan (Helper Functions)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($roles) {
    return isLoggedIn() && in_array($_SESSION['role'], (array)$roles);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($roles) {
    if (!hasRole($roles)) {
        echo "<script>alert('Akses Ditolak! Anda tidak memiliki izin.'); window.location.href='index.php';</script>";
        exit;
    }
}
?>