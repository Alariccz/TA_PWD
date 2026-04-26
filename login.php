<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

$rememberedEmail = '';
if (isset($_COOKIE['remember_email'])) {
    $rememberedEmail = $_COOKIE['remember_email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = getDB()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_name']   = $user['name'];
            $_SESSION['user_avatar'] = $user['avatar'];

            if ($rememberMe) {
                setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
            } else {
                setcookie('remember_email', '', time() - 3600, '/');
            }

            setFlash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
            header('Location: index.php');
            exit;

        } else {
            $error = 'Email atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — EcoEnzyme</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Syne:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-box">

        <div class="auth-logo">
            <div class="auth-logo-mark">
                <div class="auth-logo-leaf"></div>
            </div>
            <span class="auth-logo-text">EcoEnzyme</span>
        </div>

        <h2 class="mb-4" style="font-family: var(--font-judul); font-size: 22px;">Masuk ke Akun</h2>

        <form method="POST" action="login.php">

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="email@contoh.com"
                    value="<?= htmlspecialchars($rememberedEmail ?: ($_POST['email'] ?? '')) ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                >
            </div>

            <label class="remember-label">
                <input
                    type="checkbox"
                    name="remember_me"
                    <?= $rememberedEmail ? 'checked' : '' ?>
                >
                Ingat saya selama 30 hari
            </label>

            <?php if ($error): ?>
                <div class="form-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Masuk →</button>
        </form>

        <div class="auth-switch">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>

        <div class="auth-demo-info">
            Demo: admin@ecoenzy.com / password
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>