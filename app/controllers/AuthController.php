<?php

class AuthController extends Controller
{
    private User $userModel;
    private Area $areaModel;

    public function __construct()
    {
        $this->userModel = $this->model('User');
        $this->areaModel = $this->model('Area');
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';

            $errors = [];

            if (empty($email)) {
                $errors['email'] = 'Email is required.';
            }

            if (empty($password)) {
                $errors['password'] = 'Password is required.';
            }

            if (!empty($errors)) {
                $this->view('auth/login', [
                    'title' => 'Login',
                    'errors' => $errors,
                    'old' => ['email' => $email]
                ]);
                return;
            }

            $user = $this->userModel->findByEmail($email);

            if (!$user || !password_verify($password, $user->password_hash)) {
                $this->view('auth/login', [
                    'title' => 'Login',
                    'errors' => ['login' => 'Invalid email or password.'],
                    'old' => ['email' => $email]
                ]);
                return;
            }

            if ($user->status !== 'ACTIVE') {
                $message = match ($user->status) {
                    'PENDING' => 'Your account is pending approval.',
                    'SUSPENDED' => 'Your account has been suspended.',
                    'REJECTED' => 'Your account registration was rejected.',
                    default => 'Your account is not active.'
                };

                $this->view('auth/login', [
                    'title' => 'Login',
                    'errors' => ['login' => $message],
                    'old' => ['email' => $email]
                ]);
                return;
            }

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user->user_id;
            $_SESSION['full_name'] = $user->full_name;
            $_SESSION['email'] = $user->email;
            $_SESSION['role'] = $user->role;

            flash('auth_success', 'Login successful.');

            $this->redirect(roleDashboardPath($user->role));
            return;
        }

        $this->view('auth/login', [
            'title' => 'Login',
            'errors' => [],
            'old' => []
        ]);
    }

    public function register(): void
    {
        $areas = $this->areaModel->getActiveAreas();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $role = $_POST['role'] ?? '';

            $data = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'email' => strtolower(trim($_POST['email'] ?? '')),
                'phone' => trim($_POST['phone'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
                'role' => $role,

                'area_id' => (int) ($_POST['area_id'] ?? 0),
                'address_line1' => trim($_POST['address_line1'] ?? ''),
                'address_line2' => trim($_POST['address_line2'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),

                'company_name' => trim($_POST['company_name'] ?? ''),
                'license_no' => trim($_POST['license_no'] ?? ''),
                'license_expiry_date' => trim($_POST['license_expiry_date'] ?? ''),
                'recycler_address' => trim($_POST['recycler_address'] ?? '')
            ];

            $errors = $this->validateRegistration($data);

            if ($this->userModel->findByEmail($data['email'])) {
                $errors['email'] = 'This email is already registered.';
            }

            $selectedArea = null;

            if ($data['role'] === 'PUBLIC_USER') {
                $selectedArea = $this->areaModel->findById($data['area_id']);

                if (!$selectedArea) {
                    $errors['area_id'] = 'Please select a valid postal code area.';
                } else {
                    $data['postal_code'] = $selectedArea->postal_code;
                }
            }

            if (!empty($errors)) {
                $this->view('auth/register', [
                    'title' => 'Register',
                    'areas' => $areas,
                    'errors' => $errors,
                    'old' => $data
                ]);
                return;
            }

            if ($data['role'] === 'PUBLIC_USER') {
                $registered = $this->userModel->registerPublicUser($data);

                if ($registered) {
                    flash('auth_success', 'Registration successful. You can login now.');
                    $this->redirect('auth/login');
                    return;
                }
            }

            if ($data['role'] === 'AUTHORIZED_RECYCLER') {
                $registered = $this->userModel->registerRecycler($data);

                if ($registered) {
                    flash('auth_success', 'Recycler registration submitted. Please wait for admin approval.');
                    $this->redirect('auth/login');
                    return;
                }
            }

            $this->view('auth/register', [
                'title' => 'Register',
                'areas' => $areas,
                'errors' => ['register' => 'Registration failed. Please try again.'],
                'old' => $data
            ]);
            return;
        }

        $this->view('auth/register', [
            'title' => 'Register',
            'areas' => $areas,
            'errors' => [],
            'old' => []
        ]);
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();

        session_start();
        flash('auth_success', 'You have been logged out.');

        $this->redirect('auth/login');
    }

    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required.';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required.';
        }

        if (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        $allowedRoles = ['PUBLIC_USER', 'AUTHORIZED_RECYCLER'];

        if (!in_array($data['role'], $allowedRoles, true)) {
            $errors['role'] = 'Please select a valid account type.';
        }

        if ($data['role'] === 'PUBLIC_USER') {
            if (empty($data['address_line1'])) {
                $errors['address_line1'] = 'Address line 1 is required.';
            }

            if (empty($data['city'])) {
                $errors['city'] = 'City is required.';
            }

            if (empty($data['area_id'])) {
                $errors['area_id'] = 'Postal code area is required.';
            }
        }

        if ($data['role'] === 'AUTHORIZED_RECYCLER') {
            if (empty($data['company_name'])) {
                $errors['company_name'] = 'Company name is required.';
            }

            if (empty($data['license_no'])) {
                $errors['license_no'] = 'License number is required.';
            }

            if (empty($data['license_expiry_date'])) {
                $errors['license_expiry_date'] = 'License expiry date is required.';
            }

            if (empty($data['recycler_address'])) {
                $errors['recycler_address'] = 'Company address is required.';
            }
        }

        return $errors;
    }
}