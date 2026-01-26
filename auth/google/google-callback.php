<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../token.php';
require_once __DIR__ . '/../helpers.php';


$cfg = file_exists(__DIR__ . '/../../config/google.local.php')
    ? require __DIR__ . '/../../config/google.local.php'
    : require __DIR__ . '/../../config/google.php';

$client = new Google_Client();
$client->setClientId($cfg['client_id']);
$client->setClientSecret($cfg['client_secret']);
$client->setRedirectUri($cfg['redirect_uri']);

if (!isset($_GET['code'])) {
    header('Location: /E-commerce-shoes/auth/login.php');
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    header('Location: /E-commerce-shoes/auth/login.php');
    exit;
}

$client->setAccessToken($token);

$oauth = new Google_Service_Oauth2($client);
$userInfo = $oauth->userinfo->get();

$email = $userInfo->email ?? ($userInfo->getEmail() ?? null);
$name  = $userInfo->name ?? ($userInfo->getName() ?? null);

if (!$email) {
    header('Location: /E-commerce-shoes/auth/login.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $googleId = $userInfo->id ?? ($userInfo->getId() ?? null);

    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, role, provider, google_id, created_at) VALUES (?, ?, 'customer', 'google', ?, NOW())"
    );
    $stmt->execute([$name, $email, $googleId]);

    $user_id = $conn->lastInsertId();
    $user = ['user_id' => $user_id, 'role' => 'customer'];
} else {
    $user_id = $user['user_id'];
}

$_SESSION['user_id'] = $user_id;
$_SESSION['email']   = $email;
$_SESSION['name']    = $name;
$_SESSION['role']    = $user['role'] ?? 'user';

header('Location: /E-commerce-shoes/view/index.php');
exit;
