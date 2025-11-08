<?php
session_start();
require_once 'middleware/auth.php';
require_once 'services/inventory_service.php';

// 1. Keamanan: Pastikan user login dan adalah Inventory Staff
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Inventory Staff', 'Store Manager', 'Owner']);

$user = $_SESSION['user'];
$error = null;
$success = null;

// 2. Logika untuk memproses formulir saat disubmit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_user_id = $user['userID'];
    $produk_ids = $_POST['produk'] ?? []; // Ambil array produkID
    $jumlahs = $_POST['jumlah'] ?? []; // Ambil array jumlah

    $items_to_request = [];
    
    // 3. Validasi dan format data
    if (!empty($produk_ids) && count($produk_ids) === count($jumlahs)) {
        for ($i = 0; $i < count($produk_ids); $i++) {
            $produk_id = (int)$produk_ids[$i];
            $jumlah = (int)$jumlahs[$i];

            if ($produk_id > 0 && $jumlah > 0) {
                $items_to_request[] = [
                    'produkID' => $produk_id,
                    'jumlah' => $jumlah
                ];
            }
        }
    }

    // 4. Kirim ke service jika data valid
    if (!empty($items_to_request)) {
        $result = FUNGSI_CREATE_PENGADAAN($staff_user_id, $items_to_request);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = "Formulir tidak boleh kosong. Harap tambahkan setidaknya satu produk.";
    }
}

// 5. Logika untuk menampilkan halaman (GET)
// Ambil semua produk untuk mengisi <select> dropdown di formulir
$daftar_produk_all = FUNGSI_GET_ALL_PRODUK();

$title = 'Buat Permintaan Pengadaan';
require 'views/layouts/header_internal.php';
require 'views/inventory/request_create.php'; // Panggil view yang akan kita buat
require 'views/layouts/footer.php';

// BAGIAN JAVASCRIPT DIMULAI DI SINI
// Ini akan dieksekusi SETELAH footer.php memuat jQuery
?>

<script type="text/javascript">
    const PRODUK_LIST = <?php echo json_encode($daftar_produk_all); ?>;
</script>

<script>
    // Pastikan dokumen sudah siap (jQuery dimuat dari footer.php)
    $(document).ready(function() {
        
        // Fungsi untuk membuat <select> produk
        function createProductSelect() {
            let select = '<select class="form-control" name="produk[]" required>';
            select += '<option value="">-- Pilih Produk --</option>';
            
            PRODUK_LIST.forEach(function(produk) {
                select += `<option value="${produk.produkID}">${produk.namaProduk}</option>`;
            });
            
            select += '</select>';
            return select;
        }

        // Fungsi untuk menambah baris baru
        function addRow() {
            const newRow = `
                <tr>
                    <td>${createProductSelect()}</td>
                    <td>
                        <input type="number" name="jumlah[]" class="form-control" placeholder="Jumlah" min="1" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-row-btn">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#item-list').append(newRow);
        }

        // Klik tombol "Tambah Produk ke Permintaan"
        $('#add-row-btn').click(function() {
            addRow();
        });

        // Klik tombol "Hapus" pada baris
        $('#item-list').on('click', '.remove-row-btn', function() {
            $(this).closest('tr').remove();
        });

        // Otomatis tambahkan satu baris saat halaman dimuat
        addRow();
    });
</script>