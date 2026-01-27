<?php
require_once __DIR__ . '/../config/conn.php';
// Admin details
$name     = 'Admin';
$email    = 'admin@gmail.com';
$password = '123456';
$role     = 'admin';

try {
    // Check if admin already exists
    $check = $pdo->prepare("
        SELECT user_id FROM users WHERE email = :email LIMIT 1
    ");
    $check->execute([':email' => $email]);

    if ($check->fetch()) {
        echo "Admin already exists.\n";
        exit;
    }

    // Create admin
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role)
        VALUES (:name, :email, :password, :role)
    ");

    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role'     => $role
    ]);

    echo "Admin user created successfully!\n";
    echo "Email: admin@gmail.com\n";
    echo "Password: 123456\n";
} catch (PDOException $e) {
    echo "Seeder error: " . $e->getMessage();
}
