<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/core/Database.php';

$db = new Database();

$fullName = 'Collector One';
$email = 'collector@ecolot.lk';
$phone = '0772222222';
$password = 'Collector123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$councilId = 1;

$db->query("
    SELECT user_id
    FROM users
    WHERE email = :email
    LIMIT 1
");

$db->bind(':email', $email);
$existing = $db->single();

if ($existing) {
    echo "Collector already exists.\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
    exit;
}

$db->beginTransaction();

try {
    $db->query("
        INSERT INTO users
            (full_name, email, phone, password_hash, role, status)
        VALUES
            (:full_name, :email, :phone, :password_hash, 'COLLECTOR', 'ACTIVE')
    ");

    $db->bind(':full_name', $fullName);
    $db->bind(':email', $email);
    $db->bind(':phone', $phone);
    $db->bind(':password_hash', $passwordHash);
    $db->execute();

    $userId = (int) $db->lastInsertId();

    $db->query("
        INSERT INTO collector_profiles
            (user_id, council_id, employee_no, availability_status)
        VALUES
            (:user_id, :council_id, 'COL-001', 'AVAILABLE')
    ");

    $db->bind(':user_id', $userId);
    $db->bind(':council_id', $councilId);
    $db->execute();

    $db->commit();

    echo "Collector created successfully.\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
} catch (Throwable $e) {
    $db->rollBack();

    echo "Failed to create collector.\n";
    echo $e->getMessage() . "\n";
}