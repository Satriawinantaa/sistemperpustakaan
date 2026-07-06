<?php
require_once 'config.php';

// -- PROSES POST DATA --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // Admin & Pustakawan: Tambah Buku
        if ($action === 'add' && hasRole(['Admin', 'Pustakawan'])) {
            $stmt = $pdo->prepare("INSERT INTO books (judul, pengarang, penerbit, stok) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['judul'], $_POST['pengarang'], $_POST['penerbit'], $_POST['stok']]);
            header("Location: buku.php"); exit;
        }
        // Admin & Pustakawan: Hapus Buku
        elseif ($action === 'delete' && hasRole(['Admin', 'Pustakawan'])) {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header("Location: buku.php"); exit;
        }
        // Anggota: Pinjam Buku
        elseif ($action === 'borrow' && hasRole('Anggota')) {
            $book_id = $_POST['book_id'];
            
            // Gunakan transaksi agar database aman
            $pdo->beginTransaction();
            
            // Cek stok DAN ambil judul buku
            $stmt = $pdo->prepare("SELECT judul, stok FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
            if ($book && $book['stok'] > 0) {
                // Update stok
                $pdo->prepare("UPDATE books SET stok = stok - 1 WHERE id = ?")->execute([$book_id]);
                
                // Catat transaksi
                $tanggal = date('Y-m-d H:i:s');
                $stmtInsert = $pdo->prepare("INSERT INTO loans (user_id, book_id, tanggal_pinjam, status) VALUES (?, ?, ?, 'Dipinjam')");
                $stmtInsert->execute([$_SESSION['user_id'], $book_id, $tanggal]);
                
                $pdo->commit();
                
                // BUAT NOTIFIKASI DETAIL UNTUK MAHASISWA
                $_SESSION['flash_receipt'] = "
                    <h5 class='alert-heading fw-bold'><i class='fas fa-check-circle me-2'></i>Peminjaman Berhasil Diproses!</h5>
                    <hr>
                    <p class='mb-1'><b>Judul Buku:</b> " . htmlspecialchars($book['judul']) . "</p>
                    <p class='mb-1'><b>Waktu Pinjam:</b> " . date('d M Y, H:i', strtotime($tanggal)) . " WITA</p>
                    <p class='mb-0 mt-2 small text-muted'><em>* Tunjukkan struk / riwayat ini ke Pustakawan di meja sirkulasi untuk mengambil fisik buku Anda.</em></p>
                ";
                
                header("Location: peminjaman.php"); exit;
            } else {
                $pdo->rollBack();
                echo "<script>alert('Mohon maaf, stok buku sedang habis!'); window.location.href='buku.php';</script>";
                exit;
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

<!-- VIEW HALAMAN BUKU -->
<div class="container mt-5 mb-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold" style="color: var(--primary-color);">Katalog Pustaka</h3>
            <?php if($search): ?>
                <p class="text-muted">Hasil pencarian untuk: <b>"<?= htmlspecialchars($search) ?>"</b></p>
            <?php else: ?>
                <p class="text-muted">Jelajahi seluruh koleksi perpustakaan kami.</p>
            <?php endif; ?>
        </div>
        
        <?php if (hasRole(['Admin', 'Pustakawan'])): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-2"></i> Tambah Buku Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                        <tr>
                            <th class="ps-4 py-3">Judul Buku</th>
                            <th>Pengarang & Penerbit</th>
                            <th>Ketersediaan</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($books) == 0): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Buku tidak ditemukan.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($books as $b): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($b['judul']) ?></div>
                                <small class="text-muted">ID: BUKU-<?= sprintf("%04d", $b['id']) ?></small>
                            </td>
                            <td>
                                <div><i class="fas fa-user-edit text-muted me-2" style="width: 15px;"></i> <?= htmlspecialchars($b['pengarang']) ?></div>
                                <div class="small text-muted mt-1"><i class="fas fa-building me-2" style="width: 15px;"></i> <?= htmlspecialchars($b['penerbit']) ?></div>
                            </td>
                            <td>
                                <?php if($b['stok'] > 0): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> <?= $b['stok'] ?> Tersedia</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3 py-2">Sedang Dipinjam</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (!isLoggedIn()): ?>
                                    <!-- Jika belum login, beri tahu untuk login -->
                                    <a href="index.php?p=member" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Login untuk Pinjam</a>
                                <?php endif; ?>

                                <?php if (hasRole('Anggota') && $b['stok'] > 0): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="borrow">
                                        <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                                        <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" onclick="return confirm('Proses peminjaman buku ini?')">Pinjam Buku</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (hasRole(['Admin', 'Pustakawan'])): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger rounded-circle" style="width: 35px; height: 35px;" onclick="return confirm('Hapus buku dari katalog?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (hasRole(['Admin', 'Pustakawan'])): ?>
<!-- Modal Tambah Buku -->
<div class="modal fade" id="addBookModal">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" style="color: var(--primary-color);">Registrasi Buku Baru</h5>
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
<?php endif; ?>

<?php include 'footer.php'; ?>