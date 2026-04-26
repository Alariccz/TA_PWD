<?php
// header.php
// template header yang dipakai di semua halaman
// tinggal di-include aja, udah ada navbar dan sidebar

// ambil flash message kalau ada
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'EcoEnzyme') ?> — EcoEnzyme</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Syne:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- CSS kita sendiri -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- apply tema sebelum render supaya tidak flicker -->
    <script>
        (function() {
            const saved = localStorage.getItem('ecoenzy_theme');
            if (saved === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body>

<div id="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sb-logo">
            <div class="sb-logo-mark">
                <div class="sb-logo-leaf"></div>
            </div>
            <span class="sb-logo-text">EcoEnzyme</span>
        </div>

        <div class="sb-section">Menu</div>

        <a href="index.php"       class="sb-item <?= ($activePage ?? '') === 'overview'     ? 'active' : '' ?>">
            <div class="sb-dot"></div>
            <span>Ikhtisar</span>
        </a>
        <a href="batch.php"       class="sb-item <?= ($activePage ?? '') === 'batch'         ? 'active' : '' ?>">
            <div class="sb-dot blue"></div>
            <span>Kelola Batch</span>
        </a>
        <a href="ingredients.php" class="sb-item <?= ($activePage ?? '') === 'ingredients'   ? 'active' : '' ?>">
            <div class="sb-dot blue"></div>
            <span>Bahan &amp; Resep</span>
        </a>
        <a href="logs.php"        class="sb-item <?= ($activePage ?? '') === 'logs'           ? 'active' : '' ?>">
            <div class="sb-dot"></div>
            <span>Catatan Produksi</span>
        </a>
        <a href="trouble.php"     class="sb-item <?= ($activePage ?? '') === 'trouble'        ? 'active' : '' ?>">
            <div class="sb-dot red"></div>
            <span>Pemecahan Masalah</span>
        </a>
        <a href="benefits.php"    class="sb-item <?= ($activePage ?? '') === 'benefits'       ? 'active' : '' ?>">
            <div class="sb-dot"></div>
            <span>Manfaat Enzim</span>
        </a>
        <a href="profile.php"     class="sb-item <?= ($activePage ?? '') === 'profile'        ? 'active' : '' ?>">
            <div class="sb-dot amber"></div>
            <span>Profil Saya</span>
        </a>

        <div class="sb-spacer"></div>

        <!-- info user + tombol logout -->
        <div class="sb-user">
            <div class="sb-avatar"><?= htmlspecialchars(currentUserAvatar()) ?></div>
            <div>
                <div class="sb-user-name"><?= htmlspecialchars(currentUserName()) ?></div>
                <a href="logout.php" class="sb-logout">Keluar</a>
            </div>
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <div class="main">

        <!-- topbar -->
        <div class="topbar">
            <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dasbor') ?></span>
            <div class="topbar-right">
                <span class="topbar-salam">Halo, <?= htmlspecialchars(currentUserName()) ?>!</span>
                <button class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Ganti tema">
                    <span id="themeIcon">☀️</span>
                    <span id="themeLabel">Light</span>
                </button>
            </div>
        </div>

        <div class="content">

            <!-- tampilkan flash message kalau ada -->
            <?php if ($flash['message']): ?>
                <div class="flash-msg <?= htmlspecialchars($flash['type']) ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <script>
                function toggleTheme() {
                    const html = document.documentElement;
                    const isLight = html.getAttribute('data-theme') === 'light';

                    if (isLight) {
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

                // update tampilan tombol sesuai tema yang aktif saat ini
                document.addEventListener('DOMContentLoaded', function() {
                    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                    if (isLight) {
                        document.getElementById('themeIcon').textContent  = '🌙';
                        document.getElementById('themeLabel').textContent = 'Dark';
                    }
                });
            </script>