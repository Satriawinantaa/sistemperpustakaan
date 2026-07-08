<?php
require_once 'config.php';
requireLogin();

// -- PROSES PENGEMBALIAN & HAPUS SEMUA RIWAYAT --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(['Admin', 'Pustakawan'])) {
    $action = $_POST['action'] ?? '';
    
    // 1. Proses Terima Pengembalian Buku
    if ($action === 'return') {
        $loan_id = $_POST['loan_id'];
        $book_id = $_POST['book_id'];
        
        $pdo->prepare("UPDATE loans SET status = 'Dikembalikan' WHERE id = ?")->execute([$loan_id]);
        $pdo->prepare("UPDATE books SET stok = stok + 1 WHERE id = ?")->execute([$book_id]);
        header("Location: peminjaman.php"); exit;
    }
    
    // 2. Proses Hapus SEMUA Riwayat yang Statusnya Sudah 'Dikembalikan'
    if ($action === 'delete_all_completed') {
        $pdo->exec("DELETE FROM loans WHERE status = 'Dikembalikan'");
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

// Cek apakah ada data yang berstatus 'Dikembalikan' untuk menentukan tombol 'Hapus Semua' muncul atau tidak
$has_completed_loans = false;
foreach ($loans as $loan) {
    if ($loan['status'] === 'Dikembalikan') {
        $has_completed_loans = true;
        break;
    }
}

// CEK APAKAH ADA NOTIFIKASI STRUK DARI BUKU.PHP
$receipt = '';
if (isset($_SESSION['flash_receipt'])) {
    $receipt = $_SESSION['flash_receipt'];
    unset($_SESSION['flash_receipt']); 
}

include 'header.php';
?>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableReceipt, #printableReceipt * {
        visibility: visible;
    }
    #printableReceipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
        background: #fff !important;
        color: #000 !important;
        padding: 0 !important;
    }
    #printableReceipt .btn, #printableReceipt .btn-close {
        display: none !important;
    }
}
</style>

<div class="container mt-5 mb-5 pt-4">
    
    <?php if ($receipt): ?>
        <div id="printableReceipt" class="alert alert-success shadow border-0 rounded-4 mb-4 p-4 position-relative" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <?= $receipt ?>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            
            <div class="mt-2 p-2 rounded-3 bg-white bg-opacity-50 border border-success border-opacity-25" style="font-size: 0.9rem;">
                <i class="fas fa-calendar-alt me-1"></i> 
                <b>Batas Waktu Pengembalian:</b> 
                <span class="text-danger fw-bold">
                    <?= date('d M Y', strtotime('+14 days')) ?>
                </span> (Maksimal 2 Minggu dari sekarang, dan akan dikenakan denda sebesar Rp1.000,00/hari bila melebihi dari tanggal yang ditentukan).
            </div>

            <div class="mt-3 pt-3 border-top border-success border-opacity-25 text-end">
                <button type="button" class="btn btn-success rounded-pill px-4 btn-sm shadow-sm" onclick="window.print();">
                    <i class="fas fa-download me-1"></i> Unduh / Cetak Struk (PDF)
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h3 class="fw-bold text-dark">
                <?= hasRole(['Admin', 'Pustakawan']) ? 'Seluruh Data Sirkulasi' : 'Riwayat Peminjaman Anda' ?>
            </h3>
            <p class="text-muted mb-0">Pantau status buku yang sedang dipinjam atau sudah dikembalikan.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <?php if (hasRole(['Admin', 'Pustakawan']) && $has_completed_loans): ?>
                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA riwayat peminjaman yang sudah selesai/dikembalikan? Tindakan ini permanen.');" class="d-inline">
                    <input type="hidden" name="action" value="delete_all_completed">
                    <button type="submit" class="btn btn-danger btn-sm px-3 py-2 shadow-sm">
                        <i class="fas fa-trash-alt me-1"></i> Hapus Semua Riwayat Selesai
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px; background: #fff;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                        <tr>
                            <th class="ps-4 py-3">ID Transaksi</th>
                            <th>Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Sisa Waktu / Batas</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($loans) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Belum ada data riwayat peminjaman.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($loans as $l): ?>
                        <tr>
                            <td class="ps-4 py-3 fw-bold text-muted">TRX-<?= sprintf("%04d", $l['id']) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($l['nama_lengkap']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($l['judul']) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($l['tanggal_pinjam'])) ?> WITA</td>
                            
                            <td>
                                <?php 
                                if ($l['status'] === 'Dipinjam') {
                                    // Hitung tanggal batas kembali (Tanggal Pinjam + 14 Hari)
                                    $tanggal_pinjam = strtotime($l['tanggal_pinjam']);
                                    $batas_kembali = strtotime('+14 days', $tanggal_pinjam);
                                    $hari_ini = strtotime(date('Y-m-d'));

                                    // Hitung selisih hari
                                    $selisih_detik = $batas_kembali - $hari_ini;
                                    $sisa_hari = round($selisih_detik / (60 * 60 * 24));

                                    if ($sisa_hari < 0) {
                                        // Terlambat (Warna Merah Bold)
                                        echo '<span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> ' . $sisa_hari . ' Hari (Terlambat)</span>';
                                    } elseif ($sisa_hari == 0) {
                                        // Hari terakhir peminjaman
                                        echo '<span class="text-warning fw-bold"><i class="fas fa-hourglass-half me-1"></i> Hari Ini Batasnya!</span>';
                                    } else {
                                        // Masih aman (Hitung Mundur Normal)
                                        echo '<span class="text-success fw-semibold"><i class="fas fa-hourglass-start me-1"></i> Sisa ' . $sisa_hari . ' Hari</span>';
                                    }
                                } else {
                                    // Jika sudah dikembalikan
                                    echo '<span class="text-muted small">- Selesai -</span>';
                                }
                                ?>
                            </td>

                            <td>
                                <?php if($l['status'] == 'Dipinjam'): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1.5 shadow-sm">
                                        <i class="fas fa-clock me-1"></i> <?= $l['status'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-3 py-1.5 shadow-sm">
                                        <i class="fas fa-check me-1"></i> <?= $l['status'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($l['status'] == 'Dipinjam' && hasRole(['Admin', 'Pustakawan'])): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="loan_id" value="<?= $l['id'] ?>">
                                        <input type="hidden" name="book_id" value="<?= $l['book_id'] ?>">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="return confirm('Terima pengembalian fisik buku ini?')">
                                            Terima Buku
                                        </button>
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