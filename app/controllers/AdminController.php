<?php

class AdminController extends Controller
{
    private AdminWorkflow $adminModel;

    public function __construct()
    {
        $this->adminModel = $this->model('AdminWorkflow');
    }

    public function dashboard(): void
    {
        requireRole('ADMIN');

        $stats = $this->adminModel->getDashboardStats();

        $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats
        ]);
    }

    public function users(string $role = 'all'): void
    {
        requireRole('ADMIN');

        $allowedRoles = [
            'PUBLIC_USER',
            'MUNICIPAL_OFFICER',
            'COLLECTOR',
            'AUTHORIZED_RECYCLER',
            'ADMIN'
        ];

        $role = strtoupper($role);
        $filterRole = in_array($role, $allowedRoles, true) ? $role : null;

        $users = $this->adminModel->getUsers($filterRole);
        $councils = $this->adminModel->getCouncils();

        $this->view('admin/users', [
            'title' => 'User Management',
            'users' => $users,
            'councils' => $councils,
            'current_role' => $filterRole ?? 'ALL',
            'errors' => [],
            'old' => []
        ]);
    }

    public function storeUser(): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/users');
            return;
        }

        $old = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'phone' => trim($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => strtoupper(trim($_POST['role'] ?? '')),
            'council_id' => (int) ($_POST['council_id'] ?? 0),
            'employee_no' => trim($_POST['employee_no'] ?? ''),
            'designation' => trim($_POST['designation'] ?? '')
        ];

        $errors = [];

        if (empty($old['full_name'])) {
            $errors['full_name'] = 'Full name is required.';
        }

        if (empty($old['email']) || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required.';
        }

        if ($this->adminModel->findUserByEmail($old['email'])) {
            $errors['email'] = 'This email is already registered.';
        }

        if (strlen($old['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        $allowedRoles = ['MUNICIPAL_OFFICER', 'COLLECTOR', 'ADMIN'];

        if (!in_array($old['role'], $allowedRoles, true)) {
            $errors['role'] = 'Only officer, collector, or admin accounts can be created here.';
        }

        if (in_array($old['role'], ['MUNICIPAL_OFFICER', 'COLLECTOR'], true)) {
            if ($old['council_id'] <= 0) {
                $errors['council_id'] = 'Council is required.';
            }

            if (empty($old['employee_no'])) {
                $errors['employee_no'] = 'Employee number is required.';
            }
        }

        if (!empty($errors)) {
            $this->view('admin/users', [
                'title' => 'User Management',
                'users' => $this->adminModel->getUsers(null),
                'councils' => $this->adminModel->getCouncils(),
                'current_role' => 'ALL',
                'errors' => $errors,
                'old' => $old
            ]);
            return;
        }

        $created = $this->adminModel->createPrivilegedUser([
            'full_name' => $old['full_name'],
            'email' => $old['email'],
            'phone' => $old['phone'],
            'password' => $old['password'],
            'role' => $old['role'],
            'council_id' => $old['council_id'],
            'employee_no' => $old['employee_no'],
            'designation' => $old['designation'],
            'created_by' => (int) $_SESSION['user_id']
        ]);

        if (!$created) {
            flash('auth_error', 'Failed to create user.', 'alert alert-danger');
            $this->redirect('admin/users');
            return;
        }

        flash('auth_success', 'User account created successfully.');
        $this->redirect('admin/users');
    }

    public function updateUserStatus(int $userId): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/users');
            return;
        }

        if ($userId === (int) $_SESSION['user_id']) {
            flash('auth_error', 'You cannot change your own account status.', 'alert alert-danger');
            $this->redirect('admin/users');
            return;
        }

        $status = strtoupper(trim($_POST['status'] ?? ''));

        $allowedStatuses = ['ACTIVE', 'SUSPENDED', 'REJECTED'];

        if (!in_array($status, $allowedStatuses, true)) {
            flash('auth_error', 'Invalid user status.', 'alert alert-danger');
            $this->redirect('admin/users');
            return;
        }

        $updated = $this->adminModel->updateUserStatus(
            $userId,
            $status,
            (int) $_SESSION['user_id']
        );

        if (!$updated) {
            flash('auth_error', 'Failed to update user status.', 'alert alert-danger');
            $this->redirect('admin/users');
            return;
        }

        flash('auth_success', 'User status updated successfully.');
        $this->redirect('admin/users');
    }

    public function recyclerVerification(string $status = 'pending'): void
    {
        requireRole('ADMIN');

        $status = strtoupper($status);

        $allowedStatuses = ['PENDING', 'VERIFIED', 'REJECTED', 'ALL'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'PENDING';
        }

        $filterStatus = $status === 'ALL' ? null : $status;

        $recyclers = $this->adminModel->getRecyclerProfiles($filterStatus);

        $this->view('admin/recycler_verification', [
            'title' => 'Recycler Verification',
            'recyclers' => $recyclers,
            'current_status' => $status
        ]);
    }

    public function recyclerDetails(int $profileId): void
    {
        requireRole('ADMIN');

        $recycler = $this->adminModel->getRecyclerProfileById($profileId);

        if (!$recycler) {
            flash('auth_error', 'Recycler profile not found.', 'alert alert-danger');
            $this->redirect('admin/recycler-verification');
            return;
        }

        $capabilities = $this->adminModel->getRecyclerCapabilities($profileId);
        $categories = $this->adminModel->getCategories();

        $this->view('admin/recycler_details', [
            'title' => 'Recycler Details',
            'recycler' => $recycler,
            'capabilities' => $capabilities,
            'categories' => $categories
        ]);
    }

    public function verifyRecycler(int $profileId): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/recycler-details/' . $profileId);
            return;
        }

        $decision = strtoupper(trim($_POST['decision'] ?? ''));

        if (!in_array($decision, ['VERIFIED', 'REJECTED'], true)) {
            flash('auth_error', 'Invalid verification decision.', 'alert alert-danger');
            $this->redirect('admin/recycler-details/' . $profileId);
            return;
        }

        $categoryIds = $_POST['category_ids'] ?? [];
        $highRiskMap = $_POST['can_handle_high_risk'] ?? [];

        if ($decision === 'VERIFIED' && empty($categoryIds)) {
            flash('auth_error', 'Assign at least one capability before verifying recycler.', 'alert alert-danger');
            $this->redirect('admin/recycler-details/' . $profileId);
            return;
        }

        $updated = $this->adminModel->verifyRecycler(
            $profileId,
            (int) $_SESSION['user_id'],
            $decision,
            $categoryIds,
            $highRiskMap
        );

        if (!$updated) {
            flash('auth_error', 'Failed to update recycler verification.', 'alert alert-danger');
            $this->redirect('admin/recycler-details/' . $profileId);
            return;
        }

        flash('auth_success', 'Recycler verification updated successfully.');
        $this->redirect('admin/recycler-details/' . $profileId);
    }

    public function categories(): void
    {
        requireRole('ADMIN');

        $this->view('admin/categories', [
            'title' => 'Category & Item Management',
            'categories' => $this->adminModel->getCategories(),
            'items' => $this->adminModel->getItems(),
            'errors' => []
        ]);
    }

    public function storeCategory(): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/categories');
            return;
        }

        $categoryName = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($categoryName)) {
            flash('auth_error', 'Category name is required.', 'alert alert-danger');
            $this->redirect('admin/categories');
            return;
        }

        $created = $this->adminModel->createCategory([
            'category_name' => $categoryName,
            'description' => $description
        ]);

        flash($created ? 'auth_success' : 'auth_error', $created ? 'Category created successfully.' : 'Failed to create category.', $created ? 'alert alert-success' : 'alert alert-danger');

        $this->redirect('admin/categories');
    }

    public function updateCategoryStatus(int $categoryId): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/categories');
            return;
        }

        $status = strtoupper(trim($_POST['status'] ?? ''));

        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            flash('auth_error', 'Invalid category status.', 'alert alert-danger');
            $this->redirect('admin/categories');
            return;
        }

        $updated = $this->adminModel->updateCategoryStatus($categoryId, $status);

        flash($updated ? 'auth_success' : 'auth_error', $updated ? 'Category status updated.' : 'Failed to update category.', $updated ? 'alert alert-success' : 'alert alert-danger');

        $this->redirect('admin/categories');
    }

    public function storeItem(): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/categories');
            return;
        }

        $data = [
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'item_name' => trim($_POST['item_name'] ?? ''),
            'collection_status' => strtoupper(trim($_POST['collection_status'] ?? '')),
            'default_risk_level' => strtoupper(trim($_POST['default_risk_level'] ?? 'LOW'))
        ];

        if (
            $data['category_id'] <= 0 ||
            empty($data['item_name']) ||
            !in_array($data['collection_status'], ['ACCEPTED', 'REVIEW_REQUIRED', 'NOT_COLLECTED'], true) ||
            !in_array($data['default_risk_level'], ['LOW', 'MEDIUM', 'HIGH'], true)
        ) {
            flash('auth_error', 'Invalid item data.', 'alert alert-danger');
            $this->redirect('admin/categories');
            return;
        }

        $created = $this->adminModel->createItem($data);

        flash($created ? 'auth_success' : 'auth_error', $created ? 'E-waste item created successfully.' : 'Failed to create item.', $created ? 'alert alert-success' : 'alert alert-danger');

        $this->redirect('admin/categories');
    }

    public function riskRules(): void
    {
        requireRole('ADMIN');

        $this->view('admin/risk_rules', [
            'title' => 'Risk Rule Management',
            'rules' => $this->adminModel->getRiskRules(),
            'categories' => $this->adminModel->getCategories(),
            'items' => $this->adminModel->getItems()
        ]);
    }

    public function storeRiskRule(): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/risk-rules');
            return;
        }

        $data = [
            'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
            'item_id' => !empty($_POST['item_id']) ? (int) $_POST['item_id'] : null,
            'condition_status' => strtoupper(trim($_POST['condition_status'] ?? 'UNKNOWN')),
            'risk_level' => strtoupper(trim($_POST['risk_level'] ?? 'MEDIUM')),
            'rule_description' => trim($_POST['rule_description'] ?? '')
        ];

        if (!$data['category_id'] && !$data['item_id']) {
            flash('auth_error', 'Select at least category or item for the rule.', 'alert alert-danger');
            $this->redirect('admin/risk-rules');
            return;
        }

        if (!in_array($data['condition_status'], ['WORKING', 'DAMAGED', 'BROKEN', 'LEAKING', 'UNKNOWN'], true)) {
            flash('auth_error', 'Invalid condition status.', 'alert alert-danger');
            $this->redirect('admin/risk-rules');
            return;
        }

        if (!in_array($data['risk_level'], ['LOW', 'MEDIUM', 'HIGH'], true)) {
            flash('auth_error', 'Invalid risk level.', 'alert alert-danger');
            $this->redirect('admin/risk-rules');
            return;
        }

        $created = $this->adminModel->createRiskRule($data);

        flash($created ? 'auth_success' : 'auth_error', $created ? 'Risk rule created successfully.' : 'Failed to create risk rule.', $created ? 'alert alert-success' : 'alert alert-danger');

        $this->redirect('admin/risk-rules');
    }

    public function updateRiskRuleStatus(int $ruleId): void
    {
        requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/risk-rules');
            return;
        }

        $status = strtoupper(trim($_POST['status'] ?? ''));

        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            flash('auth_error', 'Invalid rule status.', 'alert alert-danger');
            $this->redirect('admin/risk-rules');
            return;
        }

        $updated = $this->adminModel->updateRiskRuleStatus($ruleId, $status);

        flash($updated ? 'auth_success' : 'auth_error', $updated ? 'Risk rule status updated.' : 'Failed to update risk rule.', $updated ? 'alert alert-success' : 'alert alert-danger');

        $this->redirect('admin/risk-rules');
    }

    public function reports(): void
{
    requireRole('ADMIN');

    $reportModel = $this->model('ReportWorkflow');

    $this->view('admin/reports', [
        'title' => 'Admin Reports',
        'overview' => $reportModel->getOverviewStats(),
        'request_status_counts' => $reportModel->getRequestStatusCounts(),
        'monthly_requests' => $reportModel->getMonthlyRequestCounts(null, 8),
        'category_collected_summary' => $reportModel->getCategoryCollectedSummary(),
        'elot_status_counts' => $reportModel->getElotStatusCounts(),
        'bid_summary' => $reportModel->getBidSummary(null, 10),
        'recycler_verification_counts' => $reportModel->getRecyclerVerificationCounts(),
        'top_recyclers' => $reportModel->getTopRecyclersByBids(10),
        'audit_logs' => $reportModel->getRecentAuditLogs(25)
    ]);
}
}