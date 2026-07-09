<?php
require_once 'config.php';


// -- LOGIKA PROSES LOGIN (Masuk Anggota) --
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_process'])) {
    // 1. Ambil input dan bersihkan spasi kosong di awal/akhir teks dengan trim()
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 2. VALIDASI SEDERHANA: Pastikan kolom tidak kosong
    if (empty($username) || empty($password)) {
        $msg = "<div class='alert alert-warning shadow-sm border-0'><i class='fas fa-exclamation-triangle me-2'></i>Gagal: Username dan Kata Sandi wajib diisi!</div>";
    } else {
        // 3. JIKA LOLOS VALIDASI: Jalankan perintah ke database (Kode Asli Kamu)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Berhasil login, set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php"); 
            exit;
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0'>NIM/Username salah atau belum terdaftar. Silahkahkan datang secara langsung ke perpustakaan</div>";
        }
    }
}

// Ambil Statistik untuk widget
$jmlBuku = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$jmlUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$jmlPinjam = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Dipinjam'")->fetchColumn();

// Ambil 4 Buku Terbaru untuk List
$buku_terbaru = $pdo->query("SELECT * FROM books ORDER BY id DESC LIMIT 4")->fetchAll();

include 'header.php';

// Cek apakah halaman yang dibuka adalah halaman login (member area)
$p = $_GET['p'] ?? '';
?>

<style>
    .custom-search-form {
        background: rgba(255, 255, 255, 0.2) !important;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 50px !important; /* Membuat sudut membulat elegan ala modern search */
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .custom-search-form:focus-within {
        background: rgba(255, 255, 255, 0.35) !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2) !important;
        border-color: rgba(255, 255, 255, 0.5);
    }
    .custom-search-input {
        background: transparent !important;
        color: #ffffff !important;
    }
    .custom-search-input::placeholder {
        color: rgba(255, 255, 255, 0.75) !important;
    }
    .custom-search-btn {
        background: transparent !important;
        color: #ffffff !important;
        transition: transform 0.2s;
    }
    .custom-search-btn:hover {
        color: var(--secondary-color) !important;
        transform: scale(1.1);
    }
</style>

<?php if ($p === 'member' && !isLoggedIn()): ?>
    <div style="background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.7)), url('https://www.stikom-bali.ac.id/id/wp-content/uploads/2021/05/BNN.jpg') center/cover no-repeat; padding: 140px 0 60px 0; margin-top: 0;">
        <div class="container text-center mt-3">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="buku.php" method="GET" class="d-flex p-1 shadow custom-search-form">
                        <input type="text" name="search" class="form-control border-0 p-3 fs-5 custom-search-input" placeholder="Masukkan kata kunci untuk mencari koleksi..." style="box-shadow: none;">
                        <button type="submit" class="btn border-0 px-4 fs-4 custom-search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5" style="min-height: 45vh;">
        <h2 class="fw-bold mb-3" style="color: #333;">Masuk Anggota Perpustakaan</h2>
        <hr class="mb-4" style="border-color: #ddd;">
        
        <p class="text-dark mb-4" style="font-size: 1.05rem;">
            Masukkan NIM/Username serta kata sandi yang diberikan oleh administrator sistem perpustakaan
        </p>
        
        <div class="row">
            <div class="col-md-5">
                <?= $msg ?>
                <form method="POST">
                    <input type="hidden" name="login_process" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 0.95rem;">NIM/Username</label>
                        <input type="text" name="username" class="form-control p-2" placeholder="NIM/Username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size: 0.95rem;">Kata Sandi</label>
                        <input type="password" name="password" class="form-control p-2" placeholder="Enter password" required>
                    </div>
                    <button type="submit" class="btn btn-primary px-4 py-2" style="background-color: #0d6efd; border-color: #0d6efd;">Masuk</button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <div style="background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.7)), url('https://www.stikom-bali.ac.id/id/wp-content/uploads/2021/05/BNN.jpg') center/cover no-repeat; padding: 180px 0 140px 0; margin-top: 0; min-height: 500px; display: flex; align-items: center;">
        <div class="container text-center text-white mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="buku.php" method="GET" class="d-flex p-2 shadow-lg custom-search-form">
                        <input type="text" name="search" class="form-control border-0 p-3 fs-5 custom-search-input" placeholder="Masukkan kata kunci untuk mencari koleksi..." style="box-shadow: none;">
                        <button type="submit" class="btn border-0 px-4 fs-4 custom-search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="margin-top: -50px; position: relative; z-index: 5;">
        <div class="bg-white p-4 p-md-5 shadow-sm rounded-4 border mb-5">
            <h5 class="fw-bold mb-4" style="color: var(--primary-color);">
                <i class="fas fa-book-open text-warning me-2"></i>Koleksi baru dan diperbarui
            </h5>
            
            <div class="row">
                <?php foreach($buku_terbaru as $b): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 bg-light" style="border-radius: 12px;">
                        <div class="card-body text-center mt-3">
                            <div class="mb-3">
                                <div style="width: 110px; height: 150px; background: #e2e8f0; margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 4px 4px 10px rgba(0,0,0,0.1); border-radius: 4px;">
                                    <i class="fas fa-book fa-3x text-secondary"></i>
                                </div>
                            </div>
                            <h6 class="card-title fw-bold text-truncate px-2" title="<?= htmlspecialchars($b['judul']) ?>"><?= htmlspecialchars($b['judul']) ?></h6>
                            <p class="card-text small text-muted mb-2 text-truncate px-2"><?= htmlspecialchars($b['pengarang']) ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-4">
                            <a href="buku.php" class="btn btn-sm btn-outline-primary rounded-pill px-4">Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('mainNavbar');
        <?php if ($is_home): ?>
            if (window.scrollY > 50) {
                navbar.classList.add('bg-solid');
            } else {
                navbar.classList.remove('bg-solid');
            }
        <?php endif; ?>
    });
</script>

<?php include 'footer.php'; ?>