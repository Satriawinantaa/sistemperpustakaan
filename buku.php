<?php
require_once 'config.php';
// HAPUS requireLogin() dari sini agar user yang belum login bisa melihat isi katalog!

// Ambil ID user dari session (jika ada)
$user_id = $_SESSION['user_id'] ?? 0;
$total_dipinjam = 0;

// Hitung kuota aktif hanya jika user sudah login dan rolenya Anggota
if (isset($_SESSION['user_id']) && hasRole('Anggota')) {
    $stmtCheckActive = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE user_id = ? AND status = 'Dipinjam'");
    $stmtCheckActive->execute([$user_id]);
    $total_dipinjam = (int)$stmtCheckActive->fetchColumn();
}

// -- PROSES POST DATA --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // Admin & Pustakawan: Tambah Buku
        if ($action === 'add' && isset($_SESSION['user_id']) && hasRole(['Admin', 'Pustakawan'])) {
            $stmt = $pdo->prepare("INSERT INTO books (judul, pengarang, penerbit, stok) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['judul'], $_POST['pengarang'], $_POST['penerbit'], $_POST['stok']]);
            header("Location: buku.php"); exit;
        }
        // Admin & Pustakawan: EDIT Buku (FITUR BARU)
        elseif ($action === 'edit' && isset($_SESSION['user_id']) && hasRole(['Admin', 'Pustakawan'])) {
            $stmt = $pdo->prepare("UPDATE books SET judul = ?, pengarang = ?, penerbit = ?, stok = ? WHERE id = ?");
            $stmt->execute([$_POST['judul'], $_POST['pengarang'], $_POST['penerbit'], $_POST['stok'], $_POST['id']]);
            header("Location: buku.php"); exit;
        }
        // Admin & Pustakawan: Hapus Buku
        elseif ($action === 'delete' && isset($_SESSION['user_id']) && hasRole(['Admin', 'Pustakawan'])) {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header("Location: buku.php"); exit;
        }
        // Anggota: Pinjam Buku dengan Kuantitas Jumlah
        elseif ($action === 'borrow') {
            // JIKA MENEKAN PINJAM TAPI BELUM LOGIN, PAKSA LOGIN SEKARANG
            requireLogin(); 
            
            if (hasRole('Anggota')) {
                $book_id = $_POST['book_id'];
                $jumlah_pinjam = isset($_POST['jumlah_pinjam']) ? (int)$_POST['jumlah_pinjam'] : 1;
                
                // 1. Validasi Batas Peminjaman Global (Maksimal 2 Buku Aktif)
                if (($total_dipinjam + $jumlah_pinjam) > 2) {
                    echo "<script>alert('Gagal! Batas maksimal peminjaman Anda adalah 2 buku. Saat ini Anda sedang meminjam " . $total_dipinjam . " buku.'); window.location.href='buku.php';</script>";
                    exit;
                }

                // Validasi input minimal
                if ($jumlah_pinjam < 1) {
                    echo "<script>alert('Jumlah peminjaman tidak valid!'); window.location.href='buku.php';</script>";
                    exit;
                }
                
                $pdo->beginTransaction();
                
                // Cek stok DAN ambil judul buku
                $stmt = $pdo->prepare("SELECT judul, stok FROM books WHERE id = ?");
                $stmt->execute([$book_id]);
                $book = $stmt->fetch();
                
                // Validasi apakah stok mencukupi permintaan user
                if ($book && $book['stok'] >= $jumlah_pinjam) {
                    $pdo->prepare("UPDATE books SET stok = stok - ? WHERE id = ?")->execute([$jumlah_pinjam, $book_id]);
                    
                    $tanggal = date('Y-m-d H:i:s');
                    $stmtInsert = $pdo->prepare("INSERT INTO loans (user_id, book_id, tanggal_pinjam, status) VALUES (?, ?, ?, 'Dipinjam')");
                    
                    for ($i = 0; $i < $jumlah_pinjam; $i++) {
                        $stmtInsert->execute([$user_id, $book_id, $tanggal]);
                    }
                    
                    $pdo->commit();
                    
                    // BUAT NOTIFIKASI DETAIL UNTUK MAHASISWA
                    $_SESSION['flash_receipt'] = "
                        <h5 class='alert-heading fw-bold'><i class='fas fa-check-circle me-2'></i>Peminjaman Berhasil Diproses!</h5>
                        <hr>
                        <p class='mb-1'><b>Judul Buku:</b> " . htmlspecialchars($book['judul']) . "</p>
                        <p class='mb-1'><b>Jumlah Dipinjam:</b> " . $jumlah_pinjam . " Eksemplar</p>
                        <p class='mb-1'><b>Waktu Pinjam:</b> " . date('d M Y, H:i', strtotime($tanggal)) . " WITA</p>
                        <p class='mb-0 mt-2 small text-muted'><em>* Tunjukkan struk / riwayat ini ke Pustakawan di meja sirkulasi untuk mengambil fisik buku Anda.</em></p>
                    ";
                    
                    header("Location: peminjaman.php"); exit;
                } else {
                    $pdo->rollBack();
                    echo "<script>alert('Mohon maaf, jumlah yang ingin Anda pinjam melebihi stok yang tersedia saat ini!'); window.location.href='buku.php';</script>";
                    exit;
                }
            }
        }
    } catch (Exception $e) {
        die("Terjadi Kesalahan Database: " . $e->getMessage());
    }
}

// Fitur Pencarian Sederhana
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE judul LIKE ? OR pengarang LIKE ?");
    $stmt->execute(["%$search%", "%$search%"]);
    $books = $stmt->fetchAll();
} else {
    $books = $pdo->query("SELECT * FROM books")->fetchAll();
}

include 'header.php';
?>

<div class="container mt-5 mb-5 pt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold" style="color: var(--primary-color); margin-bottom: 1px;">Katalog Pustaka</h3>
            <?php if(isset($_SESSION['user_id']) && hasRole('Anggota')): ?>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary p-2 rounded-3 border">
                        <i class="fas fa-info-circle me-1"></i> Status Kuota Anda: <b><?= $total_dipinjam ?> / 2</b> Buku Terpinjam
                    </span>
                    <?php if($total_dipinjam >= 2): ?>
                        <span class="badge bg-danger rounded-pill px-2.5 py-1.5"><i class="fas fa-exclamation-triangle me-1"></i> Kuota Penuh</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['user_id']) && hasRole(['Admin', 'Pustakawan'])): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-2"></i> Tambah Buku Baru
            </button>
        <?php endif; ?>
    </div>

    <?php if(count($books) == 0): ?>
        <div class="card border-0 shadow-sm rounded-4 py-5 text-center text-muted">
            <div class="card-body">
                <i class="fas fa-book-open fa-3x mb-3 text-secondary opacity-50"></i>
                <p class="mb-0 fs-5">Buku tidak ditemukan dalam katalog.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach($books as $b): ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden card-custom" style="border-radius: 16px; background: #fff;">
                        
                        <div class="position-relative w-100 image-container bg-light d-flex align-items-center justify-content-center" style="height: 240px; overflow: hidden; border-bottom: 1px solid #f8f9fa;">
                            <img src="https://images.unsplash.com/photo-1543002588-bfa74002ed7e?q=80&w=400&auto=format&fit=crop" 
                                 alt="Cover Buku" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                            
                            <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-3 px-2 py-1 small rounded-sm" style="font-size: 0.75rem; backdrop-filter: blur(4px);">
                                ID: <?= sprintf("%04d", $b['id']) ?>
                            </span>
                        </div>
                        
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="mb-3">
                                <h6 class="fw-bold text-dark text-truncate-2 mb-2" title="<?= htmlspecialchars($b['judul']) ?>" style="line-height: 1.4; height: 2.8em;">
                                    <?= htmlspecialchars($b['judul']) ?>
                                </h6>
                                <p class="text-secondary small mb-1 text-truncate" style="font-size: 0.85rem;">
                                    <i class="fas fa-user-edit text-muted me-1" style="width: 14px;"></i> <?= htmlspecialchars($b['pengarang']) ?>
                                </p>
                                <p class="text-muted small mb-0 text-truncate" style="font-size: 0.8rem;">
                                    <i class="fas fa-building text-muted me-1" style="width: 14px;"></i> <?= htmlspecialchars($b['penerbit']) ?>
                                </p>
                            </div>
                            
                            <div class="pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="small fw-semibold text-muted" style="font-size: 0.8rem;">Status Stok:</span>
                                    <?php if($b['stok'] > 0): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">
                                            <?= $b['stok'] ?> Tersedia
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">
                                            Kosong
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="w-100">
                                    <?php if (!isset($_SESSION['user_id'])): ?>
                                        <a href="index.php?p=member" class="btn btn-sm btn-outline-primary w-100 rounded-pill py-2" style="font-size: 0.8rem; font-weight: 500;">
                                            <i class="fas fa-sign-in-alt me-1"></i> Login untuk Meminjam
                                        </a>
                                    <?php endif; ?>

                                    <?php if (isset($_SESSION['user_id']) && hasRole('Anggota')): ?>
                                        <?php if ($total_dipinjam >= 2): ?>
                                            <button class="btn btn-sm btn-light border w-100 rounded-pill py-2 text-danger disabled" disabled style="font-size: 0.8rem; font-weight: 500;">
                                                <i class="fas fa-lock me-1"></i> Kuota Penuh (Maks 2)
                                            </button>
                                        
                                        <?php elseif ($b['stok'] <= 0): ?>
                                            <button class="btn btn-sm btn-secondary w-100 rounded-pill py-2 disabled" disabled style="font-size: 0.8rem;">Stok Habis</button>
                                        
                                        <?php else: ?>
                                            <?php 
                                                $sisa_kuota_user = 2 - $total_dipinjam;
                                                $max_input_qty = min($b['stok'], $sisa_kuota_user);
                                            ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="borrow">
                                                <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                                                
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-4">
                                                        <input type="number" name="jumlah_pinjam" class="form-control text-center bg-light border-0 qty-input" value="1" min="1" max="<?= $max_input_qty ?>" required>
                                                    </div>
                                                    <div class="col-8">
                                                        <button type="submit" class="btn btn-sm btn-success w-100 rounded-pill py-2 shadow-sm" style="font-size: 0.8rem; font-weight: 500;" onclick="return confirm('Proses peminjaman buku ini?')">
                                                            <i class="fas fa-bookmark me-1"></i> Pinjam
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (isset($_SESSION['user_id']) && hasRole(['Admin', 'Pustakawan'])): ?>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <button type="button" class="btn btn-sm btn-outline-primary w-100 rounded-pill py-2" style="font-size: 0.8rem; font-weight: 500;" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editBookModal" 
                                                        data-id="<?= $b['id'] ?>"
                                                        data-judul="<?= htmlspecialchars($b['judul']) ?>"
                                                        data-pengarang="<?= htmlspecialchars($b['pengarang']) ?>"
                                                        data-penerbit="<?= htmlspecialchars($b['penerbit']) ?>"
                                                        data-stok="<?= $b['stok'] ?>">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill py-2" style="font-size: 0.8rem; font-weight: 500;" onclick="return confirm('Hapus buku dari katalog?')">
                                                        <i class="fas fa-trash me-1"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['user_id']) && hasRole(['Admin', 'Pustakawan'])): ?>
<div class="modal fade" id="addBookModal">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" style="color: var(--primary-color);"><i class="fas fa-plus-circle text-primary me-2"></i>Registrasi Buku Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" name="action" value="add">
                <div class="mb-3"><label class="form-label small fw-bold text-muted">Judul Lengkap</label><input type="text" name="judul" class="form-control bg-light" required></div>
                <div class="mb-3"><label class="form-label small fw-bold text-muted">Nama Pengarang</label><input type="text" name="pengarang" class="form-control bg-light" required></div>
                <div class="mb-3"><label class="form-label small fw-bold text-muted">Penerbit</label><input type="text" name="penerbit" class="form-control bg-light" required></div>
                <div class="mb-3"><label class="form-label small fw-bold text-muted">Jumlah Stok (Eksemplar)</label><input type="number" name="stok" class="form-control bg-light" required min="1"></div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-3">
                <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">Simpan ke Katalog</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" style="color: var(--primary-color);"><i class="fas fa-edit text-primary me-2"></i>Ubah Data Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Judul Lengkap</label>
                    <input type="text" name="judul" id="edit-judul" class="form-control bg-light" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Nama Pengarang</label>
                    <input type="text" name="pengarang" id="edit-pengarang" class="form-control bg-light" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Penerbit</label>
                    <input type="text" name="penerbit" id="edit-penerbit" class="form-control bg-light" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Jumlah Stok (Eksemplar)</label>
                    <input type="number" name="stok" id="edit-stok" class="form-control bg-light" required min="0">
                </div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-3">
                <button type="submit" class="btn btn-success rounded-pill px-4 w-100">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editModal = document.getElementById('editBookModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            // Tombol yang memicu modal muncul
            var button = event.relatedTarget;
            
            // Ambil semua data atribut dari tombol tersebut
            var id = button.getAttribute('data-id');
            var judul = button.getAttribute('data-judul');
            var pengarang = button.getAttribute('data-pengarang');
            var penerbit = button.getAttribute('data-penerbit');
            var stok = button.getAttribute('data-stok');
            
            // Masukkan data ke dalam elemen input form modal edit
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-judul').value = judul;
            document.getElementById('edit-pengarang').value = pengarang;
            document.getElementById('edit-penerbit').value = penerbit;
            document.getElementById('edit-stok').value = stok;
        });
    }
});
</script>
<?php endif; ?>

<style>
.card-custom { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
.card-custom:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
.image-container img { transition: transform 0.3s ease; }
.card-custom:hover .image-container img { transform: scale(1.03); }
.text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; }
.qty-input { height: 38px; font-weight: 600; border-radius: 10px; }
.qty-input:focus { background-color: #e9ecef; box-shadow: none; }
</style>

<?php include 'footer.php'; ?>