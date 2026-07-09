<?php
require_once 'config.php';
requireLogin();

// -- PROSES PENGEMBALIAN & HAPUS SEMUA RIWAYAT --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(['Admin', 'Pustakawan'])) {
    $action = $_POST['action'] ?? '';
    
    // 1. Proses Terima Pengembalian Buku (Dengan Database Transaction)
    if ($action === 'return') {
        $loan_id = $_POST['loan_id'] ?? null;
        $book_id = $_POST['book_id'] ?? null;
        
        if ($loan_id && $book_id) {
            try {
                $pdo->beginTransaction();
                
                $stmt1 = $pdo->prepare("UPDATE loans SET status = 'Dikembalikan' WHERE id = ?");
                $stmt1->execute([$loan_id]);
                
                $stmt2 = $pdo->prepare("UPDATE books SET stok = stok + 1 WHERE id = ?");
                $stmt2->execute([$book_id]);
                
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                // Opsional: set pesan error ke session
            }
        }
        header("Location: peminjaman.php"); 
        exit;
    }
    
    // 2. Proses Hapus SEMUA Riwayat yang Statusnya Sudah 'Dikembalikan'
    if ($action === 'delete_all_completed') {
        $pdo->exec("DELETE FROM loans WHERE status = 'Dikembalikan'");
        header("Location: peminjaman.php"); 
        exit;
    }
}

// -- AMBIL DATA SESUAI ROLE --
if (hasRole(['Admin', 'Pustakawan'])) {
    $query = "SELECT l.*, b.judul, u.username, u.nama_lengkap FROM loans l 
              JOIN books b ON l.book_id = b.id 
              JOIN users u ON l.user_id = u.id 
              ORDER BY l.id DESC";
    $stmt = $pdo->query($query);
} else {
    $query = "SELECT l.*, b.judul, u.username, u.nama_lengkap FROM loans l 
              JOIN books b ON l.book_id = b.id 
              JOIN users u ON l.user_id = u.id 
              WHERE l.user_id = ? 
              ORDER BY l.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
}
$loans = $stmt->fetchAll();

// Cek status 'Dikembalikan' untuk visibilitas tombol hapus massal
$has_completed_loans = false;
foreach ($loans as $loan) {
    if ($loan['status'] === 'Dikembalikan') {
        $has_completed_loans = true;
        break;
    }
}

// Cek Notifikasi Struk
$receipt = '';
if (isset($_SESSION['flash_receipt'])) {
    $receipt = $_SESSION['flash_receipt'];
    unset($_SESSION['flash_receipt']); 
}

include 'header.php';
?>

<style>
/* Custom styling untuk tampilan yang lebih modern */
.table-modern thead {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}
.table-modern th {
    font-weight: 600;
    letter-spacing: 0.5px;
}
.card-sirkulasi {
    border-radius: 16px;
    overflow: hidden;
}
.badge-status {
    font-weight: 500;
    padding: 0.5em 1em;
}

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

<div class="container my-5 pt-4">
    
    <?php if ($receipt): ?>
        <div id="printableReceipt" class="alert alert-success shadow-sm border-start border-4 border-success rounded-3 mb-4 p-4 position-relative" role="alert">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="pe-3">
                    <?= $receipt ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            
            <div class="mt-3 p-3 rounded bg-white bg-opacity-75 border border-success border-opacity-25 layout-receipt" style="font-size: 0.9rem;">
                <p class="mb-1"><i class="fas fa-calendar-alt text-success me-2"></i><b>Batas Waktu Pengembalian:</b></p>
                <p class="mb-0 text-danger fw-bold fs-5 ps-4">
                    <?= date('d M Y', strtotime('+14 days')) ?>
                </p>
                <small class="text-muted d-block ps-4 mt-1">* Maksimal waktu peminjaman adalah 2 minggu. Keterlambatan akan dikenakan denda sebesar Rp1.000,00 / hari.</small>
            </div>

            <div class="mt-3 pt-3 border-top border-success border-opacity-10 text-end">
                <button type="button" class="btn btn-success btn-sm rounded-pill px-4" onclick="window.print();">
                    <i class="fas fa-print me-2"></i> Cetak / Unduh Struk PDF
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h3 class="fw-bold text-dark mb-1">
                <?= hasRole(['Admin', 'Pustakawan']) ? 'Seluruh Data Sirkulasi' : 'Riwayat Peminjaman Anda' ?>
            </h3>
            <p class="text-muted mb-0">Pantau log status buku yang sedang aktif dipinjam maupun yang sudah selesai.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <?php if (hasRole(['Admin', 'Pustakawan']) && $has_completed_loans): ?>
                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA riwayat peminjaman yang sudah selesai/dikembalikan? Tindakan ini permanen.');" class="d-inline">
                    <input type="hidden" name="action" value="delete_all_completed">
                    <button type="submit" class="btn btn-outline-danger btn-sm px-3 py-2 shadow-sm rounded-pill">
                        <i class="fas fa-trash-alt me-1"></i> Bersihkan Riwayat Selesai
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-sirkulasi border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">ID Transaksi</th>
                            <th>NIM</th>
                            <th>Peminjam</th>
                            <th>Informasi Buku</th>
                            <th>Waktu Pinjam</th>
                            <th>Batas / Sisa Waktu</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($loans) == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-3 d-block text-black-50"></i>
                                    Belum ada data sirkulasi peminjaman.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($loans as $l): ?>
                        <tr>
                            <td class="ps-4 py-3 fw-bold text-secondary">
                                <span class="badge bg-light text-dark font-monospace">TRX-<?= sprintf("%04d", $l['id']) ?></span>
                            </td>
                            
                            <td class="font-monospace text-secondary small">
                                <?= htmlspecialchars($l['username']) ?>
                            </td>
                            
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($l['nama_lengkap']) ?></span>
                            </td>
                            
                            <td style="max-width: 250px;" class="text-truncate">
                                <span class="text-dark d-block fw-medium"><?= htmlspecialchars($l['judul']) ?></span>
                            </td>
                            
                            <td class="text-muted small">
                                <?= date('d M Y', strtotime($l['tanggal_pinjam'])) ?>
                            </td>
                            
                            <td>
                                <?php 
                                if ($l['status'] === 'Dipinjam') {
                                    $tanggal_pinjam = strtotime($l['tanggal_pinjam']);
                                    $batas_kembali = strtotime('+14 days', $tanggal_pinjam);
                                    $hari_ini = strtotime(date('Y-m-d'));

                                    $selisih_detik = $batas_kembali - $hari_ini;
                                    $sisa_hari = round($selisih_detik / (60 * 60 * 24));

                                    echo '<small class="text-muted d-block mb-1">s/d ' . date('d M Y', $batas_kembali) . '</small>';

                                    if ($sisa_hari < 0) {
                                        echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="fas fa-exclamation-circle me-1"></i> Terlambat ' . abs($sisa_hari) . ' Hari</span>';
                                    } elseif ($sisa_hari == 0) {
                                        echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill"><i class="fas fa-hourglass-half me-1"></i> Hari Ini Batasnya!</span>';
                                    } else {
                                        echo '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="fas fa-hourglass-start me-1"></i> Sisa ' . $sisa_hari . ' Hari</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted small"><i class="fas fa-check-circle text-muted me-1"></i> Selesai</span>';
                                }
                                ?>
                            </td>

                            <td>
                                <?php if($l['status'] == 'Dipinjam'): ?>
                                    <span class="badge badge-status bg-warning text-dark rounded-pill">
                                        <i class="spinner-grow spinner-grow-sm text-dark me-1" style="width: 8px; height: 8px;"></i> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-status bg-success-subtle text-success border border-success-subtle rounded-pill">
                                        Selesai
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-end pe-4">
                                <?php if ($l['status'] == 'Dipinjam' && hasRole(['Admin', 'Pustakawan'])): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="loan_id" value="<?= $l['id'] ?>">
                                        <input type="hidden" name="book_id" value="<?= $l['book_id'] ?>">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm font-weight-medium" onclick="return confirm('Terima pengembalian fisik buku ini?')">
                                            <i class="fas fa-clipboard-check me-1"></i> Terima Buku
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted-50 small">-</span>
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