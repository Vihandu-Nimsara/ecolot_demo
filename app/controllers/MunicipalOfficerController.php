<?php

class MunicipalOfficerController extends Controller
{
    private MunicipalWorkflow $municipalModel;

    public function __construct()
    {
        $this->municipalModel = $this->model('MunicipalWorkflow');
    }

    public function dashboard(): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();
        $stats = $this->municipalModel->getDashboardStats((int) $profile->council_id);

        $this->view('municipal_officer/dashboard', [
            'title' => 'Municipal Officer Dashboard',
            'profile' => $profile,
            'stats' => $stats
        ]);
    }

    public function reports(): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $reportModel = $this->model('ReportWorkflow');

    $this->view('municipal_officer/reports', [
        'title' => 'Council Reports',
        'profile' => $profile,
        'overview' => $reportModel->getOverviewStats((int) $profile->council_id),
        'request_status_counts' => $reportModel->getRequestStatusCounts((int) $profile->council_id),
        'monthly_requests' => $reportModel->getMonthlyRequestCounts((int) $profile->council_id, 8),
        'category_collected_summary' => $reportModel->getCategoryCollectedSummary((int) $profile->council_id),
        'elot_status_counts' => $reportModel->getElotStatusCounts((int) $profile->council_id),
        'bid_summary' => $reportModel->getBidSummary((int) $profile->council_id, 10)
    ]);
}

    public function markElotHandover(int $elotId): void
{
    requireRole('MUNICIPAL_OFFICER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('municipal-officer/elot-details/' . $elotId);
        return;
    }

    $profile = $this->getOfficerProfileOrRedirect();

    $elot = $this->municipalModel->getElotByIdForCouncil(
        $elotId,
        (int) $profile->council_id
    );

    if (!$elot) {
        flash('auth_error', 'E-Lot not found for your council.', 'alert alert-danger');
        $this->redirect('municipal-officer/elots');
        return;
    }

    if ($elot->status !== 'AWARDED') {
        flash('auth_error', 'Only awarded E-Lots can be marked as handed over.', 'alert alert-danger');
        $this->redirect('municipal-officer/elot-details/' . $elotId);
        return;
    }

    $handoverNote = trim($_POST['handover_note'] ?? '');
    $finalNote = $handoverNote ?: 'Handover confirmed by municipal officer.';

    $updated = $this->municipalModel->markElotAsHandedOver(
        $elotId,
        (int) $profile->council_id,
        (int) $_SESSION['user_id'],
        $finalNote
    );

    if (!$updated) {
        flash('auth_error', 'Failed to mark E-Lot as handed over.', 'alert alert-danger');
        $this->redirect('municipal-officer/elot-details/' . $elotId);
        return;
    }

    flash('auth_success', 'E-Lot handover confirmed successfully.');
    $this->redirect('municipal-officer/elot-details/' . $elotId);
}

    public function elotBids(int $elotId): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $elot = $this->municipalModel->getElotByIdForCouncil(
        $elotId,
        (int) $profile->council_id
    );

    if (!$elot) {
        flash('auth_error', 'E-Lot not found for your council.', 'alert alert-danger');
        $this->redirect('municipal-officer/elots');
        return;
    }

    $bids = $this->municipalModel->getBidsForElot($elotId);

    $this->view('municipal_officer/elot_bids', [
        'title' => 'E-Lot Bids',
        'elot' => $elot,
        'bids' => $bids
    ]);
}

public function closeElotBidding(int $elotId): void
{
    requireRole('MUNICIPAL_OFFICER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('municipal-officer/elot-bids/' . $elotId);
        return;
    }

    $profile = $this->getOfficerProfileOrRedirect();

    $elot = $this->municipalModel->getElotByIdForCouncil(
        $elotId,
        (int) $profile->council_id
    );

    if (!$elot) {
        flash('auth_error', 'E-Lot not found for your council.', 'alert alert-danger');
        $this->redirect('municipal-officer/elots');
        return;
    }

    if ($elot->status !== 'OPEN_FOR_BIDDING') {
        flash('auth_error', 'Only open E-Lots can be closed for bidding.', 'alert alert-danger');
        $this->redirect('municipal-officer/elot-bids/' . $elotId);
        return;
    }

    $note = trim($_POST['note'] ?? '');
    $finalNote = $note ?: 'Bidding closed by municipal officer.';

    $closed = $this->municipalModel->closeElotBidding(
        $elotId,
        (int) $profile->council_id,
        (int) $_SESSION['user_id'],
        $finalNote
    );

    if (!$closed) {
        flash('auth_error', 'Failed to close bidding.', 'alert alert-danger');
        $this->redirect('municipal-officer/elot-bids/' . $elotId);
        return;
    }

    flash('auth_success', 'Bidding closed successfully. You can now select a winning bid.');
    $this->redirect('municipal-officer/elot-bids/' . $elotId);
}

public function awardBid(int $elotId, int $bidId): void
{
    requireRole('MUNICIPAL_OFFICER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('municipal-officer/elot-bids/' . $elotId);
        return;
    }

    $profile = $this->getOfficerProfileOrRedirect();

    $elot = $this->municipalModel->getElotByIdForCouncil(
        $elotId,
        (int) $profile->council_id
    );

    if (!$elot) {
        flash('auth_error', 'E-Lot not found for your council.', 'alert alert-danger');
        $this->redirect('municipal-officer/elots');
        return;
    }

    if ($elot->status !== 'BIDDING_CLOSED') {
        flash('auth_error', 'Close bidding before selecting a winning bid.', 'alert alert-danger');
        $this->redirect('municipal-officer/elot-bids/' . $elotId);
        return;
    }

    $officerNote = trim($_POST['officer_note'] ?? '');
    $finalNote = $officerNote ?: 'Winning bid selected by municipal officer.';

    $awarded = $this->municipalModel->awardWinningBid(
        $elotId,
        $bidId,
        (int) $profile->council_id,
        (int) $_SESSION['user_id'],
        $finalNote
    );

    if (!$awarded) {
        flash('auth_error', 'Failed to award bid. Please check bid status and recycler verification.', 'alert alert-danger');
        $this->redirect('municipal-officer/elot-bids/' . $elotId);
        return;
    }

    flash('auth_success', 'Winning bid selected successfully. E-Lot has been awarded.');
    $this->redirect('municipal-officer/elot-details/' . $elotId);
}

    public function elots(): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $elots = $this->municipalModel->getElotsForCouncil((int) $profile->council_id);

    $this->view('municipal_officer/elots', [
        'title' => 'E-Lots',
        'profile' => $profile,
        'elots' => $elots
    ]);
}

public function createElot(): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $this->view('municipal_officer/create_elot', [
        'title' => 'Create E-Lot',
        'profile' => $profile,
        'categories' => $this->municipalModel->getCategoriesWithVerifiedPool((int) $profile->council_id),
        'pool_items' => $this->municipalModel->getVerifiedPoolForCouncil((int) $profile->council_id),
        'errors' => [],
        'old' => [
            'title' => '',
            'category_id' => '',
            'description' => '',
            'status' => 'DRAFT',
            'bidding_start' => '',
            'bidding_end' => '',
            'pickup_item_ids' => []
        ]
    ]);
}

public function storeElot(): void
{
    requireRole('MUNICIPAL_OFFICER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('municipal-officer/create-elot');
        return;
    }

    $profile = $this->getOfficerProfileOrRedirect();
    $councilId = (int) $profile->council_id;

    $old = [
        'title' => trim($_POST['title'] ?? ''),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'status' => strtoupper(trim($_POST['status'] ?? 'DRAFT')),
        'bidding_start' => trim($_POST['bidding_start'] ?? ''),
        'bidding_end' => trim($_POST['bidding_end'] ?? ''),
        'pickup_item_ids' => $_POST['pickup_item_ids'] ?? []
    ];

    $errors = $this->validateElot($old);

    $pickupItemIds = array_values(array_unique(array_map('intval', $old['pickup_item_ids'])));

    if (empty($pickupItemIds)) {
        $errors['pickup_item_ids'] = 'Please select at least one verified pickup item.';
    }

    $verifiedItems = [];

    if (!empty($pickupItemIds) && $old['category_id'] > 0) {
        $verifiedItems = $this->municipalModel->validateVerifiedItemsForElot(
            $pickupItemIds,
            $councilId,
            $old['category_id']
        );

        if (count($verifiedItems) !== count($pickupItemIds)) {
            $errors['pickup_item_ids'] = 'Selected items must be verified, unassigned to another E-Lot, and from the selected category.';
        }
    }

    $biddingStart = null;
    $biddingEnd = null;

    if ($old['status'] === 'OPEN_FOR_BIDDING') {
        $biddingStart = $this->normalizeDateTimeLocal($old['bidding_start']);
        $biddingEnd = $this->normalizeDateTimeLocal($old['bidding_end']);

        if (!$biddingStart || !$biddingEnd) {
            $errors['bidding_start'] = 'Bidding start and end date/time are required when opening for bidding.';
        } elseif (strtotime($biddingEnd) <= strtotime($biddingStart)) {
            $errors['bidding_end'] = 'Bidding end must be after bidding start.';
        }
    }

    if (!empty($errors)) {
        $this->view('municipal_officer/create_elot', [
            'title' => 'Create E-Lot',
            'profile' => $profile,
            'categories' => $this->municipalModel->getCategoriesWithVerifiedPool($councilId),
            'pool_items' => $this->municipalModel->getVerifiedPoolForCouncil($councilId),
            'errors' => $errors,
            'old' => $old
        ]);
        return;
    }

    $elotId = $this->municipalModel->createElotWithItems([
        'council_id' => $councilId,
        'created_by' => (int) $_SESSION['user_id'],
        'title' => $old['title'],
        'category_id' => $old['category_id'],
        'description' => $old['description'],
        'status' => $old['status'],
        'bidding_start' => $biddingStart,
        'bidding_end' => $biddingEnd,
        'verified_items' => $verifiedItems
    ]);

    if (!$elotId) {
        $this->view('municipal_officer/create_elot', [
            'title' => 'Create E-Lot',
            'profile' => $profile,
            'categories' => $this->municipalModel->getCategoriesWithVerifiedPool($councilId),
            'pool_items' => $this->municipalModel->getVerifiedPoolForCouncil($councilId),
            'errors' => ['elot' => 'Failed to create E-Lot. Please try again.'],
            'old' => $old
        ]);
        return;
    }

    flash('auth_success', 'E-Lot created successfully.');
    $this->redirect('municipal-officer/elot-details/' . $elotId);
}

public function elotDetails(int $elotId): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $elot = $this->municipalModel->getElotByIdForCouncil(
        $elotId,
        (int) $profile->council_id
    );

    if (!$elot) {
        flash('auth_error', 'E-Lot not found for your council.', 'alert alert-danger');
        $this->redirect('municipal-officer/elots');
        return;
    }

    $items = $this->municipalModel->getElotItems($elotId);
    $history = $this->municipalModel->getElotStatusHistory($elotId);

    $this->view('municipal_officer/elot_details', [
        'title' => 'E-Lot Details',
        'elot' => $elot,
        'items' => $items,
        'history' => $history
    ]);
}

private function validateElot(array $data): array
{
    $errors = [];

    if (empty($data['title'])) {
        $errors['title'] = 'E-Lot title is required.';
    }

    if ($data['category_id'] <= 0) {
        $errors['category_id'] = 'Please select a valid category.';
    }

    $allowedStatuses = ['DRAFT', 'OPEN_FOR_BIDDING'];

    if (!in_array($data['status'], $allowedStatuses, true)) {
        $errors['status'] = 'Invalid E-Lot status.';
    }

    return $errors;
}



private function normalizeDateTimeLocal(string $value): ?string
{
    if (empty($value)) {
        return null;
    }

    $value = str_replace('T', ' ', $value);

    if (strlen($value) === 16) {
        $value .= ':00';
    }

    return $value;
}

    public function requests(string $status = 'all'): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();

        $allowedStatuses = [
            'SUBMITTED',
            'APPROVED',
            'REJECTED',
            'ASSIGNED',
            'PICKUP_PENDING',
            'COLLECTED',
            'PARTIALLY_COLLECTED',
            'CANCELLED',
            'COMPLETED'
        ];

        $filterStatus = strtoupper($status);

        if ($filterStatus === 'ALL') {
            $filterStatus = null;
        }

        if ($filterStatus !== null && !in_array($filterStatus, $allowedStatuses, true)) {
            $filterStatus = null;
        }

        $requests = $this->municipalModel->getRequestsForCouncil(
            (int) $profile->council_id,
            $filterStatus
        );

        $this->view('municipal_officer/requests', [
            'title' => 'Review Requests',
            'profile' => $profile,
            'requests' => $requests,
            'current_status' => $filterStatus ?? 'ALL'
        ]);
    }

    public function requestDetails(int $requestId): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();

        $request = $this->municipalModel->getRequestByIdForCouncil(
            $requestId,
            (int) $profile->council_id
        );

        if (!$request) {
            flash('auth_error', 'Request not found for your council.', 'alert alert-danger');
            $this->redirect('municipal-officer/requests');
            return;
        }

        $items = $this->municipalModel->getRequestItems($requestId);
        $flags = $this->municipalModel->getFlaggedItemsForRequest($requestId);
        $history = $this->municipalModel->getStatusHistory($requestId);

        $this->view('municipal_officer/request_details', [
            'title' => 'Request Details',
            'request' => $request,
            'items' => $items,
            'flags' => $flags,
            'history' => $history
        ]);
    }

    public function updateRequestStatus(int $requestId): void
    {
        requireRole('MUNICIPAL_OFFICER');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('municipal-officer/request-details/' . $requestId);
            return;
        }

        $profile = $this->getOfficerProfileOrRedirect();

        $request = $this->municipalModel->getRequestByIdForCouncil(
            $requestId,
            (int) $profile->council_id
        );

        if (!$request) {
            flash('auth_error', 'Request not found for your council.', 'alert alert-danger');
            $this->redirect('municipal-officer/requests');
            return;
        }

        $action = strtoupper(trim($_POST['action'] ?? ''));
        $note = trim($_POST['note'] ?? '');

        $allowedActions = ['APPROVED', 'REJECTED'];

        if (!in_array($action, $allowedActions, true)) {
            flash('auth_error', 'Invalid request action.', 'alert alert-danger');
            $this->redirect('municipal-officer/request-details/' . $requestId);
            return;
        }

        if ($request->status !== 'SUBMITTED') {
            flash('auth_error', 'Only submitted requests can be approved or rejected.', 'alert alert-danger');
            $this->redirect('municipal-officer/request-details/' . $requestId);
            return;
        }

        if ($action === 'REJECTED' && empty($note)) {
            flash('auth_error', 'Please add a reason when rejecting a request.', 'alert alert-danger');
            $this->redirect('municipal-officer/request-details/' . $requestId);
            return;
        }

        $finalNote = $note ?: 'Request approved by municipal officer.';

        $updated = $this->municipalModel->updateRequestStatus(
            $requestId,
            (int) $_SESSION['user_id'],
            $request->status,
            $action,
            $finalNote
        );

        if (!$updated) {
            flash('auth_error', 'Failed to update request status.', 'alert alert-danger');
            $this->redirect('municipal-officer/request-details/' . $requestId);
            return;
        }

        flash('auth_success', 'Request status updated successfully.');
        $this->redirect('municipal-officer/request-details/' . $requestId);
    }

    public function campaigns(): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();

        $campaigns = $this->municipalModel->getCampaignsForCouncil((int) $profile->council_id);

        $this->view('municipal_officer/campaigns', [
            'title' => 'Monthly Campaigns',
            'profile' => $profile,
            'campaigns' => $campaigns
        ]);
    }

    public function createCampaign(): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();

        $this->view('municipal_officer/create_campaign', [
            'title' => 'Create Monthly Campaign',
            'profile' => $profile,
            'errors' => [],
            'old' => [
                'campaign_name' => '',
                'campaign_month' => date('n'),
                'campaign_year' => date('Y'),
                'request_cutoff_date' => '',
                'status' => 'DRAFT'
            ]
        ]);
    }

    public function storeCampaign(): void
    {
        requireRole('MUNICIPAL_OFFICER');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('municipal-officer/create-campaign');
            return;
        }

        $profile = $this->getOfficerProfileOrRedirect();

        $old = [
            'campaign_name' => trim($_POST['campaign_name'] ?? ''),
            'campaign_month' => (int) ($_POST['campaign_month'] ?? 0),
            'campaign_year' => (int) ($_POST['campaign_year'] ?? 0),
            'request_cutoff_date' => trim($_POST['request_cutoff_date'] ?? ''),
            'status' => strtoupper(trim($_POST['status'] ?? 'DRAFT'))
        ];

        $errors = $this->validateCampaign($old);

        if ($this->municipalModel->campaignExists(
            (int) $profile->council_id,
            $old['campaign_month'],
            $old['campaign_year']
        )) {
            $errors['campaign_month'] = 'A campaign already exists for this month and year.';
        }

        if (!empty($errors)) {
            $this->view('municipal_officer/create_campaign', [
                'title' => 'Create Monthly Campaign',
                'profile' => $profile,
                'errors' => $errors,
                'old' => $old
            ]);
            return;
        }

        $created = $this->municipalModel->createCampaign([
            'council_id' => (int) $profile->council_id,
            'campaign_name' => $old['campaign_name'],
            'campaign_month' => $old['campaign_month'],
            'campaign_year' => $old['campaign_year'],
            'request_cutoff_date' => $old['request_cutoff_date'],
            'status' => $old['status'],
            'created_by' => (int) $_SESSION['user_id']
        ]);

        if (!$created) {
            $this->view('municipal_officer/create_campaign', [
                'title' => 'Create Monthly Campaign',
                'profile' => $profile,
                'errors' => ['campaign' => 'Failed to create campaign.'],
                'old' => $old
            ]);
            return;
        }

        flash('auth_success', 'Monthly campaign created successfully.');
        $this->redirect('municipal-officer/campaigns');
    }

    public function routes(): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();

        $routes = $this->municipalModel->getRoutesForCouncil((int) $profile->council_id);

        $this->view('municipal_officer/routes', [
            'title' => 'Collection Routes',
            'profile' => $profile,
            'routes' => $routes
        ]);
    }

    public function createRoute(): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();

        $this->view('municipal_officer/create_route', [
            'title' => 'Create Collection Route',
            'profile' => $profile,
            'campaigns' => $this->municipalModel->getCampaignOptions((int) $profile->council_id),
            'areas' => $this->municipalModel->getAreasForCouncil((int) $profile->council_id),
            'collectors' => $this->municipalModel->getCollectorsForCouncil((int) $profile->council_id),
            'vehicles' => $this->municipalModel->getVehiclesForCouncil((int) $profile->council_id),
            'approved_requests' => $this->municipalModel->getApprovedRequestsForRoutePlanning((int) $profile->council_id),
            'errors' => [],
            'old' => [
                'campaign_id' => '',
                'area_id' => '',
                'route_name' => '',
                'collection_date' => '',
                'collector_id' => '',
                'vehicle_id' => '',
                'request_ids' => []
            ]
        ]);
    }

    public function storeRoute(): void
    {
        requireRole('MUNICIPAL_OFFICER');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('municipal-officer/create-route');
            return;
        }

        $profile = $this->getOfficerProfileOrRedirect();
        $councilId = (int) $profile->council_id;

        $old = [
            'campaign_id' => (int) ($_POST['campaign_id'] ?? 0),
            'area_id' => (int) ($_POST['area_id'] ?? 0),
            'route_name' => trim($_POST['route_name'] ?? ''),
            'collection_date' => trim($_POST['collection_date'] ?? ''),
            'collector_id' => (int) ($_POST['collector_id'] ?? 0),
            'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
            'request_ids' => $_POST['request_ids'] ?? []
        ];

        $errors = $this->validateRoute($old, $councilId);

        if (!empty($errors)) {
            $this->view('municipal_officer/create_route', [
                'title' => 'Create Collection Route',
                'profile' => $profile,
                'campaigns' => $this->municipalModel->getCampaignOptions($councilId),
                'areas' => $this->municipalModel->getAreasForCouncil($councilId),
                'collectors' => $this->municipalModel->getCollectorsForCouncil($councilId),
                'vehicles' => $this->municipalModel->getVehiclesForCouncil($councilId),
                'approved_requests' => $this->municipalModel->getApprovedRequestsForRoutePlanning($councilId),
                'errors' => $errors,
                'old' => $old
            ]);
            return;
        }

        $requestIds = array_values(array_unique(array_map('intval', $old['request_ids'])));

        $created = $this->municipalModel->createRouteWithStops([
            'campaign_id' => $old['campaign_id'],
            'area_id' => $old['area_id'],
            'route_name' => $old['route_name'],
            'collection_date' => $old['collection_date'],
            'collector_id' => $old['collector_id'],
            'vehicle_id' => $old['vehicle_id'],
            'request_ids' => $requestIds,
            'created_by' => (int) $_SESSION['user_id']
        ]);

        if (!$created) {
            $this->view('municipal_officer/create_route', [
                'title' => 'Create Collection Route',
                'profile' => $profile,
                'campaigns' => $this->municipalModel->getCampaignOptions($councilId),
                'areas' => $this->municipalModel->getAreasForCouncil($councilId),
                'collectors' => $this->municipalModel->getCollectorsForCouncil($councilId),
                'vehicles' => $this->municipalModel->getVehiclesForCouncil($councilId),
                'approved_requests' => $this->municipalModel->getApprovedRequestsForRoutePlanning($councilId),
                'errors' => ['route' => 'Failed to create route. Please try again.'],
                'old' => $old
            ]);
            return;
        }

        flash('auth_success', 'Collection route created successfully.');
        $this->redirect('municipal-officer/routes');
    }

    public function routeDetails(int $routeId): void
    {
        requireRole('MUNICIPAL_OFFICER');

        $profile = $this->getOfficerProfileOrRedirect();

        $route = $this->municipalModel->getRouteByIdForCouncil(
            $routeId,
            (int) $profile->council_id
        );

        if (!$route) {
            flash('auth_error', 'Route not found for your council.', 'alert alert-danger');
            $this->redirect('municipal-officer/routes');
            return;
        }

        $stops = $this->municipalModel->getRouteStops($routeId);

        $this->view('municipal_officer/route_details', [
            'title' => 'Route Details',
            'route' => $route,
            'stops' => $stops
        ]);
    }

    public function pickupRecords(string $status = 'pending'): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $status = strtoupper($status);

    $allowedStatuses = ['PENDING', 'VERIFIED', 'REJECTED', 'ALL'];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'PENDING';
    }

    $filterStatus = $status === 'ALL' ? null : $status;

    $pickupRecords = $this->municipalModel->getPickupRecordsForCouncil(
        (int) $profile->council_id,
        $filterStatus
    );

    $this->view('municipal_officer/pickup_records', [
        'title' => 'Collection Record Verification',
        'profile' => $profile,
        'pickup_records' => $pickupRecords,
        'current_status' => $status
    ]);
}

public function pickupRecordDetails(int $pickupId): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $pickup = $this->municipalModel->getPickupRecordByIdForCouncil(
        $pickupId,
        (int) $profile->council_id
    );

    if (!$pickup) {
        flash('auth_error', 'Pickup record not found for your council.', 'alert alert-danger');
        $this->redirect('municipal-officer/pickup-records');
        return;
    }

    $items = $this->municipalModel->getPickupItems($pickupId);
    $flags = $this->municipalModel->getPickupFlags($pickupId);

    $this->view('municipal_officer/pickup_record_details', [
        'title' => 'Pickup Record Details',
        'pickup' => $pickup,
        'items' => $items,
        'flags' => $flags
    ]);
}

public function verifyPickupRecord(int $pickupId): void
{
    requireRole('MUNICIPAL_OFFICER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('municipal-officer/pickup-record-details/' . $pickupId);
        return;
    }

    $profile = $this->getOfficerProfileOrRedirect();

    $pickup = $this->municipalModel->getPickupRecordByIdForCouncil(
        $pickupId,
        (int) $profile->council_id
    );

    if (!$pickup) {
        flash('auth_error', 'Pickup record not found for your council.', 'alert alert-danger');
        $this->redirect('municipal-officer/pickup-records');
        return;
    }

    if ($pickup->verification_status !== 'PENDING') {
        flash('auth_error', 'Only pending pickup records can be verified or rejected.', 'alert alert-danger');
        $this->redirect('municipal-officer/pickup-record-details/' . $pickupId);
        return;
    }

    $decision = strtoupper(trim($_POST['decision'] ?? ''));
    $officerNote = trim($_POST['officer_note'] ?? '');

    if (!in_array($decision, ['VERIFIED', 'REJECTED'], true)) {
        flash('auth_error', 'Invalid verification decision.', 'alert alert-danger');
        $this->redirect('municipal-officer/pickup-record-details/' . $pickupId);
        return;
    }

    if ($decision === 'REJECTED' && empty($officerNote)) {
        flash('auth_error', 'Please add a reason when rejecting a pickup record.', 'alert alert-danger');
        $this->redirect('municipal-officer/pickup-record-details/' . $pickupId);
        return;
    }

    $finalNote = $officerNote ?: 'Pickup record verified by municipal officer.';

    $updated = $this->municipalModel->verifyPickupRecord(
        $pickupId,
        (int) $_SESSION['user_id'],
        $decision,
        $finalNote
    );

    if (!$updated) {
        flash('auth_error', 'Failed to update pickup record verification status.', 'alert alert-danger');
        $this->redirect('municipal-officer/pickup-record-details/' . $pickupId);
        return;
    }

    flash('auth_success', 'Pickup record verification updated successfully.');
    $this->redirect('municipal-officer/pickup-record-details/' . $pickupId);
}

public function verifiedPool(): void
{
    requireRole('MUNICIPAL_OFFICER');

    $profile = $this->getOfficerProfileOrRedirect();

    $poolItems = $this->municipalModel->getVerifiedPoolForCouncil(
        (int) $profile->council_id
    );

    $this->view('municipal_officer/verified_pool', [
        'title' => 'Verified E-Waste Pool',
        'profile' => $profile,
        'pool_items' => $poolItems
    ]);
}

    private function getOfficerProfileOrRedirect(): mixed
    {
        $profile = $this->municipalModel->getOfficerProfile((int) $_SESSION['user_id']);

        if (!$profile) {
            flash('auth_error', 'Municipal officer profile not found.', 'alert alert-danger');
            $this->redirect('auth/login');
            exit;
        }

        return $profile;
    }

    private function validateCampaign(array $data): array
    {
        $errors = [];

        if (empty($data['campaign_name'])) {
            $errors['campaign_name'] = 'Campaign name is required.';
        }

        if ($data['campaign_month'] < 1 || $data['campaign_month'] > 12) {
            $errors['campaign_month'] = 'Campaign month must be between 1 and 12.';
        }

        $currentYear = (int) date('Y');

        if ($data['campaign_year'] < $currentYear || $data['campaign_year'] > $currentYear + 2) {
            $errors['campaign_year'] = 'Campaign year must be valid.';
        }

        if (empty($data['request_cutoff_date'])) {
            $errors['request_cutoff_date'] = 'Request cut-off date is required.';
        }

        $allowedStatuses = ['DRAFT', 'OPEN'];

        if (!in_array($data['status'], $allowedStatuses, true)) {
            $errors['status'] = 'Invalid campaign status.';
        }

        return $errors;
    }

    private function validateRoute(array $data, int $councilId): array
    {
        $errors = [];

        if (empty($data['route_name'])) {
            $errors['route_name'] = 'Route name is required.';
        }

        if (empty($data['collection_date'])) {
            $errors['collection_date'] = 'Collection date is required.';
        }

        $campaign = $this->municipalModel->findCampaignForCouncil($data['campaign_id'], $councilId);

        if (!$campaign) {
            $errors['campaign_id'] = 'Please select a valid campaign.';
        }

        $area = $this->municipalModel->findAreaForCouncil($data['area_id'], $councilId);

        if (!$area) {
            $errors['area_id'] = 'Please select a valid area.';
        }

        $collector = $this->municipalModel->findCollectorForCouncil($data['collector_id'], $councilId);

        if (!$collector) {
            $errors['collector_id'] = 'Please select a valid collector.';
        }

        $vehicle = $this->municipalModel->findVehicleForCouncil($data['vehicle_id'], $councilId);

        if (!$vehicle) {
            $errors['vehicle_id'] = 'Please select a valid vehicle.';
        }

        $requestIds = array_values(array_unique(array_map('intval', $data['request_ids'])));

        if (empty($requestIds)) {
            $errors['request_ids'] = 'Please select at least one approved request.';
            return $errors;
        }

        if (!empty($data['collection_date']) && $area) {
            $validRequests = $this->municipalModel->validateApprovedRequestsForRoute(
                $requestIds,
                $councilId,
                $data['area_id'],
                $data['collection_date']
            );

            if (count($validRequests) !== count($requestIds)) {
                $errors['request_ids'] = 'Selected requests must be approved, unassigned, and match the selected area and collection date.';
            }
        }

        return $errors;
    }
}