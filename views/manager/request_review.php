<h1 class="h3 mb-2 text-gray-800"><?php echo htmlspecialchars($title); ?></h1>
<p class="mb-4">Tinjau, setujui, atau tolak permintaan pengadaan barang yang diajukan oleh staff.</p>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Permintaan yang Menunggu Persetujuan</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID Permintaan</th>
                        <th>Tanggal</th>
                        <th>Dibuat Oleh (Staff)</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_permintaan)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada permintaan yang menunggu.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar_permintaan as $req): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($req['requestID']); ?></td>
                                <td><?php echo date('d M Y', strtotime($req['tanggalPermintaan'])); ?></td>
                                <td><?php echo htmlspecialchars($req['namaStaff']); ?></td>
                                <td><span class="badge badge-warning">Menunggu</span></td>
                                <td>
                                    <form method="POST" action="manager_request_review.php" style="display: inline-block;">
                                        <input type="hidden" name="request_id" value="<?php echo $req['requestID']; ?>">
                                        <button type="submit" name="action" value="Disetujui" class="btn btn-success btn-sm" title="Setujui">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="submit" name="action" value="Ditolak" class="btn btn-danger btn-sm" title="Tolak">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    
                                    <a href="inventory_request_detail.php?id=<?php echo $req['requestID']; ?>" class="btn btn-info btn-sm" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>