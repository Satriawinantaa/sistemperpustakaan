<?php
// 1. =========================================================================
// PENGATURAN SESSION (AUTENTIKASI)
// =========================================================================

// Memulai atau melanjutkan sesi aktif untuk menyimpan status login pengguna di server
session_start();


// 2. =========================================================================
// KONEKSI DATABASE (SQLITE INTERN)
// =========================================================================

// Menentukan letak dan nama berkas database SQLite di dalam direktori yang sama (__DIR__)
$db_file = __DIR__ . '/database_perpustakaan.sqlite';

try {
    // Membuat instansiasi objek PDO baru untuk mengoneksikan PHP ke file database SQLite
    $pdo = new PDO("sqlite:" . $db_file);
    
    // Mengatur mode eror PDO agar melemparkan pengecualian (Exception) saat terjadi kesalahan query
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Menghentikan script dan menampilkan pesan galat jika koneksi ke database gagal dilakukan
    die("Koneksi Database Gagal: " . $e->getMessage());
}


// 3. =========================================================================
// OTOMATIS BUAT TABEL (MIGRASI DATABASE)
// =========================================================================

// Membuat tabel 'users' jika belum ada untuk menyimpan data login akun dan hak akses/role
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, 
    password TEXT, nama_lengkap TEXT, role TEXT
)");

// Membuat tabel 'books' jika belum ada untuk menyimpan katalog data buku perpustakaan
$pdo->exec("CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT, 
    pengarang TEXT, penerbit TEXT, stok INTEGER
)");

// Membuat tabel 'loans' jika belum ada untuk mencatat data transaksi peminjaman buku oleh anggota
$pdo->exec("CREATE TABLE IF NOT EXISTS loans (
    id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, 
    book_id INTEGER, tanggal_pinjam TEXT, status TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(book_id) REFERENCES books(id)
)");


// 4. =========================================================================
// SEEDING DATA AWAL (ISI DATA DEFAULT JIKA DATABASE KOSONG)
// =========================================================================

// Mengecek jumlah baris/data yang ada di dalam tabel 'users' saat ini
$stmt = $pdo->query("SELECT COUNT(*) FROM users");

// Jalankan blok pengisian data jika hasil hitung data user masih bernilai 0 (kosong)
if ($stmt->fetchColumn() == 0) {
    // Mengamankan kata sandi 'admin123' menggunakan algoritma hashing standar bawaan PHP
    $passAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Mengamankan kata sandi 'pustaka123' menggunakan algoritma hashing standar bawaan PHP
    $passPustakawan = password_hash('pustaka123', PASSWORD_DEFAULT);
    
    // Mengamankan kata sandi 'anggota123' menggunakan algoritma hashing standar bawaan PHP
    $passAnggota = password_hash('anggota123', PASSWORD_DEFAULT);
    
    // Memasukkan data akun berhak akses 'Admin' ke tabel users untuk kebutuhan kelola sistem
    $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('admin', '$passAdmin', 'Administrator Utama', 'Admin')");
    
    // Memasukkan data akun berhak akses 'Pustakawan' ke tabel users untuk kebutuhan sirkulasi buku
    $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('pustakawan', '$passPustakawan', 'Petugas Perpus', 'Pustakawan')");
    
    // Memasukkan data akun berhak akses 'Anggota' ke tabel users untuk simulasi user mahasiswa/siswa
    $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('budi', '$passAnggota', 'Budi Santoso', 'Anggota')");
    
    // Memasukkan contoh buku pertama ke tabel katalog books dengan jumlah persediaan stok 5
    $pdo->exec("INSERT INTO books (judul, pengarang, penerbit, stok) VALUES ('Belajar PHP Native Modular', 'John Doe', 'Tech Press', 5)");
    
    // Memasukkan contoh buku kedua ke tabel katalog books dengan jumlah persediaan stok 2
    $pdo->exec("INSERT INTO books (judul, pengarang, penerbit, stok) VALUES ('Struktur Basis Data', 'Jane Smith', 'Edu Media', 2)");
}


// 5. =========================================================================
// FUNGSI BANTUAN (HELPER FUNCTIONS UNTUK VALIDASI)
// =========================================================================

// Fungsi untuk mengecek apakah penjelajah web saat ini sudah melewati proses login resmi
function isLoggedIn() {
    // Mengembalikan nilai true jika index 'user_id' sudah tersimpan di dalam global session
    return isset($_SESSION['user_id']);
}

// Fungsi untuk mengecek kesesuaian hak akses user yang aktif terhadap batasan halaman
function hasRole($roles) {
    // Mengembalikan true jika user sudah login DAN rolenya terdaftar di dalam daftar array $roles
    return isLoggedIn() && in_array($_SESSION['role'], (array)$roles);
}

// Fungsi proteksi halaman agar tidak bisa diintip oleh pengunjung yang belum login
function requireLogin() {
    // Jika fungsi cek login menghasilkan false (belum login), paksa browser pindah ke login.php
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit; // Menghentikan eksekusi baris kode di bawahnya demi keamanan data
    }
}

// Fungsi proteksi ketat halaman khusus berdasarkan klasifikasi hak akses (Contoh: Hanya Admin)
function requireRole($roles) {
    // Jika role user tidak memenuhi kriteria $roles, munculkan notifikasi javascript dan usir ke beranda
    if (!hasRole($roles)) {
        echo "<script>alert('Akses Ditolak! Anda tidak memiliki izin.'); window.location.href='index.php';</script>";
        exit; // Menghentikan seluruh proses pembacaan file PHP lebih lanjut
    }
}
?>