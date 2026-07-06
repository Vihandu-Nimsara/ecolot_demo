<?php

class CollectorController extends Controller
{
    private CollectorWorkflow $collectorModel;

    public function __construct()
    {
        $this->collectorModel = $this->model('CollectorWorkflow');
    }

    public function dashboard(): void
    {
        requireRole('COLLECTOR');

        $profile = $this->getCollectorProfileOrRedirect();
        $stats = $this->collectorModel->getDashboardStats((int) $_SESSION['user_id']);

        $this->view('collector/dashboard', [
            'title' => 'Collector Dashboard',
            'profile' => $profile,
            'stats' => $stats
        ]);
    }

    public function routes(): void
    {
        requireRole('COLLECTOR');

        $profile = $this->getCollectorProfileOrRedirect();

        $routes = $this->collectorModel->getAssignedRoutes((int) $_SESSION['user_id']);

        $this->view('collector/routes', [
            'title' => 'Assigned Routes',
            'profile' => $profile,
            'routes' => $routes
        ]);
    }

    public function routeDetails(int $routeId): void
    {
        requireRole('COLLECTOR');

        $collectorId = (int) $_SESSION['user_id'];

        $route = $this->collectorModel->getRouteByIdForCollector($routeId, $collectorId);

        if (!$route) {
            flash('auth_error', 'Route not found or not assigned to you.', 'alert alert-danger');
            $this->redirect('collector/routes');
            return;
        }

        $stops = $this->collectorModel->getRouteStopsForCollector($routeId, $collectorId);

        $this->view('collector/route_details', [
            'title' => 'Route Details',
            'route' => $route,
            'stops' => $stops
        ]);
    }

    public function recordPickup(int $stopId): void
    {
        requireRole('COLLECTOR');

        $collectorId = (int) $_SESSION['user_id'];

        $stop = $this->collectorModel->getStopByIdForCollector($stopId, $collectorId);

        if (!$stop) {
            flash('auth_error', 'Pickup stop not found or not assigned to you.', 'alert alert-danger');
            $this->redirect('collector/routes');
            return;
        }

        if ($this->collectorModel->pickupRecordExists((int) $stop->route_id, (int) $stop->request_id, $collectorId)) {
            flash('auth_error', 'Pickup record already submitted for this stop.', 'alert alert-danger');
            $this->redirect('collector/route-details/' . $stop->route_id);
            return;
        }

        $items = $this->collectorModel->getRequestItemsForStop($stopId, $collectorId);

        $this->view('collector/record_pickup', [
            'title' => 'Record Pickup',
            'stop' => $stop,
            'items' => $items,
            'errors' => [],
            'old' => [
                'pickup_status' => 'COLLECTED',
                'total_collected_weight_kg' => '',
                'collector_note' => '',
                'collected_quantity' => [],
                'collected_weight_kg' => [],
                'condition_status' => [],
                'note' => []
            ]
        ]);
    }

    public function storePickup(int $stopId): void
    {
        requireRole('COLLECTOR');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('collector/record-pickup/' . $stopId);
            return;
        }

        $collectorId = (int) $_SESSION['user_id'];

        $stop = $this->collectorModel->getStopByIdForCollector($stopId, $collectorId);

        if (!$stop) {
            flash('auth_error', 'Pickup stop not found or not assigned to you.', 'alert alert-danger');
            $this->redirect('collector/routes');
            return;
        }

        if ($this->collectorModel->pickupRecordExists((int) $stop->route_id, (int) $stop->request_id, $collectorId)) {
            flash('auth_error', 'Pickup record already submitted for this stop.', 'alert alert-danger');
            $this->redirect('collector/route-details/' . $stop->route_id);
            return;
        }

        $requestItems = $this->collectorModel->getRequestItemsForStop($stopId, $collectorId);

        $old = [
            'pickup_status' => strtoupper(trim($_POST['pickup_status'] ?? '')),
            'total_collected_weight_kg' => trim($_POST['total_collected_weight_kg'] ?? ''),
            'collector_note' => trim($_POST['collector_note'] ?? ''),
            'item_id' => $_POST['item_id'] ?? [],
            'requested_quantity' => $_POST['requested_quantity'] ?? [],
            'collected_quantity' => $_POST['collected_quantity'] ?? [],
            'collected_weight_kg' => $_POST['collected_weight_kg'] ?? [],
            'condition_status' => $_POST['condition_status'] ?? [],
            'note' => $_POST['note'] ?? []
        ];

        $errors = [];
        $normalizedItems = $this->normalizePickupItems($old, $requestItems, $errors);

        $allowedPickupStatuses = [
            'COLLECTED',
            'PARTIALLY_COLLECTED',
            'NOT_AVAILABLE',
            'REJECTED_AT_PICKUP'
        ];

        if (!in_array($old['pickup_status'], $allowedPickupStatuses, true)) {
            $errors['pickup_status'] = 'Please select a valid pickup status.';
        }

        $totalWeight = null;

        if ($old['total_collected_weight_kg'] !== '') {
            $totalWeight = (float) $old['total_collected_weight_kg'];

            if ($totalWeight < 0) {
                $errors['total_collected_weight_kg'] = 'Total collected weight cannot be negative.';
            }
        }

        $requiresCollectedItems = in_array($old['pickup_status'], ['COLLECTED', 'PARTIALLY_COLLECTED'], true);

        if ($requiresCollectedItems) {
            $hasCollectedQuantity = false;

            foreach ($normalizedItems as $item) {
                if ((int) $item['collected_quantity'] > 0) {
                    $hasCollectedQuantity = true;
                    break;
                }
            }

            if (!$hasCollectedQuantity) {
                $errors['items'] = 'For collected or partially collected pickups, at least one collected item quantity is required.';
            }
        }

        if (in_array($old['pickup_status'], ['NOT_AVAILABLE', 'REJECTED_AT_PICKUP'], true) && empty($old['collector_note'])) {
            $errors['collector_note'] = 'Please add a note explaining why the pickup was not completed.';
        }

        if (!empty($errors)) {
            $this->view('collector/record_pickup', [
                'title' => 'Record Pickup',
                'stop' => $stop,
                'items' => $requestItems,
                'errors' => $errors,
                'old' => $old
            ]);
            return;
        }

        $created = $this->collectorModel->createPickupRecord($collectorId, $stop, [
            'pickup_status' => $old['pickup_status'],
            'total_collected_weight_kg' => $totalWeight,
            'collector_note' => $old['collector_note'],
            'items' => $normalizedItems
        ]);

        if (!$created) {
            $this->view('collector/record_pickup', [
                'title' => 'Record Pickup',
                'stop' => $stop,
                'items' => $requestItems,
                'errors' => ['submit' => 'Failed to submit pickup record. Please try again.'],
                'old' => $old
            ]);
            return;
        }

        flash('auth_success', 'Pickup record submitted successfully. It is now pending municipal officer verification.');
        $this->redirect('collector/route-details/' . $stop->route_id);
    }

    private function normalizePickupItems(array $old, array $requestItems, array &$errors): array
    {
        $normalized = [];

        $validRequestItems = [];

        foreach ($requestItems as $requestItem) {
            $validRequestItems[(int) $requestItem->item_id] = $requestItem;
        }

        $itemIds = is_array($old['item_id']) ? $old['item_id'] : [];
        $collectedQuantities = is_array($old['collected_quantity']) ? $old['collected_quantity'] : [];
        $collectedWeights = is_array($old['collected_weight_kg']) ? $old['collected_weight_kg'] : [];
        $conditions = is_array($old['condition_status']) ? $old['condition_status'] : [];
        $notes = is_array($old['note']) ? $old['note'] : [];

        $allowedConditions = ['GOOD', 'DAMAGED', 'BROKEN', 'LEAKING', 'UNKNOWN'];

        foreach ($itemIds as $index => $rawItemId) {
            $itemId = (int) $rawItemId;

            if (!isset($validRequestItems[$itemId])) {
                $errors['items'] = 'One or more submitted items are invalid.';
                continue;
            }

            $requestItem = $validRequestItems[$itemId];

            $quantity = (int) ($collectedQuantities[$index] ?? 0);

            if ($quantity < 0) {
                $errors['items'] = 'Collected quantity cannot be negative.';
                continue;
            }

            if ($quantity > (int) $requestItem->quantity) {
                $errors['items'] = 'Collected quantity cannot be greater than requested quantity.';
                continue;
            }

            $weightValue = trim((string) ($collectedWeights[$index] ?? ''));
            $weight = $weightValue === '' ? null : (float) $weightValue;

            if ($weight !== null && $weight < 0) {
                $errors['items'] = 'Collected item weight cannot be negative.';
                continue;
            }

            $condition = $conditions[$index] ?? 'UNKNOWN';

            if (!in_array($condition, $allowedConditions, true)) {
                $condition = 'UNKNOWN';
            }

            $note = trim((string) ($notes[$index] ?? ''));

            $isBadCondition = in_array($condition, ['DAMAGED', 'BROKEN', 'LEAKING'], true);
            $isRiskItem = $requestItem->collection_status === 'REVIEW_REQUIRED';

            $shouldFlag = $isBadCondition || $isRiskItem;

            $riskLevel = ($condition === 'LEAKING' || $requestItem->default_risk_level === 'HIGH')
                ? 'HIGH'
                : 'MEDIUM';

            $flagReasonParts = [];

            if ($isRiskItem) {
                $flagReasonParts[] = 'Collected item requires officer review.';
            }

            if ($isBadCondition) {
                $flagReasonParts[] = 'Collector reported condition as ' . $condition . '.';
            }

            $normalized[] = [
                'item_id' => $itemId,
                'collected_quantity' => $quantity,
                'collected_weight_kg' => $weight,
                'condition_status' => $condition,
                'note' => $note,
                'should_flag' => $shouldFlag && $quantity > 0,
                'risk_level' => $riskLevel,
                'flag_reason' => implode(' ', $flagReasonParts)
            ];
        }

        return $normalized;
    }

    private function getCollectorProfileOrRedirect(): mixed
    {
        $profile = $this->collectorModel->getCollectorProfile((int) $_SESSION['user_id']);

        if (!$profile) {
            flash('auth_error', 'Collector profile not found.', 'alert alert-danger');
            $this->redirect('auth/login');
            exit;
        }

        return $profile;
    }
}