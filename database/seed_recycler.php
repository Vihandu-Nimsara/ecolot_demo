<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/core/Database.php';

$db = new Database();

$fullName = 'Demo Recycler';
$email = 'recycler@ecolot.lk';
$phone = '0773333333';
$password = 'Recycler123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$companyName = 'Eco Recyclers Pvt Ltd';
$licenseNo = 'CEA-REC-001';
$licenseExpiryDate = '2027-12-31';
$address = 'No. 10, Green Road, Colombo';

$db->query("
    SELECT user_id
    FROM users
    WHERE email = :email
    LIMIT 1
");

$db->bind(':email', $email);
$existing = $db->single();

if ($existing) {
    echo "Recycler already exists.\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
    exit;
}

$db->beginTransaction();

try {
    $db->query("
        SELECT user_id
        FROM users
        WHERE role = 'ADMIN'
        ORDER BY user_id ASC
        LIMIT 1
    ");

    $admin = $db->single();
    $adminId = $admin->user_id ?? null;

    $db->query("
        INSERT INTO users
            (full_name, email, phone, password_hash, role, status)
        VALUES
            (:full_name, :email, :phone, :password_hash, 'AUTHORIZED_RECYCLER', 'ACTIVE')
    ");

    $db->bind(':full_name', $fullName);
    $db->bind(':email', $email);
    $db->bind(':phone', $phone);
    $db->bind(':password_hash', $passwordHash);
    $db->execute();

    $userId = (int) $db->lastInsertId();

    $db->query("
        INSERT INTO recycler_profiles
            (
                user_id,
                company_name,
                license_no,
                license_expiry_date,
                address,
                verification_status,
                verified_by,
                verified_at
            )
        VALUES
            (
                :user_id,
                :company_name,
                :license_no,
                :license_expiry_date,
                :address,
                'VERIFIED',
                :verified_by,
                NOW()
            )
    ");

    $db->bind(':user_id', $userId);
    $db->bind(':company_name', $companyName);
    $db->bind(':license_no', $licenseNo);
    $db->bind(':license_expiry_date', $licenseExpiryDate);
    $db->bind(':address', $address);
    $db->bind(':verified_by', $adminId);
    $db->execute();

    $recyclerProfileId = (int) $db->lastInsertId();

    $db->query("
        SELECT category_id, category_name
        FROM ewaste_categories
        WHERE category_name != 'Do Not Collect'
        AND status = 'ACTIVE'
    ");

    $categories = $db->resultSet();

    foreach ($categories as $category) {
        $db->query("
            INSERT INTO recycler_capabilities
                (
                    recycler_profile_id,
                    category_id,
                    can_handle_high_risk,
                    status
                )
            VALUES
                (
                    :recycler_profile_id,
                    :category_id,
                    TRUE,
                    'ACTIVE'
                )
        ");

        $db->bind(':recycler_profile_id', $recyclerProfileId);
        $db->bind(':category_id', $category->category_id);
        $db->execute();
    }

    $db->commit();

    echo "Verified recycler created successfully.\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
} catch (Throwable $e) {
    $db->rollBack();

    echo "Failed to create recycler.\n";
    echo $e->getMessage() . "\n";
}