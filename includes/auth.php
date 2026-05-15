<?php
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}


function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function currentUserName(): string {
    return $_SESSION['user_name'] ?? 'Tamu';
}
function currentUserAvatar(): string {
    return $_SESSION['user_avatar'] ?? 'A';
}

function currentUserRole(): string {
    return $_SESSION['role'] ?? 'user';
}

function isAdmin(): bool {
    return currentUserRole() === 'admin';
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash_type']    = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlash(): array {
    $flash = [
        'type'    => $_SESSION['flash_type']    ?? '',
        'message' => $_SESSION['flash_message'] ?? '',
    ];
    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    return $flash;
}