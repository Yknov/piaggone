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

<div class="row justify-content-center w-100">
    <div class="col-xl-6 col-lg-8 col-md-9">
        <div class="card o-hidden border-0 shadow-lg">
            <div class="card-body p-0">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Selamat Datang!</h1>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success_message): ?>
                                <div class="alert alert-success text-center"><?php echo htmlspecialchars($success_message); ?></div>
                            <?php endif; ?>

                            <form class="user" method="POST" action="login.php">
                                <div class="form-group">
                                    <input type="email" class="form-control form-control-user"
                                        id="email" name="email" aria-describedby="emailHelp"
                                        placeholder="Masukkan Alamat Email..." required>
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control form-control-user"
                                        id="password" name="password" placeholder="Password" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-user btn-block">
                                    Login
                                </button>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a class="small" href="register.php">Buat Akun Baru!</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'views/layouts/footer_public.php'; ?>