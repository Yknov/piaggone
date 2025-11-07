<h1 class="h2 mb-4">Dashboard</h1>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h5 class="card-title">Selamat Datang, <?php echo htmlspecialchars($user['nama']); ?>!</h5>
        <p class="card-text">Anda login sebagai Inventory Staff. Gunakan menu di sebelah kiri untuk mengelola stok dan permintaan barang.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card text-white bg-primary shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title text-uppercase">Total Varian Produk</h6>
                <h3 class="display-6"><?php echo $total_produk; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card text-dark bg-warning shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title text-uppercase">Stok Menipis</h6>
                <h3 class="display-6"><?php echo $stok_menipis; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card text-white bg-danger shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title text-uppercase">Stok Habis</h6>
                <h3 class="display-6"><?php echo $stok_habis; ?></h3>
            </div>
        </div>
    </div>
</div>