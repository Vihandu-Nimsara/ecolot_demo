<?php

require_once __DIR__ . '/../config/config.php';
require_once APP_PATH . '/core/Database.php';

$db = new Database();

function column_exists(Database $db, string $table, string $column): bool
{
    try {
        $db->query("SHOW COLUMNS FROM {$table} LIKE :column_name");
        $db->bind(':column_name', $column);
        return (bool) $db->single();
    } catch (Throwable $e) {
        return false;
    }
}

function check_rows(Database $db, string $title, string $sql): void
{
    echo "\n== {$title} ==\n";

    try {
        $db->query($sql);
        $rows = $db->resultSet();
        $count = count($rows);

        if ($count === 0) {
            echo "OK\n";
            return;
        }

        echo "WARN: {$count} row(s)\n";

        foreach (array_slice($rows, 0, 20) as $row) {
            echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
        }

        if ($count > 20) {
            echo "... truncated after 20 rows\n";
        }
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

$hasScheduleCampaign = column_exists($db, 'area_collection_dates', 'campaign_id');
$hasRouteDate = column_exists($db, 'collection_routes', 'date_id');

echo "EcoLot LK business-rule checker (read-only)\n";
echo "area_collection_dates.campaign_id: " . ($hasScheduleCampaign ? "present" : "missing") . "\n";
echo "collection_routes.date_id: " . ($hasRouteDate ? "present" : "missing") . "\n";

check_rows($db, 'Public profiles missing area_id', "
    SELECT public_profile_id, user_id, postal_code
    FROM public_user_profiles
    WHERE area_id IS NULL
");

check_rows($db, 'Public profile postal code mismatch with assigned area', "
    SELECT pup.public_profile_id, pup.user_id, pup.postal_code AS profile_postal_code, ca.postal_code AS area_postal_code
    FROM public_user_profiles pup
    INNER JOIN collection_areas ca ON pup.area_id = ca.area_id
    WHERE pup.postal_code <> ca.postal_code
");

check_rows($db, 'Requests whose area differs from selected schedule area', "
    SELECT r.request_id, r.area_id AS request_area_id, acd.area_id AS schedule_area_id, r.preferred_date_id
    FROM ewaste_requests r
    INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
    WHERE r.area_id <> acd.area_id
");

check_rows($db, 'Requests using closed/full/cancelled schedules', "
    SELECT r.request_id, r.status AS request_status, acd.date_id, acd.status AS schedule_status
    FROM ewaste_requests r
    INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
    WHERE acd.status IN ('CLOSED', 'FULL', 'CANCELLED')
");

if ($hasScheduleCampaign) {
    check_rows($db, 'Area schedules without campaign_id', "
        SELECT date_id, area_id, collection_date, status
        FROM area_collection_dates
        WHERE campaign_id IS NULL
    ");
}

if ($hasRouteDate) {
    check_rows($db, 'Collection routes without date_id', "
        SELECT route_id, campaign_id, area_id, collection_date, status
        FROM collection_routes
        WHERE date_id IS NULL
    ");

    check_rows($db, 'Routes mismatched with selected schedule', "
        SELECT cr.route_id, cr.campaign_id AS route_campaign_id, acd.campaign_id AS schedule_campaign_id,
               cr.area_id AS route_area_id, acd.area_id AS schedule_area_id,
               cr.collection_date AS route_collection_date, acd.collection_date AS schedule_collection_date
        FROM collection_routes cr
        INNER JOIN area_collection_dates acd ON cr.date_id = acd.date_id
        WHERE cr.area_id <> acd.area_id
        OR cr.collection_date <> acd.collection_date
        " . ($hasScheduleCampaign ? "OR cr.campaign_id <> acd.campaign_id" : "") . "
    ");
}

check_rows($db, 'Route stops with requests not matching route schedule/date', "
    SELECT rs.stop_id, rs.route_id, rs.request_id, cr.collection_date AS route_date, acd.collection_date AS request_schedule_date,
           cr.area_id AS route_area_id, r.area_id AS request_area_id
    FROM route_stops rs
    INNER JOIN collection_routes cr ON rs.route_id = cr.route_id
    INNER JOIN ewaste_requests r ON rs.request_id = r.request_id
    INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
    WHERE cr.area_id <> r.area_id
    OR cr.collection_date <> acd.collection_date
");

check_rows($db, 'Schedules at/over capacity but not FULL', "
    SELECT acd.date_id, acd.collection_date, acd.max_requests, acd.status, COUNT(r.request_id) AS request_count
    FROM area_collection_dates acd
    LEFT JOIN ewaste_requests r ON acd.date_id = r.preferred_date_id
    GROUP BY acd.date_id, acd.collection_date, acd.max_requests, acd.status
    HAVING request_count >= acd.max_requests AND acd.status <> 'FULL'
");

check_rows($db, 'E-Lots with mixed item categories', "
    SELECT e.elot_id, e.elot_code, COUNT(DISTINCT i.category_id) AS category_count
    FROM elots e
    INNER JOIN elot_items ei ON e.elot_id = ei.elot_id
    INNER JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id
    INNER JOIN ewaste_items i ON pi.item_id = i.item_id
    GROUP BY e.elot_id, e.elot_code
    HAVING category_count > 1
");

check_rows($db, 'E-Lot items sourced from non-verified pickup records', "
    SELECT e.elot_id, e.elot_code, ei.elot_item_id, pr.pickup_id, pr.verification_status
    FROM elots e
    INNER JOIN elot_items ei ON e.elot_id = ei.elot_id
    INNER JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id
    INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
    WHERE pr.verification_status <> 'VERIFIED'
");

check_rows($db, 'Awarded E-Lots without winning bid', "
    SELECT e.elot_id, e.elot_code, e.winner_recycler_profile_id
    FROM elots e
    LEFT JOIN bids b ON e.elot_id = b.elot_id AND b.status = 'WINNING_BID'
    WHERE e.status = 'AWARDED'
    AND b.bid_id IS NULL
");

check_rows($db, 'Winning bid mismatches E-Lot winner recycler', "
    SELECT e.elot_id, e.elot_code, e.winner_recycler_profile_id, b.recycler_profile_id AS winning_bid_recycler_profile_id
    FROM elots e
    INNER JOIN bids b ON e.elot_id = b.elot_id AND b.status = 'WINNING_BID'
    WHERE e.winner_recycler_profile_id IS NULL
    OR e.winner_recycler_profile_id <> b.recycler_profile_id
");

echo "\nDone.\n";
