<h1 class="h3 mb-2 text-gray-800"><?php echo htmlspecialchars($title); ?></h1>
<p class="mb-4">Daftar semua produk yang terdaftar di dalam sistem.</p>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Produk</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok Saat Ini</th>
                        <th>Batas Min. Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_produk)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada data produk.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar_produk as $produk): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produk['produkID']); ?></td>
                                <td><?php echo htmlspecialchars($produk['namaProduk']); ?></td>
                                <td>Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($produk['jumlahStok']); ?></td>
                                <td><?php echo htmlspecialchars($produk['batasMinimumStok']); ?></td>
                                <td>
                                    <?php
                                    // Beri tanda visual berdasarkan status stok
                                    if ($produk['jumlahStok'] == 0) {
                                        echo '<span class="badge badge-danger">Habis</span>';
                                    } else if ($produk['jumlahStok'] <= $produk['batasMinimumStok']) {
                                        echo '<span class="badge badge-warning">Menipis</span>';
                                    } else {
                                        echo '<span class="badge badge-success">Aman</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>