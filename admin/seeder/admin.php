<?php 
require_once __DIR__ . '/../../config/conn.php';

$email = 'admin@gmail.com';

$check = $pdo->prepare("SELECT user_id FROM users WHERE email=?");
$check->execute([$email]);

if ($check->rowCount() == 0) {
    $pdo->prepare("
        INSERT INTO users (name,email,password,role)
        VALUES (?,?,?,?)
    ")->execute([
        "Admin",
        $email,
        password_hash("123456", PASSWORD_DEFAULT),
        "admin"
    ]);
    echo 'admin created';
} else {
    echo 'admin already exists';
}