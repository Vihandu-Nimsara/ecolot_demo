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
<body>

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