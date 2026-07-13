<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . BASE_URL . '/index.php?error=access');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'     => $_SESSION['user_id']  ?? null,
        'nom'    => $_SESSION['nom']      ?? '',
        'prenom' => $_SESSION['prenom']   ?? '',
        'email'  => $_SESSION['email']    ?? '',
        'role'   => $_SESSION['role']     ?? '',
        'avatar' => $_SESSION['avatar']   ?? null,
    ];
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function getUserFullName(): string {
    return trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? ''));
}

function getUserInitials(): string {
    $p = strtoupper(substr($_SESSION['prenom'] ?? '', 0, 1));
    $n = strtoupper(substr($_SESSION['nom']    ?? '', 0, 1));
    return $p . $n;
}
