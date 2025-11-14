// Global variables
let keranjang = [];
let subtotal = 0;
let total = 0;

/**
 * Tambah produk ke keranjang
 * @param {Object} produk - Data produk yang akan ditambahkan
 */
function tambahKeKeranjang(produk) {
    // Cek apakah produk sudah ada di keranjang
    const existingItem = keranjang.find(item => item.id_produk === produk.id_produk);
    
    if (existingItem) {
        // Jika sudah ada, tambah jumlahnya
        // Cek stok terlebih dahulu
        if (existingItem.jumlah >= produk.stok) {
            showAlert('Stok tidak mencukupi! Stok tersedia: ' + produk.stok + ' pcs', 'danger');
            return;
        }
        existingItem.jumlah++;
    } else {
        // Jika belum ada, tambahkan item baru
        keranjang.push({
            id_produk: produk.id_produk,
            nama_produk: produk.nama_produk,
            harga: parseFloat(produk.harga),
            jumlah: 1,
            stok: produk.stok
        });
    }
    
    // Update tampilan keranjang dan hitung total
    renderKeranjang();
    hitungTotal();
    
    // Feedback ke user
    showAlert('Produk "' + produk.nama_produk + '" ditambahkan ke keranjang', 'success');
}

/**
 * Render tampilan keranjang belanja
 */
function renderKeranjang() {
    const container = document.getElementById('keranjangContainer');
    
    if (!container) {
        console.error('Element keranjangContainer tidak ditemukan!');
        return;
    }
    
    // Jika keranjang kosong
    if (keranjang.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                <p>Keranjang masih kosong</p>
                <small>Pilih produk dari daftar untuk memulai transaksi</small>
            </div>
        `;
        return;
    }
    
    // Render setiap item di keranjang
    let html = '';
    keranjang.forEach((item, index) => {
        const itemTotal = item.harga * item.jumlah;
        html += `
            <div class="card mb-2 shadow-sm">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="flex-grow-1">
                            <strong>${item.nama_produk}</strong><br>
                            <small class="text-muted">
                                Rp ${formatRupiah(item.harga)} x ${item.jumlah} pcs
                            </small>
                        </div>
                        <div class="text-end">
                            <strong class="text-success">Rp ${formatRupiah(itemTotal)}</strong>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-danger" onclick="updateJumlah(${index}, -1)" title="Kurangi">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button class="btn btn-outline-secondary" disabled>
                                <strong>${item.jumlah}</strong>
                            </button>
                            <button class="btn btn-outline-success" onclick="updateJumlah(${index}, 1)" title="Tambah">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="hapusDariKeranjang(${index})" title="Hapus">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                    ${item.jumlah >= item.stok ? '<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Stok maksimal!</small>' : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

/**
 * Update jumlah item di keranjang
 * @param {Number} index - Index item di array keranjang
 * @param {Number} change - Perubahan jumlah (+1 atau -1)
 */
function updateJumlah(index, change) {
    const item = keranjang[index];
    const newJumlah = item.jumlah + change;
    
    // Jika jumlah jadi 0 atau kurang, hapus dari keranjang
    if (newJumlah <= 0) {
        hapusDariKeranjang(index);
        return;
    }
    
    // Cek stok
    if (newJumlah > item.stok) {
        showAlert('Stok tidak mencukupi! Stok tersedia: ' + item.stok + ' pcs', 'warning');
        return;
    }
    
    // Update jumlah
    item.jumlah = newJumlah;
    
    // Update tampilan dan hitung ulang
    renderKeranjang();
    hitungTotal();
}

/**
 * Hapus item dari keranjang
 * @param {Number} index - Index item di array keranjang
 */
function hapusDariKeranjang(index) {
    const item = keranjang[index];
    
    if (confirm('Hapus "' + item.nama_produk + '" dari keranjang?')) {
        keranjang.splice(index, 1);
        renderKeranjang();
        hitungTotal();
        showAlert('Produk dihapus dari keranjang', 'info');
    }
}

/**
 * Reset/kosongkan keranjang
 */
function resetKeranjang() {
    if (keranjang.length === 0) {
        showAlert('Keranjang sudah kosong!', 'info');
        return;
    }
    
    if (confirm('Yakin ingin mengosongkan keranjang?')) {
        keranjang = [];
        renderKeranjang();
        hitungTotal();
        
        // Reset form
        document.getElementById('diskon').value = 0;
        document.getElementById('uangDiterima').value = '';
        document.getElementById('metodeBayar').value = 'Tunai';
        toggleUangDiterima();
        
        showAlert('Keranjang berhasil dikosongkan', 'info');
    }
}

/**
 * Hitung total belanja
 */
function hitungTotal() {
    // Hitung subtotal
    subtotal = keranjang.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
    
    // Ambil diskon
    const diskon = parseFloat(document.getElementById('diskon').value) || 0;
    
    // Hitung total
    total = subtotal - diskon;
    
    // Pastikan total tidak negatif
    if (total < 0) {
        total = 0;
        document.getElementById('diskon').value = subtotal;
    }
    
    // Update tampilan
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const totalDisplay = document.getElementById('totalDisplay');
    
    if (subtotalDisplay) {
        subtotalDisplay.textContent = 'Rp ' + formatRupiah(subtotal);
    }
    
    if (totalDisplay) {
        totalDisplay.textContent = 'Rp ' + formatRupiah(total);
    }
    
    // Hitung kembalian jika metode tunai
    hitungKembalian();
}

/**
 * Hitung kembalian (untuk metode pembayaran tunai)
 */
function hitungKembalian() {
    const metodeBayar = document.getElementById('metodeBayar').value;
    
    if (metodeBayar !== 'Tunai') {
        return;
    }
    
    const uangDiterima = parseFloat(document.getElementById('uangDiterima').value) || 0;
    const kembalian = uangDiterima - total;
    
    const kembalianDisplay = document.getElementById('kembalianDisplay');
    if (kembalianDisplay) {
        kembalianDisplay.textContent = 'Rp ' + formatRupiah(kembalian > 0 ? kembalian : 0);
        
        // Warning jika uang kurang
        if (uangDiterima > 0 && kembalian < 0) {
            kembalianDisplay.classList.add('text-danger');
            kembalianDisplay.textContent = 'Uang kurang: Rp ' + formatRupiah(Math.abs(kembalian));
        } else {
            kembalianDisplay.classList.remove('text-danger');
        }
    }
}

/**
 * Toggle input uang diterima berdasarkan metode pembayaran
 */
function toggleUangDiterima() {
    const metodeBayar = document.getElementById('metodeBayar').value;
    const container = document.getElementById('uangDiterimaContainer');
    const uangDiterimaInput = document.getElementById('uangDiterima');
    
    if (!container) return;
    
    if (metodeBayar === 'Tunai') {
        container.style.display = 'block';
        uangDiterimaInput.value = '';
        uangDiterimaInput.required = true;
    } else {
        container.style.display = 'none';
        uangDiterimaInput.value = total;
        uangDiterimaInput.required = false;
    }
    
    hitungKembalian();
}

/**
 * Proses transaksi pembayaran
 */
async function prosesTransaksi() {
    // Validasi keranjang
    if (keranjang.length === 0) {
        showAlert('Keranjang masih kosong! Silakan pilih produk terlebih dahulu.', 'warning');
        return;
    }
    
    // Validasi pembayaran
    const metodeBayar = document.getElementById('metodeBayar').value;
    const uangDiterima = parseFloat(document.getElementById('uangDiterima').value) || 0;
    
    if (metodeBayar === 'Tunai') {
        if (uangDiterima <= 0) {
            showAlert('Mohon masukkan nominal uang yang diterima!', 'warning');
            document.getElementById('uangDiterima').focus();
            return;
        }
        
        if (uangDiterima < total) {
            showAlert('Uang yang diterima kurang dari total pembayaran!', 'danger');
            document.getElementById('uangDiterima').focus();
            return;
        }
    }
    
    // Konfirmasi
    const konfirmasi = confirm(
        'Proses transaksi ini?\n\n' +
        'Total: Rp ' + formatRupiah(total) + '\n' +
        'Metode: ' + metodeBayar +
        (metodeBayar === 'Tunai' ? '\nKembalian: Rp ' + formatRupiah(uangDiterima - total) : '')
    );
    
    if (!konfirmasi) return;
    
    // Disable button dan tampilkan loading
    const btnProses = event.target;
    const btnOriginalHTML = btnProses.innerHTML;
    btnProses.disabled = true;
    btnProses.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses transaksi...';
    
    // Siapkan data untuk dikirim
    const data = {
        id_kasir: document.getElementById('idKasir').value,
        items: keranjang,
        diskon: parseFloat(document.getElementById('diskon').value) || 0,
        metode_bayar: metodeBayar,
        uang_diterima: metodeBayar === 'Tunai' ? uangDiterima : total
    };
    
    try {
        // Kirim request ke server
        const response = await fetch('proses_penjualan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        // Parse response
        const result = await response.json();
        
        if (result.success) {
            // Transaksi berhasil
            showAlert(
                'Transaksi berhasil!<br>Kode: <strong>' + result.kode_transaksi + '</strong>', 
                'success'
            );
            
            // Tanya apakah mau cetak nota
            setTimeout(() => {
                if (confirm('Transaksi berhasil disimpan!\n\nCetak nota pembayaran?')) {
                    // Buka halaman cetak nota di tab baru
                    window.open('cetak_nota.php?id=' + result.id_transaksi, '_blank', 'width=800,height=600');
                }
                
                // Reset keranjang dan reload halaman
                keranjang = [];
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }, 1000);
            
        } else {
            // Transaksi gagal
            showAlert('Transaksi gagal! ' + result.message, 'danger');
            btnProses.disabled = false;
            btnProses.innerHTML = btnOriginalHTML;
        }
        
    } catch (error) {
        // Error koneksi atau server
        console.error('Error:', error);
        showAlert('Terjadi kesalahan: ' + error.message + '<br>Silakan coba lagi atau hubungi admin.', 'danger');
        btnProses.disabled = false;
        btnProses.innerHTML = btnOriginalHTML;
    }
}

/**
 * Filter tabel produk berdasarkan kategori dan pencarian
 */
function filterProduk() {
    const kategori = document.getElementById('filterKategori').value.toLowerCase();
    const search = document.getElementById('searchProduk').value.toLowerCase();
    const rows = document.querySelectorAll('#tableProduk tbody tr');
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        const rowKategori = row.dataset.kategori.toLowerCase();
        const rowNama = row.dataset.nama;
        
        const matchKategori = !kategori || rowKategori === kategori;
        const matchSearch = !search || rowNama.includes(search);
        
        if (matchKategori && matchSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Tampilkan pesan jika tidak ada hasil
    if (visibleCount === 0) {
        console.log('Tidak ada produk yang sesuai dengan filter');
    }
}

/**
 * Format angka ke format rupiah
 * @param {Number} angka - Angka yang akan diformat
 * @returns {String} Format: 123.456.789
 */
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID').format(angka);
}

/**
 * Tampilkan alert/notifikasi
 * @param {String} message - Pesan yang akan ditampilkan
 * @param {String} type - Tipe alert (success, danger, warning, info)
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    
    if (!alertContainer) {
        console.warn('Element alertContainer tidak ditemukan, menggunakan alert browser');
        alert(message.replace(/<[^>]*>/g, '')); // Remove HTML tags
        return;
    }
    
    // Icon berdasarkan tipe
    const icons = {
        success: 'fa-check-circle',
        danger: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const icon = icons[type] || icons.info;
    
    // Buat alert element
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas ${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHTML;
    
    // Auto close setelah 5 detik
    setTimeout(() => {
        const alertElement = alertContainer.querySelector('.alert');
        if (alertElement) {
            alertElement.classList.remove('show');
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 150);
        }
    }, 5000);
    
    // Scroll ke atas untuk melihat alert
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Initialize saat halaman dimuat
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Penjualan.js loaded');
    
    // Set default metode bayar
    toggleUangDiterima();
    
    // Add event listener untuk input diskon
    const diskonInput = document.getElementById('diskon');
    if (diskonInput) {
        diskonInput.addEventListener('input', hitungTotal);
    }
    
    // Add event listener untuk input uang diterima
    const uangDiterimaInput = document.getElementById('uangDiterima');
    if (uangDiterimaInput) {
        uangDiterimaInput.addEventListener('input', hitungKembalian);
    }
    
    // Add event listener untuk metode bayar
    const metodeBayarSelect = document.getElementById('metodeBayar');
    if (metodeBayarSelect) {
        metodeBayarSelect.addEventListener('change', toggleUangDiterima);
    }
    
    console.log('Event listeners initialized');
});
