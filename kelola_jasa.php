<?php
// pages/admin/kelola_jasa.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions_jasa.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'tambah':
                $nama_jasa = mysqli_real_escape_string($conn, $_POST['nama_jasa']);
                $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
                $harga = floatval($_POST['harga']);
                $estimasi_waktu = intval($_POST['estimasi_waktu']);
                
                if (tambahJasa($conn, $nama_jasa, $deskripsi, $harga, $estimasi_waktu)) {
                    $message = "Jasa berhasil ditambahkan!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menambahkan jasa!";
                    $message_type = "danger";
                }
                break;
                
            case 'update':
                $id_jasa = intval($_POST['id_jasa']);
                $nama_jasa = mysqli_real_escape_string($conn, $_POST['nama_jasa']);
                $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
                $harga = floatval($_POST['harga']);
                $estimasi_waktu = intval($_POST['estimasi_waktu']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                
                if (updateJasa($conn, $id_jasa, $nama_jasa, $deskripsi, $harga, $estimasi_waktu, $status)) {
                    $message = "Jasa berhasil diupdate!";
                    $message_type = "success";
                } else {
                    $message = "Gagal mengupdate jasa!";
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                $id_jasa = intval($_POST['id_jasa']);
                if (deleteJasa($conn, $id_jasa)) {
                    $message = "Jasa berhasil dihapus!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menghapus jasa! Mungkin masih ada transaksi terkait.";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get all jasa
$data_jasa = getAllJasa($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jasa - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../../templates/navbar_admin.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="bi bi-tools"></i> Kelola Jasa</h2>
                <hr>
                
                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Tombol Tambah -->
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle"></i> Tambah Jasa Baru
                </button>
                
                <!-- Tabel Jasa -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Jasa</th>
                                        <th>Deskripsi</th>
                                        <th>Harga</th>
                                        <th>Estimasi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data_jasa)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Belum ada data jasa</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($data_jasa as $index => $jasa): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($jasa['nama_jasa']) ?></td>
                                        <td><?= htmlspecialchars(substr($jasa['deskripsi'], 0, 50)) ?>...</td>
                                        <td><?= formatRupiah($jasa['harga']) ?></td>
                                        <td><?= $jasa['estimasi_waktu'] ?> menit</td>
                                        <td>
                                            <?php if ($jasa['status'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="editJasa(<?= htmlspecialchars(json_encode($jasa)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?= $jasa['id_jasa'] ?>, '<?= htmlspecialchars($jasa['nama_jasa']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Jasa Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="tambah">
                        <div class="mb-3">
                            <label class="form-label">Nama Jasa *</label>
                            <input type="text" class="form-control" name="nama_jasa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="deskripsi" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga (Rp) *</label>
                            <input type="number" class="form-control" name="harga" min="0" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimasi Waktu (menit) *</label>
                            <input type="number" class="form-control" name="estimasi_waktu" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Jasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id_jasa" id="edit_id_jasa">
                        <div class="mb-3">
                            <label class="form-label">Nama Jasa *</label>
                            <input type="text" class="form-control" name="nama_jasa" id="edit_nama_jasa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga (Rp) *</label>
                            <input type="number" class="form-control" name="harga" id="edit_harga" min="0" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimasi Waktu (menit) *</label>
                            <input type="number" class="form-control" name="estimasi_waktu" id="edit_estimasi_waktu" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Form Delete (hidden) -->
    <form method="POST" id="formDelete">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_jasa" id="delete_id_jasa">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editJasa(jasa) {
            document.getElementById('edit_id_jasa').value = jasa.id_jasa;
            document.getElementById('edit_nama_jasa').value = jasa.nama_jasa;
            document.getElementById('edit_deskripsi').value = jasa.deskripsi;
            document.getElementById('edit_harga').value = jasa.harga;
            document.getElementById('edit_estimasi_waktu').value = jasa.estimasi_waktu;
            document.getElementById('edit_status').value = jasa.status;
            
            var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
            modal.show();
        }
        
        function confirmDelete(id, nama) {
            if (confirm('Yakin ingin menghapus jasa "' + nama + '"?\n\nJasa yang sudah memiliki transaksi tidak bisa dihapus.')) {
                document.getElementById('delete_id_jasa').value = id;
                document.getElementById('formDelete').submit();
            }
        }
    </script>
</body>
</html>
