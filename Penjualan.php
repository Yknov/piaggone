<?php
// File: config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "piaggone";
    private $username = "root"; // sesuaikan dengan username MySQL Anda
    private $password = ""; // sesuaikan dengan password MySQL Anda
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// ==========================================
// File: api/get_products.php
// ==========================================
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT produkID, namaProduk, harga, jumlahStok, batasMinimumStok 
          FROM produk 
          WHERE jumlahStok > 0 
          ORDER BY namaProduk";

$stmt = $db->prepare($query);
$stmt->execute();

$products = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $product = array(
        "id" => $row['produkID'],
        "name" => $row['namaProduk'],
        "price" => floatval($row['harga']),
        "stock" => intval($row['jumlahStok']),
        "minStock" => intval($row['batasMinimumStok'])
    );
    array_push($products, $product);
}

http_response_code(200);
echo json_encode($products);
?>

// ==========================================
// File: api/get_promos.php
// ==========================================
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$today = date('Y-m-d');

$query = "SELECT promoID, namaPromo, deskripsi, jenisDiskon, nilaiDiskon 
          FROM promo 
          WHERE tanggalMulai <= :today AND tanggalSelesai >= :today
          ORDER BY namaPromo";

$stmt = $db->prepare($query);
$stmt->bindParam(":today", $today);
$stmt->execute();

$promos = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $promo = array(
        "id" => $row['promoID'],
        "name" => $row['namaPromo'],
        "description" => $row['deskripsi'],
        "type" => $row['jenisDiskon'],
        "value" => floatval($row['nilaiDiskon'])
    );
    array_push($promos, $promo);
}

http_response_code(200);
echo json_encode($promos);
?>

// ==========================================
// File: api/create_transaction.php
// ==========================================
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->userID_pelanggan) &&
    !empty($data->userID_pegawai) &&
    !empty($data->items) &&
    !empty($data->metodePembayaran) &&
    !empty($data->totalHarga)
) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Insert transaksi
        $query = "INSERT INTO transaksi 
                  (userID_pelanggan, userID_pegawai, promoID, metodePembayaran, totalHarga, status) 
                  VALUES 
                  (:userID_pelanggan, :userID_pegawai, :promoID, :metodePembayaran, :totalHarga, 'Selesai')";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":userID_pelanggan", $data->userID_pelanggan);
        $stmt->bindParam(":userID_pegawai", $data->userID_pegawai);
        $stmt->bindParam(":promoID", $data->promoID);
        $stmt->bindParam(":metodePembayaran", $data->metodePembayaran);
        $stmt->bindParam(":totalHarga", $data->totalHarga);
        
        if($stmt->execute()) {
            $transaksiID = $db->lastInsertId();
            
            // Insert detail transaksi
            $queryDetail = "INSERT INTO detailtransaksi 
                           (transaksiID, produkID, jumlah, subTotal) 
                           VALUES 
                           (:transaksiID, :produkID, :jumlah, :subTotal)";
            
            $stmtDetail = $db->prepare($queryDetail);
            
            foreach($data->items as $item) {
                $stmtDetail->bindParam(":transaksiID", $transaksiID);
                $stmtDetail->bindParam(":produkID", $item->produkID);
                $stmtDetail->bindParam(":jumlah", $item->jumlah);
                $stmtDetail->bindParam(":subTotal", $item->subTotal);
                $stmtDetail->execute();
                
                // Update stok
                $queryUpdateStok = "UPDATE produk 
                                   SET jumlahStok = jumlahStok - :jumlah 
                                   WHERE produkID = :produkID";
                $stmtUpdateStok = $db->prepare($queryUpdateStok);
                $stmtUpdateStok->bindParam(":jumlah", $item->jumlah);
                $stmtUpdateStok->bindParam(":produkID", $item->produkID);
                $stmtUpdateStok->execute();
            }
            
            // Commit transaction
            $db->commit();
            
            http_response_code(201);
            echo json_encode(array(
                "message" => "Transaksi berhasil dibuat.",
                "transaksiID" => $transaksiID
            ));
        }
    } catch(Exception $e) {
        // Rollback on error
        $db->rollBack();
        
        http_response_code(503);
        echo json_encode(array("message" => "Gagal membuat transaksi. " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Data tidak lengkap."));
}
?>

// ==========================================
// File: api/get_sales_report.php
// ==========================================
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get date range from query parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$query = "SELECT 
            DATE(t.tanggalWaktu) as tanggal,
            COUNT(DISTINCT t.transaksiID) as jumlah_transaksi,
            SUM(t.totalHarga) as total_penjualan,
            SUM(CASE WHEN dt.produkID IS NOT NULL THEN dt.subTotal ELSE 0 END) as penjualan_produk,
            SUM(CASE WHEN dt.layananID IS NOT NULL THEN dt.subTotal ELSE 0 END) as penjualan_layanan
          FROM transaksi t
          LEFT JOIN detailtransaksi dt ON t.transaksiID = dt.transaksiID
          WHERE t.status = 'Selesai'
            AND DATE(t.tanggalWaktu) BETWEEN :startDate AND :endDate
          GROUP BY DATE(t.tanggalWaktu)
          ORDER BY DATE(t.tanggalWaktu) DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":startDate", $startDate);
$stmt->bindParam(":endDate", $endDate);
$stmt->execute();

$reports = array();
$totalRevenue = 0;
$totalTransactions = 0;

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $report = array(
        "tanggal" => $row['tanggal'],
        "jumlah_transaksi" => intval($row['jumlah_transaksi']),
        "total_penjualan" => floatval($row['total_penjualan']),
        "penjualan_produk" => floatval($row['penjualan_produk']),
        "penjualan_layanan" => floatval($row['penjualan_layanan'])
    );
    array_push($reports, $report);
    $totalRevenue += floatval($row['total_penjualan']);
    $totalTransactions += intval($row['jumlah_transaksi']);
}

$response = array(
    "period" => array(
        "start" => $startDate,
        "end" => $endDate
    ),
    "summary" => array(
        "total_revenue" => $totalRevenue,
        "total_transactions" => $totalTransactions,
        "average_transaction" => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0
    ),
    "daily_reports" => $reports
);

http_response_code(200);
echo json_encode($response);
?>

// ==========================================
// File: api/check_low_stock.php
// ==========================================
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT produkID, namaProduk, jumlahStok, batasMinimumStok,
                 (batasMinimumStok - jumlahStok) as kekurangan
          FROM produk
          WHERE jumlahStok <= batasMinimumStok
          ORDER BY kekurangan DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$products = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $product = array(
        "produkID" => $row['produkID'],
        "namaProduk" => $row['namaProduk'],
        "jumlahStok" => intval($row['jumlahStok']),
        "batasMinimumStok" => intval($row['batasMinimumStok']),
        "kekurangan" => intval($row['kekurangan'])
    );
    array_push($products, $product);
}

http_response_code(200);
echo json_encode(array(
    "count" => count($products),
    "products" => $products
));
?>
