<h1 class="h3 mb-2 text-gray-800"><?php echo htmlspecialchars($title); ?></h1>
<p class="mb-4">Daftar semua permintaan pengadaan barang yang telah Anda buat.</p>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Riwayat Permintaan</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID Permintaan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Ditangani Oleh (Manager)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_permintaan)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Anda belum pernah membuat permintaan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar_permintaan as $req): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($req['requestID']); ?></td>
                                <td><?php echo date('d M Y', strtotime($req['tanggalPermintaan'])); ?></td>
                                <td>
                                    <?php
                                    // Logika status dengan label dari SB Admin 2
                                    if ($req['status'] == 'Menunggu') {
                                        echo '<span class="badge badge-warning">Menunggu</span>';
                                    } else if ($req['status'] == 'Disetujui') {
                                        echo '<span class="badge badge-success">Disetujui</span>';
                                    } else if ($req['status'] == 'Ditolak') {
                                        echo '<span class="badge badge-danger">Ditolak</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    // Tampilkan nama manager jika sudah ditangani, jika tidak tampilkan "-"
                                    echo $req['namaManager'] ? htmlspecialchars($req['namaManager']) : '-'; 
                                    ?>
                                </td>
                                <td>
                                    <a href="inventory_request_detail.php?id=<?php echo $req['requestID']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye fa-sm"></i> Detail
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