<?php
// header.php - template header + sidebar dengan role-based navigation
// Pastikan fungsi getFlash() dan currentUserRole() sudah didefiniskan di config/functions.php
$flash = getFlash();
$role  = currentUserRole(); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'EcoEnzyme') ?> — EcoEnzyme</title>

    <!-- External CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Syne:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <script>
        // Menerapkan tema sebelum halaman dimuat sepenuhnya
        (function() {
            const saved = localStorage.getItem('ecoenzy_theme');
            if (saved === 'light') document.documentElement.setAttribute('data-theme', 'light');
        })();
    </script>
</head>
<body>

<div id="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
            <div class="sb-logo">
            <div class="sb-logo-mark"><div class="sb-logo-leaf"></div></div>
            <span class="sb-logo-text">EcoEnzyme</span>
        </div>

        <?php if ($role === 'admin'): ?>
            <!-- MENU ADMIN -->
            <div class="sb-section">Admin</div>
            <a href="admin_dashboard.php" class="sb-item <?= ($activePage ?? '') === 'admin_overview' ? 'active' : '' ?>">
                <div class="sb-dot amber"></div><span>Dasbor Admin</span>
            </a>
            <a href="admin_users.php" class="sb-item <?= ($activePage ?? '') === 'admin_users' ? 'active' : '' ?>">
                <div class="sb-dot amber"></div><span>Daftar Semua User</span>
            </a>
            <a href="admin_report.php" class="sb-item <?= ($activePage ?? '') === 'admin_report' ? 'active' : '' ?>">
                <div class="sb-dot amber"></div><span>Laporan Global</span>
            </a>

            <div class="sb-section">Manajemen</div>
            <a href="benefits.php" class="sb-item <?= ($activePage ?? '') === 'benefits' ? 'active' : '' ?>">
                <div class="sb-dot"></div><span>Manfaat Enzim</span>
            </a>
            <a href="admin_trouble.php" class="sb-item <?= ($activePage ?? '') === 'trouble' ? 'active' : '' ?>">
                <div class="sb-dot red"></div><span>Pemecahan Masalah</span>
            </a>

        <?php else: ?>
            <!-- MENU USER -->
            <div class="sb-section">Menu</div>
            <a href="index.php" class="sb-item <?= ($activePage ?? '') === 'overview' ? 'active' : '' ?>">
                <div class="sb-dot"></div><span>Ikhtisar</span>
            </a>
            <a href="batch.php" class="sb-item <?= ($activePage ?? '') === 'batch' ? 'active' : '' ?>">
                <div class="sb-dot blue"></div><span>Kelola Batch</span>
            </a>
            <a href="ingredients.php" class="sb-item <?= ($activePage ?? '') === 'ingredients' ? 'active' : '' ?>">
                <div class="sb-dot blue"></div><span>Bahan &amp; Resep</span>
            </a>
            <a href="logs.php" class="sb-item <?= ($activePage ?? '') === 'logs' ? 'active' : '' ?>">
                <div class="sb-dot"></div><span>Catatan Produksi</span>
            </a>
            <a href="log_harian.php" class="sb-item <?= ($activePage ?? '') === 'log_harian' ? 'active' : '' ?>">
                <div class="sb-dot"></div><span>Log Harian</span>
            </a>
            <a href="kalkulator.php" class="sb-item <?= ($activePage ?? '') === 'kalkulator' ? 'active' : '' ?>">
                <div class="sb-dot blue"></div><span>Kalkulator Takaran</span>
            </a>
            <a href="trouble.php" class="sb-item <?= ($activePage ?? '') === 'trouble' ? 'active' : '' ?>">
                <div class="sb-dot red"></div><span>Pemecahan Masalah</span>
            </a>
        <?php endif; ?>

        <div class="sb-section">Akun</div>
        <a href="profile.php" class="sb-item <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
            <div class="sb-dot amber"></div><span>Profil Saya</span>
        </a>

        <div class="sb-spacer"></div>

        <!-- INFO USER -->
        <div class="sb-user">
            <div class="sb-avatar"><?= htmlspecialchars(currentUserAvatar()) ?></div>
            <div style="min-width:0">
                <div class="sb-user-name"><?= htmlspecialchars(currentUserName()) ?></div>
                <span class="role-badge <?= $role === 'admin' ? 'role-badge--admin' : 'role-badge--user' ?>">
                    <?= $role === 'admin' ? 'Admin' : 'User' ?>
                </span>
                <a href="logout.php" class="sb-logout">Keluar</a>
            </div>
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <div class="main">
        <div class="topbar">
            <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dasbor') ?></span>
            <div class="topbar-right">
                <span class="topbar-salam">Halo, <?= htmlspecialchars(currentUserName()) ?>!</span>
                <span class="role-badge <?= $role === 'admin' ? 'role-badge--admin' : 'role-badge--user' ?>">
                    <?= $role === 'admin' ? '👑 Admin' : '🌿 User' ?>
                </span>
                <button class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Ganti tema">
                    <span id="themeIcon">☀️</span>
                    <span id="themeLabel">Light</span>
                </button>
            </div>
        </div>

        <div class="content">
            <!-- Pesan Flash -->
            <?php if (isset($flash['message']) && $flash['message']): ?>
                <div class="flash-msg <?= htmlspecialchars($flash['type']) ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Skrip Tema -->
            <script>
                function toggleTheme() {
                    const html  = document.documentElement;
                    const light = html.getAttribute('data-theme') === 'light';
                    if (light) {
                        html.removeAttribute('data-theme');
                        localStorage.setItem('ecoenzy_theme', 'dark');
                        document.getElementById('themeIcon').textContent  = '☀️';
                        document.getElementById('themeLabel').textContent = 'Light';
                    } else {
                        html.setAttribute('data-theme', 'light');
                        localStorage.setItem('ecoenzy_theme', 'light');
                        document.getElementById('themeIcon').textContent  = '🌙';
                        document.getElementById('themeLabel').textContent = 'Dark';
                    }
                }
                document.addEventListener('DOMContentLoaded', function() {
                    if (document.documentElement.getAttribute('data-theme') === 'light') {
                        document.getElementById('themeIcon').textContent  = '🌙';
                        document.getElementById('themeLabel').textContent = 'Dark';
                    }
                });
            </script>