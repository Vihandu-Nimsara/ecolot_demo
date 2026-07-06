<section class="hero-card">
    <h1>Municipal Officer Dashboard</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        Welcome, <?php echo htmlspecialchars(currentUserName()); ?>.
        <br>
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
    </p>

    <div class="dashboard-grid">

    <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/reports'); ?>">
    Council Reports
</a>
        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/requests/submitted'); ?>">
            Submitted Requests:
            <strong><?php echo htmlspecialchars($data['stats']->submitted_requests ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/requests/approved'); ?>">
            Approved Requests:
            <strong><?php echo htmlspecialchars($data['stats']->approved_requests ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/routes'); ?>">
            Collection Routes
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/create-route'); ?>">
            Create Route
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/pickup-records/pending'); ?>">
            Pending Pickup Verification:
            <strong><?php echo htmlspecialchars($data['stats']->pending_pickup_records ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/verified-pool'); ?>">
            Verified E-Waste Pool:
            <strong><?php echo htmlspecialchars($data['stats']->verified_pool_items ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/elots'); ?>">
            Manage E-Lots
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/create-elot'); ?>">
            Create E-Lot
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('municipal-officer/campaigns'); ?>">
            Monthly Campaigns:
            <strong><?php echo htmlspecialchars($data['stats']->total_campaigns ?? 0); ?></strong>
        </a>

        <div class="dashboard-card">
            Pending Flag Reviews:
            <strong><?php echo htmlspecialchars($data['stats']->pending_flags ?? 0); ?></strong>
        </div>
    </div>
</section>