<?php

class RecyclerController extends Controller
{
    private RecyclerWorkflow $recyclerModel;

    public function __construct()
    {
        $this->recyclerModel = $this->model('RecyclerWorkflow');
    }

    public function dashboard(): void
    {
        requireRole('AUTHORIZED_RECYCLER');

        $profile = $this->getRecyclerProfileOrRedirect();
        $capabilities = $this->recyclerModel->getRecyclerCapabilities((int) $profile->recycler_profile_id);

        $stats = null;

        if ($profile->verification_status === 'VERIFIED') {
            $stats = $this->recyclerModel->getDashboardStats((int) $profile->recycler_profile_id);
        }

        $this->view('recycler/dashboard', [
            'title' => 'Recycler Dashboard',
            'profile' => $profile,
            'capabilities' => $capabilities,
            'stats' => $stats
        ]);
    }

    public function elots(): void
    {
        requireRole('AUTHORIZED_RECYCLER');

        $profile = $this->getRecyclerProfileOrRedirect();

        if (!$this->isVerifiedRecycler($profile)) {
            flash('auth_error', 'Your recycler profile must be verified before viewing E-Lots.', 'alert alert-danger');
            $this->redirect('recycler/dashboard');
            return;
        }

        $elots = $this->recyclerModel->getEligibleOpenElots((int) $profile->recycler_profile_id);

        $this->view('recycler/elots', [
            'title' => 'Eligible Open E-Lots',
            'profile' => $profile,
            'elots' => $elots
        ]);
    }

    public function elotDetails(int $elotId): void
    {
        requireRole('AUTHORIZED_RECYCLER');

        $profile = $this->getRecyclerProfileOrRedirect();

        if (!$this->isVerifiedRecycler($profile)) {
            flash('auth_error', 'Your recycler profile must be verified before viewing E-Lots.', 'alert alert-danger');
            $this->redirect('recycler/dashboard');
            return;
        }

        $elot = $this->recyclerModel->getEligibleElotById(
            $elotId,
            (int) $profile->recycler_profile_id
        );

        if (!$elot) {
            flash('auth_error', 'E-Lot not found, not open, or not eligible for your capability profile.', 'alert alert-danger');
            $this->redirect('recycler/elots');
            return;
        }

        $items = $this->recyclerModel->getElotItemsForRecycler($elotId);
        $existingBid = $this->recyclerModel->getExistingBid($elotId, (int) $profile->recycler_profile_id);

        $this->view('recycler/elot_details', [
            'title' => 'E-Lot Details',
            'profile' => $profile,
            'elot' => $elot,
            'items' => $items,
            'existing_bid' => $existingBid,
            'errors' => [],
            'old' => [
                'bid_amount' => '',
                'bid_note' => ''
            ]
        ]);
    }

    public function storeBid(int $elotId): void
    {
        requireRole('AUTHORIZED_RECYCLER');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('recycler/elot-details/' . $elotId);
            return;
        }

        $profile = $this->getRecyclerProfileOrRedirect();

        if (!$this->isVerifiedRecycler($profile)) {
            flash('auth_error', 'Your recycler profile must be verified before submitting bids.', 'alert alert-danger');
            $this->redirect('recycler/dashboard');
            return;
        }

        $elot = $this->recyclerModel->getEligibleElotById(
            $elotId,
            (int) $profile->recycler_profile_id
        );

        if (!$elot) {
            flash('auth_error', 'This E-Lot is not available for your recycler profile.', 'alert alert-danger');
            $this->redirect('recycler/elots');
            return;
        }

        $existingBid = $this->recyclerModel->getExistingBid($elotId, (int) $profile->recycler_profile_id);

        if ($existingBid) {
            flash('auth_error', 'You have already submitted a bid for this E-Lot.', 'alert alert-danger');
            $this->redirect('recycler/elot-details/' . $elotId);
            return;
        }

        $old = [
            'bid_amount' => trim($_POST['bid_amount'] ?? ''),
            'bid_note' => trim($_POST['bid_note'] ?? '')
        ];

        $errors = [];

        if ($old['bid_amount'] === '' || !is_numeric($old['bid_amount'])) {
            $errors['bid_amount'] = 'Please enter a valid bid amount.';
        } elseif ((float) $old['bid_amount'] <= 0) {
            $errors['bid_amount'] = 'Bid amount must be greater than zero.';
        }

        if (!empty($errors)) {
            $items = $this->recyclerModel->getElotItemsForRecycler($elotId);

            $this->view('recycler/elot_details', [
                'title' => 'E-Lot Details',
                'profile' => $profile,
                'elot' => $elot,
                'items' => $items,
                'existing_bid' => null,
                'errors' => $errors,
                'old' => $old
            ]);
            return;
        }

        $created = $this->recyclerModel->createBid(
            (int) $_SESSION['user_id'],
            $elotId,
            (int) $profile->recycler_profile_id,
            (float) $old['bid_amount'],
            $old['bid_note']
        );

        if (!$created) {
            $items = $this->recyclerModel->getElotItemsForRecycler($elotId);

            $this->view('recycler/elot_details', [
                'title' => 'E-Lot Details',
                'profile' => $profile,
                'elot' => $elot,
                'items' => $items,
                'existing_bid' => null,
                'errors' => ['bid' => 'Failed to submit bid. Please try again.'],
                'old' => $old
            ]);
            return;
        }

        flash('auth_success', 'Bid submitted successfully.');
        $this->redirect('recycler/my-bids');
    }

    public function myBids(): void
    {
        requireRole('AUTHORIZED_RECYCLER');

        $profile = $this->getRecyclerProfileOrRedirect();

        if (!$this->isVerifiedRecycler($profile)) {
            flash('auth_error', 'Your recycler profile must be verified before viewing bids.', 'alert alert-danger');
            $this->redirect('recycler/dashboard');
            return;
        }

        $bids = $this->recyclerModel->getMyBids((int) $profile->recycler_profile_id);

        $this->view('recycler/my_bids', [
            'title' => 'My Bids',
            'profile' => $profile,
            'bids' => $bids
        ]);
    }

    public function myElots(): void
{
    requireRole('AUTHORIZED_RECYCLER');

    $profile = $this->getRecyclerProfileOrRedirect();

    if (!$this->isVerifiedRecycler($profile)) {
        flash('auth_error', 'Your recycler profile must be verified first.', 'alert alert-danger');
        $this->redirect('recycler/dashboard');
        return;
    }

    $elots = $this->recyclerModel->getMyAwardedElots((int) $profile->recycler_profile_id);

    $this->view('recycler/my_elots', [
        'title' => 'My Awarded E-Lots',
        'profile' => $profile,
        'elots' => $elots
    ]);
}

public function myElotDetails(int $elotId): void
{
    requireRole('AUTHORIZED_RECYCLER');

    $profile = $this->getRecyclerProfileOrRedirect();

    if (!$this->isVerifiedRecycler($profile)) {
        flash('auth_error', 'Your recycler profile must be verified first.', 'alert alert-danger');
        $this->redirect('recycler/dashboard');
        return;
    }

    $elot = $this->recyclerModel->getMyAwardedElotById(
        $elotId,
        (int) $profile->recycler_profile_id
    );

    if (!$elot) {
        flash('auth_error', 'Awarded E-Lot not found for your account.', 'alert alert-danger');
        $this->redirect('recycler/my-elots');
        return;
    }

    $items = $this->recyclerModel->getElotItemsForRecycler($elotId);
    $history = $this->recyclerModel->getElotStatusHistory($elotId);

    $this->view('recycler/my_elot_details', [
        'title' => 'My E-Lot Details',
        'profile' => $profile,
        'elot' => $elot,
        'items' => $items,
        'history' => $history
    ]);
}

public function updateProcessingStatus(int $elotId): void
{
    requireRole('AUTHORIZED_RECYCLER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('recycler/my-elot-details/' . $elotId);
        return;
    }

    $profile = $this->getRecyclerProfileOrRedirect();

    if (!$this->isVerifiedRecycler($profile)) {
        flash('auth_error', 'Your recycler profile must be verified first.', 'alert alert-danger');
        $this->redirect('recycler/dashboard');
        return;
    }

    $elot = $this->recyclerModel->getMyAwardedElotById(
        $elotId,
        (int) $profile->recycler_profile_id
    );

    if (!$elot) {
        flash('auth_error', 'Awarded E-Lot not found for your account.', 'alert alert-danger');
        $this->redirect('recycler/my-elots');
        return;
    }

    $newStatus = strtoupper(trim($_POST['new_status'] ?? ''));
    $note = trim($_POST['processing_note'] ?? '');

    $allowedStatuses = ['PROCESSING', 'COMPLETED'];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        flash('auth_error', 'Invalid processing status.', 'alert alert-danger');
        $this->redirect('recycler/my-elot-details/' . $elotId);
        return;
    }

    if (empty($note)) {
        flash('auth_error', 'Please add a processing note.', 'alert alert-danger');
        $this->redirect('recycler/my-elot-details/' . $elotId);
        return;
    }

    $updated = $this->recyclerModel->updateProcessingStatus(
        $elotId,
        (int) $profile->recycler_profile_id,
        (int) $_SESSION['user_id'],
        $newStatus,
        $note
    );

    if (!$updated) {
        flash('auth_error', 'Invalid status transition. Handover must be completed before processing can start.', 'alert alert-danger');
        $this->redirect('recycler/my-elot-details/' . $elotId);
        return;
    }

    flash('auth_success', 'E-Lot processing status updated successfully.');
    $this->redirect('recycler/my-elot-details/' . $elotId);
}

    private function getRecyclerProfileOrRedirect(): mixed
    {
        $profile = $this->recyclerModel->getRecyclerProfile((int) $_SESSION['user_id']);

        if (!$profile) {
            flash('auth_error', 'Recycler profile not found.', 'alert alert-danger');
            $this->redirect('auth/login');
            exit;
        }

        return $profile;
    }

    private function isVerifiedRecycler(mixed $profile): bool
    {
        return $profile && $profile->verification_status === 'VERIFIED';
    }
}