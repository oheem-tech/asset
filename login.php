<?php
require_once 'config.php';

// Jika sudah login, langsung arahkan ke index
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Kredensial Hardcode sesuai permintaan pengguna
    if ($username === 'ibrohim' && $password === 'sman1gegesik') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Inventaris Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 400px;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="login-box text-center">
    <div class="mb-4">
        <i class="bi bi-box-seam text-primary" style="font-size: 3rem;"></i>
        <h4 class="mt-2 fw-bold">Sistem Inventaris Aset</h4>
        <p class="text-muted">Silakan login untuk melanjutkan</p>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="post" action="login.php">
        <div class="form-floating mb-3 text-start">
            <input type="text" class="form-control" id="floatingInput" name="username" placeholder="Username" required autofocus>
            <label for="floatingInput">Username</label>
        </div>
        <div class="form-floating mb-4 text-start">
            <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
            <label for="floatingPassword">Password</label>
        </div>
        
        <button class="btn btn-primary w-100 py-2 fw-bold" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Masuk
        </button>
    </form>
    <div class="mt-4 text-muted" style="font-size: 12px;">
        &copy; <?= date('Y') ?> Tata Usaha
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
