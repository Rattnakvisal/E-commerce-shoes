<?php
return [
    'client_id'     => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_NEW_CLIENT_ID',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_NEW_CLIENT_SECRET',
    'redirect_uri'  => getenv('GOOGLE_REDIRECT_URI')
        ?: 'http://localhost/E-commerce-shoes/auth/google/google-callback.php',
];
