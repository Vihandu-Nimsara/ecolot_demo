<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/core/Database.php';

$db = new Database();

$fullName = 'System Admin';
$email = 'admin@ecolot.lk';
$phone = '0770000000';
$password = 'Admin123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$db->query("
    SELECT user_id 
    FROM users 
    WHERE email = :email 
    LIMIT 1
");

$db->bind(':email', $email);
$existing = $db->single();

if ($existing) {
    echo "Admin already exists.\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
    exit;
}

$db->query("
    INSERT INTO users 
        (full_name, email, phone, password_hash, role, status)
    VALUES
        (:full_name, :email, :phone, :password_hash, 'ADMIN', 'ACTIVE')
");

$db->bind(':full_name', $fullName);
$db->bind(':email', $email);
$db->bind(':phone', $phone);
$db->bind(':password_hash', $passwordHash);

$db->execute();

echo "Admin created successfully.\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";