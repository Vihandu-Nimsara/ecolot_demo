<section class="hero-card">
    <h1>Collector Dashboard</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        Welcome, <?php echo htmlspecialchars(currentUserName()); ?>.
        <br>
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
        <br>
        <strong>Availability:</strong>
        <?php echo htmlspecialchars($data['profile']->availability_status); ?>
    </p>

    <div class="dashboard-grid">
        <a class="dashboard-card link-card" href="<?php echo url('collector/routes'); ?>">
            Assigned Routes:
            <strong><?php echo htmlspecialchars($data['stats']->total_routes ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('collector/routes'); ?>">
            Pending Stops:
            <strong><?php echo htmlspecialchars($data['stats']->pending_stops ?? 0); ?></strong>
        </a>

        <div class="dashboard-card">
            Collected Stops:
            <strong><?php echo htmlspecialchars($data['stats']->collected_stops ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Failed Stops:
            <strong><?php echo htmlspecialchars($data['stats']->failed_stops ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Submitted Pickup Records:
            <strong><?php echo htmlspecialchars($data['stats']->submitted_pickups ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Completed Routes:
            <strong><?php echo htmlspecialchars($data['stats']->completed_routes ?? 0); ?></strong>
        </div>
    </div>
</section>