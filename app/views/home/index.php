<section class="hero-card">
    <h1><?php echo htmlspecialchars($data['title'] ?? 'EcoLot LK'); ?></h1>

    <p>
        <?php echo htmlspecialchars($data['description'] ?? 'MVC core is running successfully.'); ?>
    </p>

    <?php if (isset($data['total_categories'])): ?>
        <div class="success-box">
            Database connected successfully.
            Total e-waste categories:
            <strong><?php echo htmlspecialchars($data['total_categories']); ?></strong>
        </div>
    <?php endif; ?>

    <div class="button-row">
        <a class="btn" href="<?php echo url('auth/login'); ?>">Go to Login</a>
        <a class="btn secondary" href="<?php echo url('home/db-test'); ?>">Test Database</a>
    </div>
</section>