<?php
require_once 'config.php';
requireLogin();
requireRole('Admin'); // Hanya Admin yang bisa akses file ini

// -- PROSES POST DATA (CRUD LOGIC) --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE (Tambah Pengguna)
    if ($action === 'add') {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['username'], $pass, $_POST['nama_lengkap'], $_POST['role']]);
        header("Location: pengguna.php"); exit;
    } 
    
    // 2. UPDATE (Edit Pengguna)
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $role = $_POST['role'];

        if (!empty($_POST['password'])) {
            // Jika password baru diisi, update password juga
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $pass, $nama_lengkap, $role, $id]);
        } else {
            // Jika password kosong, pertahankan password lama
            $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $nama_lengkap, $role, $id]);
        }
        header("Location: pengguna.php"); exit;
    } 
    
    // 3. DELETE (Hapus Pengguna)
    elseif ($action === 'delete') {
        // Proteksi: Mencegah admin menghapus dirinya sendiri yang sedang login
        if ($_POST['id'] != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
        header("Location: pengguna.php"); exit;
    }
}

// READ: Ambil semua data pengguna dari database
$users = $pdo->query("SELECT * FROM users")->fetchAll();
include 'header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold" style="color: var(--primary-color);">Kelola Pustakawan & Anggota</h3>
            <p class="text-muted mb-0">Manajemen akses dan data pengguna sistem perpustakaan.</p>
        </div>
        <button class="btn btn-warning fw-bold text-dark rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-2"></i> Tambah Pengguna
        </button>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom p-3 pt-4">
            <h6 class="mb-0 fw-bold"><i class="fas fa-users text-primary me-2"></i> Daftar Pengguna Sistem</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                        <tr>
                            <th class="ps-4 py-3">Nama Lengkap</th>
                            <th>NIM/Username</th>
                            <th>Hak Akses / Role</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['nama_lengkap']) ?>&background=random&color=fff&rounded=true" width="40" height="40" class="me-3 shadow-sm">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                </div>
                            </td>
                            <td><span class="text-muted">@<?= htmlspecialchars($u['username']) ?></span></td>
                            <td>
                                <?php 
                                    $bg = 'bg-secondary';
                                    if($u['role'] == 'Admin') $bg = 'bg-danger';
                                    if($u['role'] == 'Pustakawan') $bg = 'bg-primary';
                                    if($u['role'] == 'Mahasiswa') $bg = 'bg-success';
                                ?>
                                <span class="badge <?= $bg ?> rounded-pill px-3 py-2 shadow-sm"><?= $u['role'] ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-outline-primary rounded-circle me-1 edit-btn" style="width: 35px; height: 35px;"
                                        data-id="<?= $u['id'] ?>"
                                        data-nama="<?= htmlspecialchars($u['nama_lengkap']) ?>"
                                        data-username="<?= htmlspecialchars($u['username']) ?>"
                                        data-role="<?= $u['role'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-circle" style="width: 35px; height: 35px;" onclick="return confirm('Yakin hapus nii??')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border px-3 py-2">Sedang Login</span>
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

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-bottom-0 pb-0 mt-2">
                <h5 class="modal-title fw-bold" style="color: var(--primary-color);">Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nama</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-font text-muted"></i></span>
                        <input type="text" name="nama_lengkap" class="form-control border-start-0 ps-0 bg-light" required placeholder="Contoh: I Nyoman Puyeng">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">NIM/Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="username" class="form-control border-start-0 ps-0 bg-light" required placeholder="NIM/Username">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 ps-0 bg-light" required placeholder="Buat kata sandi">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Hak Akses / Role</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user-shield text-muted"></i></span>
                        <select name="role" class="form-select border-start-0 ps-0 bg-light">
                            <option value="Mahasiswa">Mahasiswa</option>
                            <option value="Pustakawan">Pustakawan</option>
                            <option value="Admin">Administrator</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 mb-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-bottom-0 pb-0 mt-2">
                <h5 class="modal-title fw-bold" style="color: var(--primary-color);">Ubah Data Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nama</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-font text-muted"></i></span>
                        <input type="text" name="nama_lengkap" id="edit_nama" class="form-control border-start-0 ps-0 bg-light" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">NIM/Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="username" id="edit_username" class="form-control border-start-0 ps-0 bg-light" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Password Baru <span class="text-danger fw-normal" style="font-size: 0.75rem;">*(Kosongkan jika tidak diubah)</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 ps-0 bg-light" placeholder="Masukkan password baru">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Hak Akses / Role</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user-shield text-muted"></i></span>
                        <select name="role" id="edit_role" class="form-select border-start-0 ps-0 bg-light">
                            <option value="Mahasiswa">Mahasiswa</option>
                            <option value="Pustakawan">Pustakawan</option>
                            <option value="Admin">Administrator</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 mb-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Ambil data atribut dari tombol edit yang diklik
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const username = this.getAttribute('data-username');
            const role = this.getAttribute('data-role');

            // Masukkan data tersebut ke dalam input modal edit
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
        });
    });
});
</script>

