<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/core/Database.php';

$db = new Database();

$fullName = 'Municipal Officer';
$email = 'officer@ecolot.lk';
$phone = '0771111111';
$password = 'Officer123';
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
    echo "Municipal officer already exists.\n";
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
            (:full_name, :email, :phone, :password_hash, 'MUNICIPAL_OFFICER', 'ACTIVE')
    ");

    $db->bind(':full_name', $fullName);
    $db->bind(':email', $email);
    $db->bind(':phone', $phone);
    $db->bind(':password_hash', $passwordHash);
    $db->execute();

    $userId = (int) $db->lastInsertId();

    $db->query("
        INSERT INTO municipal_officer_profiles
            (user_id, council_id, employee_no, designation)
        VALUES
            (:user_id, :council_id, 'MO-001', 'Municipal E-Waste Officer')
    ");

    $db->bind(':user_id', $userId);
    $db->bind(':council_id', $councilId);
    $db->execute();

    $db->commit();

    echo "Municipal officer created successfully.\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
} catch (Throwable $e) {
    $db->rollBack();

    echo "Failed to create municipal officer.\n";
    echo $e->getMessage() . "\n";
}