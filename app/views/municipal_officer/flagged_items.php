<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="eyebrow">Municipal Officer</p>
            <h1>Flagged Item Review</h1>
            <p class="muted">
                <?php echo htmlspecialchars($data['profile']->council_name); ?>
            </p>
        </div>

        <div class="page-actions">
            <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">Dashboard</a>
            <a class="btn secondary" href="<?php echo url('municipal-officer/pickup-records/pending'); ?>">Pickup Verification</a>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="filter-tabs">
        <?php foreach (['PENDING', 'SPECIAL_HANDLING_REQUIRED', 'APPROVED_FOR_COLLECTION', 'REJECTED', 'ALL'] as $status): ?>
            <a
                class="<?php echo $data['current_status'] === $status ? 'active' : ''; ?>"
                href="<?php echo url('municipal-officer/flagged-items') . '?status=' . urlencode($status); ?>"
            >
                <?php echo htmlspecialchars(str_replace('_', ' ', $status)); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($data['flags'])): ?>
        <div class="empty-state">No flagged items found for this filter.</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Flag</th>
                        <th>Item</th>
                        <th>Request / Pickup</th>
                        <th>Area</th>
                        <th>Reason</th>
                        <th>Risk</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['flags'] as $flag): ?>
                        <tr>
                            <td>
                                #<?php echo htmlspecialchars($flag->flag_id); ?>
                                <br>
                                <small><?php echo htmlspecialchars($flag->created_at); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($flag->item_name ?? 'Unknown item'); ?></strong>
                                <br>
                                <small><?php echo htmlspecialchars($flag->category_name ?? '-'); ?></small>
                                <br>
                                <small>
                                    <?php echo htmlspecialchars(($flag->quantity ?? '-') . ' item(s), ' . ($flag->condition_status ?? 'UNKNOWN')); ?>
                                </small>
                            </td>
                            <td>
                                <?php if (!empty($flag->request_id)): ?>
                                    <a href="<?php echo url('municipal-officer/request-details/' . $flag->request_id); ?>">
                                        Request #<?php echo htmlspecialchars($flag->request_id); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($flag->pickup_id)): ?>
                                    <br>
                                    <a href="<?php echo url('municipal-officer/pickup-record-details/' . $flag->pickup_id); ?>">
                                        Pickup #<?php echo htmlspecialchars($flag->pickup_id); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($flag->postal_code . ' - ' . $flag->area_name); ?></td>
                            <td>
                                <?php echo htmlspecialchars($flag->flag_reason); ?>
                                <br>
                                <small>Flagged by <?php echo htmlspecialchars($flag->flagged_by_name); ?></small>
                            </td>
                            <td><span class="status-badge warning"><?php echo htmlspecialchars($flag->risk_level); ?></span></td>
                            <td>
                                <span class="status-badge"><?php echo htmlspecialchars($flag->review_status); ?></span>
                                <?php if (!empty($flag->reviewed_by_name)): ?>
                                    <br>
                                    <small>By <?php echo htmlspecialchars($flag->reviewed_by_name); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($flag->review_status === 'PENDING'): ?>
                                    <form class="stacked-form" method="POST" action="<?php echo url('municipal-officer/update-flagged-item/' . $flag->flag_id); ?>">
                                        <select name="review_status" required>
                                            <option value="APPROVED_FOR_COLLECTION">Approve collection</option>
                                            <option value="SPECIAL_HANDLING_REQUIRED">Special handling</option>
                                            <option value="REJECTED">Reject</option>
                                        </select>
                                        <textarea name="officer_note" placeholder="Officer note"></textarea>
                                        <button class="btn compact" type="submit">Save</button>
                                    </form>
                                <?php else: ?>
                                    <small><?php echo htmlspecialchars($flag->officer_note ?: 'Reviewed'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
