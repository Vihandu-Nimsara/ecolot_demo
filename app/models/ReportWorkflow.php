<?php

class ReportWorkflow
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getOverviewStats(?int $councilId = null): mixed
    {
        $requestWhere = $councilId !== null ? "WHERE ca.council_id = :council_id" : "";

        $this->db->query("
            SELECT
                COUNT(*) AS total_requests,
                SUM(r.status = 'SUBMITTED') AS submitted_requests,
                SUM(r.status = 'APPROVED') AS approved_requests,
                SUM(r.status = 'ASSIGNED') AS assigned_requests,
                SUM(r.status = 'COLLECTED') AS collected_requests,
                SUM(r.status = 'COMPLETED') AS completed_requests,
                SUM(r.status = 'REJECTED') AS rejected_requests
            FROM ewaste_requests r
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            {$requestWhere}
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        $requestStats = $this->db->single();

        $pickupWhere = $councilId !== null ? "WHERE ca.council_id = :council_id" : "";

        $this->db->query("
            SELECT
                COUNT(*) AS total_pickup_records,
                SUM(pr.verification_status = 'PENDING') AS pending_pickups,
                SUM(pr.verification_status = 'VERIFIED') AS verified_pickups,
                SUM(pr.verification_status = 'REJECTED') AS rejected_pickups
            FROM pickup_records pr
            INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            {$pickupWhere}
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        $pickupStats = $this->db->single();

        $elotWhere = $councilId !== null ? "WHERE council_id = :council_id" : "";

        $this->db->query("
            SELECT
                COUNT(*) AS total_elots,
                SUM(status = 'DRAFT') AS draft_elots,
                SUM(status = 'OPEN_FOR_BIDDING') AS open_elots,
                SUM(status = 'AWARDED') AS awarded_elots,
                SUM(status = 'HANDED_OVER') AS handed_over_elots,
                SUM(status = 'PROCESSING') AS processing_elots,
                SUM(status = 'COMPLETED') AS completed_elots
            FROM elots
            {$elotWhere}
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        $elotStats = $this->db->single();

        $bidWhere = $councilId !== null ? "WHERE e.council_id = :council_id" : "";

        $this->db->query("
            SELECT
                COUNT(b.bid_id) AS total_bids,
                SUM(b.status = 'SUBMITTED') AS submitted_bids,
                SUM(b.status = 'WINNING_BID') AS winning_bids,
                SUM(b.status = 'REJECTED') AS rejected_bids
            FROM bids b
            INNER JOIN elots e ON b.elot_id = e.elot_id
            {$bidWhere}
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        $bidStats = $this->db->single();

        $totalUsers = null;

        if ($councilId === null) {
            $this->db->query("
                SELECT COUNT(*) AS total_users
                FROM users
            ");

            $userStats = $this->db->single();
            $totalUsers = $userStats->total_users ?? 0;
        }

        return (object) [
            'total_users' => $totalUsers,

            'total_requests' => $requestStats->total_requests ?? 0,
            'submitted_requests' => $requestStats->submitted_requests ?? 0,
            'approved_requests' => $requestStats->approved_requests ?? 0,
            'assigned_requests' => $requestStats->assigned_requests ?? 0,
            'collected_requests' => $requestStats->collected_requests ?? 0,
            'completed_requests' => $requestStats->completed_requests ?? 0,
            'rejected_requests' => $requestStats->rejected_requests ?? 0,

            'total_pickup_records' => $pickupStats->total_pickup_records ?? 0,
            'pending_pickups' => $pickupStats->pending_pickups ?? 0,
            'verified_pickups' => $pickupStats->verified_pickups ?? 0,
            'rejected_pickups' => $pickupStats->rejected_pickups ?? 0,

            'total_elots' => $elotStats->total_elots ?? 0,
            'draft_elots' => $elotStats->draft_elots ?? 0,
            'open_elots' => $elotStats->open_elots ?? 0,
            'awarded_elots' => $elotStats->awarded_elots ?? 0,
            'handed_over_elots' => $elotStats->handed_over_elots ?? 0,
            'processing_elots' => $elotStats->processing_elots ?? 0,
            'completed_elots' => $elotStats->completed_elots ?? 0,

            'total_bids' => $bidStats->total_bids ?? 0,
            'submitted_bids' => $bidStats->submitted_bids ?? 0,
            'winning_bids' => $bidStats->winning_bids ?? 0,
            'rejected_bids' => $bidStats->rejected_bids ?? 0
        ];
    }

    public function getRequestStatusCounts(?int $councilId = null): array
    {
        $where = $councilId !== null ? "WHERE ca.council_id = :council_id" : "";

        $this->db->query("
            SELECT
                r.status,
                COUNT(*) AS total
            FROM ewaste_requests r
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            {$where}
            GROUP BY r.status
            ORDER BY total DESC
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        return $this->db->resultSet();
    }

    public function getMonthlyRequestCounts(?int $councilId = null, int $limit = 8): array
    {
        $limit = max(1, min($limit, 24));
        $where = $councilId !== null ? "WHERE ca.council_id = :council_id" : "";

        $this->db->query("
            SELECT
                DATE_FORMAT(r.created_at, '%Y-%m') AS month_label,
                COUNT(*) AS total_requests
            FROM ewaste_requests r
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            {$where}
            GROUP BY DATE_FORMAT(r.created_at, '%Y-%m')
            ORDER BY month_label DESC
            LIMIT {$limit}
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        return $this->db->resultSet();
    }

    public function getCategoryCollectedSummary(?int $councilId = null): array
    {
        $where = $councilId !== null ? "AND ca.council_id = :council_id" : "";

        $this->db->query("
            SELECT
                c.category_name,
                COUNT(pi.pickup_item_id) AS pickup_item_count,
                COALESCE(SUM(pi.collected_quantity), 0) AS total_quantity,
                COALESCE(SUM(pi.collected_weight_kg), 0) AS total_weight
            FROM pickup_items pi
            INNER JOIN pickup_records pr ON pi.pickup_id = pr.pickup_id
            INNER JOIN ewaste_items i ON pi.item_id = i.item_id
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            INNER JOIN ewaste_requests r ON pr.request_id = r.request_id
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            WHERE pr.verification_status = 'VERIFIED'
            {$where}
            GROUP BY c.category_name
            ORDER BY total_weight DESC, total_quantity DESC
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        return $this->db->resultSet();
    }

    public function getElotStatusCounts(?int $councilId = null): array
    {
        $where = $councilId !== null ? "WHERE council_id = :council_id" : "";

        $this->db->query("
            SELECT
                status,
                COUNT(*) AS total
            FROM elots
            {$where}
            GROUP BY status
            ORDER BY total DESC
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        return $this->db->resultSet();
    }

    public function getBidSummary(?int $councilId = null, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $where = $councilId !== null ? "WHERE e.council_id = :council_id" : "";

        $this->db->query("
            SELECT
                e.elot_id,
                e.elot_code,
                e.title,
                e.status AS elot_status,
                c.category_name,
                COUNT(b.bid_id) AS bid_count,
                MAX(b.bid_amount) AS highest_bid,
                AVG(b.bid_amount) AS average_bid
            FROM elots e
            INNER JOIN ewaste_categories c ON e.category_id = c.category_id
            LEFT JOIN bids b ON e.elot_id = b.elot_id
            {$where}
            GROUP BY
                e.elot_id,
                e.elot_code,
                e.title,
                e.status,
                c.category_name
            ORDER BY e.created_at DESC
            LIMIT {$limit}
        ");

        if ($councilId !== null) {
            $this->db->bind(':council_id', $councilId);
        }

        return $this->db->resultSet();
    }

    public function getRecyclerVerificationCounts(): array
    {
        $this->db->query("
            SELECT
                verification_status,
                COUNT(*) AS total
            FROM recycler_profiles
            GROUP BY verification_status
            ORDER BY total DESC
        ");

        return $this->db->resultSet();
    }

    public function getTopRecyclersByBids(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        $this->db->query("
            SELECT
                rp.company_name,
                rp.verification_status,
                COUNT(b.bid_id) AS bid_count,
                SUM(b.status = 'WINNING_BID') AS winning_count,
                COALESCE(SUM(b.bid_amount), 0) AS total_bid_amount
            FROM recycler_profiles rp
            LEFT JOIN bids b ON rp.recycler_profile_id = b.recycler_profile_id
            GROUP BY
                rp.recycler_profile_id,
                rp.company_name,
                rp.verification_status
            ORDER BY bid_count DESC, winning_count DESC
            LIMIT {$limit}
        ");

        return $this->db->resultSet();
    }

    public function getRecentAuditLogs(int $limit = 25): array
    {
        $limit = max(1, min($limit, 100));

        $this->db->query("
            SELECT
                al.*,
                u.full_name,
                u.role
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT {$limit}
        ");

        return $this->db->resultSet();
    }
}