<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">07</p>
            <h1 class="page-title">Officer Dashboard</h1>
            <p class="page-subtitle">
                Manage campaigns, schedules, requests, routes, collections, and E-Lots.
            </p>
            <p class="muted">
                <?php echo htmlspecialchars(currentUserName()); ?> ·
                <?php echo htmlspecialchars($data['profile']->council_name); ?>
            </p>
        </div>

        <span class="role-badge">MUNICIPAL_OFFICER</span>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="stats-grid">
        <a class="stat-card link-card" href="<?php echo url('municipal-officer/campaigns'); ?>">
            <span class="stat-label">Active Campaigns</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->active_campaigns ?? 0); ?></strong>
        </a>

        <a class="stat-card link-card" href="<?php echo url('municipal-officer/requests/submitted'); ?>">
            <span class="stat-label">Pending Requests</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->submitted_requests ?? 0); ?></strong>
        </a>

        <a class="stat-card link-card" href="<?php echo url('municipal-officer/routes'); ?>">
            <span class="stat-label">Planned Routes</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->scheduled_routes ?? 0); ?></strong>
        </a>

        <a class="stat-card link-card" href="<?php echo url('municipal-officer/pickup-records/pending'); ?>">
            <span class="stat-label">Pending Verifications</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->pending_pickup_records ?? 0); ?></strong>
        </a>

        <a class="stat-card link-card" href="<?php echo url('municipal-officer/elots'); ?>">
            <span class="stat-label">Open E-Lots</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->open_elots ?? 0); ?></strong>
        </a>
    </div>
</section>

<section class="table-card">
    <div class="page-header">
        <div>
            <h2 class="section-title">Today&apos;s / Upcoming Collection Schedule</h2>
            <p class="muted">Upcoming area collection dates for your council.</p>
        </div>

        <a class="btn secondary" href="<?php echo url('municipal-officer/area-schedule'); ?>">Manage Schedules</a>
    </div>

    <?php if (empty($data['upcoming_area_dates'])): ?>
        <div class="empty-state">No upcoming area collection schedules found.</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Schedule ID</th>
                        <th>Postal Code</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Requests</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['upcoming_area_dates'] as $areaDate): ?>
                        <tr>
                            <td>SCH<?php echo htmlspecialchars(str_pad((string) $areaDate->date_id, 4, '0', STR_PAD_LEFT)); ?></td>
                            <td><?php echo htmlspecialchars($areaDate->postal_code); ?></td>
                            <td><?php echo htmlspecialchars($areaDate->area_name); ?></td>
                            <td><?php echo htmlspecialchars($areaDate->collection_date); ?></td>
                            <td>
                                <?php echo htmlspecialchars($areaDate->request_count); ?> /
                                <?php echo htmlspecialchars($areaDate->max_requests); ?>
                            </td>
                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($areaDate->status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
