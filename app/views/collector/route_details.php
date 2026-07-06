<section class="hero-card">
    <h1>Route Details</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('collector/routes'); ?>">
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

        <strong>Vehicle:</strong>
        <?php echo htmlspecialchars(($data['route']->vehicle_no ?? '-') . ' - ' . ($data['route']->vehicle_type ?? '-')); ?>
        <br>

        <strong>Route Status:</strong>
        <?php echo htmlspecialchars($data['route']->status); ?>
    </div>

    <h2>Pickup Stops</h2>

    <?php if (empty($data['stops'])): ?>
        <p class="muted">No stops found for this route.</p>
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
                        <th>Action</th>
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

                            <td>
                                <?php if ((int) $stop->pickup_record_count > 0): ?>
                                    <span class="muted">Submitted</span>
                                <?php elseif ($stop->stop_status === 'PENDING'): ?>
                                    <a href="<?php echo url('collector/record-pickup/' . $stop->stop_id); ?>">
                                        Record Pickup
                                    </a>
                                <?php else: ?>
                                    <span class="muted">Closed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>