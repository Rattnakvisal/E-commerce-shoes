<?php
$clientId = getenv('GOOGLE_CLIENT_ID') ?: null;
$clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: null;
$redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: null;

// Fallback: load local credentials file for development (do not commit real secrets).
if (!$clientId || !$clientSecret || !$redirectUri) {
    $credFile = __DIR__ . '/google_credentials.php';
    if (file_exists($credFile)) {
        $local = require $credFile;
        $clientId = $clientId ?: ($local['client_id'] ?? null);
        $clientSecret = $clientSecret ?: ($local['client_secret'] ?? null);
        $redirectUri = $redirectUri ?: ($local['redirect_uri'] ?? null);
    }
}

// Provide a clear error for developers if credentials are still not configured.
if (
    empty($clientId) || empty($clientSecret) || empty($redirectUri) ||
    $clientId === 'YOUR_GOOGLE_CLIENT_ID' || $clientSecret === 'YOUR_GOOGLE_CLIENT_SECRET'
) {
    error_log('Google OAuth credentials are not configured. Set environment variables GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI, or edit config/google_credentials.php.');
}

return [
    'client_id'     => $clientId ?? 'YOUR_GOOGLE_CLIENT_ID',
    'client_secret' => $clientSecret ?? 'YOUR_GOOGLE_CLIENT_SECRET',
    'redirect_uri'  => $redirectUri ?? 'http://localhost/E-commerce-shoes/auth/google/google-callback.php',
];
