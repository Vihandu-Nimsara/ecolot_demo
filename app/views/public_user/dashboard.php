<section class="hero-card">
    <h1>Public User Dashboard</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>Welcome, <?php echo htmlspecialchars(currentUserName()); ?>.</p>

    <div class="dashboard-grid">
        <a class="dashboard-card link-card" href="<?php echo url('public-user/create-request'); ?>">
            Submit E-Waste Request
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('public-user/requests'); ?>">
            Track My Requests
        </a>

        <div class="dashboard-card">
            Total Requests:
            <strong><?php echo htmlspecialchars($data['stats']->total_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Submitted:
            <strong><?php echo htmlspecialchars($data['stats']->submitted_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Approved:
            <strong><?php echo htmlspecialchars($data['stats']->approved_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Completed:
            <strong><?php echo htmlspecialchars($data['stats']->completed_requests ?? 0); ?></strong>
        </div>
    </div>
</section>