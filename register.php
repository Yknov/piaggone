<?php
session_start();
require_once 'services/auth_service.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi sederhana
    if (empty($_POST['nama']) || empty($_POST['email']) || empty($_POST['password'])) {
        $error = 'Nama, Email, dan Password wajib diisi.';
    } else if ($_POST['password'] !== $_POST['password_repeat']) {
        $error = 'Password dan konfirmasi password tidak cocok.';
    } else {
        // Panggil fungsi BARU untuk registrasi PELANGGAN
        $result = FUNGSI_REGISTER_PELANGGAN(
            $_POST['nama'],
            $_POST['nomorHP'],
            $_POST['email'],
            $_POST['password']
        );

        if ($result['success']) {
            header('Location: login.php?status=reg_success');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$title = 'Register Akun Pelanggan';
require 'views/layouts/header_public.php';
?>

<div class="row justify-content-center w-100">
    <div class="col-xl-8 col-lg-10 col-md-9">
        <div class="card o-hidden border-0 shadow-lg">
            <div class="card-body p-0">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Buat Akun Pelanggan!</h1>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>

                            <form class="user" method="POST" action="register.php">
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" id="nama" name="nama"
                                        placeholder="Nama Lengkap" required>
                                </div>
                                <div class="form-group">
                                    <input type="email" class="form-control form-control-user" id="email" name="email"
                                        placeholder="Alamat Email" required>
                                </div>
                                <div class="form-group">
                                    <input type="tel" class="form-control form-control-user" id="nomorHP" name="nomorHP"
                                        placeholder="Nomor HP (Opsional)">
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="password" class="form-control form-control-user"
                                            id="password" name="password" placeholder="Password" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="password" class="form-control form-control-user"
                                            id="password_repeat" name="password_repeat" placeholder="Ulangi Password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-user btn-block">
                                    Register Akun
                                </button>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a class="small" href="login.php">Sudah punya akun? Login!</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'views/layouts/footer_public.php'; ?>