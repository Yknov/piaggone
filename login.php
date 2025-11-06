<?php
session_start();
require_once 'services/auth_service.php';

$error = null;

// Jika sudah login, tendang ke dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

// Cek jika ada status sukses dari register
$success_message = null;
if (isset($_GET['status']) && $_GET['status'] === 'reg_success') {
    $success_message = 'Registrasi berhasil! Silakan login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user = FUNGSI_VERIFIKASI_LOGIN($email, $password);

    if ($user) {
        $_SESSION['user'] = $user;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Email atau password salah';
    }
}

$title = 'Login';
require 'views/layouts/header_public.php';
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title text-center"><?php echo htmlspecialchars($title); ?></h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label" for="email">Email:</label>
                        <input class="form-control" type="email" name="email" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password:</label>
                        <input class="form-control" type="password" name="password" id="password" required>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary" type="submit">Login</button>
                    </div>
                </form>
                
                <p class="mt-3 text-center">
                    <a href="register.php">Register Pegawai Baru</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require 'views/layouts/footer.php'; ?>