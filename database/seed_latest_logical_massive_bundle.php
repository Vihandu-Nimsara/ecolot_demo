<?php
/**
 * EcoLot LK latest logical massive seed bundle.
 *
 * Creates one admin, one municipal officer, many collectors, recycler companies,
 * public users, campaigns, postal-code schedules, requests, routes, pickups,
 * E-Lots, bids, handover/processing states, feedback and audit logs.
 *
 * Safe style: add-only. It does not truncate or reset the database.
 * It stops if this exact bundle marker already exists unless run with --force.
 */

require_once dirname(__DIR__) . '/config/config.php';

$config = require ROOT_PATH . '/config/database.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$force = in_array('--force', $argv, true);
$marker = 'LATEST_LOGICAL_MASSIVE_BUNDLE_V1';
$passwordPlain = 'EcoLot@123';
$passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

function out(string $message): void
{
    echo $message . PHP_EOL;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");
    $stmt->execute([':table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column");
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function insertRow(PDO $pdo, string $table, array $data): int
{
    $columns = array_keys($data);
    $placeholders = array_map(fn($column) => ':' . $column, $columns);
    $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    foreach ($data as $column => $value) {
        $stmt->bindValue(':' . $column, $value);
    }
    $stmt->execute();
    return (int) $pdo->lastInsertId();
}

function updateRows(PDO $pdo, string $sql, array $params = []): void
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function fetchOne(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function fetchAllRows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getEnumValues(PDO $pdo, string $table, string $column): array
{
    $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column");
    $stmt->execute([':table' => $table, ':column' => $column]);
    $type = (string) $stmt->fetchColumn();
    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
    return $matches[1] ?? [];
}

function ensureUser(PDO $pdo, string $name, string $email, string $phone, string $role, string $status, string $passwordHash): int
{
    $existing = fetchOne($pdo, "SELECT user_id FROM users WHERE email = :email LIMIT 1", [':email' => $email]);
    if ($existing) {
        updateRows($pdo, "UPDATE users SET full_name = :name, phone = :phone, role = :role, status = :status WHERE user_id = :id", [
            ':name' => $name,
            ':phone' => $phone,
            ':role' => $role,
            ':status' => $status,
            ':id' => $existing->user_id,
        ]);
        return (int) $existing->user_id;
    }

    return insertRow($pdo, 'users', [
        'full_name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password_hash' => $passwordHash,
        'role' => $role,
        'status' => $status,
    ]);
}

function ensureCouncil(PDO $pdo): int
{
    $existing = fetchOne($pdo, "SELECT council_id FROM local_councils WHERE council_name = 'EcoLot Final Municipal Council' LIMIT 1");
    if ($existing) {
        return (int) $existing->council_id;
    }

    return insertRow($pdo, 'local_councils', [
        'council_name' => 'EcoLot Final Municipal Council',
        'district' => 'Colombo',
        'province' => 'Western',
        'status' => 'ACTIVE',
    ]);
}

function ensureArea(PDO $pdo, int $councilId, string $postalCode, string $areaName): int
{
    $existing = fetchOne($pdo, "SELECT area_id FROM collection_areas WHERE postal_code = :postal_code LIMIT 1", [':postal_code' => $postalCode]);
    if ($existing) {
        updateRows($pdo, "UPDATE collection_areas SET council_id = :council_id, area_name = :area_name, status = 'ACTIVE' WHERE area_id = :area_id", [
            ':council_id' => $councilId,
            ':area_name' => $areaName,
            ':area_id' => $existing->area_id,
        ]);
        return (int) $existing->area_id;
    }

    return insertRow($pdo, 'collection_areas', [
        'council_id' => $councilId,
        'area_name' => $areaName,
        'postal_code' => $postalCode,
        'status' => 'ACTIVE',
    ]);
}

function ensureOfficerProfile(PDO $pdo, int $userId, int $councilId): int
{
    $existing = fetchOne($pdo, "SELECT officer_profile_id FROM municipal_officer_profiles WHERE user_id = :user_id LIMIT 1", [':user_id' => $userId]);
    if ($existing) {
        updateRows($pdo, "UPDATE municipal_officer_profiles SET council_id = :council_id, employee_no = 'FMO-001', designation = 'Municipal E-Waste Operations Officer' WHERE user_id = :user_id", [
            ':council_id' => $councilId,
            ':user_id' => $userId,
        ]);
        return (int) $existing->officer_profile_id;
    }

    return insertRow($pdo, 'municipal_officer_profiles', [
        'user_id' => $userId,
        'council_id' => $councilId,
        'employee_no' => 'FMO-001',
        'designation' => 'Municipal E-Waste Operations Officer',
    ]);
}

function ensureCollectorProfile(PDO $pdo, int $userId, int $councilId, int $index): int
{
    $existing = fetchOne($pdo, "SELECT collector_profile_id FROM collector_profiles WHERE user_id = :user_id LIMIT 1", [':user_id' => $userId]);
    $availability = $index <= 4 ? 'ASSIGNED' : 'AVAILABLE';
    if ($existing) {
        updateRows($pdo, "UPDATE collector_profiles SET council_id = :council_id, employee_no = :employee_no, availability_status = :availability WHERE user_id = :user_id", [
            ':council_id' => $councilId,
            ':employee_no' => 'FCL-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            ':availability' => $availability,
            ':user_id' => $userId,
        ]);
        return (int) $existing->collector_profile_id;
    }

    return insertRow($pdo, 'collector_profiles', [
        'user_id' => $userId,
        'council_id' => $councilId,
        'employee_no' => 'FCL-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
        'availability_status' => $availability,
    ]);
}

function ensurePublicProfile(PDO $pdo, int $userId, object $area, int $index): int
{
    $existing = fetchOne($pdo, "SELECT public_profile_id FROM public_user_profiles WHERE user_id = :user_id LIMIT 1", [':user_id' => $userId]);
    $data = [
        ':address1' => 'No. ' . (10 + $index) . ', ' . $area->area_name,
        ':address2' => 'EcoLot Final Test Lane',
        ':city' => 'Colombo',
        ':postal_code' => $area->postal_code,
        ':area_id' => $area->area_id,
        ':user_id' => $userId,
    ];
    if ($existing) {
        updateRows($pdo, "UPDATE public_user_profiles SET address_line1 = :address1, address_line2 = :address2, city = :city, postal_code = :postal_code, area_id = :area_id WHERE user_id = :user_id", $data);
        return (int) $existing->public_profile_id;
    }

    return insertRow($pdo, 'public_user_profiles', [
        'user_id' => $userId,
        'address_line1' => $data[':address1'],
        'address_line2' => $data[':address2'],
        'city' => $data[':city'],
        'postal_code' => $data[':postal_code'],
        'area_id' => $data[':area_id'],
    ]);
}

function ensureRecyclerProfile(PDO $pdo, int $userId, int $adminId, int $index, string $status): int
{
    $existing = fetchOne($pdo, "SELECT recycler_profile_id FROM recycler_profiles WHERE user_id = :user_id LIMIT 1", [':user_id' => $userId]);
    $company = 'Final Green Recycler Company ' . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . ' Pvt Ltd';
    if ($existing) {
        updateRows($pdo, "UPDATE recycler_profiles SET company_name = :company_name, license_no = :license_no, license_expiry_date = :expiry, address = :address, verification_status = :status, verified_by = :verified_by, verified_at = :verified_at WHERE user_id = :user_id", [
            ':company_name' => $company,
            ':license_no' => 'CEA-FREC-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            ':expiry' => '2028-12-31',
            ':address' => 'Industrial Zone, Colombo, Sri Lanka',
            ':status' => $status,
            ':verified_by' => $status === 'VERIFIED' ? $adminId : null,
            ':verified_at' => $status === 'VERIFIED' ? date('Y-m-d H:i:s') : null,
            ':user_id' => $userId,
        ]);
        return (int) $existing->recycler_profile_id;
    }

    return insertRow($pdo, 'recycler_profiles', [
        'user_id' => $userId,
        'company_name' => $company,
        'license_no' => 'CEA-FREC-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
        'license_expiry_date' => '2028-12-31',
        'address' => 'Industrial Zone, Colombo, Sri Lanka',
        'verification_status' => $status,
        'verified_by' => $status === 'VERIFIED' ? $adminId : null,
        'verified_at' => $status === 'VERIFIED' ? date('Y-m-d H:i:s') : null,
    ]);
}

function ensureCategory(PDO $pdo, string $name, string $description): int
{
    $existing = fetchOne($pdo, "SELECT category_id FROM ewaste_categories WHERE category_name = :name LIMIT 1", [':name' => $name]);
    if ($existing) {
        updateRows($pdo, "UPDATE ewaste_categories SET description = :description, status = 'ACTIVE' WHERE category_id = :id", [
            ':description' => $description,
            ':id' => $existing->category_id,
        ]);
        return (int) $existing->category_id;
    }
    return insertRow($pdo, 'ewaste_categories', [
        'category_name' => $name,
        'description' => $description,
        'status' => 'ACTIVE',
    ]);
}

function ensureItem(PDO $pdo, int $categoryId, string $itemName, string $collectionStatus, string $riskLevel, string $notes = ''): int
{
    $existing = fetchOne($pdo, "SELECT item_id FROM ewaste_items WHERE item_name = :item_name LIMIT 1", [':item_name' => $itemName]);
    if ($existing) {
        updateRows($pdo, "UPDATE ewaste_items SET category_id = :category_id, collection_status = :collection_status, default_risk_level = :risk_level, notes = :notes WHERE item_id = :item_id", [
            ':category_id' => $categoryId,
            ':collection_status' => $collectionStatus,
            ':risk_level' => $riskLevel,
            ':notes' => $notes,
            ':item_id' => $existing->item_id,
        ]);
        return (int) $existing->item_id;
    }
    return insertRow($pdo, 'ewaste_items', [
        'category_id' => $categoryId,
        'item_name' => $itemName,
        'collection_status' => $collectionStatus,
        'default_risk_level' => $riskLevel,
        'notes' => $notes,
    ]);
}

function ensureRiskRule(PDO $pdo, int $itemId, string $itemName, string $collectionStatus, string $riskLevel): void
{
    $existing = fetchOne($pdo, "SELECT rule_id FROM risk_rules WHERE item_id = :item_id LIMIT 1", [':item_id' => $itemId]);
    $action = match ($collectionStatus) {
        'NOT_COLLECTED' => 'REJECT_COLLECTION',
        'REVIEW_REQUIRED' => 'FLAG_FOR_REVIEW',
        default => 'ALLOW_COLLECTION',
    };
    if ($existing) {
        updateRows($pdo, "UPDATE risk_rules SET rule_name = :rule_name, condition_text = :condition_text, risk_level = :risk_level, action_required = :action, status = 'ACTIVE' WHERE rule_id = :rule_id", [
            ':rule_name' => $itemName . ' handling rule',
            ':condition_text' => $itemName . ' should follow EcoLot LK collection and risk review rules.',
            ':risk_level' => $riskLevel,
            ':action' => $action,
            ':rule_id' => $existing->rule_id,
        ]);
        return;
    }
    insertRow($pdo, 'risk_rules', [
        'item_id' => $itemId,
        'rule_name' => $itemName . ' handling rule',
        'condition_text' => $itemName . ' should follow EcoLot LK collection and risk review rules.',
        'risk_level' => $riskLevel,
        'action_required' => $action,
        'status' => 'ACTIVE',
    ]);
}

function ensureVehicle(PDO $pdo, int $councilId, int $index): int
{
    $vehicleNo = 'WP-FEC-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);
    $existing = fetchOne($pdo, "SELECT vehicle_id FROM vehicles WHERE vehicle_no = :vehicle_no LIMIT 1", [':vehicle_no' => $vehicleNo]);
    $types = ['Small Truck', 'Box Truck', 'Collection Van', 'Medium Truck'];
    $status = $index <= 4 ? 'ASSIGNED' : 'AVAILABLE';
    if ($existing) {
        updateRows($pdo, "UPDATE vehicles SET council_id = :council_id, vehicle_type = :vehicle_type, capacity_kg = :capacity, status = :status WHERE vehicle_id = :vehicle_id", [
            ':council_id' => $councilId,
            ':vehicle_type' => $types[$index % count($types)],
            ':capacity' => 600 + ($index * 120),
            ':status' => $status,
            ':vehicle_id' => $existing->vehicle_id,
        ]);
        return (int) $existing->vehicle_id;
    }
    return insertRow($pdo, 'vehicles', [
        'council_id' => $councilId,
        'vehicle_no' => $vehicleNo,
        'vehicle_type' => $types[$index % count($types)],
        'capacity_kg' => 600 + ($index * 120),
        'status' => $status,
    ]);
}

function addAudit(PDO $pdo, ?int $userId, string $action, string $description): void
{
    insertRow($pdo, 'audit_logs', [
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'ip_address' => '127.0.0.1',
    ]);
}

function addRequestHistory(PDO $pdo, int $requestId, ?int $userId, ?string $oldStatus, string $newStatus, string $note): void
{
    insertRow($pdo, 'request_status_history', [
        'request_id' => $requestId,
        'changed_by' => $userId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'note' => $note,
    ]);
}

function addElotHistory(PDO $pdo, int $elotId, ?int $userId, ?string $oldStatus, string $newStatus, string $note): void
{
    insertRow($pdo, 'elot_status_history', [
        'elot_id' => $elotId,
        'changed_by' => $userId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'note' => $note,
    ]);
}

if (fetchOne($pdo, "SELECT audit_id FROM audit_logs WHERE action = :marker LIMIT 1", [':marker' => $marker]) && !$force) {
    out('Safety stop: this latest logical massive bundle marker already exists.');
    out('Run php database/check_latest_logical_massive_bundle.php to inspect data.');
    out('Run with --force only if you intentionally want to add another batch.');
    exit(0);
}

out('EcoLot LK latest logical massive bundle seeding started.');
out('Configured DB: ' . $config['dbname']);
out('Password for generated accounts: ' . $passwordPlain);

$hasScheduleCampaign = hasColumn($pdo, 'area_collection_dates', 'campaign_id');
$hasRouteDateId = hasColumn($pdo, 'collection_routes', 'date_id');
out('Optional schema support: area_collection_dates.campaign_id = ' . ($hasScheduleCampaign ? 'yes' : 'no'));
out('Optional schema support: collection_routes.date_id = ' . ($hasRouteDateId ? 'yes' : 'no'));

$pdo->beginTransaction();
try {
    $councilId = ensureCouncil($pdo);

    $adminId = ensureUser($pdo, 'Final System Admin', 'admin.final@ecolot.lk', '0770000001', 'ADMIN', 'ACTIVE', $passwordHash);
    $officerId = ensureUser($pdo, 'Final Municipal Officer', 'officer.final@ecolot.lk', '0770000002', 'MUNICIPAL_OFFICER', 'ACTIVE', $passwordHash);
    ensureOfficerProfile($pdo, $officerId, $councilId);

    $areaDefinitions = [
        ['10400', 'Bambalapitiya Final Zone'],
        ['10500', 'Kollupitiya Final Zone'],
        ['10600', 'Narahenpita Final Zone'],
        ['10700', 'Borella Final Zone'],
        ['10800', 'Rajagiriya Final Zone'],
        ['10900', 'Nugegoda Final Zone'],
        ['11000', 'Dehiwala Final Zone'],
        ['11100', 'Wellawatte Final Zone'],
    ];
    $areas = [];
    foreach ($areaDefinitions as [$postalCode, $areaName]) {
        $areaId = ensureArea($pdo, $councilId, $postalCode, $areaName);
        $areas[] = fetchOne($pdo, "SELECT * FROM collection_areas WHERE area_id = :area_id", [':area_id' => $areaId]);
    }

    $collectorUserIds = [];
    for ($i = 1; $i <= 14; $i++) {
        $collectorUserId = ensureUser(
            $pdo,
            'Final Collector ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'collector' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '.final@ecolot.lk',
            '07710' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'COLLECTOR',
            'ACTIVE',
            $passwordHash
        );
        ensureCollectorProfile($pdo, $collectorUserId, $councilId, $i);
        $collectorUserIds[] = $collectorUserId;
    }

    $vehicleIds = [];
    for ($i = 1; $i <= 12; $i++) {
        $vehicleIds[] = ensureVehicle($pdo, $councilId, $i);
    }

    $categoryDefinitions = [
        'Domestic E-Waste' => 'Household electronic waste items',
        'Office E-Waste' => 'Office and business electronic waste items',
        'Automobile E-Waste' => 'Vehicle-related electronic waste items',
        'Industrial E-Waste' => 'Industrial electronic waste items',
        'Medical E-Waste' => 'Medical electronic waste requiring review',
        'Do Not Collect' => 'Excluded hazardous or unsupported items',
    ];
    $categoryIds = [];
    foreach ($categoryDefinitions as $name => $description) {
        $categoryIds[$name] = ensureCategory($pdo, $name, $description);
    }

    $itemDefinitions = [
        ['Domestic E-Waste', 'Final Mobile Phone', 'ACCEPTED', 'LOW'],
        ['Domestic E-Waste', 'Final Laptop Computer', 'ACCEPTED', 'LOW'],
        ['Domestic E-Waste', 'Final Desktop Computer', 'ACCEPTED', 'LOW'],
        ['Domestic E-Waste', 'Final LCD Monitor', 'ACCEPTED', 'LOW'],
        ['Domestic E-Waste', 'Final LED Television', 'ACCEPTED', 'MEDIUM'],
        ['Domestic E-Waste', 'Final Microwave Oven', 'ACCEPTED', 'MEDIUM'],
        ['Domestic E-Waste', 'Final Router', 'ACCEPTED', 'LOW'],
        ['Office E-Waste', 'Final Printer', 'ACCEPTED', 'LOW'],
        ['Office E-Waste', 'Final Scanner', 'ACCEPTED', 'LOW'],
        ['Office E-Waste', 'Final UPS Unit', 'REVIEW_REQUIRED', 'MEDIUM'],
        ['Office E-Waste', 'Final Network Switch', 'ACCEPTED', 'LOW'],
        ['Office E-Waste', 'Final POS Machine', 'ACCEPTED', 'LOW'],
        ['Office E-Waste', 'Final Photocopier', 'ACCEPTED', 'MEDIUM'],
        ['Automobile E-Waste', 'Final Dashboard Display', 'ACCEPTED', 'LOW'],
        ['Automobile E-Waste', 'Final Car Sensor Module', 'ACCEPTED', 'LOW'],
        ['Automobile E-Waste', 'Final Automobile Battery', 'REVIEW_REQUIRED', 'HIGH'],
        ['Automobile E-Waste', 'Final ECU Board', 'ACCEPTED', 'MEDIUM'],
        ['Industrial E-Waste', 'Final PLC Unit', 'ACCEPTED', 'MEDIUM'],
        ['Industrial E-Waste', 'Final VFD Inverter', 'ACCEPTED', 'MEDIUM'],
        ['Industrial E-Waste', 'Final Industrial Control Board', 'ACCEPTED', 'MEDIUM'],
        ['Industrial E-Waste', 'Final Power Supply Unit', 'ACCEPTED', 'MEDIUM'],
        ['Industrial E-Waste', 'Final Air Conditioner Controller', 'REVIEW_REQUIRED', 'HIGH'],
        ['Medical E-Waste', 'Final ECG Machine', 'REVIEW_REQUIRED', 'HIGH'],
        ['Medical E-Waste', 'Final Medical Monitor', 'REVIEW_REQUIRED', 'HIGH'],
        ['Medical E-Waste', 'Final Infusion Pump', 'REVIEW_REQUIRED', 'MEDIUM'],
        ['Medical E-Waste', 'Final Oximeter', 'REVIEW_REQUIRED', 'MEDIUM'],
        ['Do Not Collect', 'Final Leaking Lithium Battery', 'NOT_COLLECTED', 'HIGH'],
        ['Do Not Collect', 'Final CRT Monitor', 'NOT_COLLECTED', 'HIGH'],
        ['Do Not Collect', 'Final Mercury Lamp', 'NOT_COLLECTED', 'HIGH'],
        ['Do Not Collect', 'Final Biohazard Device', 'NOT_COLLECTED', 'HIGH'],
    ];
    $items = [];
    foreach ($itemDefinitions as [$categoryName, $itemName, $collectionStatus, $riskLevel]) {
        $itemId = ensureItem($pdo, $categoryIds[$categoryName], $itemName, $collectionStatus, $riskLevel, 'Created/updated by final logical bundle.');
        ensureRiskRule($pdo, $itemId, $itemName, $collectionStatus, $riskLevel);
        $items[] = fetchOne($pdo, "SELECT i.*, c.category_name FROM ewaste_items i INNER JOIN ewaste_categories c ON i.category_id = c.category_id WHERE i.item_id = :item_id", [':item_id' => $itemId]);
    }
    $collectableItems = array_values(array_filter($items, fn($item) => $item->collection_status !== 'NOT_COLLECTED'));
    $highRiskItems = array_values(array_filter($items, fn($item) => $item->default_risk_level === 'HIGH'));

    $recyclerProfileIds = [];
    for ($i = 1; $i <= 9; $i++) {
        $verification = $i <= 7 ? 'VERIFIED' : ($i === 8 ? 'PENDING' : 'REJECTED');
        $userStatus = $verification === 'REJECTED' ? 'REJECTED' : 'ACTIVE';
        $recyclerUserId = ensureUser(
            $pdo,
            'Final Recycler Company Contact ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'recycler' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '.final@ecolot.lk',
            '07720' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'AUTHORIZED_RECYCLER',
            $userStatus,
            $passwordHash
        );
        $profileId = ensureRecyclerProfile($pdo, $recyclerUserId, $adminId, $i, $verification);
        if ($verification === 'VERIFIED') {
            $recyclerProfileIds[] = $profileId;
            foreach ($categoryIds as $categoryName => $categoryId) {
                if ($categoryName === 'Do Not Collect') {
                    continue;
                }
                $existingCap = fetchOne($pdo, "SELECT capability_id FROM recycler_capabilities WHERE recycler_profile_id = :profile_id AND category_id = :category_id", [
                    ':profile_id' => $profileId,
                    ':category_id' => $categoryId,
                ]);
                $canHighRisk = $i <= 4 ? 1 : 0;
                if ($existingCap) {
                    updateRows($pdo, "UPDATE recycler_capabilities SET can_handle_high_risk = :can_high_risk, status = 'ACTIVE' WHERE capability_id = :id", [
                        ':can_high_risk' => $canHighRisk,
                        ':id' => $existingCap->capability_id,
                    ]);
                } else {
                    insertRow($pdo, 'recycler_capabilities', [
                        'recycler_profile_id' => $profileId,
                        'category_id' => $categoryId,
                        'can_handle_high_risk' => $canHighRisk,
                        'status' => 'ACTIVE',
                    ]);
                }
            }
        }
    }

    $campaignIds = [];
    $campaignMonths = [
        ['month' => 8, 'year' => 2026, 'status' => 'OPEN', 'cutoff' => '2026-08-25'],
        ['month' => 9, 'year' => 2026, 'status' => 'OPEN', 'cutoff' => '2026-09-25'],
        ['month' => 10, 'year' => 2026, 'status' => 'OPEN', 'cutoff' => '2026-10-25'],
        ['month' => 11, 'year' => 2026, 'status' => 'DRAFT', 'cutoff' => '2026-11-25'],
        ['month' => 6, 'year' => 2026, 'status' => 'COMPLETED', 'cutoff' => '2026-06-25'],
        ['month' => 7, 'year' => 2026, 'status' => 'CLOSED', 'cutoff' => '2026-07-25'],
    ];
    foreach ($campaignMonths as $campaign) {
        $existing = fetchOne($pdo, "SELECT campaign_id FROM collection_campaigns WHERE council_id = :council_id AND campaign_month = :month AND campaign_year = :year LIMIT 1", [
            ':council_id' => $councilId,
            ':month' => $campaign['month'],
            ':year' => $campaign['year'],
        ]);
        $name = 'Final Monthly E-Waste Campaign ' . $campaign['year'] . '-' . str_pad((string) $campaign['month'], 2, '0', STR_PAD_LEFT);
        if ($existing) {
            updateRows($pdo, "UPDATE collection_campaigns SET campaign_name = :name, request_cutoff_date = :cutoff, status = :status, created_by = :created_by WHERE campaign_id = :id", [
                ':name' => $name,
                ':cutoff' => $campaign['cutoff'],
                ':status' => $campaign['status'],
                ':created_by' => $officerId,
                ':id' => $existing->campaign_id,
            ]);
            $campaignIds[] = (int) $existing->campaign_id;
        } else {
            $campaignIds[] = insertRow($pdo, 'collection_campaigns', [
                'council_id' => $councilId,
                'campaign_name' => $name,
                'campaign_month' => $campaign['month'],
                'campaign_year' => $campaign['year'],
                'request_cutoff_date' => $campaign['cutoff'],
                'status' => $campaign['status'],
                'created_by' => $officerId,
            ]);
        }
    }

    $scheduleIds = [];
    $datesByMonth = [
        0 => ['2026-08-06', '2026-08-13', '2026-08-20', '2026-08-27'],
        1 => ['2026-09-04', '2026-09-11', '2026-09-18', '2026-09-25'],
        2 => ['2026-10-03', '2026-10-10', '2026-10-17', '2026-10-24'],
        3 => ['2026-11-05', '2026-11-12'],
        4 => ['2026-06-05', '2026-06-12', '2026-06-19'],
        5 => ['2026-07-04', '2026-07-11', '2026-07-18'],
    ];
    foreach ($campaignIds as $campaignIndex => $campaignId) {
        $campaign = fetchOne($pdo, "SELECT * FROM collection_campaigns WHERE campaign_id = :id", [':id' => $campaignId]);
        foreach ($areas as $areaIndex => $area) {
            $dateList = $datesByMonth[$campaignIndex] ?? ['2026-12-05'];
            $date = $dateList[$areaIndex % count($dateList)];
            $existing = fetchOne($pdo, "SELECT date_id FROM area_collection_dates WHERE area_id = :area_id AND collection_date = :date LIMIT 1", [
                ':area_id' => $area->area_id,
                ':date' => $date,
            ]);
            $status = $campaign->status === 'DRAFT' ? 'CLOSED' : 'OPEN';
            $maxRequests = 35 + (($areaIndex % 4) * 10);
            if ($existing) {
                $sql = "UPDATE area_collection_dates SET max_requests = :max_requests, status = :status" . ($hasScheduleCampaign ? ", campaign_id = :campaign_id" : "") . " WHERE date_id = :date_id";
                $params = [':max_requests' => $maxRequests, ':status' => $status, ':date_id' => $existing->date_id];
                if ($hasScheduleCampaign) {
                    $params[':campaign_id'] = $campaignId;
                }
                updateRows($pdo, $sql, $params);
                $scheduleIds[] = (int) $existing->date_id;
            } else {
                $row = [
                    'area_id' => $area->area_id,
                    'collection_date' => $date,
                    'max_requests' => $maxRequests,
                    'status' => $status,
                ];
                if ($hasScheduleCampaign) {
                    $row['campaign_id'] = $campaignId;
                }
                $scheduleIds[] = insertRow($pdo, 'area_collection_dates', $row);
            }
        }
    }

    $publicUserIds = [];
    for ($i = 1; $i <= 120; $i++) {
        $area = $areas[($i - 1) % count($areas)];
        $userId = ensureUser(
            $pdo,
            'Final Public User ' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'public' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '.final@ecolot.lk',
            '07730' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'PUBLIC_USER',
            'ACTIVE',
            $passwordHash
        );
        ensurePublicProfile($pdo, $userId, $area, $i);
        $publicUserIds[] = $userId;
    }

    $schedules = fetchAllRows($pdo, "SELECT acd.*, ca.council_id, ca.area_name, ca.postal_code FROM area_collection_dates acd INNER JOIN collection_areas ca ON acd.area_id = ca.area_id WHERE ca.council_id = :council_id ORDER BY acd.collection_date ASC, ca.postal_code ASC", [':council_id' => $councilId]);
    $schedulesByArea = [];
    foreach ($schedules as $schedule) {
        $schedulesByArea[(int) $schedule->area_id][] = $schedule;
    }

    $requestStatusPlan = [];
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 32, 'SUBMITTED'));
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 45, 'APPROVED'));
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 16, 'REJECTED'));
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 50, 'ASSIGNED'));
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 32, 'COLLECTED'));
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 20, 'PARTIALLY_COLLECTED'));
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 20, 'PICKUP_PENDING'));
    $requestStatusPlan = array_merge($requestStatusPlan, array_fill(0, 65, 'COMPLETED'));

    $requestIds = [];
    foreach ($requestStatusPlan as $index => $status) {
        $publicUserId = $publicUserIds[$index % count($publicUserIds)];
        $profile = fetchOne($pdo, "SELECT * FROM public_user_profiles WHERE user_id = :user_id", [':user_id' => $publicUserId]);
        $areaSchedules = $schedulesByArea[(int) $profile->area_id] ?? $schedules;
        $schedule = $areaSchedules[$index % count($areaSchedules)];
        $pickupAddress = $profile->address_line1 . ', ' . $profile->city . ' ' . $profile->postal_code;
        $requestId = insertRow($pdo, 'ewaste_requests', [
            'public_user_id' => $publicUserId,
            'area_id' => $profile->area_id,
            'preferred_date_id' => $schedule->date_id,
            'pickup_address' => $pickupAddress,
            'contact_phone' => '07730' . str_pad((string) (($index % 120) + 1), 5, '0', STR_PAD_LEFT),
            'special_note' => 'Final logical dataset request for ' . $schedule->postal_code . ' on ' . $schedule->collection_date,
            'status' => $status,
        ]);
        $requestIds[$status][] = $requestId;
        addRequestHistory($pdo, $requestId, $publicUserId, null, 'SUBMITTED', 'Request submitted by public user.');
        if ($status !== 'SUBMITTED') {
            $next = in_array($status, ['REJECTED'], true) ? 'REJECTED' : 'APPROVED';
            addRequestHistory($pdo, $requestId, $officerId, 'SUBMITTED', $next, 'Officer reviewed request.');
            if ($next !== $status && $status !== 'APPROVED') {
                addRequestHistory($pdo, $requestId, $officerId, $next, $status, 'Workflow progressed by final logical dataset.');
            }
        }

        $itemCount = 1 + ($index % 3);
        for ($j = 0; $j < $itemCount; $j++) {
            $item = $collectableItems[($index + $j) % count($collectableItems)];
            $condition = match (($index + $j) % 7) {
                0 => 'DAMAGED',
                1 => 'BROKEN',
                2 => 'LEAKING',
                default => 'WORKING',
            };
            $riskFlag = ($item->collection_status === 'REVIEW_REQUIRED' || in_array($condition, ['DAMAGED', 'BROKEN', 'LEAKING'], true)) ? 'AUTO_FLAGGED' : 'NONE';
            $requestItemId = insertRow($pdo, 'request_items', [
                'request_id' => $requestId,
                'item_id' => $item->item_id,
                'quantity' => 1 + (($index + $j) % 4),
                'estimated_weight_kg' => round(1.5 + (($index + $j) % 12) * 2.35, 2),
                'condition_status' => $condition,
                'risk_flag' => $riskFlag,
                'note' => $riskFlag !== 'NONE' ? 'Auto flagged for officer review.' : null,
            ]);
            if ($riskFlag !== 'NONE') {
                insertRow($pdo, 'flagged_items', [
                    'request_item_id' => $requestItemId,
                    'pickup_item_id' => null,
                    'flagged_by' => $publicUserId,
                    'flag_reason' => 'Request item is ' . $condition . ' or review-required.',
                    'risk_level' => $item->default_risk_level === 'HIGH' ? 'HIGH' : 'MEDIUM',
                    'review_status' => $status === 'COMPLETED' ? 'APPROVED_FOR_COLLECTION' : 'PENDING',
                    'reviewed_by' => $status === 'COMPLETED' ? $officerId : null,
                    'reviewed_at' => $status === 'COMPLETED' ? date('Y-m-d H:i:s') : null,
                    'officer_note' => $status === 'COMPLETED' ? 'Reviewed during final logical dataset.' : null,
                ]);
            }
        }
    }

    $routeRequestStatuses = ['ASSIGNED', 'COLLECTED', 'PARTIALLY_COLLECTED', 'PICKUP_PENDING', 'COMPLETED'];
    $routeGroups = [];
    foreach ($routeRequestStatuses as $status) {
        foreach ($requestIds[$status] ?? [] as $requestId) {
            $request = fetchOne($pdo, "SELECT r.*, acd.collection_date FROM ewaste_requests r INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id WHERE r.request_id = :id", [':id' => $requestId]);
            $key = $request->preferred_date_id;
            $routeGroups[$key][] = $request;
        }
    }

    $routeIds = [];
    $routeIndex = 0;
    foreach ($routeGroups as $dateId => $requests) {
        if (empty($requests)) {
            continue;
        }
        $routeIndex++;
        $first = $requests[0];
        $date = fetchOne($pdo, "SELECT acd.*, ca.area_name, ca.postal_code FROM area_collection_dates acd INNER JOIN collection_areas ca ON acd.area_id = ca.area_id WHERE acd.date_id = :date_id", [':date_id' => $dateId]);
        $campaignId = null;
        if ($hasScheduleCampaign && isset($date->campaign_id) && $date->campaign_id) {
            $campaignId = (int) $date->campaign_id;
        } else {
            $campaign = fetchOne($pdo, "SELECT campaign_id FROM collection_campaigns WHERE council_id = :council_id AND campaign_month = MONTH(:collection_date_month) AND campaign_year = YEAR(:collection_date_year) LIMIT 1", [
                ':council_id' => $councilId,
                ':collection_date_month' => $date->collection_date,
                ':collection_date_year' => $date->collection_date,
            ]);
            $campaignId = $campaign ? (int) $campaign->campaign_id : $campaignIds[0];
        }
        $routeStatus = $routeIndex % 5 === 0 ? 'COMPLETED' : (($routeIndex % 3 === 0) ? 'IN_PROGRESS' : 'ASSIGNED');
        $routeData = [
            'campaign_id' => $campaignId,
            'area_id' => $date->area_id,
            'route_name' => 'Final Route ' . str_pad((string) $routeIndex, 3, '0', STR_PAD_LEFT) . ' - ' . $date->postal_code,
            'collection_date' => $date->collection_date,
            'collector_id' => $collectorUserIds[($routeIndex - 1) % count($collectorUserIds)],
            'vehicle_id' => $vehicleIds[($routeIndex - 1) % count($vehicleIds)],
            'status' => $routeStatus,
        ];
        if ($hasRouteDateId) {
            $routeData['date_id'] = $date->date_id;
        }
        $routeId = insertRow($pdo, 'collection_routes', $routeData);
        $routeIds[] = $routeId;
        $stopOrder = 1;
        foreach ($requests as $request) {
            $stopStatus = match ($request->status) {
                'COLLECTED', 'PARTIALLY_COLLECTED', 'COMPLETED' => 'COLLECTED',
                'PICKUP_PENDING' => 'FAILED',
                default => 'PENDING',
            };
            insertRow($pdo, 'route_stops', [
                'route_id' => $routeId,
                'request_id' => $request->request_id,
                'stop_order' => $stopOrder++,
                'stop_status' => $stopStatus,
                'collector_note' => $stopStatus === 'FAILED' ? 'Resident was not available during pickup attempt.' : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    $pickupRows = [];
    $pickupStatuses = ['COLLECTED', 'PARTIALLY_COLLECTED', 'COMPLETED', 'PICKUP_PENDING'];
    foreach ($pickupStatuses as $status) {
        foreach ($requestIds[$status] ?? [] as $requestId) {
            $route = fetchOne($pdo, "SELECT cr.* FROM collection_routes cr INNER JOIN route_stops rs ON cr.route_id = rs.route_id WHERE rs.request_id = :request_id LIMIT 1", [':request_id' => $requestId]);
            if (!$route) {
                continue;
            }
            $pickupStatus = match ($status) {
                'PARTIALLY_COLLECTED' => 'PARTIALLY_COLLECTED',
                'PICKUP_PENDING' => 'NOT_AVAILABLE',
                default => 'COLLECTED',
            };
            $verification = match ($status) {
                'COMPLETED' => 'VERIFIED',
                'PICKUP_PENDING' => 'REJECTED',
                default => (($requestId % 3 === 0) ? 'PENDING' : 'VERIFIED'),
            };
            $itemsForRequest = fetchAllRows($pdo, "SELECT ri.*, i.default_risk_level, i.collection_status FROM request_items ri INNER JOIN ewaste_items i ON ri.item_id = i.item_id WHERE ri.request_id = :request_id", [':request_id' => $requestId]);
            $totalWeight = 0.0;
            foreach ($itemsForRequest as $ri) {
                $collectedQty = $pickupStatus === 'PARTIALLY_COLLECTED' ? max(1, (int) floor($ri->quantity / 2)) : (int) $ri->quantity;
                $totalWeight += ($ri->estimated_weight_kg ? (float) $ri->estimated_weight_kg : 1.0) * ($collectedQty / max(1, (int) $ri->quantity));
            }
            $pickupId = insertRow($pdo, 'pickup_records', [
                'request_id' => $requestId,
                'route_id' => $route->route_id,
                'collector_id' => $route->collector_id,
                'pickup_status' => $pickupStatus,
                'total_collected_weight_kg' => round($totalWeight, 2),
                'collector_note' => $pickupStatus === 'NOT_AVAILABLE' ? 'Pickup could not be completed.' : 'Collected during final logical route run.',
                'verification_status' => $verification,
                'verified_by' => $verification === 'VERIFIED' ? $officerId : ($verification === 'REJECTED' ? $officerId : null),
                'verified_at' => $verification === 'PENDING' ? null : date('Y-m-d H:i:s'),
                'officer_note' => $verification === 'PENDING' ? null : 'Verified/reviewed by final logical dataset.',
            ]);
            $pickupRows[] = $pickupId;
            foreach ($itemsForRequest as $ri) {
                if ($pickupStatus === 'NOT_AVAILABLE') {
                    continue;
                }
                $collectedQty = $pickupStatus === 'PARTIALLY_COLLECTED' ? max(1, (int) floor($ri->quantity / 2)) : (int) $ri->quantity;
                $condition = match ($ri->condition_status) {
                    'WORKING' => 'GOOD',
                    'DAMAGED' => 'DAMAGED',
                    'BROKEN' => 'BROKEN',
                    'LEAKING' => 'LEAKING',
                    default => 'UNKNOWN',
                };
                $weight = round(($ri->estimated_weight_kg ? (float) $ri->estimated_weight_kg : 1.0) * ($collectedQty / max(1, (int) $ri->quantity)), 2);
                $pickupItemId = insertRow($pdo, 'pickup_items', [
                    'pickup_id' => $pickupId,
                    'item_id' => $ri->item_id,
                    'collected_quantity' => $collectedQty,
                    'collected_weight_kg' => $weight,
                    'condition_status' => $condition,
                    'note' => in_array($condition, ['DAMAGED', 'BROKEN', 'LEAKING'], true) ? 'Condition needs officer review.' : null,
                ]);
                if (in_array($condition, ['DAMAGED', 'BROKEN', 'LEAKING'], true) || $ri->default_risk_level === 'HIGH') {
                    insertRow($pdo, 'flagged_items', [
                        'request_item_id' => $ri->request_item_id,
                        'pickup_item_id' => $pickupItemId,
                        'flagged_by' => $route->collector_id,
                        'flag_reason' => 'Collected item condition/risk requires review.',
                        'risk_level' => $ri->default_risk_level === 'HIGH' || $condition === 'LEAKING' ? 'HIGH' : 'MEDIUM',
                        'review_status' => $verification === 'VERIFIED' ? 'APPROVED_FOR_COLLECTION' : 'PENDING',
                        'reviewed_by' => $verification === 'VERIFIED' ? $officerId : null,
                        'reviewed_at' => $verification === 'VERIFIED' ? date('Y-m-d H:i:s') : null,
                        'officer_note' => $verification === 'VERIFIED' ? 'Approved after pickup verification.' : null,
                    ]);
                }
            }
        }
    }

    $verifiedPickupItems = fetchAllRows($pdo, "
        SELECT pi.*, i.category_id, i.item_name, i.default_risk_level
        FROM pickup_items pi
        INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
        INNER JOIN ewaste_items i ON pi.item_id = i.item_id
        LEFT JOIN elot_items ei ON pi.pickup_item_id = ei.pickup_item_id
        WHERE pr.verification_status = 'VERIFIED'
        AND ei.elot_item_id IS NULL
        ORDER BY i.category_id ASC, pi.pickup_item_id ASC
    ");
    $byCategory = [];
    foreach ($verifiedPickupItems as $item) {
        $byCategory[(int) $item->category_id][] = $item;
    }
    $elotStatusPlan = ['DRAFT', 'OPEN_FOR_BIDDING', 'OPEN_FOR_BIDDING', 'BIDDING_CLOSED', 'BIDDING_CLOSED', 'AWARDED', 'AWARDED', 'HANDED_OVER', 'PROCESSING', 'COMPLETED', 'COMPLETED', 'DRAFT'];
    $elotIds = [];
    $elotIndex = 0;
    foreach ($byCategory as $categoryId => $categoryItems) {
        while (count($categoryItems) >= 3 && $elotIndex < count($elotStatusPlan)) {
            $chunk = array_splice($categoryItems, 0, min(5, count($categoryItems)));
            $status = $elotStatusPlan[$elotIndex];
            $elotIndex++;
            $totalWeight = array_sum(array_map(fn($pi) => (float) $pi->collected_weight_kg, $chunk));
            $code = 'FINAL-ELOT-' . str_pad((string) $elotIndex, 4, '0', STR_PAD_LEFT);
            $winnerProfileId = null;
            if (in_array($status, ['AWARDED', 'HANDED_OVER', 'PROCESSING', 'COMPLETED'], true)) {
                $winnerProfileId = $recyclerProfileIds[$elotIndex % count($recyclerProfileIds)] ?? null;
            }
            $elotId = insertRow($pdo, 'elots', [
                'council_id' => $councilId,
                'created_by' => $officerId,
                'elot_code' => $code,
                'title' => 'Final Logical E-Lot ' . str_pad((string) $elotIndex, 2, '0', STR_PAD_LEFT),
                'category_id' => $categoryId,
                'total_weight_kg' => round($totalWeight, 2),
                'description' => 'E-Lot generated from verified pickup items in final logical data bundle.',
                'status' => $status,
                'bidding_start' => $status === 'DRAFT' ? null : '2026-08-01 09:00:00',
                'bidding_end' => $status === 'OPEN_FOR_BIDDING' ? '2026-12-31 17:00:00' : '2026-08-15 17:00:00',
                'winner_recycler_profile_id' => $winnerProfileId,
            ]);
            $elotIds[$status][] = $elotId;
            addElotHistory($pdo, $elotId, $officerId, null, 'DRAFT', 'E-Lot created.');
            if ($status !== 'DRAFT') {
                addElotHistory($pdo, $elotId, $officerId, 'DRAFT', 'OPEN_FOR_BIDDING', 'Bidding opened.');
            }
            if (in_array($status, ['BIDDING_CLOSED', 'AWARDED', 'HANDED_OVER', 'PROCESSING', 'COMPLETED'], true)) {
                addElotHistory($pdo, $elotId, $officerId, 'OPEN_FOR_BIDDING', 'BIDDING_CLOSED', 'Bidding closed.');
            }
            if (in_array($status, ['AWARDED', 'HANDED_OVER', 'PROCESSING', 'COMPLETED'], true)) {
                addElotHistory($pdo, $elotId, $officerId, 'BIDDING_CLOSED', 'AWARDED', 'Winning bid selected.');
            }
            if (in_array($status, ['HANDED_OVER', 'PROCESSING', 'COMPLETED'], true)) {
                addElotHistory($pdo, $elotId, $officerId, 'AWARDED', 'HANDED_OVER', 'Handover confirmed.');
            }
            if (in_array($status, ['PROCESSING', 'COMPLETED'], true)) {
                addElotHistory($pdo, $elotId, $winnerProfileId ? fetchOne($pdo, "SELECT user_id FROM recycler_profiles WHERE recycler_profile_id = :id", [':id' => $winnerProfileId])->user_id : null, 'HANDED_OVER', 'PROCESSING', 'Recycler started processing.');
            }
            if ($status === 'COMPLETED') {
                addElotHistory($pdo, $elotId, $winnerProfileId ? fetchOne($pdo, "SELECT user_id FROM recycler_profiles WHERE recycler_profile_id = :id", [':id' => $winnerProfileId])->user_id : null, 'PROCESSING', 'COMPLETED', 'Recycler completed processing.');
            }
            foreach ($chunk as $pi) {
                insertRow($pdo, 'elot_items', [
                    'elot_id' => $elotId,
                    'pickup_item_id' => $pi->pickup_item_id,
                    'quantity' => $pi->collected_quantity,
                    'weight_kg' => $pi->collected_weight_kg,
                ]);
            }
            if ($status !== 'DRAFT') {
                $bidderCount = min(5, count($recyclerProfileIds));
                $winningIndex = $winnerProfileId ? array_search($winnerProfileId, $recyclerProfileIds, true) : null;
                for ($b = 0; $b < $bidderCount; $b++) {
                    $profileId = $recyclerProfileIds[$b];
                    $bidStatus = 'SUBMITTED';
                    if (in_array($status, ['AWARDED', 'HANDED_OVER', 'PROCESSING', 'COMPLETED'], true)) {
                        $bidStatus = $profileId === $winnerProfileId ? 'WINNING_BID' : 'REJECTED';
                    }
                    insertRow($pdo, 'bids', [
                        'elot_id' => $elotId,
                        'recycler_profile_id' => $profileId,
                        'bid_amount' => 15000 + ($elotIndex * 850) + ($b * 1750),
                        'bid_note' => 'Logical final company bid for E-Lot ' . $code,
                        'status' => $bidStatus,
                    ]);
                }
            }
        }
    }

    for ($i = 1; $i <= 35; $i++) {
        $publicUserId = $publicUserIds[($i - 1) % count($publicUserIds)];
        $relatedRequest = $i % 3 === 0 ? ($requestIds['COMPLETED'][$i % count($requestIds['COMPLETED'])] ?? null) : null;
        insertRow($pdo, 'complaints_feedback', [
            'request_id' => $relatedRequest,
            'public_user_id' => $publicUserId,
            'subject' => 'Final feedback case ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'message' => 'Feedback/complaint generated for final logical municipal officer review workflow.',
            'status' => match ($i % 4) {0 => 'RESOLVED', 1 => 'OPEN', 2 => 'IN_REVIEW', default => 'CLOSED'},
            'officer_reply' => $i % 4 === 0 ? 'Resolved by officer in final logical dataset.' : null,
        ]);
    }

    foreach (['SEED_USERS', 'SEED_REQUESTS', 'SEED_ROUTES', 'SEED_PICKUPS', 'SEED_ELOTS', $marker] as $action) {
        addAudit($pdo, $adminId, $action, 'Latest logical massive bundle generated/updated ' . $action . '.');
    }

    if (tableExists($pdo, 'report_snapshots')) {
        insertRow($pdo, 'report_snapshots', [
            'council_id' => $councilId,
            'report_type' => 'FINAL_LOGICAL_BUNDLE_SUMMARY',
            'report_month' => 8,
            'report_year' => 2026,
            'total_requests' => count($requestStatusPlan),
            'completed_collections' => count($requestIds['COMPLETED'] ?? []),
            'pending_pickups' => count($requestIds['PICKUP_PENDING'] ?? []),
            'total_elots' => array_sum(array_map('count', $elotIds)),
            'total_bids' => (int) fetchOne($pdo, "SELECT COUNT(*) AS c FROM bids WHERE bid_note LIKE 'Logical final company bid%' ")->c,
            'flagged_items_count' => (int) fetchOne($pdo, "SELECT COUNT(*) AS c FROM flagged_items WHERE flag_reason LIKE '%final logical%' OR flag_reason LIKE 'Request item%' ")->c,
            'generated_by' => $adminId,
        ]);
    }

    $pdo->commit();

    out('Seed completed successfully.');
    out('Generated account password: ' . $passwordPlain);
    out('Main logins:');
    out('  Admin:   admin.final@ecolot.lk');
    out('  Officer: officer.final@ecolot.lk');
    out('  Collector example: collector01.final@ecolot.lk');
    out('  Recycler example:  recycler01.final@ecolot.lk');
    out('  Public example:    public001.final@ecolot.lk');
    out('Run: php database/check_latest_logical_massive_bundle.php');
} catch (Throwable $e) {
    $pdo->rollBack();
    out('Seed failed. Transaction rolled back.');
    out($e->getMessage());
    exit(1);
}
