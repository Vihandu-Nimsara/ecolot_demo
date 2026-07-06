<section class="hero-card">
    <h1>Admin Reports & Analytics</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('admin/dashboard'); ?>">Dashboard</a>
        <a class="btn secondary" href="<?php echo url('admin/users'); ?>">Users</a>
        <a class="btn secondary" href="<?php echo url('admin/recycler-verification'); ?>">Recycler Verification</a>
        <button class="btn" onclick="window.print()">Print Report</button>
    </div>

    <h2>System Overview</h2>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            Total Users:
            <strong><?php echo htmlspecialchars($data['overview']->total_users ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Total Requests:
            <strong><?php echo htmlspecialchars($data['overview']->total_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Completed Requests:
            <strong><?php echo htmlspecialchars($data['overview']->completed_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Pickup Records:
            <strong><?php echo htmlspecialchars($data['overview']->total_pickup_records ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Verified Pickups:
            <strong><?php echo htmlspecialchars($data['overview']->verified_pickups ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Total E-Lots:
            <strong><?php echo htmlspecialchars($data['overview']->total_elots ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Total Bids:
            <strong><?php echo htmlspecialchars($data['overview']->total_bids ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Winning Bids:
            <strong><?php echo htmlspecialchars($data['overview']->winning_bids ?? 0); ?></strong>
        </div>
    </div>
</section>

<section class="hero-card">
    <h2>Request Status Summary</h2>

    <?php if (empty($data['request_status_counts'])): ?>
        <p class="muted">No request data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total Requests</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['request_status_counts'] as $row): ?>
                        <tr>
                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($row->status); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row->total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>Monthly Request Trend</h2>

    <?php if (empty($data['monthly_requests'])): ?>
        <p class="muted">No monthly request data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Requests</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['monthly_requests'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row->month_label); ?></td>
                            <td><?php echo htmlspecialchars($row->total_requests); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>Verified Collected E-Waste by Category</h2>

    <?php if (empty($data['category_collected_summary'])): ?>
        <p class="muted">No verified collected item data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Pickup Item Records</th>
                        <th>Total Quantity</th>
                        <th>Total Weight</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['category_collected_summary'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row->category_name); ?></td>
                            <td><?php echo htmlspecialchars($row->pickup_item_count); ?></td>
                            <td><?php echo htmlspecialchars($row->total_quantity); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float)$row->total_weight, 2)); ?> kg</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>E-Lot Status Summary</h2>

    <?php if (empty($data['elot_status_counts'])): ?>
        <p class="muted">No E-Lot data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>E-Lot Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['elot_status_counts'] as $row): ?>
                        <tr>
                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($row->status); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row->total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>Recent E-Lot Bid Summary</h2>

    <?php if (empty($data['bid_summary'])): ?>
        <p class="muted">No E-Lot bid data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>E-Lot Code</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Bids</th>
                        <th>Highest Bid</th>
                        <th>Average Bid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['bid_summary'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row->elot_code); ?></td>
                            <td><?php echo htmlspecialchars($row->title); ?></td>
                            <td><?php echo htmlspecialchars($row->category_name); ?></td>
                            <td><?php echo htmlspecialchars($row->elot_status); ?></td>
                            <td><?php echo htmlspecialchars($row->bid_count); ?></td>
                            <td>
                                <?php echo $row->highest_bid !== null
                                    ? 'Rs. ' . htmlspecialchars(number_format((float)$row->highest_bid, 2))
                                    : '-'; ?>
                            </td>
                            <td>
                                <?php echo $row->average_bid !== null
                                    ? 'Rs. ' . htmlspecialchars(number_format((float)$row->average_bid, 2))
                                    : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>Recycler Verification Summary</h2>

    <?php if (empty($data['recycler_verification_counts'])): ?>
        <p class="muted">No recycler verification data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Verification Status</th>
                        <th>Total Recyclers</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['recycler_verification_counts'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row->verification_status); ?></td>
                            <td><?php echo htmlspecialchars($row->total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>Top Recyclers by Bids</h2>

    <?php if (empty($data['top_recyclers'])): ?>
        <p class="muted">No recycler bid data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Recycler Company</th>
                        <th>Verification</th>
                        <th>Total Bids</th>
                        <th>Winning Bids</th>
                        <th>Total Bid Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['top_recyclers'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row->company_name); ?></td>
                            <td><?php echo htmlspecialchars($row->verification_status); ?></td>
                            <td><?php echo htmlspecialchars($row->bid_count); ?></td>
                            <td><?php echo htmlspecialchars($row->winning_count ?? 0); ?></td>
                            <td>Rs. <?php echo htmlspecialchars(number_format((float)$row->total_bid_amount, 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>Recent Audit Logs</h2>

    <?php if (empty($data['audit_logs'])): ?>
        <p class="muted">No audit log data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['audit_logs'] as $log): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($log->log_id); ?></td>
                            <td><?php echo htmlspecialchars($log->full_name ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($log->role ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($log->action); ?></td>
                            <td><?php echo htmlspecialchars($log->description ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($log->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>