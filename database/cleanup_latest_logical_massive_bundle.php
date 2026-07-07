<?php
/**
 * Optional cleanup for the EcoLot LK latest logical massive bundle.
 * Deletes only records generated with *.final@ecolot.lk users / Final zones / FINAL-ELOT codes.
 * It does not truncate tables and does not reset the whole database.
 */

require_once dirname(__DIR__) . '/config/config.php';
$config = require ROOT_PATH . '/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function out(string $message): void { echo $message . PHP_EOL; }
function ids(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql); $stmt->execute($params); return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
function execSql(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->rowCount();
}
function deleteIn(PDO $pdo, string $table, string $column, array $ids): int {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) { return 0; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$column} IN ({$placeholders})");
    $stmt->execute($ids);
    return $stmt->rowCount();
}

out('EcoLot LK latest logical massive bundle cleanup.');
out('Configured DB: ' . $config['dbname']);
out('This removes only generated FINAL bundle data, not the entire database.');
out('Run with --yes to continue.');
if (!in_array('--yes', $argv, true)) {
    exit(0);
}

$pdo->beginTransaction();
try {
    $userIds = ids($pdo, "SELECT user_id FROM users WHERE email LIKE '%.final@ecolot.lk'");
    $publicUserIds = ids($pdo, "SELECT user_id FROM users WHERE email LIKE 'public%.final@ecolot.lk'");
    $recyclerProfileIds = ids($pdo, "SELECT rp.recycler_profile_id FROM recycler_profiles rp INNER JOIN users u ON rp.user_id = u.user_id WHERE u.email LIKE 'recycler%.final@ecolot.lk'");
    $councilIds = ids($pdo, "SELECT council_id FROM local_councils WHERE council_name = 'EcoLot Final Municipal Council'");
    $areaIds = ids($pdo, "SELECT area_id FROM collection_areas WHERE area_name LIKE '%Final Zone' OR council_id IN (" . (empty($councilIds) ? '0' : implode(',', $councilIds)) . ")");
    $dateIds = ids($pdo, "SELECT date_id FROM area_collection_dates WHERE area_id IN (" . (empty($areaIds) ? '0' : implode(',', $areaIds)) . ")");
    $campaignIds = ids($pdo, "SELECT campaign_id FROM collection_campaigns WHERE campaign_name LIKE 'Final Monthly E-Waste Campaign%' OR council_id IN (" . (empty($councilIds) ? '0' : implode(',', $councilIds)) . ")");
    $requestIds = ids($pdo, "SELECT request_id FROM ewaste_requests WHERE public_user_id IN (" . (empty($publicUserIds) ? '0' : implode(',', $publicUserIds)) . ") OR preferred_date_id IN (" . (empty($dateIds) ? '0' : implode(',', $dateIds)) . ") OR special_note LIKE 'Final logical dataset%'");
    $routeIds = ids($pdo, "SELECT route_id FROM collection_routes WHERE route_name LIKE 'Final Route%' OR campaign_id IN (" . (empty($campaignIds) ? '0' : implode(',', $campaignIds)) . ")");
    $pickupIds = ids($pdo, "SELECT pickup_id FROM pickup_records WHERE request_id IN (" . (empty($requestIds) ? '0' : implode(',', $requestIds)) . ") OR route_id IN (" . (empty($routeIds) ? '0' : implode(',', $routeIds)) . ")");
    $pickupItemIds = ids($pdo, "SELECT pickup_item_id FROM pickup_items WHERE pickup_id IN (" . (empty($pickupIds) ? '0' : implode(',', $pickupIds)) . ")");
    $elotIds = ids($pdo, "SELECT elot_id FROM elots WHERE elot_code LIKE 'FINAL-ELOT-%' OR council_id IN (" . (empty($councilIds) ? '0' : implode(',', $councilIds)) . ")");
    $requestItemIds = ids($pdo, "SELECT request_item_id FROM request_items WHERE request_id IN (" . (empty($requestIds) ? '0' : implode(',', $requestIds)) . ")");
    $itemIds = ids($pdo, "SELECT item_id FROM ewaste_items WHERE item_name LIKE 'Final %'");

    $deleted = [];
    $deleted['report_snapshots'] = execSql($pdo, "DELETE FROM report_snapshots WHERE report_type = 'FINAL_LOGICAL_BUNDLE_SUMMARY' OR council_id IN (" . (empty($councilIds) ? '0' : implode(',', $councilIds)) . ")");
    $deleted['elot_status_history'] = deleteIn($pdo, 'elot_status_history', 'elot_id', $elotIds);
    $deleted['bids'] = execSql($pdo, "DELETE FROM bids WHERE elot_id IN (" . (empty($elotIds) ? '0' : implode(',', $elotIds)) . ") OR recycler_profile_id IN (" . (empty($recyclerProfileIds) ? '0' : implode(',', $recyclerProfileIds)) . ")");
    $deleted['elot_items'] = deleteIn($pdo, 'elot_items', 'elot_id', $elotIds);
    $deleted['elots'] = deleteIn($pdo, 'elots', 'elot_id', $elotIds);
    $deleted['flagged_items_by_pickup'] = deleteIn($pdo, 'flagged_items', 'pickup_item_id', $pickupItemIds);
    $deleted['flagged_items_by_request'] = deleteIn($pdo, 'flagged_items', 'request_item_id', $requestItemIds);
    $deleted['pickup_items'] = deleteIn($pdo, 'pickup_items', 'pickup_id', $pickupIds);
    $deleted['pickup_records'] = deleteIn($pdo, 'pickup_records', 'pickup_id', $pickupIds);
    $deleted['route_stops_by_route'] = deleteIn($pdo, 'route_stops', 'route_id', $routeIds);
    $deleted['route_stops_by_request'] = deleteIn($pdo, 'route_stops', 'request_id', $requestIds);
    $deleted['collection_routes'] = deleteIn($pdo, 'collection_routes', 'route_id', $routeIds);
    $deleted['request_status_history'] = deleteIn($pdo, 'request_status_history', 'request_id', $requestIds);
    $deleted['complaints_feedback'] = execSql($pdo, "DELETE FROM complaints_feedback WHERE public_user_id IN (" . (empty($publicUserIds) ? '0' : implode(',', $publicUserIds)) . ") OR subject LIKE 'Final feedback case%'");
    $deleted['request_items'] = deleteIn($pdo, 'request_items', 'request_id', $requestIds);
    $deleted['ewaste_requests'] = deleteIn($pdo, 'ewaste_requests', 'request_id', $requestIds);
    $deleted['area_collection_dates'] = deleteIn($pdo, 'area_collection_dates', 'date_id', $dateIds);
    $deleted['collection_campaigns'] = deleteIn($pdo, 'collection_campaigns', 'campaign_id', $campaignIds);
    $deleted['recycler_capabilities'] = deleteIn($pdo, 'recycler_capabilities', 'recycler_profile_id', $recyclerProfileIds);
    $deleted['recycler_profiles'] = deleteIn($pdo, 'recycler_profiles', 'recycler_profile_id', $recyclerProfileIds);
    $deleted['public_user_profiles'] = deleteIn($pdo, 'public_user_profiles', 'user_id', $userIds);
    $deleted['municipal_officer_profiles'] = deleteIn($pdo, 'municipal_officer_profiles', 'user_id', $userIds);
    $deleted['collector_profiles'] = deleteIn($pdo, 'collector_profiles', 'user_id', $userIds);
    $deleted['audit_logs'] = execSql($pdo, "DELETE FROM audit_logs WHERE action LIKE 'SEED_%' OR action = 'LATEST_LOGICAL_MASSIVE_BUNDLE_V1' OR description LIKE '%Latest logical massive bundle%'");
    $deleted['users'] = deleteIn($pdo, 'users', 'user_id', $userIds);
    $deleted['vehicles'] = execSql($pdo, "DELETE FROM vehicles WHERE vehicle_no LIKE 'WP-FEC-%'");
    $deleted['risk_rules'] = deleteIn($pdo, 'risk_rules', 'item_id', $itemIds);
    $deleted['ewaste_items'] = deleteIn($pdo, 'ewaste_items', 'item_id', $itemIds);
    $deleted['collection_areas'] = deleteIn($pdo, 'collection_areas', 'area_id', $areaIds);
    $deleted['local_councils'] = deleteIn($pdo, 'local_councils', 'council_id', $councilIds);

    $pdo->commit();
    out('Cleanup completed. Deleted rows:');
    foreach ($deleted as $table => $count) {
        out(str_pad($table, 30) . ' : ' . $count);
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    out('Cleanup failed. Transaction rolled back.');
    out($e->getMessage());
    exit(1);
}
