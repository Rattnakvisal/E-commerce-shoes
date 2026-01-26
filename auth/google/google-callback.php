<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../token.php';
require_once __DIR__ . '/../helpers.php';

$cfg = require __DIR__ . '/../../config/google.php';

if (!isset($_GET['code'])) {
    header('Location: ../../../login.php');
    exit;
}

// OAuth state check (anti-CSRF)
if (
    empty($_SESSION['google_oauth_state']) ||
    empty($_GET['state']) ||
    !hash_equals($_SESSION['google_oauth_state'], (string)$_GET['state'])
) {
    unset($_SESSION['google_oauth_state']);
    die('Invalid OAuth state');
}
unset($_SESSION['google_oauth_state']);

$client = new Google_Client();
$client->setClientId($cfg['client_id']);
$client->setClientSecret($cfg['client_secret']);
$client->setRedirectUri($cfg['redirect_uri']);

$token = $client->fetchAccessTokenWithAuthCode((string)$_GET['code']);
$tokenErr = $token['error'] ?? null;
if ($tokenErr) {
    error_log('Google token error: ' . json_encode($token));
    http_response_code(400);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Google Auth Error</title></head><body style="font-family:Arial,Helvetica,sans-serif;margin:40px">';
    echo '<h2>Google authentication failed</h2>';
    echo '<p>Error: ' . htmlspecialchars((string)$tokenErr) . '</p>';
    if (!empty($token['error_description'])) {
        echo '<p>Description: ' . htmlspecialchars((string)$token['error_description']) . '</p>';
    }
    echo '<p>Check your Client ID/Secret and Redirect URI in the Google Cloud Console.</p>';
    echo '</body></html>';
    exit;
}
$client->setAccessToken($token);

$oauth = new Google_Service_Oauth2($client);
$gUser = $oauth->userinfo->get();

$googleId = (string)$gUser->id;
$email    = (string)$gUser->email;
$name     = trim((string)$gUser->name);
if ($name === '') $name = 'User';

// 1) find by google_id
$stmt = $conn->prepare("SELECT user_id, name, email, role FROM users WHERE google_id = ? LIMIT 1");
$stmt->execute([$googleId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 2) if not found, link by email OR create new user
if (!$user) {
    $stmt = $conn->prepare("SELECT user_id, name, email, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // link google
        $stmt = $conn->prepare("UPDATE users SET google_id = ?, provider = 'google' WHERE user_id = ?");
        $stmt->execute([$googleId, $user['user_id']]);

        // keep name updated if empty
        if (empty($user['name']) && $name !== '') {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
            $stmt->execute([$name, $user['user_id']]);
            $user['name'] = $name;
        }
    } else {
        // create new
        $stmt = $conn->prepare("INSERT INTO users (name, email, role, google_id, provider) VALUES (?, ?, 'user', ?, 'google')");
        $stmt->execute([$name, $email, $googleId]);

        $user = [
            'user_id' => $conn->lastInsertId(),
            'name' => $name,
            'email' => $email,
            'role' => 'user',
        ];
    }
}

// set session + cookie like normal login
login_set_session_and_cookie($conn, $user);

// regenerate CSRF after login
regenerate_csrf_token();

redirect_by_role((string)$user['role']);
