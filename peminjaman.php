<?php
require_once 'config.php';
requireLogin();

// -- PROSES PENGEMBALIAN --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(['Admin', 'Pustakawan'])) {
    $action = $_POST['action'] ?? '';
    if ($action === 'return') {
        $loan_id = $_POST['loan_id'];
        $book_id = $_POST['book_id'];
        
        $pdo->prepare("UPDATE loans SET status = 'Dikembalikan' WHERE id = ?")->execute([$loan_id]);
        $pdo->prepare("UPDATE books SET stok = stok + 1 WHERE id = ?")->execute([$book_id]);
        header("Location: peminjaman.php"); exit;
    }
}

// -- AMBIL DATA SESUAI ROLE --
if (hasRole(['Admin', 'Pustakawan'])) {
    $query = "SELECT l.*, b.judul, u.nama_lengkap FROM loans l JOIN books b ON l.book_id = b.id JOIN users u ON l.user_id = u.id ORDER BY l.id DESC";
    $stmt = $pdo->query($query);
} else {
    $query = "SELECT l.*, b.judul, u.nama_lengkap FROM loans l JOIN books b ON l.book_id = b.id JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
}
$loans = $stmt->fetchAll();

// CEK APAKAH ADA NOTIFIKASI STRUK DARI BUKU.PHP
$receipt = '';
if (isset($_SESSION['flash_receipt'])) {
    $receipt = $_SESSION['flash_receipt'];
    unset($_SESSION['flash_receipt']); // Hapus setelah ditampilkan agar tidak muncul terus
}

include 'header.php';
?>

<div class="container mt-5 mb-5 pt-4">
    
    <!-- AREA NOTIFIKASI STRUK PEMINJAMAN -->
    <?php if ($receipt): ?>
        <div class="alert alert-success alert-dismissible fade show shadow border-0 rounded-4 mb-4 p-4" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
            <?= $receipt ?>
            <button type="button" class="btn-close mt-2 me-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <h3 class="fw-bold" style="color: var(--primary-color);">
            <?= hasRole(['Admin', 'Pustakawan']) ? 'Seluruh Data Sirkulasi' : 'Riwayat Peminjaman Anda' ?>
        </h3>
        <p class="text-muted">Pantau status buku yang sedang dipinjam atau sudah dikembalikan.</p>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                        <tr>
                            <th class="ps-4 py-3">ID Transaksi</th>
                            <th>Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($loans) == 0): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada riwayat peminjaman.</td></tr>
                        <?php endif; ?>

                        <?php foreach($loans as $l): ?>
                        <tr>
                            <td class="ps-4 py-3 fw-bold text-muted">TRX-<?= sprintf("%04d", $l['id']) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($l['nama_lengkap']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($l['judul']) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($l['tanggal_pinjam'])) ?></td>
                            <td>
                                <?php if($l['status'] == 'Dipinjam'): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2 shadow-sm"><i class="fas fa-clock me-1"></i> <?= $l['status'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2 shadow-sm"><i class="fas fa-check me-1"></i> <?= $l['status'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($l['status'] == 'Dipinjam' && hasRole(['Admin', 'Pustakawan'])): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="loan_id" value="<?= $l['id'] ?>">
                                        <input type="hidden" name="book_id" value="<?= $l['book_id'] ?>">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="return confirm('Terima pengembalian fisik buku ini?')">Terima Buku</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
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

<?php include 'footer.php'; ?>