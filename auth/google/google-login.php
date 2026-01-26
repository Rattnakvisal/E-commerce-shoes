<?php
session_start();
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$cfg = file_exists(__DIR__ . '/../../config/google.local.php')
	? require __DIR__ . '/../../config/google.local.php'
	: require __DIR__ . '/../../config/google.php';

$client = new Google_Client();
$client->setClientId($cfg['client_id']);
$client->setClientSecret($cfg['client_secret']);
$client->setRedirectUri($cfg['redirect_uri']);

$client->addScope('email');
$client->addScope('profile');
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

header('Location: ' . $client->createAuthUrl());
exit;
