<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function flash(string $name, string $message = '', string $class = 'alert alert-success'): ?string
{
    if (!empty($message)) {
        $_SESSION[$name] = [
            'message' => $message,
            'class' => $class
        ];

        return null;
    }

    if (!empty($_SESSION[$name])) {
        $flash = $_SESSION[$name];

        unset($_SESSION[$name]);

        return '<div class="' . htmlspecialchars($flash['class']) . '">' .
            htmlspecialchars($flash['message']) .
            '</div>';
    }

    return null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function currentUserName(): string
{
    return $_SESSION['full_name'] ?? 'User';
}

function roleDashboardPath(?string $role = null): string
{
    $role = $role ?? currentUserRole();

    return match ($role) {
        'PUBLIC_USER' => 'public-user/dashboard',
        'MUNICIPAL_OFFICER' => 'municipal-officer/dashboard',
        'COLLECTOR' => 'collector/dashboard',
        'AUTHORIZED_RECYCLER' => 'recycler/dashboard',
        'ADMIN' => 'admin/dashboard',
        default => 'auth/login'
    };
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        flash('auth_error', 'Please login first.', 'alert alert-danger');
        header('Location: ' . url('auth/login'));
        exit;
    }
}

function requireRole(string|array $roles): void
{
    requireAuth();

    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!in_array(currentUserRole(), $allowedRoles, true)) {
        flash('auth_error', 'You do not have permission to access that page.', 'alert alert-danger');
        header('Location: ' . url(roleDashboardPath()));
        exit;
    }
}