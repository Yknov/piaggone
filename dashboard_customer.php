<?php
session_start();
require_once 'middleware/auth.php';

// 1. Keamanan
FUNGSI_CEK_OTENTIKASI();
FUNGSI_CEK_ROLE(['Pelanggan']); // Hanya Pelanggan

$user = $_SESSION['user'];
$title = 'Dashboard Pelanggan';

require 'views/layouts/header_internal.php';
require 'views/customer/dashboard.php';
require 'views/layouts/footer.php';
?>