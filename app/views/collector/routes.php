<section class="hero-card">
    <h1>Assigned Routes</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
    </p>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('collector/dashboard'); ?>">
            Back to Dashboard
        </a>
    </div>

    <?php if (empty($data['routes'])): ?>
        <p class="muted">No routes are currently assigned to you.</p>
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
                        <th>Vehicle</th>
                        <th>Stops</th>
                        <th>Pending</th>
                        <th>Collected</th>
                        <th>Failed</th>
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

                            <td>
                                <?php echo htmlspecialchars(($route->vehicle_no ?? '-') . ' - ' . ($route->vehicle_type ?? '-')); ?>
                            </td>

                            <td><?php echo htmlspecialchars($route->stop_count); ?></td>
                            <td><?php echo htmlspecialchars($route->pending_count ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($route->collected_count ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($route->failed_count ?? 0); ?></td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($route->status); ?>
                                </span>
                            </td>

                            <td>
                                <a href="<?php echo url('collector/route-details/' . $route->route_id); ?>">
                                    View Stops
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>