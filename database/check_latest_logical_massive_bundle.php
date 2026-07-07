<?php
/**
 * Non-destructive checker for the EcoLot LK latest logical massive bundle.
 * It only SELECTs and prints counts/warnings.
 */

require_once dirname(__DIR__) . '/config/config.php';
$config = require ROOT_PATH . '/config/database.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function out(string $message = ''): void
{
    echo $message . PHP_EOL;
}

function rows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function one(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function scalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column");
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function printGroup(PDO $pdo, string $title, string $sql): void
{
    out('\n' . $title);
    out(str_repeat('-', strlen($title)));
    foreach (rows($pdo, $sql) as $row) {
        $values = array_values((array) $row);
        out(str_pad((string) $values[0], 28) . ' : ' . $values[1]);
    }
}

function warn(string $message): void
{
    echo '[WARNING] ' . $message . PHP_EOL;
}

out('EcoLot LK latest logical massive bundle check');
out('Database: ' . $GLOBALS['config']['dbname']);
out('Generated account password should be: EcoLot@123');

printGroup($pdo, 'Users by role', "SELECT role, COUNT(*) AS total FROM users GROUP BY role ORDER BY role");
printGroup($pdo, 'Users by status', "SELECT status, COUNT(*) AS total FROM users GROUP BY status ORDER BY status");
printGroup($pdo, 'Recycler verification', "SELECT verification_status, COUNT(*) AS total FROM recycler_profiles GROUP BY verification_status ORDER BY verification_status");
printGroup($pdo, 'Requests by status', "SELECT status, COUNT(*) AS total FROM ewaste_requests GROUP BY status ORDER BY status");
printGroup($pdo, 'Campaigns by status', "SELECT status, COUNT(*) AS total FROM collection_campaigns GROUP BY status ORDER BY status");
printGroup($pdo, 'Area schedules by status', "SELECT status, COUNT(*) AS total FROM area_collection_dates GROUP BY status ORDER BY status");
printGroup($pdo, 'Routes by status', "SELECT status, COUNT(*) AS total FROM collection_routes GROUP BY status ORDER BY status");
printGroup($pdo, 'Pickup verification', "SELECT verification_status, COUNT(*) AS total FROM pickup_records GROUP BY verification_status ORDER BY verification_status");
printGroup($pdo, 'E-Lots by status', "SELECT status, COUNT(*) AS total FROM elots GROUP BY status ORDER BY status");
printGroup($pdo, 'Bids by status', "SELECT status, COUNT(*) AS total FROM bids GROUP BY status ORDER BY status");
printGroup($pdo, 'Feedback by status', "SELECT status, COUNT(*) AS total FROM complaints_feedback GROUP BY status ORDER BY status");

out('\nCore counts');
out('-----------');
$counts = [
    'Public profiles' => "SELECT COUNT(*) FROM public_user_profiles",
    'Officer profiles' => "SELECT COUNT(*) FROM municipal_officer_profiles",
    'Collector profiles' => "SELECT COUNT(*) FROM collector_profiles",
    'Recycler profiles' => "SELECT COUNT(*) FROM recycler_profiles",
    'Collection areas' => "SELECT COUNT(*) FROM collection_areas",
    'Area schedules' => "SELECT COUNT(*) FROM area_collection_dates",
    'Request items' => "SELECT COUNT(*) FROM request_items",
    'Route stops' => "SELECT COUNT(*) FROM route_stops",
    'Pickup items' => "SELECT COUNT(*) FROM pickup_items",
    'Available verified pool items' => "SELECT COUNT(*) FROM pickup_items pi INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id LEFT JOIN elot_items ei ON pi.pickup_item_id = ei.pickup_item_id WHERE pr.verification_status = 'VERIFIED' AND ei.elot_item_id IS NULL",
    'E-Lot items' => "SELECT COUNT(*) FROM elot_items",
    'Flagged items' => "SELECT COUNT(*) FROM flagged_items",
    'Audit logs' => "SELECT COUNT(*) FROM audit_logs",
];
foreach ($counts as $label => $sql) {
    out(str_pad($label, 32) . ' : ' . scalar($pdo, $sql));
}

out('\nGenerated login accounts');
out('------------------------');
$emails = [
    'admin.final@ecolot.lk',
    'officer.final@ecolot.lk',
    'collector01.final@ecolot.lk',
    'recycler01.final@ecolot.lk',
    'public001.final@ecolot.lk',
];
foreach ($emails as $email) {
    $user = one($pdo, "SELECT user_id, full_name, role, status FROM users WHERE email = :email", [':email' => $email]);
    if ($user) {
        out(str_pad($email, 32) . ' => ' . $user->full_name . ' | ' . $user->role . ' | ' . $user->status);
    } else {
        warn($email . ' missing');
    }
}

out('\nRelationship warnings');
out('---------------------');
$warnings = 0;
$checks = [
    'PUBLIC_USER without public_user_profile' => "SELECT COUNT(*) FROM users u LEFT JOIN public_user_profiles p ON u.user_id = p.user_id WHERE u.role = 'PUBLIC_USER' AND p.public_profile_id IS NULL",
    'MUNICIPAL_OFFICER without profile' => "SELECT COUNT(*) FROM users u LEFT JOIN municipal_officer_profiles p ON u.user_id = p.user_id WHERE u.role = 'MUNICIPAL_OFFICER' AND p.officer_profile_id IS NULL",
    'COLLECTOR without profile' => "SELECT COUNT(*) FROM users u LEFT JOIN collector_profiles p ON u.user_id = p.user_id WHERE u.role = 'COLLECTOR' AND p.collector_profile_id IS NULL",
    'AUTHORIZED_RECYCLER without profile' => "SELECT COUNT(*) FROM users u LEFT JOIN recycler_profiles p ON u.user_id = p.user_id WHERE u.role = 'AUTHORIZED_RECYCLER' AND p.recycler_profile_id IS NULL",
    'Requests without request_items' => "SELECT COUNT(*) FROM ewaste_requests r LEFT JOIN request_items ri ON r.request_id = ri.request_id WHERE ri.request_item_id IS NULL",
    'Requests area_id not matching preferred_date area_id' => "SELECT COUNT(*) FROM ewaste_requests r INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id WHERE r.area_id <> acd.area_id",
    'Route stops where request area/date mismatches route' => "SELECT COUNT(*) FROM route_stops rs INNER JOIN collection_routes cr ON rs.route_id = cr.route_id INNER JOIN ewaste_requests r ON rs.request_id = r.request_id INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id WHERE cr.area_id <> r.area_id OR cr.collection_date <> acd.collection_date",
    'Collected/partial pickup_records without pickup_items' => "SELECT COUNT(*) FROM pickup_records pr LEFT JOIN pickup_items pi ON pr.pickup_id = pi.pickup_id WHERE pr.pickup_status IN ('COLLECTED','PARTIALLY_COLLECTED') AND pi.pickup_item_id IS NULL",
    'E-Lots without elot_items' => "SELECT COUNT(*) FROM elots e LEFT JOIN elot_items ei ON e.elot_id = ei.elot_id WHERE ei.elot_item_id IS NULL",
    'E-Lots with mixed item categories' => "SELECT COUNT(*) FROM (SELECT e.elot_id FROM elots e INNER JOIN elot_items ei ON e.elot_id = ei.elot_id INNER JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id INNER JOIN ewaste_items i ON pi.item_id = i.item_id GROUP BY e.elot_id HAVING COUNT(DISTINCT i.category_id) > 1) mixed",
    'E-Lot items from unverified pickups' => "SELECT COUNT(*) FROM elot_items ei INNER JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id WHERE pr.verification_status <> 'VERIFIED'",
    'Awarded/final E-Lots without winner' => "SELECT COUNT(*) FROM elots WHERE status IN ('AWARDED','HANDOVER_PENDING','HANDED_OVER','PROCESSING','COMPLETED') AND winner_recycler_profile_id IS NULL",
    'Winning bids not matching E-Lot winner' => "SELECT COUNT(*) FROM bids b INNER JOIN elots e ON b.elot_id = e.elot_id WHERE b.status = 'WINNING_BID' AND e.winner_recycler_profile_id <> b.recycler_profile_id",
];

if (hasColumn($pdo, 'area_collection_dates', 'campaign_id')) {
    $checks['Area schedules without campaign_id'] = "SELECT COUNT(*) FROM area_collection_dates acd INNER JOIN collection_areas ca ON acd.area_id = ca.area_id WHERE ca.area_name LIKE '%Final Zone' AND acd.campaign_id IS NULL";
}
if (hasColumn($pdo, 'collection_routes', 'date_id')) {
    $checks['Collection routes without date_id'] = "SELECT COUNT(*) FROM collection_routes cr INNER JOIN collection_areas ca ON cr.area_id = ca.area_id WHERE ca.area_name LIKE '%Final Zone' AND cr.date_id IS NULL";
    $checks['Route date_id mismatch'] = "SELECT COUNT(*) FROM collection_routes cr INNER JOIN area_collection_dates acd ON cr.date_id = acd.date_id WHERE cr.area_id <> acd.area_id OR cr.collection_date <> acd.collection_date";
}

foreach ($checks as $label => $sql) {
    $count = scalar($pdo, $sql);
    if ($count > 0) {
        warn($label . ': ' . $count);
        $warnings++;
    } else {
        out('[OK] ' . $label);
    }
}

out('\nCheck completed. Warnings: ' . $warnings);
