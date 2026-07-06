<?php

class PublicUserController extends Controller
{
    private EWasteRequest $requestModel;

    public function __construct()
    {
        $this->requestModel = $this->model('EWasteRequest');
    }

    public function dashboard(): void
    {
        requireRole('PUBLIC_USER');

        $stats = $this->requestModel->getDashboardStats((int) $_SESSION['user_id']);

        $this->view('public_user/dashboard', [
            'title' => 'Public User Dashboard',
            'stats' => $stats
        ]);
    }

    public function requests(): void
    {
        requireRole('PUBLIC_USER');

        $requests = $this->requestModel->getMyRequests((int) $_SESSION['user_id']);

        $this->view('public_user/requests', [
            'title' => 'My Requests',
            'requests' => $requests
        ]);
    }

    public function createRequest(): void
    {
        requireRole('PUBLIC_USER');

        $userId = (int) $_SESSION['user_id'];

        $profile = $this->requestModel->getPublicProfile($userId);

        if (!$profile || !$profile->area_id) {
            flash('auth_error', 'Your public user profile is incomplete. Please contact admin.', 'alert alert-danger');
            $this->redirect('public-user/dashboard');
            return;
        }

        $dates = $this->requestModel->getOpenDatesForArea((int) $profile->area_id);
        $items = $this->requestModel->getAvailableItems();

        $defaultAddress = trim(
            $profile->address_line1 . ', ' .
            ($profile->address_line2 ? $profile->address_line2 . ', ' : '') .
            $profile->city
        );

        $this->view('public_user/create_request', [
            'title' => 'Submit E-Waste Request',
            'profile' => $profile,
            'dates' => $dates,
            'items' => $items,
            'errors' => [],
            'old' => [
                'pickup_address' => $defaultAddress,
                'contact_phone' => $profile->phone ?? '',
                'special_note' => ''
            ]
        ]);
    }

    public function storeRequest(): void
    {
        requireRole('PUBLIC_USER');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('public-user/create-request');
            return;
        }

        $userId = (int) $_SESSION['user_id'];

        $profile = $this->requestModel->getPublicProfile($userId);

        if (!$profile || !$profile->area_id) {
            flash('auth_error', 'Your public user profile is incomplete.', 'alert alert-danger');
            $this->redirect('public-user/dashboard');
            return;
        }

        $dates = $this->requestModel->getOpenDatesForArea((int) $profile->area_id);
        $availableItems = $this->requestModel->getAvailableItems();

        $old = [
            'preferred_date_id' => (int) ($_POST['preferred_date_id'] ?? 0),
            'pickup_address' => trim($_POST['pickup_address'] ?? ''),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'special_note' => trim($_POST['special_note'] ?? ''),
            'item_id' => $_POST['item_id'] ?? [],
            'quantity' => $_POST['quantity'] ?? [],
            'estimated_weight_kg' => $_POST['estimated_weight_kg'] ?? [],
            'condition_status' => $_POST['condition_status'] ?? [],
            'note' => $_POST['note'] ?? []
        ];

        $errors = [];

        if (empty($old['preferred_date_id'])) {
            $errors['preferred_date_id'] = 'Please select a collection date.';
        } else {
            $validDate = $this->requestModel->findOpenDateByIdForArea(
                (int) $old['preferred_date_id'],
                (int) $profile->area_id
            );

            if (!$validDate) {
                $errors['preferred_date_id'] = 'Selected collection date is invalid or closed.';
            }
        }

        if (empty($old['pickup_address'])) {
            $errors['pickup_address'] = 'Pickup address is required.';
        }

        if (empty($old['contact_phone'])) {
            $errors['contact_phone'] = 'Contact phone is required.';
        }

        $normalizedItems = $this->normalizeRequestItems($old, $errors);

        if (empty($normalizedItems)) {
            $errors['items'] = 'Please add at least one valid e-waste item.';
        }

        if (!empty($errors)) {
            $this->view('public_user/create_request', [
                'title' => 'Submit E-Waste Request',
                'profile' => $profile,
                'dates' => $dates,
                'items' => $availableItems,
                'errors' => $errors,
                'old' => $old
            ]);
            return;
        }

        $created = $this->requestModel->createRequestWithItems($userId, [
            'area_id' => (int) $profile->area_id,
            'preferred_date_id' => (int) $old['preferred_date_id'],
            'pickup_address' => $old['pickup_address'],
            'contact_phone' => $old['contact_phone'],
            'special_note' => $old['special_note'],
            'items' => $normalizedItems
        ]);

        if (!$created) {
            $this->view('public_user/create_request', [
                'title' => 'Submit E-Waste Request',
                'profile' => $profile,
                'dates' => $dates,
                'items' => $availableItems,
                'errors' => ['submit' => 'Request submission failed. Please try again.'],
                'old' => $old
            ]);
            return;
        }

        flash('auth_success', 'E-waste request submitted successfully.');
        $this->redirect('public-user/requests');
    }

    public function requestDetails(int $requestId): void
    {
        requireRole('PUBLIC_USER');

        $userId = (int) $_SESSION['user_id'];

        $request = $this->requestModel->getRequestByIdForUser($requestId, $userId);

        if (!$request) {
            flash('auth_error', 'Request not found.', 'alert alert-danger');
            $this->redirect('public-user/requests');
            return;
        }

        $items = $this->requestModel->getRequestItems($requestId);
        $history = $this->requestModel->getStatusHistory($requestId);

        $this->view('public_user/request_details', [
            'title' => 'Request Details',
            'request' => $request,
            'items' => $items,
            'history' => $history
        ]);
    }

    private function normalizeRequestItems(array $old, array &$errors): array
    {
        $normalized = [];

        $itemIds = is_array($old['item_id']) ? $old['item_id'] : [];
        $quantities = is_array($old['quantity']) ? $old['quantity'] : [];
        $weights = is_array($old['estimated_weight_kg']) ? $old['estimated_weight_kg'] : [];
        $conditions = is_array($old['condition_status']) ? $old['condition_status'] : [];
        $notes = is_array($old['note']) ? $old['note'] : [];

        $allowedConditions = ['WORKING', 'DAMAGED', 'BROKEN', 'LEAKING', 'UNKNOWN'];

        foreach ($itemIds as $index => $rawItemId) {
            $itemId = (int) $rawItemId;

            if ($itemId <= 0) {
                continue;
            }

            $item = $this->requestModel->getItemById($itemId);

            if (!$item) {
                $errors['items'] = 'One or more selected items are invalid.';
                continue;
            }

            if ($item->collection_status === 'NOT_COLLECTED') {
                $errors['items'] = $item->item_name . ' cannot be submitted as a normal collection item.';
                continue;
            }

            $quantity = (int) ($quantities[$index] ?? 0);

            if ($quantity <= 0) {
                $errors['items'] = 'Item quantity must be greater than zero.';
                continue;
            }

            $weightValue = trim((string) ($weights[$index] ?? ''));

            $estimatedWeight = $weightValue === '' ? null : (float) $weightValue;

            if ($estimatedWeight !== null && $estimatedWeight < 0) {
                $errors['items'] = 'Estimated weight cannot be negative.';
                continue;
            }

            $condition = $conditions[$index] ?? 'UNKNOWN';

            if (!in_array($condition, $allowedConditions, true)) {
                $condition = 'UNKNOWN';
            }

            $note = trim((string) ($notes[$index] ?? ''));

            $isRiskItem = $item->collection_status === 'REVIEW_REQUIRED';
            $isBadCondition = in_array($condition, ['DAMAGED', 'BROKEN', 'LEAKING'], true);

            $shouldFlag = $isRiskItem || $isBadCondition;

            $riskLevel = 'MEDIUM';

            if ($item->default_risk_level === 'HIGH' || $condition === 'LEAKING') {
                $riskLevel = 'HIGH';
            }

            $flagReasonParts = [];

            if ($isRiskItem) {
                $flagReasonParts[] = 'Item requires municipal officer review.';
            }

            if ($isBadCondition) {
                $flagReasonParts[] = 'User reported item condition as ' . $condition . '.';
            }

            $normalized[] = [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'estimated_weight_kg' => $estimatedWeight,
                'condition_status' => $condition,
                'risk_flag' => $shouldFlag ? 'AUTO_FLAGGED' : 'NONE',
                'note' => $note,
                'should_flag' => $shouldFlag,
                'risk_level' => $riskLevel,
                'flag_reason' => implode(' ', $flagReasonParts)
            ];
        }

        return $normalized;
    }
}