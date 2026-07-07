<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo isset($data['title']) ? htmlspecialchars($data['title']) . ' | ' . APP_NAME : APP_NAME; ?>
    </title>

    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<?php
$isMunicipalOfficerLayout = isLoggedIn() && currentUserRole() === 'MUNICIPAL_OFFICER';
$currentPath = trim($_GET['url'] ?? '', '/');
$municipalNavItems = [
    ['label' => 'Dashboard', 'path' => 'municipal-officer/dashboard'],
    ['label' => 'Campaigns', 'path' => 'municipal-officer/campaigns'],
    ['label' => 'Area Schedules', 'path' => 'municipal-officer/area-schedule'],
    ['label' => 'Requests', 'path' => 'municipal-officer/requests'],
    ['label' => 'Routes', 'path' => 'municipal-officer/routes'],
    ['label' => 'Collection Records', 'path' => 'municipal-officer/pickup-records/pending'],
    ['label' => 'E-Lots', 'path' => 'municipal-officer/elots'],
    ['label' => 'Feedback', 'path' => 'municipal-officer/feedback'],
];
$isMunicipalNavActive = static function (string $path) use ($currentPath): bool {
    if ($currentPath === $path) {
        return true;
    }

    $groups = [
        'municipal-officer/campaigns' => ['municipal-officer/create-campaign'],
        'municipal-officer/area-schedule' => ['municipal-officer/create-area-date'],
        'municipal-officer/requests' => ['municipal-officer/request-details'],
        'municipal-officer/routes' => ['municipal-officer/create-route', 'municipal-officer/route-details'],
        'municipal-officer/pickup-records/pending' => ['municipal-officer/pickup-records', 'municipal-officer/pickup-record-details', 'municipal-officer/verified-pool', 'municipal-officer/flagged-items'],
        'municipal-officer/elots' => ['municipal-officer/create-elot', 'municipal-officer/elot-details', 'municipal-officer/elot-bids'],
        'municipal-officer/feedback' => ['municipal-officer/feedback-details'],
    ];

    foreach ($groups[$path] ?? [] as $prefix) {
        if (str_starts_with($currentPath, $prefix)) {
            return true;
        }
    }

    return false;
};
?>
<body class="<?php echo $isMunicipalOfficerLayout ? 'municipal-shell' : ''; ?>">

<?php if ($isMunicipalOfficerLayout): ?>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo url('municipal-officer/dashboard'); ?>" class="sidebar-brand">
                <span class="sidebar-brand-mark">EL</span>
                <span>
                    EcoLot LK
                    <small class="sidebar-subtitle">Workspace</small>
                </span>
            </a>

            <nav class="sidebar-nav" aria-label="Municipal officer navigation">
                <?php foreach ($municipalNavItems as $item): ?>
                    <a
                        class="sidebar-link <?php echo $isMunicipalNavActive($item['path']) ? 'active' : ''; ?>"
                        href="<?php echo url($item['path']); ?>"
                    >
                        <?php echo htmlspecialchars($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo url('municipal-officer/profile'); ?>">
                    <?php echo htmlspecialchars(currentUserName()); ?>
                </a>
                <a href="<?php echo url('auth/logout'); ?>">Logout</a>
            </div>
        </aside>

        <main class="main-content">
<?php else: ?>
    <header class="main-header">
        <div class="container nav-wrapper">
            <a href="<?php echo url(); ?>" class="brand">EcoLot LK</a>

            <nav>
                <a href="<?php echo url(); ?>">Home</a>

                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo url(roleDashboardPath()); ?>">Dashboard</a>
                    <a href="<?php echo url('auth/logout'); ?>">Logout</a>
                <?php else: ?>
                    <a href="<?php echo url('auth/register'); ?>">Register</a>
                    <a href="<?php echo url('auth/login'); ?>">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
<?php endif; ?>
