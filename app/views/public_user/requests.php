<section class="hero-card">
    <h1>My E-Waste Requests</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn" href="<?php echo url('public-user/create-request'); ?>">
            Submit New Request
        </a>
    </div>

    <?php if (empty($data['requests'])): ?>
        <p class="muted">You have not submitted any e-waste requests yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
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
                                <a href="<?php echo url('public-user/request-details/' . $request->request_id); ?>">
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