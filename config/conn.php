<?php
$host = 'localhost';
$port = '3306';
$db = 'ecommerce';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=$charset",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $conn = $pdo;
} catch (PDOException $e) {
    $pdo = null;
    error_log('Database connection failed: ' . $e->getMessage());
}
