<?php

class MunicipalWorkflow
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }
    
    
    

    public function getOfficerProfile(int $userId): mixed
    {
        $this->db->query("
            SELECT 
                mop.*,
                lc.council_name,
                lc.district,
                lc.province
            FROM municipal_officer_profiles mop
            INNER JOIN local_councils lc ON mop.council_id = lc.council_id
            WHERE mop.user_id = :user_id
            LIMIT 1
        ");

        $this->db->bind(':user_id', $userId);

        return $this->db->single();
    }

    public function getDashboardStats(int $councilId): mixed
    {
    $this->db->query("
        SELECT
            COUNT(*) AS total_requests,
            SUM(r.status = 'SUBMITTED') AS submitted_requests,
            SUM(r.status = 'APPROVED') AS approved_requests,
            SUM(r.status = 'REJECTED') AS rejected_requests,
            SUM(r.status = 'ASSIGNED') AS assigned_requests,
            SUM(r.status = 'COLLECTED') AS collected_requests,
            SUM(r.status = 'COMPLETED') AS completed_requests
        FROM ewaste_requests r
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        WHERE ca.council_id = :council_id
    ");

    $this->db->bind(':council_id', $councilId);
    $requestStats = $this->db->single();

    $this->db->query("
        SELECT COUNT(*) AS total_campaigns
        FROM collection_campaigns
        WHERE council_id = :council_id
    ");

    $this->db->bind(':council_id', $councilId);
    $campaignStats = $this->db->single();

    $this->db->query("
        SELECT COUNT(*) AS pending_flags
        FROM flagged_items fi
        LEFT JOIN request_items ri ON fi.request_item_id = ri.request_item_id
        LEFT JOIN pickup_items pi ON fi.pickup_item_id = pi.pickup_item_id
        LEFT JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
        LEFT JOIN ewaste_requests r1 ON ri.request_id = r1.request_id
        LEFT JOIN ewaste_requests r2 ON pr.request_id = r2.request_id
        INNER JOIN collection_areas ca ON ca.area_id = COALESCE(r1.area_id, r2.area_id)
        WHERE ca.council_id = :council_id
        AND fi.review_status = 'PENDING'
    ");

    $this->db->bind(':council_id', $councilId);
    $flagStats = $this->db->single();

    $this->db->query("
        SELECT
            COUNT(*) AS total_pickup_records,
            SUM(pr.verification_status = 'PENDING') AS pending_pickup_records,
            SUM(pr.verification_status = 'VERIFIED') AS verified_pickup_records,
            SUM(pr.verification_status = 'REJECTED') AS rejected_pickup_records
        FROM pickup_records pr
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        WHERE ca.council_id = :council_id
    ");

    $this->db->bind(':council_id', $councilId);
    $pickupStats = $this->db->single();

    $this->db->query("
        SELECT COUNT(*) AS verified_pool_items
        FROM pickup_items pi
        INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        LEFT JOIN elot_items ei ON pi.pickup_item_id = ei.pickup_item_id
        WHERE ca.council_id = :council_id
        AND pr.verification_status = 'VERIFIED'
        AND pi.collected_quantity > 0
        AND ei.elot_item_id IS NULL
    ");

    $this->db->bind(':council_id', $councilId);
    $poolStats = $this->db->single();

    return (object) [
        'total_requests' => $requestStats->total_requests ?? 0,
        'submitted_requests' => $requestStats->submitted_requests ?? 0,
        'approved_requests' => $requestStats->approved_requests ?? 0,
        'rejected_requests' => $requestStats->rejected_requests ?? 0,
        'assigned_requests' => $requestStats->assigned_requests ?? 0,
        'collected_requests' => $requestStats->collected_requests ?? 0,
        'completed_requests' => $requestStats->completed_requests ?? 0,

        'total_campaigns' => $campaignStats->total_campaigns ?? 0,
        'pending_flags' => $flagStats->pending_flags ?? 0,

        'total_pickup_records' => $pickupStats->total_pickup_records ?? 0,
        'pending_pickup_records' => $pickupStats->pending_pickup_records ?? 0,
        'verified_pickup_records' => $pickupStats->verified_pickup_records ?? 0,
        'rejected_pickup_records' => $pickupStats->rejected_pickup_records ?? 0,

        'verified_pool_items' => $poolStats->verified_pool_items ?? 0
    ];
}

    public function getPickupRecordsForCouncil(int $councilId, ?string $verificationStatus = null): array
{
    $statusSql = '';

    if ($verificationStatus !== null) {
        $statusSql = "AND pr.verification_status = :verification_status";
    }

    $this->db->query("
        SELECT
            pr.pickup_id,
            pr.request_id,
            pr.route_id,
            pr.collector_id,
            pr.pickup_status,
            pr.total_collected_weight_kg,
            pr.collector_note,
            pr.submitted_at,
            pr.verification_status,
            pr.verified_at,
            pr.officer_note,

            cr.route_name,
            cr.collection_date,

            collector.full_name AS collector_name,
            public_user.full_name AS public_user_name,

            ca.area_name,
            ca.postal_code,

            COUNT(pi.pickup_item_id) AS collected_item_count,
            COALESCE(SUM(pi.collected_quantity), 0) AS total_collected_quantity
        FROM pickup_records pr
        INNER JOIN collection_routes cr ON pr.route_id = cr.route_id
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        INNER JOIN users collector ON pr.collector_id = collector.user_id
        INNER JOIN users public_user ON r.public_user_id = public_user.user_id
        LEFT JOIN pickup_items pi ON pr.pickup_id = pi.pickup_id
        WHERE ca.council_id = :council_id
        {$statusSql}
        GROUP BY
            pr.pickup_id,
            pr.request_id,
            pr.route_id,
            pr.collector_id,
            pr.pickup_status,
            pr.total_collected_weight_kg,
            pr.collector_note,
            pr.submitted_at,
            pr.verification_status,
            pr.verified_at,
            pr.officer_note,
            cr.route_name,
            cr.collection_date,
            collector.full_name,
            public_user.full_name,
            ca.area_name,
            ca.postal_code
        ORDER BY pr.submitted_at DESC
    ");

    $this->db->bind(':council_id', $councilId);

    if ($verificationStatus !== null) {
        $this->db->bind(':verification_status', $verificationStatus);
    }

    return $this->db->resultSet();
}

public function getPickupRecordByIdForCouncil(int $pickupId, int $councilId): mixed
{
    $this->db->query("
        SELECT
            pr.*,

            cr.route_name,
            cr.collection_date,

            collector.full_name AS collector_name,
            collector.email AS collector_email,

            public_user.full_name AS public_user_name,
            public_user.email AS public_user_email,

            r.status AS request_status,
            r.pickup_address,
            r.contact_phone,
            r.special_note,

            ca.area_name,
            ca.postal_code
        FROM pickup_records pr
        INNER JOIN collection_routes cr ON pr.route_id = cr.route_id
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        INNER JOIN users collector ON pr.collector_id = collector.user_id
        INNER JOIN users public_user ON r.public_user_id = public_user.user_id
        WHERE pr.pickup_id = :pickup_id
        AND ca.council_id = :council_id
        LIMIT 1
    ");

    $this->db->bind(':pickup_id', $pickupId);
    $this->db->bind(':council_id', $councilId);

    return $this->db->single();
}

public function getPickupItems(int $pickupId): array
{
    $this->db->query("
        SELECT
            pi.*,
            i.item_name,
            i.collection_status,
            i.default_risk_level,
            c.category_name
        FROM pickup_items pi
        INNER JOIN ewaste_items i ON pi.item_id = i.item_id
        INNER JOIN ewaste_categories c ON i.category_id = c.category_id
        WHERE pi.pickup_id = :pickup_id
        ORDER BY c.category_name ASC, i.item_name ASC
    ");

    $this->db->bind(':pickup_id', $pickupId);

    return $this->db->resultSet();
}

public function getPickupFlags(int $pickupId): array
{
    $this->db->query("
        SELECT
            fi.*,
            pi.collected_quantity,
            pi.condition_status,
            i.item_name,
            c.category_name,
            flagged_by_user.full_name AS flagged_by_name
        FROM flagged_items fi
        INNER JOIN pickup_items pi ON fi.pickup_item_id = pi.pickup_item_id
        INNER JOIN ewaste_items i ON pi.item_id = i.item_id
        INNER JOIN ewaste_categories c ON i.category_id = c.category_id
        INNER JOIN users flagged_by_user ON fi.flagged_by = flagged_by_user.user_id
        WHERE pi.pickup_id = :pickup_id
        ORDER BY fi.created_at DESC
    ");

    $this->db->bind(':pickup_id', $pickupId);

    return $this->db->resultSet();
}

public function getCategoriesWithVerifiedPool(int $councilId): array
{
    $this->db->query("
        SELECT
            c.category_id,
            c.category_name,
            COUNT(pi.pickup_item_id) AS available_items,
            COALESCE(SUM(pi.collected_quantity), 0) AS total_quantity,
            COALESCE(SUM(pi.collected_weight_kg), 0) AS total_weight
        FROM ewaste_categories c
        INNER JOIN ewaste_items i ON c.category_id = i.category_id
        INNER JOIN pickup_items pi ON i.item_id = pi.item_id
        INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        LEFT JOIN elot_items ei ON pi.pickup_item_id = ei.pickup_item_id
        WHERE ca.council_id = :council_id
        AND pr.verification_status = 'VERIFIED'
        AND pi.collected_quantity > 0
        AND ei.elot_item_id IS NULL
        AND c.status = 'ACTIVE'
        GROUP BY c.category_id, c.category_name
        ORDER BY c.category_name ASC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function getElotsForCouncil(int $councilId): array
{
    $this->db->query("
        SELECT
            e.*,
            c.category_name,
            u.full_name AS created_by_name,
            winner.company_name AS winner_company_name,
            COUNT(ei.elot_item_id) AS item_count,
            COUNT(b.bid_id) AS bid_count
        FROM elots e
        INNER JOIN ewaste_categories c ON e.category_id = c.category_id
        INNER JOIN users u ON e.created_by = u.user_id
        LEFT JOIN recycler_profiles winner ON e.winner_recycler_profile_id = winner.recycler_profile_id
        LEFT JOIN elot_items ei ON e.elot_id = ei.elot_id
        LEFT JOIN bids b ON e.elot_id = b.elot_id
        WHERE e.council_id = :council_id
        GROUP BY
            e.elot_id,
            e.council_id,
            e.created_by,
            e.elot_code,
            e.title,
            e.category_id,
            e.total_weight_kg,
            e.description,
            e.status,
            e.bidding_start,
            e.bidding_end,
            e.winner_recycler_profile_id,
            e.created_at,
            c.category_name,
            u.full_name,
            winner.company_name
        ORDER BY e.created_at DESC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function getElotByIdForCouncil(int $elotId, int $councilId): mixed
{
    $this->db->query("
        SELECT
            e.*,
            c.category_name,
            u.full_name AS created_by_name,
            winner.company_name AS winner_company_name
        FROM elots e
        INNER JOIN ewaste_categories c ON e.category_id = c.category_id
        INNER JOIN users u ON e.created_by = u.user_id
        LEFT JOIN recycler_profiles winner ON e.winner_recycler_profile_id = winner.recycler_profile_id
        WHERE e.elot_id = :elot_id
        AND e.council_id = :council_id
        LIMIT 1
    ");

    $this->db->bind(':elot_id', $elotId);
    $this->db->bind(':council_id', $councilId);

    return $this->db->single();
}

public function getElotItems(int $elotId): array
{
    $this->db->query("
        SELECT
            ei.*,
            pi.pickup_id,
            pi.item_id,
            pi.condition_status,
            pi.note AS pickup_item_note,
            pr.request_id,
            pr.verified_at,
            i.item_name,
            c.category_name,
            cr.route_name,
            cr.collection_date,
            ca.area_name,
            ca.postal_code
        FROM elot_items ei
        INNER JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id
        INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
        INNER JOIN ewaste_items i ON pi.item_id = i.item_id
        INNER JOIN ewaste_categories c ON i.category_id = c.category_id
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        INNER JOIN collection_routes cr ON pr.route_id = cr.route_id
        WHERE ei.elot_id = :elot_id
        ORDER BY c.category_name ASC, i.item_name ASC
    ");

    $this->db->bind(':elot_id', $elotId);

    return $this->db->resultSet();
}

public function getElotStatusHistory(int $elotId): array
{
    $this->db->query("
        SELECT
            h.*,
            u.full_name AS changed_by_name
        FROM elot_status_history h
        LEFT JOIN users u ON h.changed_by = u.user_id
        WHERE h.elot_id = :elot_id
        ORDER BY h.created_at ASC
    ");

    $this->db->bind(':elot_id', $elotId);

    return $this->db->resultSet();
}

public function validateVerifiedItemsForElot(
    array $pickupItemIds,
    int $councilId,
    int $categoryId
): array {
    if (empty($pickupItemIds)) {
        return [];
    }

    $placeholders = [];

    foreach ($pickupItemIds as $index => $pickupItemId) {
        $placeholders[] = ':pickup_item_id_' . $index;
    }

    $inSql = implode(',', $placeholders);

    $this->db->query("
        SELECT
            pi.pickup_item_id,
            pi.pickup_id,
            pi.item_id,
            pi.collected_quantity,
            pi.collected_weight_kg,
            pi.condition_status,
            pr.request_id,
            i.item_name,
            i.category_id,
            c.category_name
        FROM pickup_items pi
        INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
        INNER JOIN ewaste_items i ON pi.item_id = i.item_id
        INNER JOIN ewaste_categories c ON i.category_id = c.category_id
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        LEFT JOIN elot_items ei ON pi.pickup_item_id = ei.pickup_item_id
        WHERE pi.pickup_item_id IN ($inSql)
        AND ca.council_id = :council_id
        AND i.category_id = :category_id
        AND pr.verification_status = 'VERIFIED'
        AND pi.collected_quantity > 0
        AND ei.elot_item_id IS NULL
    ");

    foreach ($pickupItemIds as $index => $pickupItemId) {
        $this->db->bind(':pickup_item_id_' . $index, (int) $pickupItemId);
    }

    $this->db->bind(':council_id', $councilId);
    $this->db->bind(':category_id', $categoryId);

    return $this->db->resultSet();
}

public function createElotWithItems(array $data): int|false
{
    try {
        $this->db->beginTransaction();

        $elotCode = 'ELOT-' . date('YmdHis') . '-' . random_int(100, 999);

        $totalWeight = 0;

        foreach ($data['verified_items'] as $item) {
            $totalWeight += (float) ($item->collected_weight_kg ?? 0);
        }

        $this->db->query("
            INSERT INTO elots
                (
                    council_id,
                    created_by,
                    elot_code,
                    title,
                    category_id,
                    total_weight_kg,
                    description,
                    status,
                    bidding_start,
                    bidding_end
                )
            VALUES
                (
                    :council_id,
                    :created_by,
                    :elot_code,
                    :title,
                    :category_id,
                    :total_weight_kg,
                    :description,
                    :status,
                    :bidding_start,
                    :bidding_end
                )
        ");

        $this->db->bind(':council_id', $data['council_id']);
        $this->db->bind(':created_by', $data['created_by']);
        $this->db->bind(':elot_code', $elotCode);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':total_weight_kg', $totalWeight);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':bidding_start', $data['bidding_start']);
        $this->db->bind(':bidding_end', $data['bidding_end']);

        $this->db->execute();

        $elotId = (int) $this->db->lastInsertId();

        foreach ($data['verified_items'] as $item) {
            $this->db->query("
                INSERT INTO elot_items
                    (
                        elot_id,
                        pickup_item_id,
                        quantity,
                        weight_kg
                    )
                VALUES
                    (
                        :elot_id,
                        :pickup_item_id,
                        :quantity,
                        :weight_kg
                    )
            ");

            $this->db->bind(':elot_id', $elotId);
            $this->db->bind(':pickup_item_id', $item->pickup_item_id);
            $this->db->bind(':quantity', $item->collected_quantity);
            $this->db->bind(':weight_kg', $item->collected_weight_kg);

            $this->db->execute();
        }

        $this->db->query("
            INSERT INTO elot_status_history
                (elot_id, changed_by, old_status, new_status, note)
            VALUES
                (:elot_id, :changed_by, NULL, :new_status, :note)
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':changed_by', $data['created_by']);
        $this->db->bind(':new_status', $data['status']);
        $this->db->bind(':note', 'E-Lot created from verified pickup items.');
        $this->db->execute();

        $this->db->query("
            INSERT INTO audit_logs
                (user_id, action, description)
            VALUES
                (:user_id, 'CREATE_ELOT', :description)
        ");

        $this->db->bind(':user_id', $data['created_by']);
        $this->db->bind(':description', 'Created E-Lot ID ' . $elotId . ' with ' . count($data['verified_items']) . ' verified pickup items.');
        $this->db->execute();

        $this->db->commit();

        return $elotId;
    } catch (Throwable $e) {
        $this->db->rollBack();
        return false;
    }
}

public function getBidsForElot(int $elotId): array
{
    $this->db->query("
        SELECT
            b.*,
            rp.company_name,
            rp.license_no,
            rp.verification_status,
            u.full_name AS recycler_contact_name,
            u.email AS recycler_email,
            u.phone AS recycler_phone
        FROM bids b
        INNER JOIN recycler_profiles rp ON b.recycler_profile_id = rp.recycler_profile_id
        INNER JOIN users u ON rp.user_id = u.user_id
        WHERE b.elot_id = :elot_id
        ORDER BY b.bid_amount DESC, b.submitted_at ASC
    ");

    $this->db->bind(':elot_id', $elotId);

    return $this->db->resultSet();
}

public function closeElotBidding(int $elotId, int $councilId, int $officerId, string $note): bool
{
    try {
        $this->db->beginTransaction();

        $this->db->query("
            SELECT *
            FROM elots
            WHERE elot_id = :elot_id
            AND council_id = :council_id
            LIMIT 1
            FOR UPDATE
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':council_id', $councilId);

        $elot = $this->db->single();

        if (!$elot || $elot->status !== 'OPEN_FOR_BIDDING') {
            $this->db->rollBack();
            return false;
        }

        $this->db->query("
            UPDATE elots
            SET status = 'BIDDING_CLOSED'
            WHERE elot_id = :elot_id
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->execute();

        $this->db->query("
            INSERT INTO elot_status_history
                (elot_id, changed_by, old_status, new_status, note)
            VALUES
                (:elot_id, :changed_by, 'OPEN_FOR_BIDDING', 'BIDDING_CLOSED', :note)
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':changed_by', $officerId);
        $this->db->bind(':note', $note);
        $this->db->execute();

        $this->db->query("
            INSERT INTO audit_logs
                (user_id, action, description)
            VALUES
                (:user_id, 'CLOSE_ELOT_BIDDING', :description)
        ");

        $this->db->bind(':user_id', $officerId);
        $this->db->bind(':description', 'Closed bidding for E-Lot ID ' . $elotId);
        $this->db->execute();

        $this->db->commit();

        return true;
    } catch (Throwable $e) {
        $this->db->rollBack();
        return false;
    }
}

public function awardWinningBid(
    int $elotId,
    int $bidId,
    int $councilId,
    int $officerId,
    string $officerNote
): bool {
    try {
        $this->db->beginTransaction();

        $this->db->query("
            SELECT *
            FROM elots
            WHERE elot_id = :elot_id
            AND council_id = :council_id
            LIMIT 1
            FOR UPDATE
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':council_id', $councilId);

        $elot = $this->db->single();

        if (!$elot || $elot->status !== 'BIDDING_CLOSED') {
            $this->db->rollBack();
            return false;
        }

        $this->db->query("
            SELECT
                b.*,
                rp.company_name,
                rp.verification_status
            FROM bids b
            INNER JOIN recycler_profiles rp ON b.recycler_profile_id = rp.recycler_profile_id
            WHERE b.bid_id = :bid_id
            AND b.elot_id = :elot_id
            LIMIT 1
            FOR UPDATE
        ");

        $this->db->bind(':bid_id', $bidId);
        $this->db->bind(':elot_id', $elotId);

        $winningBid = $this->db->single();

        if (
            !$winningBid ||
            $winningBid->status !== 'SUBMITTED' ||
            $winningBid->verification_status !== 'VERIFIED'
        ) {
            $this->db->rollBack();
            return false;
        }

        $this->db->query("
            UPDATE bids
            SET status = 'REJECTED'
            WHERE elot_id = :elot_id
            AND bid_id != :bid_id
            AND status = 'SUBMITTED'
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':bid_id', $bidId);
        $this->db->execute();

        $this->db->query("
            UPDATE bids
            SET status = 'WINNING_BID'
            WHERE bid_id = :bid_id
        ");

        $this->db->bind(':bid_id', $bidId);
        $this->db->execute();

        $this->db->query("
            UPDATE elots
            SET status = 'AWARDED',
                winner_recycler_profile_id = :winner_recycler_profile_id
            WHERE elot_id = :elot_id
        ");

        $this->db->bind(':winner_recycler_profile_id', $winningBid->recycler_profile_id);
        $this->db->bind(':elot_id', $elotId);
        $this->db->execute();

        $this->db->query("
            INSERT INTO elot_status_history
                (elot_id, changed_by, old_status, new_status, note)
            VALUES
                (:elot_id, :changed_by, 'BIDDING_CLOSED', 'AWARDED', :note)
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':changed_by', $officerId);
        $this->db->bind(
            ':note',
            'Winning bid selected. Bid ID: ' . $bidId .
            ', Recycler: ' . $winningBid->company_name .
            ', Amount: Rs. ' . number_format((float)$winningBid->bid_amount, 2) .
            '. ' . $officerNote
        );
        $this->db->execute();

        $this->db->query("
            INSERT INTO audit_logs
                (user_id, action, description)
            VALUES
                (:user_id, 'AWARD_ELOT_BID', :description)
        ");

        $this->db->bind(':user_id', $officerId);
        $this->db->bind(
            ':description',
            'Awarded E-Lot ID ' . $elotId . ' to bid ID ' . $bidId
        );
        $this->db->execute();

        $this->db->commit();

        return true;
    } catch (Throwable $e) {
        $this->db->rollBack();
        return false;
    }
}

public function markElotAsHandedOver(
    int $elotId,
    int $councilId,
    int $officerId,
    string $handoverNote
): bool {
    try {
        $this->db->beginTransaction();

        $this->db->query("
            SELECT 
                e.*,
                rp.company_name AS winner_company_name
            FROM elots e
            LEFT JOIN recycler_profiles rp 
                ON e.winner_recycler_profile_id = rp.recycler_profile_id
            WHERE e.elot_id = :elot_id
            AND e.council_id = :council_id
            LIMIT 1
            FOR UPDATE
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':council_id', $councilId);

        $elot = $this->db->single();

        if (!$elot || $elot->status !== 'AWARDED' || empty($elot->winner_recycler_profile_id)) {
            $this->db->rollBack();
            return false;
        }

        $this->db->query("
            UPDATE elots
            SET status = 'HANDED_OVER'
            WHERE elot_id = :elot_id
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->execute();

        $this->db->query("
            INSERT INTO elot_status_history
                (elot_id, changed_by, old_status, new_status, note)
            VALUES
                (:elot_id, :changed_by, 'AWARDED', 'HANDED_OVER', :note)
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':changed_by', $officerId);
        $this->db->bind(
            ':note',
            'E-Lot handed over to recycler: ' .
            ($elot->winner_company_name ?? 'Selected recycler') .
            '. ' .
            $handoverNote
        );
        $this->db->execute();

        $this->db->query("
            INSERT INTO audit_logs
                (user_id, action, description)
            VALUES
                (:user_id, 'MARK_ELOT_HANDED_OVER', :description)
        ");

        $this->db->bind(':user_id', $officerId);
        $this->db->bind(':description', 'Marked E-Lot ID ' . $elotId . ' as handed over.');
        $this->db->execute();

        $this->db->commit();

        return true;
    } catch (Throwable $e) {
        $this->db->rollBack();
        return false;
    }
}

public function verifyPickupRecord(
    int $pickupId,
    int $officerId,
    string $decision,
    string $officerNote
): bool {
    try {
        $this->db->beginTransaction();

        $this->db->query("
            SELECT
                pr.*,
                r.status AS request_status
            FROM pickup_records pr
            INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
            WHERE pr.pickup_id = :pickup_id
            LIMIT 1
            FOR UPDATE
        ");

        $this->db->bind(':pickup_id', $pickupId);
        $pickup = $this->db->single();

        if (!$pickup || $pickup->verification_status !== 'PENDING') {
            $this->db->rollBack();
            return false;
        }

        $newVerificationStatus = $decision === 'VERIFIED' ? 'VERIFIED' : 'REJECTED';

        $this->db->query("
            UPDATE pickup_records
            SET verification_status = :verification_status,
                verified_by = :verified_by,
                verified_at = NOW(),
                officer_note = :officer_note
            WHERE pickup_id = :pickup_id
        ");

        $this->db->bind(':verification_status', $newVerificationStatus);
        $this->db->bind(':verified_by', $officerId);
        $this->db->bind(':officer_note', $officerNote);
        $this->db->bind(':pickup_id', $pickupId);
        $this->db->execute();

        $newRequestStatus = $pickup->request_status;

        if ($newVerificationStatus === 'VERIFIED') {
            if (in_array($pickup->pickup_status, ['COLLECTED', 'PARTIALLY_COLLECTED'], true)) {
                $newRequestStatus = 'COMPLETED';
            } else {
                $newRequestStatus = 'PICKUP_PENDING';
            }
        }

        if ($newVerificationStatus === 'REJECTED') {
            $newRequestStatus = 'PICKUP_PENDING';
        }

        if ($newRequestStatus !== $pickup->request_status) {
            $this->db->query("
                UPDATE ewaste_requests
                SET status = :status
                WHERE request_id = :request_id
            ");

            $this->db->bind(':status', $newRequestStatus);
            $this->db->bind(':request_id', $pickup->request_id);
            $this->db->execute();

            $this->db->query("
                INSERT INTO request_status_history
                    (request_id, changed_by, old_status, new_status, note)
                VALUES
                    (:request_id, :changed_by, :old_status, :new_status, :note)
            ");

            $this->db->bind(':request_id', $pickup->request_id);
            $this->db->bind(':changed_by', $officerId);
            $this->db->bind(':old_status', $pickup->request_status);
            $this->db->bind(':new_status', $newRequestStatus);
            $this->db->bind(':note', 'Pickup record verification decision: ' . $newVerificationStatus . '. ' . $officerNote);
            $this->db->execute();
        }

        $this->db->query("
            INSERT INTO audit_logs
                (user_id, action, description)
            VALUES
                (:user_id, 'VERIFY_PICKUP_RECORD', :description)
        ");

        $this->db->bind(':user_id', $officerId);
        $this->db->bind(
            ':description',
            'Pickup record ID ' . $pickupId . ' marked as ' . $newVerificationStatus
        );
        $this->db->execute();

        $this->db->commit();

        return true;
    } catch (Throwable $e) {
        $this->db->rollBack();
        return false;
    }
}

public function getVerifiedPoolForCouncil(int $councilId): array
{
    $this->db->query("
        SELECT
            pi.pickup_item_id,
            pi.pickup_id,
            pi.item_id,
            pi.collected_quantity,
            pi.collected_weight_kg,
            pi.condition_status,
            pi.note,

            pr.request_id,
            pr.route_id,
            pr.submitted_at,
            pr.verified_at,

            i.item_name,
            i.default_risk_level,
            i.collection_status,

            c.category_id,
            c.category_name,

            ca.area_name,
            ca.postal_code,

            cr.route_name,
            cr.collection_date,

            collector.full_name AS collector_name
        FROM pickup_items pi
        INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
        INNER JOIN ewaste_items i ON pi.item_id = i.item_id
        INNER JOIN ewaste_categories c ON i.category_id = c.category_id
        INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        INNER JOIN collection_routes cr ON pr.route_id = cr.route_id
        INNER JOIN users collector ON pr.collector_id = collector.user_id
        LEFT JOIN elot_items ei ON pi.pickup_item_id = ei.pickup_item_id
        WHERE ca.council_id = :council_id
        AND pr.verification_status = 'VERIFIED'
        AND pi.collected_quantity > 0
        AND ei.elot_item_id IS NULL
        ORDER BY c.category_name ASC, pr.verified_at ASC, pi.pickup_item_id ASC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}


    public function getRequestsForCouncil(int $councilId, ?string $status = null): array
    {
        $statusSql = '';

        if ($status !== null) {
            $statusSql = 'AND r.status = :status';
        }

        $this->db->query("
            SELECT
                r.request_id,
                r.status,
                r.pickup_address,
                r.contact_phone,
                r.created_at,
                r.updated_at,
                u.full_name AS public_user_name,
                u.email AS public_user_email,
                ca.area_name,
                ca.postal_code,
                acd.collection_date,
                COUNT(ri.request_item_id) AS item_count,
                COALESCE(SUM(ri.estimated_weight_kg), 0) AS total_estimated_weight
            FROM ewaste_requests r
            INNER JOIN users u ON r.public_user_id = u.user_id
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
            LEFT JOIN request_items ri ON r.request_id = ri.request_id
            WHERE ca.council_id = :council_id
            {$statusSql}
            GROUP BY
                r.request_id,
                r.status,
                r.pickup_address,
                r.contact_phone,
                r.created_at,
                r.updated_at,
                u.full_name,
                u.email,
                ca.area_name,
                ca.postal_code,
                acd.collection_date
            ORDER BY r.created_at DESC
        ");

        $this->db->bind(':council_id', $councilId);

        if ($status !== null) {
            $this->db->bind(':status', $status);
        }

        return $this->db->resultSet();
    }

    public function getRequestByIdForCouncil(int $requestId, int $councilId): mixed
    {
        $this->db->query("
            SELECT
                r.*,
                u.full_name AS public_user_name,
                u.email AS public_user_email,
                ca.area_name,
                ca.postal_code,
                acd.collection_date
            FROM ewaste_requests r
            INNER JOIN users u ON r.public_user_id = u.user_id
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
            WHERE r.request_id = :request_id
            AND ca.council_id = :council_id
            LIMIT 1
        ");

        $this->db->bind(':request_id', $requestId);
        $this->db->bind(':council_id', $councilId);

        return $this->db->single();
    }

    public function getRequestItems(int $requestId): array
    {
        $this->db->query("
            SELECT
                ri.*,
                i.item_name,
                i.collection_status,
                i.default_risk_level,
                c.category_name
            FROM request_items ri
            INNER JOIN ewaste_items i ON ri.item_id = i.item_id
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            WHERE ri.request_id = :request_id
            ORDER BY c.category_name ASC, i.item_name ASC
        ");

        $this->db->bind(':request_id', $requestId);

        return $this->db->resultSet();
    }

    public function getFlaggedItemsForRequest(int $requestId): array
    {
        $this->db->query("
            SELECT
                fi.*,
                i.item_name,
                c.category_name,
                ri.condition_status,
                ri.quantity
            FROM flagged_items fi
            INNER JOIN request_items ri ON fi.request_item_id = ri.request_item_id
            INNER JOIN ewaste_items i ON ri.item_id = i.item_id
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            WHERE ri.request_id = :request_id
            ORDER BY fi.created_at DESC
        ");

        $this->db->bind(':request_id', $requestId);

        return $this->db->resultSet();
    }

    public function getStatusHistory(int $requestId): array
    {
        $this->db->query("
            SELECT
                h.*,
                u.full_name AS changed_by_name
            FROM request_status_history h
            LEFT JOIN users u ON h.changed_by = u.user_id
            WHERE h.request_id = :request_id
            ORDER BY h.created_at ASC
        ");

        $this->db->bind(':request_id', $requestId);

        return $this->db->resultSet();
    }

    public function updateRequestStatus(
        int $requestId,
        int $officerId,
        string $oldStatus,
        string $newStatus,
        string $note
    ): bool {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                UPDATE ewaste_requests
                SET status = :status
                WHERE request_id = :request_id
            ");

            $this->db->bind(':status', $newStatus);
            $this->db->bind(':request_id', $requestId);
            $this->db->execute();

            $this->db->query("
                INSERT INTO request_status_history
                    (request_id, changed_by, old_status, new_status, note)
                VALUES
                    (:request_id, :changed_by, :old_status, :new_status, :note)
            ");

            $this->db->bind(':request_id', $requestId);
            $this->db->bind(':changed_by', $officerId);
            $this->db->bind(':old_status', $oldStatus);
            $this->db->bind(':new_status', $newStatus);
            $this->db->bind(':note', $note);
            $this->db->execute();

            $this->db->query("
                INSERT INTO audit_logs
                    (user_id, action, description)
                VALUES
                    (:user_id, 'UPDATE_REQUEST_STATUS', :description)
            ");

            $this->db->bind(':user_id', $officerId);
            $this->db->bind(
                ':description',
                'Request ID ' . $requestId . ' changed from ' . $oldStatus . ' to ' . $newStatus
            );
            $this->db->execute();

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getCampaignsForCouncil(int $councilId): array
    {
        $this->db->query("
            SELECT
                cc.*,
                u.full_name AS created_by_name
            FROM collection_campaigns cc
            INNER JOIN users u ON cc.created_by = u.user_id
            WHERE cc.council_id = :council_id
            ORDER BY cc.campaign_year DESC, cc.campaign_month DESC, cc.created_at DESC
        ");

        $this->db->bind(':council_id', $councilId);

        return $this->db->resultSet();
    }

    public function campaignExists(int $councilId, int $month, int $year): bool
    {
        $this->db->query("
            SELECT campaign_id
            FROM collection_campaigns
            WHERE council_id = :council_id
            AND campaign_month = :campaign_month
            AND campaign_year = :campaign_year
            LIMIT 1
        ");

        $this->db->bind(':council_id', $councilId);
        $this->db->bind(':campaign_month', $month);
        $this->db->bind(':campaign_year', $year);

        return (bool) $this->db->single();
    }

    public function createCampaign(array $data): bool
    {
        $this->db->query("
            INSERT INTO collection_campaigns
                (
                    council_id,
                    campaign_name,
                    campaign_month,
                    campaign_year,
                    request_cutoff_date,
                    status,
                    created_by
                )
            VALUES
                (
                    :council_id,
                    :campaign_name,
                    :campaign_month,
                    :campaign_year,
                    :request_cutoff_date,
                    :status,
                    :created_by
                )
        ");

        $this->db->bind(':council_id', $data['council_id']);
        $this->db->bind(':campaign_name', $data['campaign_name']);
        $this->db->bind(':campaign_month', $data['campaign_month']);
        $this->db->bind(':campaign_year', $data['campaign_year']);
        $this->db->bind(':request_cutoff_date', $data['request_cutoff_date']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':created_by', $data['created_by']);

        return $this->db->execute();
    }

    public function getRoutesForCouncil(int $councilId): array
{
    $this->db->query("
        SELECT
            cr.*,
            cc.campaign_name,
            ca.area_name,
            ca.postal_code,
            u.full_name AS collector_name,
            v.vehicle_no,
            COUNT(rs.stop_id) AS stop_count
        FROM collection_routes cr
        INNER JOIN collection_campaigns cc ON cr.campaign_id = cc.campaign_id
        INNER JOIN collection_areas ca ON cr.area_id = ca.area_id
        LEFT JOIN users u ON cr.collector_id = u.user_id
        LEFT JOIN vehicles v ON cr.vehicle_id = v.vehicle_id
        LEFT JOIN route_stops rs ON cr.route_id = rs.route_id
        WHERE cc.council_id = :council_id
        GROUP BY
            cr.route_id,
            cr.campaign_id,
            cr.area_id,
            cr.route_name,
            cr.collection_date,
            cr.collector_id,
            cr.vehicle_id,
            cr.status,
            cr.created_at,
            cc.campaign_name,
            ca.area_name,
            ca.postal_code,
            u.full_name,
            v.vehicle_no
        ORDER BY cr.collection_date DESC, cr.created_at DESC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function getCampaignOptions(int $councilId): array
{
    $this->db->query("
        SELECT campaign_id, campaign_name, campaign_month, campaign_year, status
        FROM collection_campaigns
        WHERE council_id = :council_id
        AND status IN ('DRAFT', 'OPEN')
        ORDER BY campaign_year DESC, campaign_month DESC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function getAreasForCouncil(int $councilId): array
{
    $this->db->query("
        SELECT area_id, area_name, postal_code
        FROM collection_areas
        WHERE council_id = :council_id
        AND status = 'ACTIVE'
        ORDER BY postal_code ASC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function getCollectorsForCouncil(int $councilId): array
{
    $this->db->query("
        SELECT
            u.user_id,
            u.full_name,
            u.email,
            cp.employee_no,
            cp.availability_status
        FROM collector_profiles cp
        INNER JOIN users u ON cp.user_id = u.user_id
        WHERE cp.council_id = :council_id
        AND u.role = 'COLLECTOR'
        AND u.status = 'ACTIVE'
        AND cp.availability_status IN ('AVAILABLE', 'ASSIGNED')
        ORDER BY cp.availability_status ASC, u.full_name ASC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function getVehiclesForCouncil(int $councilId): array
{
    $this->db->query("
        SELECT vehicle_id, vehicle_no, vehicle_type, capacity_kg, status
        FROM vehicles
        WHERE council_id = :council_id
        AND status IN ('AVAILABLE', 'ASSIGNED')
        ORDER BY status ASC, vehicle_no ASC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function getApprovedRequestsForRoutePlanning(int $councilId): array
{
    $this->db->query("
        SELECT
            r.request_id,
            r.area_id,
            r.pickup_address,
            r.contact_phone,
            r.status,
            u.full_name AS public_user_name,
            ca.area_name,
            ca.postal_code,
            acd.collection_date,
            COUNT(ri.request_item_id) AS item_count,
            COALESCE(SUM(ri.estimated_weight_kg), 0) AS total_estimated_weight
        FROM ewaste_requests r
        INNER JOIN users u ON r.public_user_id = u.user_id
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
        LEFT JOIN request_items ri ON r.request_id = ri.request_id
        LEFT JOIN route_stops rs ON r.request_id = rs.request_id
        WHERE ca.council_id = :council_id
        AND r.status = 'APPROVED'
        AND rs.stop_id IS NULL
        GROUP BY
            r.request_id,
            r.area_id,
            r.pickup_address,
            r.contact_phone,
            r.status,
            u.full_name,
            ca.area_name,
            ca.postal_code,
            acd.collection_date
        ORDER BY acd.collection_date ASC, ca.postal_code ASC, r.created_at ASC
    ");

    $this->db->bind(':council_id', $councilId);

    return $this->db->resultSet();
}

public function findCampaignForCouncil(int $campaignId, int $councilId): mixed
{
    $this->db->query("
        SELECT *
        FROM collection_campaigns
        WHERE campaign_id = :campaign_id
        AND council_id = :council_id
        LIMIT 1
    ");

    $this->db->bind(':campaign_id', $campaignId);
    $this->db->bind(':council_id', $councilId);

    return $this->db->single();
}

public function findAreaForCouncil(int $areaId, int $councilId): mixed
{
    $this->db->query("
        SELECT *
        FROM collection_areas
        WHERE area_id = :area_id
        AND council_id = :council_id
        AND status = 'ACTIVE'
        LIMIT 1
    ");

    $this->db->bind(':area_id', $areaId);
    $this->db->bind(':council_id', $councilId);

    return $this->db->single();
}

public function findCollectorForCouncil(int $collectorId, int $councilId): mixed
{
    $this->db->query("
        SELECT
            u.user_id,
            u.full_name,
            cp.collector_profile_id,
            cp.council_id,
            cp.availability_status
        FROM users u
        INNER JOIN collector_profiles cp ON u.user_id = cp.user_id
        WHERE u.user_id = :collector_id
        AND cp.council_id = :council_id
        AND u.role = 'COLLECTOR'
        AND u.status = 'ACTIVE'
        LIMIT 1
    ");

    $this->db->bind(':collector_id', $collectorId);
    $this->db->bind(':council_id', $councilId);

    return $this->db->single();
}

public function findVehicleForCouncil(int $vehicleId, int $councilId): mixed
{
    $this->db->query("
        SELECT *
        FROM vehicles
        WHERE vehicle_id = :vehicle_id
        AND council_id = :council_id
        LIMIT 1
    ");

    $this->db->bind(':vehicle_id', $vehicleId);
    $this->db->bind(':council_id', $councilId);

    return $this->db->single();
}

public function validateApprovedRequestsForRoute(
    array $requestIds,
    int $councilId,
    int $areaId,
    string $collectionDate
): array {
    if (empty($requestIds)) {
        return [];
    }

    $placeholders = [];
    foreach ($requestIds as $index => $requestId) {
        $placeholders[] = ':request_id_' . $index;
    }

    $inSql = implode(',', $placeholders);

    $this->db->query("
        SELECT
            r.request_id,
            r.status,
            r.area_id,
            acd.collection_date,
            ca.council_id
        FROM ewaste_requests r
        INNER JOIN collection_areas ca ON r.area_id = ca.area_id
        INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
        LEFT JOIN route_stops rs ON r.request_id = rs.request_id
        WHERE r.request_id IN ($inSql)
        AND ca.council_id = :council_id
        AND r.area_id = :area_id
        AND acd.collection_date = :collection_date
        AND r.status = 'APPROVED'
        AND rs.stop_id IS NULL
    ");

    foreach ($requestIds as $index => $requestId) {
        $this->db->bind(':request_id_' . $index, (int) $requestId);
    }

    $this->db->bind(':council_id', $councilId);
    $this->db->bind(':area_id', $areaId);
    $this->db->bind(':collection_date', $collectionDate);

    return $this->db->resultSet();
}

public function createRouteWithStops(array $data): bool
{
    try {
        $this->db->beginTransaction();

        $status = $data['collector_id'] && $data['vehicle_id'] ? 'ASSIGNED' : 'PLANNED';

        $this->db->query("
            INSERT INTO collection_routes
                (
                    campaign_id,
                    area_id,
                    route_name,
                    collection_date,
                    collector_id,
                    vehicle_id,
                    status
                )
            VALUES
                (
                    :campaign_id,
                    :area_id,
                    :route_name,
                    :collection_date,
                    :collector_id,
                    :vehicle_id,
                    :status
                )
        ");

        $this->db->bind(':campaign_id', $data['campaign_id']);
        $this->db->bind(':area_id', $data['area_id']);
        $this->db->bind(':route_name', $data['route_name']);
        $this->db->bind(':collection_date', $data['collection_date']);
        $this->db->bind(':collector_id', $data['collector_id']);
        $this->db->bind(':vehicle_id', $data['vehicle_id']);
        $this->db->bind(':status', $status);

        $this->db->execute();

        $routeId = (int) $this->db->lastInsertId();

        $stopOrder = 1;

        foreach ($data['request_ids'] as $requestId) {
            $this->db->query("
                INSERT INTO route_stops
                    (route_id, request_id, stop_order, stop_status)
                VALUES
                    (:route_id, :request_id, :stop_order, 'PENDING')
            ");

            $this->db->bind(':route_id', $routeId);
            $this->db->bind(':request_id', (int) $requestId);
            $this->db->bind(':stop_order', $stopOrder);
            $this->db->execute();

            $this->db->query("
                UPDATE ewaste_requests
                SET status = 'ASSIGNED'
                WHERE request_id = :request_id
            ");

            $this->db->bind(':request_id', (int) $requestId);
            $this->db->execute();

            $this->db->query("
                INSERT INTO request_status_history
                    (request_id, changed_by, old_status, new_status, note)
                VALUES
                    (:request_id, :changed_by, 'APPROVED', 'ASSIGNED', :note)
            ");

            $this->db->bind(':request_id', (int) $requestId);
            $this->db->bind(':changed_by', $data['created_by']);
            $this->db->bind(':note', 'Request assigned to route: ' . $data['route_name']);
            $this->db->execute();

            $stopOrder++;
        }

        if (!empty($data['collector_id'])) {
            $this->db->query("
                UPDATE collector_profiles
                SET availability_status = 'ASSIGNED'
                WHERE user_id = :collector_id
            ");

            $this->db->bind(':collector_id', $data['collector_id']);
            $this->db->execute();
        }

        if (!empty($data['vehicle_id'])) {
            $this->db->query("
                UPDATE vehicles
                SET status = 'ASSIGNED'
                WHERE vehicle_id = :vehicle_id
            ");

            $this->db->bind(':vehicle_id', $data['vehicle_id']);
            $this->db->execute();
        }

        $this->db->query("
            INSERT INTO audit_logs
                (user_id, action, description)
            VALUES
                (:user_id, 'CREATE_COLLECTION_ROUTE', :description)
        ");

        $this->db->bind(':user_id', $data['created_by']);
        $this->db->bind(':description', 'Created route ID ' . $routeId . ' with ' . count($data['request_ids']) . ' stops.');
        $this->db->execute();

        $this->db->commit();

        return true;
    } catch (Throwable $e) {
        $this->db->rollBack();
        return false;
    }
}

public function getRouteByIdForCouncil(int $routeId, int $councilId): mixed
{
    $this->db->query("
        SELECT
            cr.*,
            cc.campaign_name,
            ca.area_name,
            ca.postal_code,
            u.full_name AS collector_name,
            u.email AS collector_email,
            v.vehicle_no,
            v.vehicle_type,
            v.capacity_kg
        FROM collection_routes cr
        INNER JOIN collection_campaigns cc ON cr.campaign_id = cc.campaign_id
        INNER JOIN collection_areas ca ON cr.area_id = ca.area_id
        LEFT JOIN users u ON cr.collector_id = u.user_id
        LEFT JOIN vehicles v ON cr.vehicle_id = v.vehicle_id
        WHERE cr.route_id = :route_id
        AND cc.council_id = :council_id
        LIMIT 1
    ");

    $this->db->bind(':route_id', $routeId);
    $this->db->bind(':council_id', $councilId);

    return $this->db->single();
}

public function getRouteStops(int $routeId): array
{
    $this->db->query("
        SELECT
            rs.*,
            r.pickup_address,
            r.contact_phone,
            r.status AS request_status,
            u.full_name AS public_user_name,
            acd.collection_date,
            COUNT(ri.request_item_id) AS item_count,
            COALESCE(SUM(ri.estimated_weight_kg), 0) AS total_estimated_weight
        FROM route_stops rs
        INNER JOIN ewaste_requests r ON rs.request_id = r.request_id
        INNER JOIN users u ON r.public_user_id = u.user_id
        INNER JOIN area_collection_dates acd ON r.preferred_date_id = acd.date_id
        LEFT JOIN request_items ri ON r.request_id = ri.request_id
        WHERE rs.route_id = :route_id
        GROUP BY
            rs.stop_id,
            rs.route_id,
            rs.request_id,
            rs.stop_order,
            rs.stop_status,
            rs.collector_note,
            rs.updated_at,
            r.pickup_address,
            r.contact_phone,
            r.status,
            u.full_name,
            acd.collection_date
        ORDER BY rs.stop_order ASC
    ");

    $this->db->bind(':route_id', $routeId);

    return $this->db->resultSet();
}
}