<section class="hero-card">
    <h1>Pickup Record Details</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/pickup-records'); ?>">
            Back to Pickup Records
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/route-details/' . $data['pickup']->route_id); ?>">
            View Route
        </a>
    </div>

    <div class="info-box">
        <strong>Pickup ID:</strong>
        #<?php echo htmlspecialchars($data['pickup']->pickup_id); ?>
        <br>

        <strong>Request ID:</strong>
        #<?php echo htmlspecialchars($data['pickup']->request_id); ?>
        <br>

        <strong>Route:</strong>
        <?php echo htmlspecialchars($data['pickup']->route_name); ?>
        |
        <?php echo htmlspecialchars($data['pickup']->collection_date); ?>
        <br>

        <strong>Collector:</strong>
        <?php echo htmlspecialchars($data['pickup']->collector_name); ?>
        |
        <?php echo htmlspecialchars($data['pickup']->collector_email); ?>
        <br>

        <strong>Public User:</strong>
        <?php echo htmlspecialchars($data['pickup']->public_user_name); ?>
        |
        <?php echo htmlspecialchars($data['pickup']->public_user_email); ?>
        <br>

        <strong>Area:</strong>
        <?php echo htmlspecialchars($data['pickup']->postal_code . ' - ' . $data['pickup']->area_name); ?>
        <br>

        <strong>Pickup Address:</strong>
        <?php echo nl2br(htmlspecialchars($data['pickup']->pickup_address)); ?>
        <br>

        <strong>Contact Phone:</strong>
        <?php echo htmlspecialchars($data['pickup']->contact_phone); ?>
        <br>

        <strong>Pickup Status:</strong>
        <?php echo htmlspecialchars($data['pickup']->pickup_status); ?>
        <br>

        <strong>Verification Status:</strong>
        <?php echo htmlspecialchars($data['pickup']->verification_status); ?>
        <br>

        <strong>Collector Note:</strong>
        <?php echo nl2br(htmlspecialchars($data['pickup']->collector_note ?? '-')); ?>
        <br>

        <strong>Officer Note:</strong>
        <?php echo nl2br(htmlspecialchars($data['pickup']->officer_note ?? '-')); ?>
    </div>

    <h2>Collected Items</h2>

    <?php if (empty($data['items'])): ?>
        <p class="muted">No collected items recorded.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Collected Qty</th>
                        <th>Weight</th>
                        <th>Condition</th>
                        <th>Risk Level</th>
                        <th>Collection Status</th>
                        <th>Note</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->category_name); ?></td>

                            <td><?php echo htmlspecialchars($item->item_name); ?></td>

                            <td><?php echo htmlspecialchars($item->collected_quantity); ?></td>

                            <td>
                                <?php echo $item->collected_weight_kg !== null
                                    ? htmlspecialchars(number_format((float)$item->collected_weight_kg, 2)) . ' kg'
                                    : '-'; ?>
                            </td>

                            <td><?php echo htmlspecialchars($item->condition_status); ?></td>

                            <td><?php echo htmlspecialchars($item->default_risk_level); ?></td>

                            <td><?php echo htmlspecialchars($item->collection_status); ?></td>

                            <td><?php echo htmlspecialchars($item->note ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Pickup Flags</h2>

    <?php if (empty($data['flags'])): ?>
        <p class="muted">No collector-created flags for this pickup record.</p>
    <?php else: ?>
        <div class="alert alert-danger">
            This pickup contains items flagged by collector or system rules. Review carefully before verification.
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Condition</th>
                        <th>Risk Level</th>
                        <th>Reason</th>
                        <th>Flagged By</th>
                        <th>Review Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['flags'] as $flag): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($flag->category_name . ' - ' . $flag->item_name); ?>
                            </td>

                            <td><?php echo htmlspecialchars($flag->collected_quantity); ?></td>

                            <td><?php echo htmlspecialchars($flag->condition_status); ?></td>

                            <td><?php echo htmlspecialchars($flag->risk_level); ?></td>

                            <td><?php echo htmlspecialchars($flag->flag_reason); ?></td>

                            <td><?php echo htmlspecialchars($flag->flagged_by_name); ?></td>

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

    <?php if ($data['pickup']->verification_status === 'PENDING'): ?>
        <h2>Officer Verification Decision</h2>

        <form method="POST" action="<?php echo url('municipal-officer/verify-pickup-record/' . $data['pickup']->pickup_id); ?>">
            <div class="form-group">
                <label for="officer_note">Officer Note</label>

                <textarea
                    id="officer_note"
                    name="officer_note"
                    placeholder="Add verification note or rejection reason"
                ></textarea>
            </div>

            <div class="button-row">
                <button type="submit" name="decision" value="VERIFIED" class="btn">
                    Verify Pickup Record
                </button>

                <button type="submit" name="decision" value="REJECTED" class="btn danger">
                    Reject Pickup Record
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="info-box">
            This pickup record has already been reviewed.
            Current verification status:
            <strong><?php echo htmlspecialchars($data['pickup']->verification_status); ?></strong>
        </div>
    <?php endif; ?>
</section>