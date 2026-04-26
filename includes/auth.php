<?php
// auth.php - fungsi-fungsi untuk cek session dan login

// cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// kalau belum login, paksa redirect ke halaman login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// ambil ID user yang sedang login
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// ambil nama user yang sedang login
function currentUserName() {
    return $_SESSION['user_name'] ?? 'Tamu';
}

// ambil huruf avatar
function currentUserAvatar() {
    return $_SESSION['user_avatar'] ?? 'A';
}

// simpan pesan flash ke session
// dipake setelah redirect supaya pesannya bisa ditampilin
function setFlash(string $type, string $message) {
    $_SESSION['flash_type']    = $type;
    $_SESSION['flash_message'] = $message;
}

// ambil pesan flash dan langsung hapus dari session
function getFlash(): array {
    $flash = [
        'type'    => $_SESSION['flash_type']    ?? '',
        'message' => $_SESSION['flash_message'] ?? '',
    ];
    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    return $flash;
}
