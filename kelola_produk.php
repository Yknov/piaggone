<?php

session_start();
require_once '../config/database.php';
require_once '../middleware/auth.php';

checkAuth();
checkRole(['inventory', 'manager', 'owner']);

$user = $_SESSION['user'];

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = $_POST['nama_produk'];
        $kategori = $_POST['kategori'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        $deskripsi = $_POST['deskripsi'];
        
        $query = "INSERT INTO produk (nama_produk, kategori, harga, stok, deskripsi) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdis", $nama, $kategori, $harga, $stok, $deskripsi);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Produk berhasil ditambahkan!'];
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Gagal menambahkan produk!'];
        }
        header('Location: kelola_produk.php');
        exit;
    }
    
    if ($action === 'edit') {
        $id = $_POST['id_produk'];
        $nama = $_POST['nama_produk'];
        $kategori = $_POST['kategori'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        $deskripsi = $_POST['deskripsi'];
        $status = $_POST['status'];
        
        $query = "UPDATE produk SET nama_produk=?, kategori=?, harga=?, stok=?, 
                  deskripsi=?, status=? WHERE id_produk=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdissi", $nama, $kategori, $harga, $stok, $deskripsi, $status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Produk berhasil diupdate!'];
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Gagal mengupdate produk!'];
        }
        header('Location: kelola_produk.php');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id_produk'];
        
        // Soft delete dengan mengubah status
        $query = "UPDATE produk SET status='nonaktif' WHERE id_produk=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Produk berhasil dinonaktifkan!'];
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Gagal menonaktifkan produk!'];
        }
        header('Location: kelola_produk.php');
        exit;
    }
    
    if ($action === 'update_stok') {
        $id = $_POST['id_produk'];
        $jenis = $_POST['jenis_transaksi'];
        $jumlah = $_POST['jumlah'];
        $keterangan = $_POST['keterangan'];
        
        // Get stok sebelum
        $queryStok = "SELECT stok FROM produk WHERE id_produk=?";
        $stmtStok = $conn->prepare($queryStok);
        $stmtStok->bind_param("i", $id);
        $stmtStok->execute();
        $stokSebelum = $stmtStok->get_result()->fetch_assoc()['stok'];
        
        // Hitung stok sesudah
        $stokSesudah = $jenis === 'masuk' ? $stokSebelum + $jumlah : $stokSebelum - $jumlah;
        
        // Update stok
        $queryUpdate = $jenis === 'masuk' 
            ? "UPDATE produk SET stok = stok + ? WHERE id_produk=?"
            : "UPDATE produk SET stok = stok - ? WHERE id_produk=?";
        $stmtUpdate = $conn->prepare($queryUpdate);
        $stmtUpdate->bind_param("ii", $jumlah, $id);
        $stmtUpdate->execute();
        
        // Catat riwayat
        $queryRiwayat = "INSERT INTO riwayat_stok 
                        (id_produk, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, 
                         keterangan, id_user) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtRiwayat = $conn->prepare($queryRiwayat);
        $stmtRiwayat->bind_param("isiissi", $id, $jenis, $jumlah, $stokSebelum, $stokSesudah, $keterangan, $user['id_user']);
        $stmtRiwayat->execute();
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Stok berhasil diupdate!'];
        header('Location: kelola_produk.php');
        exit;
    }
}

// Get products
$query = "SELECT * FROM produk ORDER BY status ASC, nama_produk ASC";
$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);

include '../layouts/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-box"></i> Kelola Produk
                    </h4>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </button>
                </div>
                <div class="card-body">
                    <!-- Alert -->
                    <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
                        <?= $_SESSION['message']['text'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); endif; ?>
                    
                    <!-- Filter -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select id="filterKategori" class="form-control" onchange="filterTable()">
                                <option value="">Semua Kategori</option>
                                <option value="Styling">Styling</option>
                                <option value="Perawatan">Perawatan</option>
                                <option value="Aksesoris">Aksesoris</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filterStatus" class="form-control" onchange="filterTable()">
                                <option value="">Semua Status</option>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" id="searchProduk" class="form-control" 
                                   placeholder="Cari produk..." onkeyup="filterTable()">
                        </div>
                    </div>
                    
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="tableProduk">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th width="200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <tr data-kategori="<?= $p['kategori'] ?>" 
                                    data-status="<?= $p['status'] ?>"
                                    data-nama="<?= strtolower($p['nama_produk']) ?>">
                                    <td><?= $p['id_produk'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['nama_produk']) ?></strong>
                                        <?php if ($p['deskripsi']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($p['deskripsi']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?= $p['kategori'] ?></span></td>
                                    <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge <?= $p['stok'] < 5 ? 'bg-danger' : 'bg-success' ?>">
                                            <?= $p['stok'] ?> pcs
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $p['status'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick='editProduk(<?= json_encode($p) ?>)'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-info" 
                                                onclick='updateStok(<?= json_encode($p) ?>)'>
                                            <i class="fas fa-box"></i> Stok
                                        </button>
                                        <?php if ($p['status'] === 'aktif'): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteProduk(<?= $p['id_produk'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk *</label>
                        <input type="text" name="nama_produk" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori *</label>
                        <select name="kategori" class="form-control" required>
                            <option value="Styling">Styling</option>
                            <option value="Perawatan">Perawatan</option>
                            <option value="Aksesoris">Aksesoris</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga *</label>
                        <input type="number" name="harga" class="form-control" required min="0" step="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal *</label>
                        <input type="number" name="stok" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"></textarea>
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
            <form method="POST" id="formEdit">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_produk" id="edit_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk *</label>
                        <input type="text" name="nama_produk" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori *</label>
                        <select name="kategori" id="edit_kategori" class="form-control" required>
                            <option value="Styling">Styling</option>
                            <option value="Perawatan">Perawatan</option>
                            <option value="Aksesoris">Aksesoris</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga *</label>
                        <input type="number" name="harga" id="edit_harga" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok *</label>
                        <input type="number" name="stok" id="edit_stok" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Update Stok -->
<div class="modal fade" id="modalStok" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formStok">
                <input type="hidden" name="action" value="update_stok">
                <input type="hidden" name="id_produk" id="stok_id">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Update Stok</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong id="stok_nama"></strong><br>
                        Stok Saat Ini: <strong id="stok_current"></strong> pcs
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Transaksi *</label>
                        <select name="jenis_transaksi" class="form-control" required>
                            <option value="masuk">Stok Masuk</option>
                            <option value="keluar">Stok Keluar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah *</label>
                        <input type="number" name="jumlah" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan *</label>
                        <textarea name="keterangan" class="form-control" rows="3" required 
                                  placeholder="Contoh: Penerimaan dari supplier, Retur, dll"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info">Update Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduk(produk) {
    document.getElementById('edit_id').value = produk.id_produk;
    document.getElementById('edit_nama').value = produk.nama_produk;
    document.getElementById('edit_kategori').value = produk.kategori;
    document.getElementById('edit_harga').value = produk.harga;
    document.getElementById('edit_stok').value = produk.stok;
    document.getElementById('edit_deskripsi').value = produk.deskripsi || '';
    document.getElementById('edit_status').value = produk.status;
    
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function updateStok(produk) {
    document.getElementById('stok_id').value = produk.id_produk;
    document.getElementById('stok_nama').textContent = produk.nama_produk;
    document.getElementById('stok_current').textContent = produk.stok;
    
    new bootstrap.Modal(document.getElementById('modalStok')).show();
}

function deleteProduk(id, nama) {
    if (confirm('Yakin ingin menonaktifkan produk "' + nama + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_produk" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterTable() {
    const kategori = document.getElementById('filterKategori').value.toLowerCase();
    const status = document.getElementById('filterStatus').value.toLowerCase();
    const search = document.getElementById('searchProduk').value.toLowerCase();
    const rows = document.querySelectorAll('#tableProduk tbody tr');
    
    rows.forEach(row => {
        const rowKategori = row.dataset.kategori.toLowerCase();
        const rowStatus = row.dataset.status.toLowerCase();
        const rowNama = row.dataset.nama;
        
        const matchKategori = !kategori || rowKategori === kategori;
        const matchStatus = !status || rowStatus === status;
        const matchSearch = !search || rowNama.includes(search);
        
        row.style.display = (matchKategori && matchStatus && matchSearch) ? '' : 'none';
    });
}
</script>

<?php include '../layouts/footer.php'; ?>
