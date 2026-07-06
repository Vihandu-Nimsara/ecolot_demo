<?php

class EWasteRequest
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getPublicProfile(int $userId): mixed
    {
        $this->db->query("
            SELECT 
                pup.*,
                u.phone,
                ca.area_name,
                ca.postal_code,
                lc.council_name
            FROM public_user_profiles pup
            INNER JOIN users u ON pup.user_id = u.user_id
            LEFT JOIN collection_areas ca ON pup.area_id = ca.area_id
            LEFT JOIN local_councils lc ON ca.council_id = lc.council_id
            WHERE pup.user_id = :user_id
            LIMIT 1
        ");

        $this->db->bind(':user_id', $userId);

        return $this->db->single();
    }

    public function getOpenDatesForArea(int $areaId): array
    {
        $this->db->query("
            SELECT 
                date_id,
                collection_date,
                max_requests,
                status
            FROM area_collection_dates
            WHERE area_id = :area_id
            AND status = 'OPEN'
            AND collection_date >= CURDATE()
            ORDER BY collection_date ASC
        ");

        $this->db->bind(':area_id', $areaId);

        return $this->db->resultSet();
    }

    public function findOpenDateByIdForArea(int $dateId, int $areaId): mixed
    {
        $this->db->query("
            SELECT *
            FROM area_collection_dates
            WHERE date_id = :date_id
            AND area_id = :area_id
            AND status = 'OPEN'
            AND collection_date >= CURDATE()
            LIMIT 1
        ");

        $this->db->bind(':date_id', $dateId);
        $this->db->bind(':area_id', $areaId);

        return $this->db->single();
    }

    public function getAvailableItems(): array
    {
        $this->db->query("
            SELECT 
                i.item_id,
                i.item_name,
                i.collection_status,
                i.default_risk_level,
                c.category_name
            FROM ewaste_items i
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            WHERE i.collection_status IN ('ACCEPTED', 'REVIEW_REQUIRED')
            AND c.status = 'ACTIVE'
            ORDER BY c.category_name ASC, i.item_name ASC
        ");

        return $this->db->resultSet();
    }

    public function getItemById(int $itemId): mixed
    {
        $this->db->query("
            SELECT 
                i.*,
                c.category_name
            FROM ewaste_items i
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            WHERE i.item_id = :item_id
            LIMIT 1
        ");

        $this->db->bind(':item_id', $itemId);

        return $this->db->single();
    }

    public function createRequestWithItems(int $userId, array $data): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                INSERT INTO ewaste_requests
                    (
                        public_user_id,
                        area_id,
                        preferred_date_id,
                        pickup_address,
                        contact_phone,
                        special_note,
                        status
                    )
                VALUES
                    (
                        :public_user_id,
                        :area_id,
                        :preferred_date_id,
                        :pickup_address,
                        :contact_phone,
                        :special_note,
                        'SUBMITTED'
                    )
            ");

            $this->db->bind(':public_user_id', $userId);
            $this->db->bind(':area_id', $data['area_id']);
            $this->db->bind(':preferred_date_id', $data['preferred_date_id']);
            $this->db->bind(':pickup_address', $data['pickup_address']);
            $this->db->bind(':contact_phone', $data['contact_phone']);
            $this->db->bind(':special_note', $data['special_note']);

            $this->db->execute();

            $requestId = (int) $this->db->lastInsertId();

            $this->db->query("
                INSERT INTO request_status_history
                    (request_id, changed_by, old_status, new_status, note)
                VALUES
                    (:request_id, :changed_by, NULL, 'SUBMITTED', 'Request submitted by public user.')
            ");

            $this->db->bind(':request_id', $requestId);
            $this->db->bind(':changed_by', $userId);
            $this->db->execute();

            foreach ($data['items'] as $item) {
                $this->db->query("
                    INSERT INTO request_items
                        (
                            request_id,
                            item_id,
                            quantity,
                            estimated_weight_kg,
                            condition_status,
                            risk_flag,
                            note
                        )
                    VALUES
                        (
                            :request_id,
                            :item_id,
                            :quantity,
                            :estimated_weight_kg,
                            :condition_status,
                            :risk_flag,
                            :note
                        )
                ");

                $this->db->bind(':request_id', $requestId);
                $this->db->bind(':item_id', $item['item_id']);
                $this->db->bind(':quantity', $item['quantity']);
                $this->db->bind(':estimated_weight_kg', $item['estimated_weight_kg']);
                $this->db->bind(':condition_status', $item['condition_status']);
                $this->db->bind(':risk_flag', $item['risk_flag']);
                $this->db->bind(':note', $item['note']);

                $this->db->execute();

                $requestItemId = (int) $this->db->lastInsertId();

                if ($item['should_flag']) {
                    $this->db->query("
                        INSERT INTO flagged_items
                            (
                                request_item_id,
                                pickup_item_id,
                                flagged_by,
                                flag_reason,
                                risk_level,
                                review_status
                            )
                        VALUES
                            (
                                :request_item_id,
                                NULL,
                                :flagged_by,
                                :flag_reason,
                                :risk_level,
                                'PENDING'
                            )
                    ");

                    $this->db->bind(':request_item_id', $requestItemId);
                    $this->db->bind(':flagged_by', $userId);
                    $this->db->bind(':flag_reason', $item['flag_reason']);
                    $this->db->bind(':risk_level', $item['risk_level']);

                    $this->db->execute();
                }
            }

            $this->db->query("
                INSERT INTO audit_logs
                    (user_id, action, description)
                VALUES
                    (:user_id, 'SUBMIT_EWASTE_REQUEST', :description)
            ");

            $this->db->bind(':user_id', $userId);
            $this->db->bind(':description', 'Public user submitted e-waste request ID: ' . $requestId);
            $this->db->execute();

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getMyRequests(int $userId): array
    {
        $this->db->query("
            SELECT 
                r.request_id,
                r.status,
                r.pickup_address,
                r.contact_phone,
                r.created_at,
                d.collection_date,
                ca.area_name,
                ca.postal_code,
                COUNT(ri.request_item_id) AS item_count,
                COALESCE(SUM(ri.estimated_weight_kg), 0) AS total_estimated_weight
            FROM ewaste_requests r
            INNER JOIN area_collection_dates d ON r.preferred_date_id = d.date_id
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            LEFT JOIN request_items ri ON r.request_id = ri.request_id
            WHERE r.public_user_id = :user_id
            GROUP BY 
                r.request_id,
                r.status,
                r.pickup_address,
                r.contact_phone,
                r.created_at,
                d.collection_date,
                ca.area_name,
                ca.postal_code
            ORDER BY r.created_at DESC
        ");

        $this->db->bind(':user_id', $userId);

        return $this->db->resultSet();
    }

    public function getRequestByIdForUser(int $requestId, int $userId): mixed
    {
        $this->db->query("
            SELECT 
                r.*,
                d.collection_date,
                ca.area_name,
                ca.postal_code
            FROM ewaste_requests r
            INNER JOIN area_collection_dates d ON r.preferred_date_id = d.date_id
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            WHERE r.request_id = :request_id
            AND r.public_user_id = :user_id
            LIMIT 1
        ");

        $this->db->bind(':request_id', $requestId);
        $this->db->bind(':user_id', $userId);

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

    public function getDashboardStats(int $userId): mixed
    {
        $this->db->query("
            SELECT
                COUNT(*) AS total_requests,
                SUM(status = 'SUBMITTED') AS submitted_requests,
                SUM(status = 'APPROVED') AS approved_requests,
                SUM(status = 'ASSIGNED') AS assigned_requests,
                SUM(status = 'COLLECTED') AS collected_requests,
                SUM(status = 'COMPLETED') AS completed_requests
            FROM ewaste_requests
            WHERE public_user_id = :user_id
        ");

        $this->db->bind(':user_id', $userId);

        return $this->db->single();
    }
}