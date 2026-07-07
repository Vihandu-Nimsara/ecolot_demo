<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">09</p>
            <h1 class="page-title">Route Details</h1>
            <p class="page-subtitle">Review assigned route stops and request status for this route.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/routes'); ?>">
            Back to Routes
        </a>
    </div>

    <div class="info-box">
        <strong>Route ID:</strong>
        #<?php echo htmlspecialchars($data['route']->route_id); ?>
        <br>

        <strong>Route Name:</strong>
        <?php echo htmlspecialchars($data['route']->route_name); ?>
        <br>

        <strong>Campaign:</strong>
        <?php echo htmlspecialchars($data['route']->campaign_name); ?>
        <br>

        <strong>Area:</strong>
        <?php echo htmlspecialchars($data['route']->postal_code . ' - ' . $data['route']->area_name); ?>
        <br>

        <strong>Collection Date:</strong>
        <?php echo htmlspecialchars($data['route']->collection_date); ?>
        <br>

        <strong>Collector:</strong>
        <?php echo htmlspecialchars($data['route']->collector_name ?? '-'); ?>
        <?php if (!empty($data['route']->collector_email)): ?>
            |
            <?php echo htmlspecialchars($data['route']->collector_email); ?>
        <?php endif; ?>
        <br>

        <strong>Vehicle:</strong>
        <?php echo htmlspecialchars(($data['route']->vehicle_no ?? '-') . ' - ' . ($data['route']->vehicle_type ?? '-')); ?>
        <br>

        <strong>Status:</strong>
        <?php echo htmlspecialchars($data['route']->status); ?>
    </div>

    <h2>Route Stops</h2>

    <?php if (empty($data['stops'])): ?>
        <p class="muted">No stops assigned to this route.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Stop Order</th>
                        <th>Request ID</th>
                        <th>Public User</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Items</th>
                        <th>Weight</th>
                        <th>Stop Status</th>
                        <th>Request Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['stops'] as $stop): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stop->stop_order); ?></td>

                            <td>#<?php echo htmlspecialchars($stop->request_id); ?></td>

                            <td><?php echo htmlspecialchars($stop->public_user_name); ?></td>

                            <td><?php echo htmlspecialchars($stop->pickup_address); ?></td>

                            <td><?php echo htmlspecialchars($stop->contact_phone); ?></td>

                            <td><?php echo htmlspecialchars($stop->item_count); ?></td>

                            <td>
                                <?php echo htmlspecialchars(number_format((float)$stop->total_estimated_weight, 2)); ?> kg
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($stop->stop_status); ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($stop->request_status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
