<?php

class RecyclerWorkflow
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getRecyclerProfile(int $userId): mixed
    {
        $this->db->query("
            SELECT
                rp.*,
                u.full_name,
                u.email,
                u.phone
            FROM recycler_profiles rp
            INNER JOIN users u ON rp.user_id = u.user_id
            WHERE rp.user_id = :user_id
            LIMIT 1
        ");

        $this->db->bind(':user_id', $userId);

        return $this->db->single();
    }

    public function getRecyclerCapabilities(int $recyclerProfileId): array
    {
        $this->db->query("
            SELECT
                rc.*,
                c.category_name
            FROM recycler_capabilities rc
            INNER JOIN ewaste_categories c ON rc.category_id = c.category_id
            WHERE rc.recycler_profile_id = :recycler_profile_id
            AND rc.status = 'ACTIVE'
            AND c.status = 'ACTIVE'
            ORDER BY c.category_name ASC
        ");

        $this->db->bind(':recycler_profile_id', $recyclerProfileId);

        return $this->db->resultSet();
    }

    public function getDashboardStats(int $recyclerProfileId): mixed
{
    $this->db->query("
        SELECT COUNT(*) AS my_bids
        FROM bids
        WHERE recycler_profile_id = :recycler_profile_id
    ");

    $this->db->bind(':recycler_profile_id', $recyclerProfileId);
    $bidStats = $this->db->single();

    $this->db->query("
        SELECT COUNT(*) AS winning_bids
        FROM bids
        WHERE recycler_profile_id = :recycler_profile_id
        AND status = 'WINNING_BID'
    ");

    $this->db->bind(':recycler_profile_id', $recyclerProfileId);
    $winningStats = $this->db->single();

    $this->db->query("
        SELECT
            COUNT(*) AS my_awarded_elots,
            SUM(status = 'AWARDED') AS awaiting_handover,
            SUM(status = 'HANDED_OVER') AS handed_over,
            SUM(status = 'PROCESSING') AS processing,
            SUM(status = 'COMPLETED') AS completed
        FROM elots
        WHERE winner_recycler_profile_id = :recycler_profile_id
    ");

    $this->db->bind(':recycler_profile_id', $recyclerProfileId);
    $elotStats = $this->db->single();

    $eligibleElots = $this->getEligibleOpenElots($recyclerProfileId);

    return (object) [
        'eligible_open_elots' => count($eligibleElots),
        'my_bids' => $bidStats->my_bids ?? 0,
        'winning_bids' => $winningStats->winning_bids ?? 0,
        'my_awarded_elots' => $elotStats->my_awarded_elots ?? 0,
        'awaiting_handover' => $elotStats->awaiting_handover ?? 0,
        'handed_over' => $elotStats->handed_over ?? 0,
        'processing' => $elotStats->processing ?? 0,
        'completed' => $elotStats->completed ?? 0
    ];
}

    public function getMyAwardedElots(int $recyclerProfileId): array
{
    $this->db->query("
        SELECT
            e.*,
            c.category_name,
            lc.council_name,
            b.bid_amount,
            b.submitted_at AS bid_submitted_at,
            COUNT(ei.elot_item_id) AS item_count,
            COALESCE(SUM(ei.quantity), 0) AS total_quantity,
            COALESCE(SUM(ei.weight_kg), 0) AS calculated_weight
        FROM elots e
        INNER JOIN ewaste_categories c ON e.category_id = c.category_id
        INNER JOIN local_councils lc ON e.council_id = lc.council_id
        INNER JOIN bids b 
            ON b.elot_id = e.elot_id
            AND b.recycler_profile_id = :recycler_profile_id
            AND b.status = 'WINNING_BID'
        LEFT JOIN elot_items ei ON e.elot_id = ei.elot_id
        WHERE e.winner_recycler_profile_id = :recycler_profile_id_2
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
            lc.council_name,
            b.bid_amount,
            b.submitted_at
        ORDER BY e.created_at DESC
    ");

    $this->db->bind(':recycler_profile_id', $recyclerProfileId);
    $this->db->bind(':recycler_profile_id_2', $recyclerProfileId);

    return $this->db->resultSet();
}

public function getMyAwardedElotById(int $elotId, int $recyclerProfileId): mixed
{
    $this->db->query("
        SELECT
            e.*,
            c.category_name,
            lc.council_name,
            b.bid_amount,
            b.bid_note,
            b.submitted_at AS bid_submitted_at,
            COUNT(ei.elot_item_id) AS item_count,
            COALESCE(SUM(ei.quantity), 0) AS total_quantity,
            COALESCE(SUM(ei.weight_kg), 0) AS calculated_weight
        FROM elots e
        INNER JOIN ewaste_categories c ON e.category_id = c.category_id
        INNER JOIN local_councils lc ON e.council_id = lc.council_id
        INNER JOIN bids b 
            ON b.elot_id = e.elot_id
            AND b.recycler_profile_id = :recycler_profile_id
            AND b.status = 'WINNING_BID'
        LEFT JOIN elot_items ei ON e.elot_id = ei.elot_id
        WHERE e.elot_id = :elot_id
        AND e.winner_recycler_profile_id = :recycler_profile_id_2
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
            lc.council_name,
            b.bid_amount,
            b.bid_note,
            b.submitted_at
        LIMIT 1
    ");

    $this->db->bind(':recycler_profile_id', $recyclerProfileId);
    $this->db->bind(':recycler_profile_id_2', $recyclerProfileId);
    $this->db->bind(':elot_id', $elotId);

    return $this->db->single();
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

public function updateProcessingStatus(
    int $elotId,
    int $recyclerProfileId,
    int $userId,
    string $newStatus,
    string $note
): bool {
    try {
        $this->db->beginTransaction();

        $this->db->query("
            SELECT *
            FROM elots
            WHERE elot_id = :elot_id
            AND winner_recycler_profile_id = :recycler_profile_id
            LIMIT 1
            FOR UPDATE
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':recycler_profile_id', $recyclerProfileId);

        $elot = $this->db->single();

        if (!$elot) {
            $this->db->rollBack();
            return false;
        }

        $allowedTransition = false;

        if ($elot->status === 'HANDED_OVER' && $newStatus === 'PROCESSING') {
            $allowedTransition = true;
        }

        if ($elot->status === 'PROCESSING' && $newStatus === 'COMPLETED') {
            $allowedTransition = true;
        }

        if (!$allowedTransition) {
            $this->db->rollBack();
            return false;
        }

        $this->db->query("
            UPDATE elots
            SET status = :status
            WHERE elot_id = :elot_id
        ");

        $this->db->bind(':status', $newStatus);
        $this->db->bind(':elot_id', $elotId);
        $this->db->execute();

        $this->db->query("
            INSERT INTO elot_status_history
                (elot_id, changed_by, old_status, new_status, note)
            VALUES
                (:elot_id, :changed_by, :old_status, :new_status, :note)
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':changed_by', $userId);
        $this->db->bind(':old_status', $elot->status);
        $this->db->bind(':new_status', $newStatus);
        $this->db->bind(':note', $note);
        $this->db->execute();

        $this->db->query("
            INSERT INTO audit_logs
                (user_id, action, description)
            VALUES
                (:user_id, 'UPDATE_ELOT_PROCESSING_STATUS', :description)
        ");

        $this->db->bind(':user_id', $userId);
        $this->db->bind(
            ':description',
            'Recycler updated E-Lot ID ' . $elotId . ' from ' . $elot->status . ' to ' . $newStatus
        );
        $this->db->execute();

        $this->db->commit();

        return true;
    } catch (Throwable $e) {
        $this->db->rollBack();
        return false;
    }
}

    public function getEligibleOpenElots(int $recyclerProfileId): array
    {
        $this->db->query("
            SELECT
                e.*,
                c.category_name,
                lc.council_name,
                rc.can_handle_high_risk,
                COUNT(ei.elot_item_id) AS item_count,
                COALESCE(SUM(ei.quantity), 0) AS total_quantity,
                COALESCE(SUM(ei.weight_kg), 0) AS calculated_weight,
                MAX(
                    CASE 
                        WHEN i.default_risk_level = 'HIGH' OR pi.condition_status = 'LEAKING'
                        THEN 1
                        ELSE 0
                    END
                ) AS has_high_risk_item,
                b.bid_id AS my_bid_id,
                b.bid_amount AS my_bid_amount,
                b.status AS my_bid_status
            FROM elots e
            INNER JOIN ewaste_categories c ON e.category_id = c.category_id
            INNER JOIN local_councils lc ON e.council_id = lc.council_id
            INNER JOIN recycler_capabilities rc 
                ON rc.category_id = e.category_id
                AND rc.recycler_profile_id = :recycler_profile_id
                AND rc.status = 'ACTIVE'
            LEFT JOIN elot_items ei ON e.elot_id = ei.elot_id
            LEFT JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id
            LEFT JOIN ewaste_items i ON pi.item_id = i.item_id
            LEFT JOIN bids b 
                ON b.elot_id = e.elot_id
                AND b.recycler_profile_id = :recycler_profile_id_2
            WHERE e.status = 'OPEN_FOR_BIDDING'
            AND e.bidding_start <= NOW()
            AND e.bidding_end >= NOW()
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
                lc.council_name,
                rc.can_handle_high_risk,
                b.bid_id,
                b.bid_amount,
                b.status
            HAVING has_high_risk_item = 0 OR rc.can_handle_high_risk = 1
            ORDER BY e.bidding_end ASC, e.created_at DESC
        ");

        $this->db->bind(':recycler_profile_id', $recyclerProfileId);
        $this->db->bind(':recycler_profile_id_2', $recyclerProfileId);

        return $this->db->resultSet();
    }

    public function getEligibleElotById(int $elotId, int $recyclerProfileId): mixed
    {
        $this->db->query("
            SELECT
                e.*,
                c.category_name,
                lc.council_name,
                rc.can_handle_high_risk,
                COUNT(ei.elot_item_id) AS item_count,
                COALESCE(SUM(ei.quantity), 0) AS total_quantity,
                COALESCE(SUM(ei.weight_kg), 0) AS calculated_weight,
                MAX(
                    CASE 
                        WHEN i.default_risk_level = 'HIGH' OR pi.condition_status = 'LEAKING'
                        THEN 1
                        ELSE 0
                    END
                ) AS has_high_risk_item
            FROM elots e
            INNER JOIN ewaste_categories c ON e.category_id = c.category_id
            INNER JOIN local_councils lc ON e.council_id = lc.council_id
            INNER JOIN recycler_capabilities rc 
                ON rc.category_id = e.category_id
                AND rc.recycler_profile_id = :recycler_profile_id
                AND rc.status = 'ACTIVE'
            LEFT JOIN elot_items ei ON e.elot_id = ei.elot_id
            LEFT JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id
            LEFT JOIN ewaste_items i ON pi.item_id = i.item_id
            WHERE e.elot_id = :elot_id
            AND e.status = 'OPEN_FOR_BIDDING'
            AND e.bidding_start <= NOW()
            AND e.bidding_end >= NOW()
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
                lc.council_name,
                rc.can_handle_high_risk
            HAVING has_high_risk_item = 0 OR rc.can_handle_high_risk = 1
            LIMIT 1
        ");

        $this->db->bind(':recycler_profile_id', $recyclerProfileId);
        $this->db->bind(':elot_id', $elotId);

        return $this->db->single();
    }

    public function getElotItemsForRecycler(int $elotId): array
    {
        $this->db->query("
            SELECT
                ei.*,
                pi.pickup_id,
                pi.item_id,
                pi.condition_status,
                pi.note AS pickup_item_note,
                i.item_name,
                i.default_risk_level,
                i.collection_status,
                c.category_name
            FROM elot_items ei
            INNER JOIN pickup_items pi ON ei.pickup_item_id = pi.pickup_item_id
            INNER JOIN ewaste_items i ON pi.item_id = i.item_id
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            WHERE ei.elot_id = :elot_id
            ORDER BY c.category_name ASC, i.item_name ASC
        ");

        $this->db->bind(':elot_id', $elotId);

        return $this->db->resultSet();
    }

    public function getExistingBid(int $elotId, int $recyclerProfileId): mixed
    {
        $this->db->query("
            SELECT *
            FROM bids
            WHERE elot_id = :elot_id
            AND recycler_profile_id = :recycler_profile_id
            LIMIT 1
        ");

        $this->db->bind(':elot_id', $elotId);
        $this->db->bind(':recycler_profile_id', $recyclerProfileId);

        return $this->db->single();
    }

    public function createBid(int $userId, int $elotId, int $recyclerProfileId, float $bidAmount, string $bidNote): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                INSERT INTO bids
                    (
                        elot_id,
                        recycler_profile_id,
                        bid_amount,
                        bid_note,
                        status
                    )
                VALUES
                    (
                        :elot_id,
                        :recycler_profile_id,
                        :bid_amount,
                        :bid_note,
                        'SUBMITTED'
                    )
            ");

            $this->db->bind(':elot_id', $elotId);
            $this->db->bind(':recycler_profile_id', $recyclerProfileId);
            $this->db->bind(':bid_amount', $bidAmount);
            $this->db->bind(':bid_note', $bidNote);

            $this->db->execute();

            $bidId = (int) $this->db->lastInsertId();

            $this->db->query("
                INSERT INTO audit_logs
                    (user_id, action, description)
                VALUES
                    (:user_id, 'SUBMIT_ELOT_BID', :description)
            ");

            $this->db->bind(':user_id', $userId);
            $this->db->bind(':description', 'Submitted bid ID ' . $bidId . ' for E-Lot ID ' . $elotId);
            $this->db->execute();

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getMyBids(int $recyclerProfileId): array
    {
        $this->db->query("
            SELECT
                b.*,
                e.elot_code,
                e.title AS elot_title,
                e.status AS elot_status,
                e.bidding_start,
                e.bidding_end,
                c.category_name,
                lc.council_name
            FROM bids b
            INNER JOIN elots e ON b.elot_id = e.elot_id
            INNER JOIN ewaste_categories c ON e.category_id = c.category_id
            INNER JOIN local_councils lc ON e.council_id = lc.council_id
            WHERE b.recycler_profile_id = :recycler_profile_id
            ORDER BY b.submitted_at DESC
        ");

        $this->db->bind(':recycler_profile_id', $recyclerProfileId);

        return $this->db->resultSet();
    }
}