<?php
session_start();
require_once 'services/auth_service.php';

$error = null;
$success = null;

// Jika sudah login, redirect ke dashboard (opsional)
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil & trim input dengan aman
    $nama = trim((string)($_POST['nama'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $nomorHP = trim((string)($_POST['nomorHP'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = trim((string)($_POST['role'] ?? ''));

    // Validasi sederhana (di PHP)
    $allowed_roles = ['Owner','Store Manager','Inventory Staff','Kasir','Barber','Marketing'];

    if ($nama === '' || $email === '' || $password === '' || $role === '') {
        $error = 'Nama, Email, Password, dan Role wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = 'Role tidak valid.';
    } else {
        // Biarkan service yang melakukan pengecekan duplikat lebih teliti
        $result = FUNGSI_REGISTER_PEGAWAI(
            $nama,
            $nomorHP === '' ? null : $nomorHP,
            $email,
            $password,
            $role
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
                        <input class="form-control" type="text" name="nama" id="nama" required value="<?php echo isset($nama) ? htmlspecialchars($nama) : ''; ?>">
                    </div>
                     <div class="mb-3">
                        <label class="form-label" for="email">Email:</label>
                        <input class="form-control" type="email" name="email" id="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nomorHP">Nomor HP:</label>
                        <input class="form-control" type="tel" name="nomorHP" id="nomorHP" value="<?php echo isset($nomorHP) ? htmlspecialchars($nomorHP) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password:</label>
                        <input class="form-control" type="password" name="password" id="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role">Role:</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="Owner" <?php echo (isset($role) && $role==='Owner') ? 'selected' : ''; ?>>Owner</option>
                            <option value="Store Manager" <?php echo (isset($role) && $role==='Store Manager') ? 'selected' : ''; ?>>Store Manager</option>
                            <option value="Inventory Staff" <?php echo (isset($role) && $role==='Inventory Staff') ? 'selected' : ''; ?>>Inventory Staff</option>
                            <option value="Kasir" <?php echo (isset($role) && $role==='Kasir') ? 'selected' : ''; ?>>Kasir</option>
                            <option value="Barber" <?php echo (isset($role) && $role==='Barber') ? 'selected' : ''; ?>>Barber</option>
                            <option value="Marketing" <?php echo (isset($role) && $role==='Marketing') ? 'selected' : ''; ?>>Marketing</option>
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