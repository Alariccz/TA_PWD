<?php

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $avatar = strtoupper(substr($name, 0, 1));

        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO users (name, email, password, avatar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $avatar]);

            $newUserId = $db->lastInsertId();

            $_SESSION['user_id']     = $newUserId;
            $_SESSION['user_name']   = $name;
            $_SESSION['user_avatar'] = $avatar;

            setFlash('success', 'Akun berhasil dibuat! Selamat datang, ' . $name . ' 🌿');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Email sudah terdaftar. Gunakan email lain atau masuk ke akun yang ada.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — EcoEnzyme</title>

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

        <h2 class="mb-4" style="font-family: var(--font-judul); font-size: 22px;">Buat Akun Baru</h2>

        <form method="POST" action="register.php">

            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Nama kamu"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="email@contoh.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Min. 6 karakter"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Ketik ulang password"
                    required
                >
            </div>

            <?php if ($error): ?>
                <div class="form-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Buat Akun →</button>
        </form>

        <div class="auth-switch">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
