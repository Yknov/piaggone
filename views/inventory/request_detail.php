<h1 class="h3 mb-2 text-gray-800"><?php echo $title; ?></h1>
<p class="mb-4">Detail untuk permintaan pengadaan yang Anda buat.</p>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Item</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Jumlah Diminta</th>
                                <th>Estimasi Harga Satuan</th>
                                <th>Estimasi Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_estimasi = 0;
                            foreach ($request_items as $item): 
                                $subtotal = $item['harga'] * $item['jumlahDiminta'];
                                $total_estimasi += $subtotal;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['namaProduk']); ?></td>
                                    <td><?php echo htmlspecialchars($item['jumlahDiminta']); ?></td>
                                    <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Total Estimasi Biaya</th>
                                <th>Rp <?php echo number_format($total_estimasi, 0, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Status Permintaan</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Status</strong>
                        <span>
                            <?php
                            // Logika status dengan label
                            if ($request_master['status'] == 'Menunggu') {
                                echo '<span class="badge badge-warning">Menunggu</span>';
                            } else if ($request_master['status'] == 'Disetujui') {
                                echo '<span class="badge badge-success">Disetujui</span>';
                            } else if ($request_master['status'] == 'Ditolak') {
                                echo '<span class="badge badge-danger">Ditolak</span>';
                            }
                            ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Tanggal Dibuat</strong>
                        <span><?php echo date('d M Y', strtotime($request_master['tanggalPermintaan'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Dibuat Oleh</strong>
                        <span><?php echo htmlspecialchars($request_master['namaStaff']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Peninjau</strong>
                        <span>
                            <?php 
                            echo $request_master['namaManager'] ? htmlspecialchars($request_master['namaManager']) : '<i>Belum ditinjau</i>'; 
                            ?>
                        </span>
                    </li>
                </ul>
                <a href="inventory_request_history.php" class="btn btn-secondary btn-sm mt-3">
                    <i class="fas fa-arrow-left fa-sm"></i> Kembali ke Riwayat
                </a>
            </div>
        </div>
    </div>
</div>