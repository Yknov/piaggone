<?php
// File ini mengasumsikan session sudah dimulai oleh file pemanggil
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Piagone Barbershop'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container">
        <a class="navbar-brand" href="dashboard.php">Piagone Barbershop</a>
        <div class="d-flex">
          <?php if (isset($_SESSION['user'])): ?>
            <span class="navbar-text me-3">
              Login sebagai: 
              <strong><?php echo htmlspecialchars($_SESSION['user']['nama']); // DIGANTI DARI USERNAME ?> (<?php echo htmlspecialchars($_SESSION['user']['role']); ?>)</strong>
            </span>
            <a class="btn btn-outline-light" href="logout.php">Logout</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <main class="container mt-4">