<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">10</p>
            <h1 class="page-title">Collection Verification</h1>
            <p class="page-subtitle">Verify pickup records submitted by collectors before creating E-Lots.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p class="muted"><?php echo htmlspecialchars($data['profile']->council_name); ?></p>

    <div class="stats-grid">
        <a class="stat-card link-card" href="<?php echo url('municipal-officer/pickup-records/pending'); ?>">
            <span class="stat-label">Pending Records</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->pending_pickup_records ?? 0); ?></strong>
        </a>

        <a class="stat-card link-card" href="<?php echo url('municipal-officer/pickup-records/verified'); ?>">
            <span class="stat-label">Verified Records</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->verified_pickup_records ?? 0); ?></strong>
        </a>

        <a class="stat-card link-card" href="<?php echo url('municipal-officer/pickup-records/rejected'); ?>">
            <span class="stat-label">Rejected Records</span>
            <strong class="stat-number"><?php echo htmlspecialchars($data['stats']->rejected_pickup_records ?? 0); ?></strong>
        </a>
    </div>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/pickup-records/pending'); ?>">Pending</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/pickup-records/verified'); ?>">Verified</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/pickup-records/rejected'); ?>">Rejected</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/pickup-records/all'); ?>">All</a>
        <a class="btn" href="<?php echo url('municipal-officer/verified-pool'); ?>">Verified Pool</a>
    </div>

    <p class="muted">
        Current filter: <?php echo htmlspecialchars($data['current_status']); ?>
    </p>

    <?php if (empty($data['pickup_records'])): ?>
        <p class="muted">No pickup records found for this filter.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Pickup ID</th>
                        <th>Request</th>
                        <th>Route</th>
                        <th>Collector</th>
                        <th>Public User</th>
                        <th>Area</th>
                        <th>Pickup Status</th>
                        <th>Items</th>
                        <th>Total Weight</th>
                        <th>Verification</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['pickup_records'] as $record): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($record->pickup_id); ?></td>

                            <td>#<?php echo htmlspecialchars($record->request_id); ?></td>

                            <td>
                                <?php echo htmlspecialchars($record->route_name); ?>
                                <br>
                                <small><?php echo htmlspecialchars($record->collection_date); ?></small>
                            </td>

                            <td><?php echo htmlspecialchars($record->collector_name); ?></td>

                            <td><?php echo htmlspecialchars($record->public_user_name); ?></td>

                            <td>
                                <?php echo htmlspecialchars($record->postal_code . ' - ' . $record->area_name); ?>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($record->pickup_status); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($record->collected_item_count); ?></td>

                            <td>
                                <?php echo $record->total_collected_weight_kg !== null
                                    ? htmlspecialchars(number_format((float)$record->total_collected_weight_kg, 2)) . ' kg'
                                    : '-'; ?>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($record->verification_status); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($record->submitted_at); ?></td>

                            <td>
                                <a href="<?php echo url('municipal-officer/pickup-record-details/' . $record->pickup_id); ?>">
                                    Review
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
