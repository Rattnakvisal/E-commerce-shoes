<?php

declare(strict_types=1);

/* =========================
   Session
========================= */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =========================
   Generate CSRF token
========================= */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/* =========================
   Verify CSRF token
========================= */
function verify_csrf_token(?string $token): bool
{
    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !is_string($token)
    ) {
        return false;
    }

    // Optional expiry (30 minutes)
    if (!empty($_SESSION['csrf_token_time'])) {
        if (time() - (int)$_SESSION['csrf_token_time'] > 1800) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/* =========================
   Regenerate CSRF token
========================= */
function regenerate_csrf_token(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

/* =========================
   Hidden input helper
========================= */
function csrf_input_field(): string
{
    $token = htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
