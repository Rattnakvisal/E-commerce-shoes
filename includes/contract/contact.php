<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$success = (int)($_SESSION['flash']['success'] ?? 0);
$error   = $_SESSION['flash']['error'] ?? null;
$old     = $_SESSION['flash']['old'] ?? ['name' => '', 'email' => '', 'message' => ''];

unset($_SESSION['flash']);

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$oldName    = (string)($old['name'] ?? '');
$oldEmail   = (string)($old['email'] ?? '');
$oldMessage = (string)($old['message'] ?? '');
