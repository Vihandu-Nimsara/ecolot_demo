<section class="hero-card">
    <h1>Request Details</h1>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('public-user/requests'); ?>">
            Back to Requests
        </a>
    </div>

    <div class="info-box">
        <strong>Request ID:</strong>
        #<?php echo htmlspecialchars($data['request']->request_id); ?>
        <br>

        <strong>Status:</strong>
        <?php echo htmlspecialchars($data['request']->status); ?>
        <br>

        <strong>Collection Date:</strong>
        <?php echo htmlspecialchars($data['request']->collection_date); ?>
        <br>

        <strong>Area:</strong>
        <?php echo htmlspecialchars($data['request']->postal_code . ' - ' . $data['request']->area_name); ?>
        <br>

        <strong>Pickup Address:</strong>
        <?php echo nl2br(htmlspecialchars($data['request']->pickup_address)); ?>
        <br>

        <strong>Contact Phone:</strong>
        <?php echo htmlspecialchars($data['request']->contact_phone); ?>
        <br>

        <strong>Special Note:</strong>
        <?php echo nl2br(htmlspecialchars($data['request']->special_note ?? '-')); ?>
    </div>

    <h2>Requested Items</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Estimated Weight</th>
                    <th>Condition</th>
                    <th>Risk Flag</th>
                    <th>Note</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($data['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item->category_name); ?></td>

                        <td><?php echo htmlspecialchars($item->item_name); ?></td>

                        <td><?php echo htmlspecialchars($item->quantity); ?></td>

                        <td>
                            <?php echo $item->estimated_weight_kg !== null
                                ? htmlspecialchars(number_format((float)$item->estimated_weight_kg, 2)) . ' kg'
                                : '-'; ?>
                        </td>

                        <td><?php echo htmlspecialchars($item->condition_status); ?></td>

                        <td>
                            <span class="status-badge">
                                <?php echo htmlspecialchars($item->risk_flag); ?>
                            </span>
                        </td>

                        <td><?php echo htmlspecialchars($item->note ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2>Status History</h2>

    <?php if (empty($data['history'])): ?>
        <p class="muted">No status history found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Old Status</th>
                        <th>New Status</th>
                        <th>Changed By</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['history'] as $history): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($history->old_status ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($history->new_status); ?></td>

                            <td><?php echo htmlspecialchars($history->changed_by_name ?? 'System'); ?></td>

                            <td><?php echo htmlspecialchars($history->note ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($history->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>