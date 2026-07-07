<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">09</p>
            <h1 class="page-title">Request Review & Route Assignment</h1>
            <p class="page-subtitle">Review pickup requests and assign approved requests to collection routes.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p class="muted"><?php echo htmlspecialchars($data['profile']->council_name); ?></p>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests'); ?>">All</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests/submitted'); ?>">Submitted</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests/approved'); ?>">Approved</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests/rejected'); ?>">Rejected</a>
    </div>

    <p class="muted">
        Current filter: <?php echo htmlspecialchars($data['current_status']); ?>
    </p>

    <?php if (empty($data['requests'])): ?>
        <p class="muted">No requests found for this filter.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User</th>
                        <th>Collection Date</th>
                        <th>Area</th>
                        <th>Items</th>
                        <th>Estimated Weight</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['requests'] as $request): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($request->request_id); ?></td>

                            <td>
                                <?php echo htmlspecialchars($request->public_user_name); ?>
                                <br>
                                <small><?php echo htmlspecialchars($request->public_user_email); ?></small>
                            </td>

                            <td><?php echo htmlspecialchars($request->collection_date); ?></td>

                            <td>
                                <?php echo htmlspecialchars($request->postal_code . ' - ' . $request->area_name); ?>
                            </td>

                            <td><?php echo htmlspecialchars($request->item_count); ?></td>

                            <td>
                                <?php echo htmlspecialchars(number_format((float)$request->total_estimated_weight, 2)); ?> kg
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($request->status); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($request->created_at); ?></td>

                            <td>
                                <a href="<?php echo url('municipal-officer/request-details/' . $request->request_id); ?>">
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
