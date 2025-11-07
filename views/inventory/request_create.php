<h1 class="h3 mb-2 text-gray-800"><?php echo htmlspecialchars($title); ?></h1>
<p class="mb-4">Buat permintaan pengadaan barang baru. Tambahkan produk yang stoknya menipis.</p>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Formulir Permintaan Pengadaan</h6>
                <a href="inventory_produk_create.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus fa-sm"></i> Buat Produk Baru
                </a>
            </div>
            <div class="card-body">
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="inventory_request_create.php">
                    <div class="table-responsive">
                        <table class="table" id="item-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Jumlah Diminta</th>
                                    <th width="50px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="item-list">
                                </tbody>
                        </table>
                    </div>
                    
                    <button type="button" id="add-row-btn" class="btn btn-info btn-sm">
                        <i class="fas fa-plus"></i> Tambah Produk ke Permintaan
                    </button>
                    
                    <hr>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kirim Permintaan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>