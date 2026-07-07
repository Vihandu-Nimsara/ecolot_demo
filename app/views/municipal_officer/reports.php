<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">Reports</p>
            <h1 class="page-title">Council Reports & Analytics</h1>
            <p class="page-subtitle">Review council workload, collection, E-Lot, route, and bid performance.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
    </p>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">Dashboard</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests'); ?>">Requests</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/elots'); ?>">E-Lots</a>
        <button class="btn" onclick="window.print()">Print Report</button>
    </div>

    <h2>Council Overview</h2>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            Total Requests:
            <strong><?php echo htmlspecialchars($data['overview']->total_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Submitted:
            <strong><?php echo htmlspecialchars($data['overview']->submitted_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Approved:
            <strong><?php echo htmlspecialchars($data['overview']->approved_requests ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Completed:
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
            E-Lots:
            <strong><?php echo htmlspecialchars($data['overview']->total_elots ?? 0); ?></strong>
        </div>

        <div class="dashboard-card">
            Bids:
            <strong><?php echo htmlspecialchars($data['overview']->total_bids ?? 0); ?></strong>
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
    <h2>Route Status Summary</h2>

    <?php if (empty($data['route_status_counts'])): ?>
        <p class="muted">No route data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Route Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['route_status_counts'] as $row): ?>
                        <tr>
                            <td><span class="status-badge"><?php echo htmlspecialchars($row->status); ?></span></td>
                            <td><?php echo htmlspecialchars($row->total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="hero-card">
    <h2>Collector Workload Summary</h2>

    <?php if (empty($data['collector_workload'])): ?>
        <p class="muted">No collector workload data found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Collector</th>
                        <th>Availability</th>
                        <th>Routes</th>
                        <th>Stops</th>
                        <th>Collected Stops</th>
                        <th>Failed Stops</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['collector_workload'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row->collector_name); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($row->availability_status); ?></span></td>
                            <td><?php echo htmlspecialchars($row->route_count); ?></td>
                            <td><?php echo htmlspecialchars($row->stop_count); ?></td>
                            <td><?php echo htmlspecialchars($row->collected_stops ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($row->failed_stops ?? 0); ?></td>
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
    <h2>E-Lot Bid Summary</h2>

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
