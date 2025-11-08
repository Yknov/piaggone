<h1 class="h3 mb-2 text-gray-800"><?php echo htmlspecialchars($title); ?></h1>
<p class="mb-4">Isi detail produk baru yang akan ditambahkan ke dalam sistem.</p>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Formulir Produk Baru</h6>
            </div>
            <div class="card-body">

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; // $success sudah di-HTML-kan di controller jika perlu ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="inventory_produk_create.php">
                    <div class="form-group">
                        <label for="namaProduk">Nama Produk</label>
                        <input type="text" class="form-control" id="namaProduk" name="namaProduk" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga">Harga</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" class="form-control" id="harga" name="harga" min="0" step="100" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jumlahStok">Jumlah Stok Awal</label>
                                <input type="number" class="form-control" id="jumlahStok" name="jumlahStok" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="batasMinimumStok">Batas Minimum Stok</label>
                                <input type="number" class="form-control" id="batasMinimumStok" name="batasMinimumStok" min="0" value="0" required>
                                <small class="form-text text-muted">Akan memicu status "Menipis" jika stok di bawah angka ini.</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Produk
                    </button>
                    <a href="inventory_stok.php" class="btn btn-secondary">
                        Batal
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>