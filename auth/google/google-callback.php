<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../Helper/token.php';
require_once __DIR__ . '/../Helper/helpers.php';

/* ---------------------------------
   Ensure PDO ($conn)
---------------------------------- */
if (!isset($conn) || !($conn instanceof PDO)) {
    if (isset($pdo) && $pdo instanceof PDO) {
        $conn = $pdo;
    }
}
if (!isset($conn) || !($conn instanceof PDO)) {
    http_response_code(500);
    die('Database connection not available');
}

/* ---------------------------------
   Load Google config (local first)
---------------------------------- */
$cfg = file_exists(__DIR__ . '/../../config/google.local.php')
    ? require __DIR__ . '/../../config/google.local.php'
    : require __DIR__ . '/../../config/google.php';

function go_login(string $q = ''): void
{
    header('Location: /E-commerce-shoes/auth/login.php' . $q);
    exit;
}

/* ---------------------------------
   Validate query
---------------------------------- */
if (!isset($_GET['code'])) {
    go_login('?error=no_code');
}

/* ---------------------------------
   OAuth STATE check (CSRF)
---------------------------------- */
$sessionState = (string)($_SESSION['google_oauth_state'] ?? '');
$queryState   = (string)($_GET['state'] ?? '');

if ($sessionState === '' || $queryState === '' || !hash_equals($sessionState, $queryState)) {
    unset($_SESSION['google_oauth_state']);
    go_login('?error=oauth_state');
}
unset($_SESSION['google_oauth_state']);

/* ---------------------------------
   Google client
---------------------------------- */
$clientId     = (string)($cfg['client_id'] ?? '');
$clientSecret = (string)($cfg['client_secret'] ?? '');
$redirectUri  = (string)($cfg['redirect_uri'] ?? '');

if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
    go_login('?error=google_config');
}

$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

$token = $client->fetchAccessTokenWithAuthCode((string)$_GET['code']);
if (!empty($token['error'])) {
    error_log('Google token error: ' . json_encode($token));
    go_login('?error=google_token');
}

$client->setAccessToken($token);

/* ---------------------------------
   Get Google user info
---------------------------------- */
$oauth = new Google_Service_Oauth2($client);
$gUser = $oauth->userinfo->get();

$googleId = (string)($gUser->id ?? '');
$email    = strtolower(trim((string)($gUser->email ?? '')));
$name     = trim((string)($gUser->name ?? ''));

if ($googleId === '' || $email === '') {
    go_login('?error=google_userinfo');
}
if ($name === '') $name = 'User';

/* ---------------------------------
   1) Find by google_id
---------------------------------- */
$stmt = $conn->prepare(
    "SELECT user_id, name, email, role
     FROM users
     WHERE google_id = ?
     LIMIT 1"
);
$stmt->execute([$googleId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ---------------------------------
   2) If not found: link by email OR create
---------------------------------- */
if (!$user) {
    // try existing email
    $stmt = $conn->prepare(
        "SELECT user_id, name, email, role
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // link google to existing account
        $stmt = $conn->prepare(
            "UPDATE users
             SET google_id = ?, provider = 'google'
             WHERE user_id = ?"
        );
        $stmt->execute([$googleId, $user['user_id']]);

        // update name if empty
        if (empty($user['name'])) {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
            $stmt->execute([$name, $user['user_id']]);
            $user['name'] = $name;
        }
    } else {
        // create new user (DEFAULT ROLE = customer)
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, role, provider, google_id, created_at)
             VALUES (?, ?, 'customer', 'google', ?, NOW())"
        );
        $stmt->execute([$name, $email, $googleId]);

        $user = [
            'user_id' => (int)$conn->lastInsertId(),
            'name'    => $name,
            'email'   => $email,
            'role'    => 'customer',
        ];
    }
}

/* ---------------------------------
   Login + Redirect by role
---------------------------------- */
login_set_session_and_cookie($conn, $user);
regenerate_csrf_token();
redirect_by_role((string)($user['role'] ?? 'customer'));
