<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Daftar Stok Produk</h3>
        </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark">
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
                            <td colspan="7" class="text-center">Belum ada data produk.</td>
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
                                        echo '<span class="badge bg-danger">Habis</span>';
                                    } else if ($produk['jumlahStok'] <= $produk['batasMinimumStok']) {
                                        echo '<span class="badge bg-warning text-dark">Menipis</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Aman</span>';
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