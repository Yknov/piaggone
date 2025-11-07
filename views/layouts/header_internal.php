<?php
// File ini mengasumsikan session sudah dimulai oleh file pemanggil
// Dapatkan nama file saat ini (misal: "dashboard_inventory.php")
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Piagone Barbershop'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/stylesheets/style.css">
</head>
<body class="bg-light">
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark navbar-fixed-top shadow-sm">
      <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Piagone Barbershop</a>
        <div class="d-flex">
          <?php if (isset($_SESSION['user'])): ?>
            <span class="navbar-text me-3">
              Login sebagai: 
              <strong><?php echo htmlspecialchars($_SESSION['user']['nama']); ?> (<?php echo htmlspecialchars($_SESSION['user']['role']); ?>)</strong>
            </span>
            <a class="btn btn-outline-light" href="logout.php">Logout</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <div class="sidebar shadow-sm">
        <nav class="nav flex-column p-3">
            
            <?php if ($_SESSION['user']['role'] === 'Inventory Staff'): ?>
                <div class="sidebar-heading">Menu Inventory</div>
                
                <a class="nav-link <?php echo ($current_page === 'dashboard_inventory.php') ? 'active' : ''; ?>" 
                   href="dashboard_inventory.php">
                    Dashboard
                </a>
                <a class="nav-link <?php echo ($current_page === 'inventory_stok.php') ? 'active' : ''; ?>" 
                   href="inventory_stok.php">
                    Stok Produk
                </a>
                <a class="nav-link <?php echo ($current_page === 'inventory_request.php') ? 'active' : ''; ?>" 
                   href="inventory_request.php">
                    Buat Permintaan
                </a>
                <?php elseif ($_SESSION['user']['role'] === 'Owner'): ?>
                <div class="sidebar-heading">Menu Owner</div>
                <?php elseif ($_SESSION['user']['role'] === 'Kasir'): ?>
                <div class="sidebar-heading">Menu Kasir</div>
                <?php endif; ?>
            
        </nav>
    </div>

    <main class="main-content p-4">