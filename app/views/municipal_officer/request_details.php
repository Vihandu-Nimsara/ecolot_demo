<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">09</p>
            <h1 class="page-title">Request Review</h1>
            <p class="page-subtitle">Inspect request details, requested items, flags, and approval status.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests'); ?>">
            Back to Requests
        </a>
    </div>

    <div class="info-box">
        <strong>Request ID:</strong>
        #<?php echo htmlspecialchars($data['request']->request_id); ?>
        <br>

        <strong>Public User:</strong>
        <?php echo htmlspecialchars($data['request']->public_user_name); ?>
        |
        <?php echo htmlspecialchars($data['request']->public_user_email); ?>
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
                    <th>Collection Status</th>
                    <th>Risk Level</th>
                    <th>Quantity</th>
                    <th>Weight</th>
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
                        <td><?php echo htmlspecialchars($item->collection_status); ?></td>
                        <td><?php echo htmlspecialchars($item->default_risk_level); ?></td>
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

    <h2>Flagged Items</h2>

    <?php if (empty($data['flags'])): ?>
        <p class="muted">No flagged items for this request.</p>
    <?php else: ?>
        <div class="alert alert-danger">
            This request contains items that need officer attention before approval.
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Condition</th>
                        <th>Risk Level</th>
                        <th>Reason</th>
                        <th>Review Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['flags'] as $flag): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($flag->category_name . ' - ' . $flag->item_name); ?>
                            </td>
                            <td><?php echo htmlspecialchars($flag->quantity); ?></td>
                            <td><?php echo htmlspecialchars($flag->condition_status); ?></td>
                            <td><?php echo htmlspecialchars($flag->risk_level); ?></td>
                            <td><?php echo htmlspecialchars($flag->flag_reason); ?></td>
                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($flag->review_status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($data['request']->status === 'SUBMITTED'): ?>
        <h2>Officer Decision</h2>

        <form method="POST" action="<?php echo url('municipal-officer/update-request-status/' . $data['request']->request_id); ?>">
            <div class="form-group">
                <label for="note">Officer Note</label>
                <textarea
                    id="note"
                    name="note"
                    placeholder="Add approval note or rejection reason"
                ></textarea>
            </div>

            <div class="button-row">
                <button type="submit" name="action" value="APPROVED" class="btn">
                    Approve Request
                </button>

                <button type="submit" name="action" value="REJECTED" class="btn danger">
                    Reject Request
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="info-box">
            This request is already reviewed. Current status:
            <strong><?php echo htmlspecialchars($data['request']->status); ?></strong>
        </div>
    <?php endif; ?>

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
