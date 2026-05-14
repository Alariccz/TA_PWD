<?php
// auth.php - fungsi session, login, dan role

// cek apakah user sudah login
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// paksa redirect ke login kalau belum login
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// paksa redirect ke dashboard user kalau bukan admin
// dipakai di semua halaman admin_*.php
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

// ambil ID user yang login
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// ambil nama user yang login
function currentUserName(): string {
    return $_SESSION['user_name'] ?? 'Tamu';
}

// ambil huruf avatar
function currentUserAvatar(): string {
    return $_SESSION['user_avatar'] ?? 'A';
}

// ambil role user yang login ('admin' atau 'user')
function currentUserRole(): string {
    return $_SESSION['role'] ?? 'user';
}

// cek apakah user yang login adalah admin
function isAdmin(): bool {
    return currentUserRole() === 'admin';
}

// simpan pesan flash ke session
function setFlash(string $type, string $message): void {
    $_SESSION['flash_type']    = $type;
    $_SESSION['flash_message'] = $message;
}

// ambil dan hapus pesan flash dari session
function getFlash(): array {
    $flash = [
        'type'    => $_SESSION['flash_type']    ?? '',
        'message' => $_SESSION['flash_message'] ?? '',
    ];
    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    return $flash;
}