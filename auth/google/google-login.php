<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

$cfg = require __DIR__ . '/../../config/google.php';

$client = new Google_Client();
$client->setClientId($cfg['client_id']);
$client->setClientSecret($cfg['client_secret']);
$client->setRedirectUri($cfg['redirect_uri']);

$client->addScope('email');
$client->addScope('profile');

// OAuth CSRF protection
$_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));
$client->setState($_SESSION['google_oauth_state']);

// If credentials are not configured, show a helpful message instead of redirecting
if (empty($cfg['client_id']) || strpos((string)$cfg['client_id'], 'YOUR_GOOGLE') !== false) {
    http_response_code(500);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Google OAuth Not Configured</title></head><body style="font-family:Arial,Helvetica,sans-serif;max-width:800px;margin:40px">
		<h2>Google OAuth Not Configured</h2>
		<p>The Google OAuth client ID/secret are not configured for this application.</p>
		<p>Fix options:</p>
		<ul>
			<li>Set environment variables: <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code>, and <code>GOOGLE_REDIRECT_URI</code>.</li>
			<li>Or edit <strong>config/google_credentials.php</strong> and fill in your credentials for local development.</li>
		</ul>
		<p>Make sure the Redirect URI in Google Cloud Console matches exactly:</p>
		<pre>' . htmlspecialchars($cfg['redirect_uri']) . "</pre>
		<p>See: <a href=\"https://console.cloud.google.com/apis/credentials\" target=\"_blank\">Google Cloud Console &gt; Credentials</a></p>
		</body></html>";
    exit;
}

header('Location: ' . $client->createAuthUrl());
exit;
