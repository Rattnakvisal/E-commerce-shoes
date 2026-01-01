<?php
$host = 'localhost';
$root = '3306';
$db = 'pos_ecommerce';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $conn = $pdo;
} catch (PDOException $e) {
    // Don't terminate the whole script here — set $pdo to null and log the error
    $pdo = null;
    error_log('Database connection failed: ' . $e->getMessage());
}
