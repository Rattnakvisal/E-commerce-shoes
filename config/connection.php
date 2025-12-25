<?php
$host = 'localhost';
$root = '3306';
$db = 'dynamic_pos';
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
    // Provide a backwards-compatible variable name expected by the app
    $conn = $pdo;
} catch (PDOException $e) {
    die("Could not connect to the database.");
}
