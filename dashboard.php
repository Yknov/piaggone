<?php
session_start();
require_once 'middleware/auth.php';

// 1. Pastikan user sudah login
FUNGSI_CEK_OTENTIKASI();

// 2. Ambil peran user dari session
$userRole = $_SESSION['user']['role'];

// 3. Arahkan ke dashboard yang sesuai
switch ($userRole) {
    case 'Owner':
        header('Location: dashboard_owner.php');
        break;
    case 'Store Manager':
        header('Location: dashboard_manager.php');
        break;
    case 'Inventory Staff':
        header('Location: dashboard_inventory.php');
        break;
    case 'Kasir':
        header('Location: dashboard_kasir.php');
        break;
    case 'Barber':
        header('Location: dashboard_barber.php');
        break;
    case 'Pelanggan':
        header('Location: dashboard_customer.php');
        break;
    default:
        // Jika role tidak dikenal, logout saja untuk keamanan
        header('Location: logout.php');
}
exit;
?>