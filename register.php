<?php
session_start();
require_once 'services/auth_service.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi sederhana (di PHP)
    if (empty($_POST['nama']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['role'])) {
        $error = 'Nama, Email, Password, dan Role wajib diisi.';
    } else {
        $result = FUNGSI_REGISTER_PEGAWAI(
            $_POST['nama'],
            $_POST['nomorHP'],
            $_POST['email'],
            $_POST['password'],
            $_POST['role']
        );

        if ($result['success']) {
            header('Location: login.php?status=reg_success');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$title = 'Register Pegawai Baru';
require 'views/layouts/header_public.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title text-center"><?php echo htmlspecialchars($title); ?></h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label" for="nama">Nama Lengkap:</label>
                        <input class="form-control" type="text" name="nama" id="nama" required>
                    </div>
                     <div class="mb-3">
                        <label class="form-label" for="email">Email:</label>
                        <input class="form-control" type="email" name="email" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nomorHP">Nomor HP:</label>
                        <input class="form-control" type="tel" name="nomorHP" id="nomorHP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password:</label>
                        <input class="form-control" type="password" name="password" id="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role">Role:</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="Owner">Owner</option>
                            <option value="Store Manager">Store Manager</option>
                            <option value="Inventory Staff">Inventory Staff</option>
                            <option value="Kasir">Kasir</option>
                            <option value="Barber">Barber</option>
                            <option value="Marketing">Marketing</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary" type="submit">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'views/layouts/footer.php'; ?>