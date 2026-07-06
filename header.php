<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STIKOM Library - Sistem Manajemen Perpustakaan</title>
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a365d; /* Biru gelap */
            --secondary-color: #ed8936; /* Oranye */
            --bg-color: #f7fafc;
        }
        body { 
            background-color: var(--bg-color); 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar { 
            background-color: transparent; 
            padding: 1.5rem 0;
            transition: all 0.3s ease;
            position: absolute; /* Membuat navbar melayang di atas gambar */
            width: 100%;
            z-index: 10;
        }
        /* Navbar background saat discroll atau di halaman selain index */
        .navbar.bg-solid { background-color: var(--primary-color) !important; padding: 1rem 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); position: sticky; top: 0; }
        
        .navbar-brand { font-weight: 700; font-size: 1.4rem; color: white !important; }
        .nav-link { color: rgba(255,255,255,0.9) !important; font-weight: 500; font-size: 0.95rem; margin-right: 15px; transition: all 0.3s;}
        .nav-link:hover, .nav-link.active { color: var(--secondary-color) !important; }
        .main-content { flex: 1; }
        .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: none; }
    </style>
</head>
<body>

<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$is_home = ($current_page == 'index.php' && !isset($_GET['p']));
?>

<nav class="navbar navbar-expand-lg <?= $is_home ? '' : 'bg-solid' ?>" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-layer-group fa-lg me-2 text-warning"></i>
            STIKOM Library
        </a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?= $is_home ? 'active' : '' ?>" href="index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'buku.php' ? 'active' : '' ?>" href="buku.php">Katalog Buku</a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <!-- MENU KHUSUS JIKA SUDAH LOGIN -->
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'peminjaman.php' ? 'active' : '' ?>" href="peminjaman.php">
                            <?= hasRole(['Admin', 'Pustakawan']) ? 'Sirkulasi' : 'Area Anggota' ?>
                        </a>
                    </li>
                    <?php if (hasRole('Admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'pengguna.php' ? 'active' : '' ?>" href="pengguna.php">Pustakawan</a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item ms-3">
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle text-white d-flex align-items-center bg-dark bg-opacity-25 rounded-pill px-3 py-1" href="#" data-bs-toggle="dropdown">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama_lengkap']) ?>&background=ed8936&color=fff&rounded=true" width="28" height="28" class="me-2 shadow-sm">
                                <span style="font-size: 0.85rem; font-weight: 600;"><?= $_SESSION['nama_lengkap'] ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
                            </ul>
                        </div>
                    </li>
                <?php else: ?>
                    <!-- MENU LOGIN UNTUK PUBLIK ALA SLIMS -->
                    <li class="nav-item ms-2">
                        <a class="nav-link <?= (isset($_GET['p']) && $_GET['p'] == 'member') ? 'active fw-bold' : '' ?>" href="index.php?p=member">
                            Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">