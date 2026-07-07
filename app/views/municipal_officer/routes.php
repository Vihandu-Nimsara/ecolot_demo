<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">09</p>
            <h1 class="page-title">Collection Routes</h1>
            <p class="page-subtitle">Track planned, assigned, and in-progress municipal collection routes.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
    </p>

    <div class="button-row">
        <a class="btn" href="<?php echo url('municipal-officer/create-route'); ?>">
            Create Route
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">
            Back to Dashboard
        </a>
    </div>

    <?php if (empty($data['routes'])): ?>
        <p class="muted">No collection routes have been created yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Route ID</th>
                        <th>Route</th>
                        <th>Campaign</th>
                        <th>Area</th>
                        <th>Date</th>
                        <th>Collector</th>
                        <th>Vehicle</th>
                        <th>Stops</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['routes'] as $route): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($route->route_id); ?></td>

                            <td><?php echo htmlspecialchars($route->route_name); ?></td>

                            <td><?php echo htmlspecialchars($route->campaign_name); ?></td>

                            <td>
                                <?php echo htmlspecialchars($route->postal_code . ' - ' . $route->area_name); ?>
                            </td>

                            <td><?php echo htmlspecialchars($route->collection_date); ?></td>

                            <td><?php echo htmlspecialchars($route->collector_name ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($route->vehicle_no ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($route->stop_count); ?></td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($route->status); ?>
                                </span>
                            </td>

                            <td>
                                <a href="<?php echo url('municipal-officer/route-details/' . $route->route_id); ?>">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
