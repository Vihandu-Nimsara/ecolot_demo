<?php

class CollectorWorkflow
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getCollectorProfile(int $userId): mixed
    {
        $this->db->query("
            SELECT
                cp.*,
                lc.council_name,
                lc.district,
                lc.province
            FROM collector_profiles cp
            INNER JOIN local_councils lc ON cp.council_id = lc.council_id
            WHERE cp.user_id = :user_id
            LIMIT 1
        ");

        $this->db->bind(':user_id', $userId);

        return $this->db->single();
    }

    public function getDashboardStats(int $collectorId): mixed
    {
        $this->db->query("
            SELECT
                COUNT(DISTINCT cr.route_id) AS total_routes,
                SUM(cr.status = 'ASSIGNED') AS assigned_routes,
                SUM(cr.status = 'IN_PROGRESS') AS in_progress_routes,
                SUM(cr.status = 'COMPLETED') AS completed_routes
            FROM collection_routes cr
            WHERE cr.collector_id = :collector_id
        ");

        $this->db->bind(':collector_id', $collectorId);
        $routeStats = $this->db->single();

        $this->db->query("
            SELECT
                COUNT(rs.stop_id) AS total_stops,
                SUM(rs.stop_status = 'PENDING') AS pending_stops,
                SUM(rs.stop_status = 'COLLECTED') AS collected_stops,
                SUM(rs.stop_status = 'FAILED') AS failed_stops
            FROM route_stops rs
            INNER JOIN collection_routes cr ON rs.route_id = cr.route_id
            WHERE cr.collector_id = :collector_id
        ");

        $this->db->bind(':collector_id', $collectorId);
        $stopStats = $this->db->single();

        $this->db->query("
            SELECT COUNT(*) AS submitted_pickups
            FROM pickup_records
            WHERE collector_id = :collector_id
        ");

        $this->db->bind(':collector_id', $collectorId);
        $pickupStats = $this->db->single();

        return (object) [
            'total_routes' => $routeStats->total_routes ?? 0,
            'assigned_routes' => $routeStats->assigned_routes ?? 0,
            'in_progress_routes' => $routeStats->in_progress_routes ?? 0,
            'completed_routes' => $routeStats->completed_routes ?? 0,
            'total_stops' => $stopStats->total_stops ?? 0,
            'pending_stops' => $stopStats->pending_stops ?? 0,
            'collected_stops' => $stopStats->collected_stops ?? 0,
            'failed_stops' => $stopStats->failed_stops ?? 0,
            'submitted_pickups' => $pickupStats->submitted_pickups ?? 0
        ];
    }

    public function getAssignedRoutes(int $collectorId): array
    {
        $this->db->query("
            SELECT
                cr.*,
                cc.campaign_name,
                ca.area_name,
                ca.postal_code,
                v.vehicle_no,
                v.vehicle_type,
                COUNT(rs.stop_id) AS stop_count,
                SUM(rs.stop_status = 'PENDING') AS pending_count,
                SUM(rs.stop_status = 'COLLECTED') AS collected_count,
                SUM(rs.stop_status = 'FAILED') AS failed_count
            FROM collection_routes cr
            INNER JOIN collection_campaigns cc ON cr.campaign_id = cc.campaign_id
            INNER JOIN collection_areas ca ON cr.area_id = ca.area_id
            LEFT JOIN vehicles v ON cr.vehicle_id = v.vehicle_id
            LEFT JOIN route_stops rs ON cr.route_id = rs.route_id
            WHERE cr.collector_id = :collector_id
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
                v.vehicle_no,
                v.vehicle_type
            ORDER BY cr.collection_date ASC, cr.created_at DESC
        ");

        $this->db->bind(':collector_id', $collectorId);

        return $this->db->resultSet();
    }

    public function getRouteByIdForCollector(int $routeId, int $collectorId): mixed
    {
        $this->db->query("
            SELECT
                cr.*,
                cc.campaign_name,
                ca.area_name,
                ca.postal_code,
                v.vehicle_no,
                v.vehicle_type,
                v.capacity_kg
            FROM collection_routes cr
            INNER JOIN collection_campaigns cc ON cr.campaign_id = cc.campaign_id
            INNER JOIN collection_areas ca ON cr.area_id = ca.area_id
            LEFT JOIN vehicles v ON cr.vehicle_id = v.vehicle_id
            WHERE cr.route_id = :route_id
            AND cr.collector_id = :collector_id
            LIMIT 1
        ");

        $this->db->bind(':route_id', $routeId);
        $this->db->bind(':collector_id', $collectorId);

        return $this->db->single();
    }

    public function getRouteStopsForCollector(int $routeId, int $collectorId): array
    {
        $this->db->query("
            SELECT
                rs.*,
                r.pickup_address,
                r.contact_phone,
                r.status AS request_status,
                u.full_name AS public_user_name,
                COUNT(ri.request_item_id) AS item_count,
                COALESCE(SUM(ri.estimated_weight_kg), 0) AS total_estimated_weight,
                COUNT(pr.pickup_id) AS pickup_record_count
            FROM route_stops rs
            INNER JOIN collection_routes cr ON rs.route_id = cr.route_id
            INNER JOIN ewaste_requests r ON rs.request_id = r.request_id
            INNER JOIN users u ON r.public_user_id = u.user_id
            LEFT JOIN request_items ri ON r.request_id = ri.request_id
            LEFT JOIN pickup_records pr 
                ON pr.request_id = r.request_id 
                AND pr.route_id = cr.route_id
                AND pr.collector_id = cr.collector_id
            WHERE rs.route_id = :route_id
            AND cr.collector_id = :collector_id
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
                u.full_name
            ORDER BY rs.stop_order ASC
        ");

        $this->db->bind(':route_id', $routeId);
        $this->db->bind(':collector_id', $collectorId);

        return $this->db->resultSet();
    }

    public function getStopByIdForCollector(int $stopId, int $collectorId): mixed
    {
        $this->db->query("
            SELECT
                rs.*,
                cr.collector_id,
                cr.route_name,
                cr.collection_date,
                cr.status AS route_status,
                r.status AS request_status,
                r.pickup_address,
                r.contact_phone,
                r.special_note,
                u.full_name AS public_user_name,
                u.email AS public_user_email,
                ca.area_name,
                ca.postal_code
            FROM route_stops rs
            INNER JOIN collection_routes cr ON rs.route_id = cr.route_id
            INNER JOIN ewaste_requests r ON rs.request_id = r.request_id
            INNER JOIN users u ON r.public_user_id = u.user_id
            INNER JOIN collection_areas ca ON r.area_id = ca.area_id
            WHERE rs.stop_id = :stop_id
            AND cr.collector_id = :collector_id
            LIMIT 1
        ");

        $this->db->bind(':stop_id', $stopId);
        $this->db->bind(':collector_id', $collectorId);

        return $this->db->single();
    }

    public function getRequestItemsForStop(int $stopId, int $collectorId): array
    {
        $this->db->query("
            SELECT
                ri.request_item_id,
                ri.request_id,
                ri.item_id,
                ri.quantity,
                ri.estimated_weight_kg,
                ri.condition_status,
                ri.risk_flag,
                ri.note,
                i.item_name,
                i.collection_status,
                i.default_risk_level,
                c.category_name
            FROM route_stops rs
            INNER JOIN collection_routes cr ON rs.route_id = cr.route_id
            INNER JOIN request_items ri ON rs.request_id = ri.request_id
            INNER JOIN ewaste_items i ON ri.item_id = i.item_id
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            WHERE rs.stop_id = :stop_id
            AND cr.collector_id = :collector_id
            ORDER BY c.category_name ASC, i.item_name ASC
        ");

        $this->db->bind(':stop_id', $stopId);
        $this->db->bind(':collector_id', $collectorId);

        return $this->db->resultSet();
    }

    public function pickupRecordExists(int $routeId, int $requestId, int $collectorId): bool
    {
        $this->db->query("
            SELECT pickup_id
            FROM pickup_records
            WHERE route_id = :route_id
            AND request_id = :request_id
            AND collector_id = :collector_id
            LIMIT 1
        ");

        $this->db->bind(':route_id', $routeId);
        $this->db->bind(':request_id', $requestId);
        $this->db->bind(':collector_id', $collectorId);

        return (bool) $this->db->single();
    }

    public function createPickupRecord(int $collectorId, mixed $stop, array $data): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                INSERT INTO pickup_records
                    (
                        request_id,
                        route_id,
                        collector_id,
                        pickup_status,
                        total_collected_weight_kg,
                        collector_note,
                        verification_status
                    )
                VALUES
                    (
                        :request_id,
                        :route_id,
                        :collector_id,
                        :pickup_status,
                        :total_collected_weight_kg,
                        :collector_note,
                        'PENDING'
                    )
            ");

            $this->db->bind(':request_id', $stop->request_id);
            $this->db->bind(':route_id', $stop->route_id);
            $this->db->bind(':collector_id', $collectorId);
            $this->db->bind(':pickup_status', $data['pickup_status']);
            $this->db->bind(':total_collected_weight_kg', $data['total_collected_weight_kg']);
            $this->db->bind(':collector_note', $data['collector_note']);

            $this->db->execute();

            $pickupId = (int) $this->db->lastInsertId();

            foreach ($data['items'] as $item) {
                if ((int) $item['collected_quantity'] <= 0) {
                    continue;
                }

                $this->db->query("
                    INSERT INTO pickup_items
                        (
                            pickup_id,
                            item_id,
                            collected_quantity,
                            collected_weight_kg,
                            condition_status,
                            note
                        )
                    VALUES
                        (
                            :pickup_id,
                            :item_id,
                            :collected_quantity,
                            :collected_weight_kg,
                            :condition_status,
                            :note
                        )
                ");

                $this->db->bind(':pickup_id', $pickupId);
                $this->db->bind(':item_id', $item['item_id']);
                $this->db->bind(':collected_quantity', $item['collected_quantity']);
                $this->db->bind(':collected_weight_kg', $item['collected_weight_kg']);
                $this->db->bind(':condition_status', $item['condition_status']);
                $this->db->bind(':note', $item['note']);
                $this->db->execute();

                $pickupItemId = (int) $this->db->lastInsertId();

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
                                NULL,
                                :pickup_item_id,
                                :flagged_by,
                                :flag_reason,
                                :risk_level,
                                'PENDING'
                            )
                    ");

                    $this->db->bind(':pickup_item_id', $pickupItemId);
                    $this->db->bind(':flagged_by', $collectorId);
                    $this->db->bind(':flag_reason', $item['flag_reason']);
                    $this->db->bind(':risk_level', $item['risk_level']);
                    $this->db->execute();
                }
            }

            $stopStatus = match ($data['pickup_status']) {
                'COLLECTED', 'PARTIALLY_COLLECTED' => 'COLLECTED',
                'NOT_AVAILABLE', 'REJECTED_AT_PICKUP' => 'FAILED',
                default => 'PENDING'
            };

            $newRequestStatus = match ($data['pickup_status']) {
                'COLLECTED' => 'COLLECTED',
                'PARTIALLY_COLLECTED' => 'PARTIALLY_COLLECTED',
                'NOT_AVAILABLE', 'REJECTED_AT_PICKUP' => 'PICKUP_PENDING',
                default => $stop->request_status
            };

            $this->db->query("
                UPDATE route_stops
                SET stop_status = :stop_status,
                    collector_note = :collector_note,
                    updated_at = NOW()
                WHERE stop_id = :stop_id
            ");

            $this->db->bind(':stop_status', $stopStatus);
            $this->db->bind(':collector_note', $data['collector_note']);
            $this->db->bind(':stop_id', $stop->stop_id);
            $this->db->execute();

            $this->db->query("
                UPDATE ewaste_requests
                SET status = :status
                WHERE request_id = :request_id
            ");

            $this->db->bind(':status', $newRequestStatus);
            $this->db->bind(':request_id', $stop->request_id);
            $this->db->execute();

            $this->db->query("
                INSERT INTO request_status_history
                    (request_id, changed_by, old_status, new_status, note)
                VALUES
                    (:request_id, :changed_by, :old_status, :new_status, :note)
            ");

            $this->db->bind(':request_id', $stop->request_id);
            $this->db->bind(':changed_by', $collectorId);
            $this->db->bind(':old_status', $stop->request_status);
            $this->db->bind(':new_status', $newRequestStatus);
            $this->db->bind(':note', 'Collector submitted pickup record. Pickup status: ' . $data['pickup_status']);
            $this->db->execute();

            $this->db->query("
                INSERT INTO audit_logs
                    (user_id, action, description)
                VALUES
                    (:user_id, 'SUBMIT_PICKUP_RECORD', :description)
            ");

            $this->db->bind(':user_id', $collectorId);
            $this->db->bind(':description', 'Pickup record submitted for request ID ' . $stop->request_id . ', route ID ' . $stop->route_id);
            $this->db->execute();

            $this->refreshRouteStatus((int) $stop->route_id, $collectorId);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    private function refreshRouteStatus(int $routeId, int $collectorId): void
    {
        $this->db->query("
            SELECT
                COUNT(*) AS total_stops,
                SUM(stop_status = 'PENDING') AS pending_stops
            FROM route_stops
            WHERE route_id = :route_id
        ");

        $this->db->bind(':route_id', $routeId);
        $stats = $this->db->single();

        $pendingStops = (int) ($stats->pending_stops ?? 0);

        if ($pendingStops === 0) {
            $this->db->query("
                UPDATE collection_routes
                SET status = 'COMPLETED'
                WHERE route_id = :route_id
            ");

            $this->db->bind(':route_id', $routeId);
            $this->db->execute();

            $this->db->query("
                UPDATE collector_profiles
                SET availability_status = 'AVAILABLE'
                WHERE user_id = :collector_id
            ");

            $this->db->bind(':collector_id', $collectorId);
            $this->db->execute();

            $this->db->query("
                UPDATE vehicles v
                INNER JOIN collection_routes cr ON v.vehicle_id = cr.vehicle_id
                SET v.status = 'AVAILABLE'
                WHERE cr.route_id = :route_id
            ");

            $this->db->bind(':route_id', $routeId);
            $this->db->execute();
        } else {
            $this->db->query("
                UPDATE collection_routes
                SET status = 'IN_PROGRESS'
                WHERE route_id = :route_id
                AND status != 'COMPLETED'
            ");

            $this->db->bind(':route_id', $routeId);
            $this->db->execute();
        }
    }
}