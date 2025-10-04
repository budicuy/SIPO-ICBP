<?php
session_start();
include 'koneksi.php';

$error = '';
$login_success = false;

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM user WHERE username='$username' LIMIT 1");
    $user = mysqli_fetch_assoc($query);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['id_user']       = $user['id_user'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
            $_SESSION['role']          = $user['role'];
            $login_success = true;
        } else {
            $error = 'Password salah.';
        }
    } else {
        $error = 'Username tidak ditemukan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Klinik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        .login-box {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 30px;
            width: 100%;
            max-width: 360px;
        }
        .login-title {
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
            color: #343a40;
        }
        .btn-login {
            background: #0d6efd;
            border: none;
            font-weight: 500;
        }
        .btn-login:hover {
            background: #0b5ed7;
        }
        .error-msg {
            background: #f8d7da;
            color: #842029;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        footer {
            font-size: 0.8em;
            color: #6c757d;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="login-title">Sistem Informasi Klinik</div>

    <?php if ($error): ?>
        <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="login" class="btn btn-login w-100">Login</button>
    </form>
</div>

<?php if ($login_success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Login Berhasil',
    text: 'Selamat datang <?= $_SESSION['nama_lengkap'] ?>',
    confirmButtonText: 'OK'
}).then(() => {
    window.location.href = "dashboard.php";
});
</script>
<?php endif; ?>

</body>
</html>
