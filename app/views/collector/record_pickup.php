<section class="form-card large-form">
    <h1>Record Pickup</h1>

    <?php if (!empty($data['errors']['submit'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['submit']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['errors']['items'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['items']); ?>
        </div>
    <?php endif; ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('collector/route-details/' . $data['stop']->route_id); ?>">
            Back to Route
        </a>
    </div>

    <div class="info-box">
        <strong>Route:</strong>
        <?php echo htmlspecialchars($data['stop']->route_name); ?>
        <br>

        <strong>Stop Order:</strong>
        <?php echo htmlspecialchars($data['stop']->stop_order); ?>
        <br>

        <strong>Request ID:</strong>
        #<?php echo htmlspecialchars($data['stop']->request_id); ?>
        <br>

        <strong>Public User:</strong>
        <?php echo htmlspecialchars($data['stop']->public_user_name); ?>
        |
        <?php echo htmlspecialchars($data['stop']->public_user_email); ?>
        <br>

        <strong>Area:</strong>
        <?php echo htmlspecialchars($data['stop']->postal_code . ' - ' . $data['stop']->area_name); ?>
        <br>

        <strong>Pickup Address:</strong>
        <?php echo nl2br(htmlspecialchars($data['stop']->pickup_address)); ?>
        <br>

        <strong>Contact Phone:</strong>
        <?php echo htmlspecialchars($data['stop']->contact_phone); ?>
        <br>

        <strong>Special Note:</strong>
        <?php echo nl2br(htmlspecialchars($data['stop']->special_note ?? '-')); ?>
    </div>

    <form method="POST" action="<?php echo url('collector/store-pickup/' . $data['stop']->stop_id); ?>">
        <div class="form-group">
            <label for="pickup_status">Pickup Status</label>

            <select id="pickup_status" name="pickup_status" required>
                <?php
                    $selectedPickupStatus = $data['old']['pickup_status'] ?? 'COLLECTED';
                ?>

                <option value="COLLECTED" <?php echo $selectedPickupStatus === 'COLLECTED' ? 'selected' : ''; ?>>
                    Collected
                </option>

                <option value="PARTIALLY_COLLECTED" <?php echo $selectedPickupStatus === 'PARTIALLY_COLLECTED' ? 'selected' : ''; ?>>
                    Partially Collected
                </option>

                <option value="NOT_AVAILABLE" <?php echo $selectedPickupStatus === 'NOT_AVAILABLE' ? 'selected' : ''; ?>>
                    User / Items Not Available
                </option>

                <option value="REJECTED_AT_PICKUP" <?php echo $selectedPickupStatus === 'REJECTED_AT_PICKUP' ? 'selected' : ''; ?>>
                    Rejected at Pickup
                </option>
            </select>

            <?php if (!empty($data['errors']['pickup_status'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['pickup_status']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="total_collected_weight_kg">Total Collected Weight kg</label>

            <input
                type="number"
                id="total_collected_weight_kg"
                name="total_collected_weight_kg"
                min="0"
                step="0.01"
                value="<?php echo htmlspecialchars($data['old']['total_collected_weight_kg'] ?? ''); ?>"
                placeholder="Optional"
            >

            <?php if (!empty($data['errors']['total_collected_weight_kg'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['total_collected_weight_kg']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="collector_note">Collector Note</label>

            <textarea
                id="collector_note"
                name="collector_note"
                placeholder="Add pickup notes, issue details, or reason if pickup failed"
            ><?php echo htmlspecialchars($data['old']['collector_note'] ?? ''); ?></textarea>

            <?php if (!empty($data['errors']['collector_note'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['collector_note']); ?>
                </small>
            <?php endif; ?>
        </div>

        <hr>

        <h2>Collected Items</h2>

        <p class="muted">
            Enter the actual quantity and condition collected. Damaged, broken, leaking, or review-required items will be flagged for municipal officer review.
        </p>

        <?php foreach ($data['items'] as $index => $item): ?>
            <div class="item-row">
                <input type="hidden" name="item_id[]" value="<?php echo htmlspecialchars($item->item_id); ?>">
                <input type="hidden" name="requested_quantity[]" value="<?php echo htmlspecialchars($item->quantity); ?>">

                <div class="info-box">
                    <strong>Category:</strong>
                    <?php echo htmlspecialchars($item->category_name); ?>
                    <br>

                    <strong>Item:</strong>
                    <?php echo htmlspecialchars($item->item_name); ?>
                    <br>

                    <strong>Requested Quantity:</strong>
                    <?php echo htmlspecialchars($item->quantity); ?>
                    <br>

                    <strong>Requested Condition:</strong>
                    <?php echo htmlspecialchars($item->condition_status); ?>
                    <br>

                    <strong>Collection Status:</strong>
                    <?php echo htmlspecialchars($item->collection_status); ?>
                    <br>

                    <strong>Risk Level:</strong>
                    <?php echo htmlspecialchars($item->default_risk_level); ?>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Collected Quantity</label>

                        <input
                            type="number"
                            name="collected_quantity[]"
                            min="0"
                            max="<?php echo htmlspecialchars($item->quantity); ?>"
                            value="<?php echo htmlspecialchars($data['old']['collected_quantity'][$index] ?? $item->quantity); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Collected Weight kg</label>

                        <input
                            type="number"
                            name="collected_weight_kg[]"
                            min="0"
                            step="0.01"
                            value="<?php echo htmlspecialchars($data['old']['collected_weight_kg'][$index] ?? ''); ?>"
                            placeholder="Optional"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Collected Condition</label>

                    <?php $selectedCondition = $data['old']['condition_status'][$index] ?? 'GOOD'; ?>

                    <select name="condition_status[]">
                        <option value="GOOD" <?php echo $selectedCondition === 'GOOD' ? 'selected' : ''; ?>>
                            Good
                        </option>
                        <option value="DAMAGED" <?php echo $selectedCondition === 'DAMAGED' ? 'selected' : ''; ?>>
                            Damaged
                        </option>
                        <option value="BROKEN" <?php echo $selectedCondition === 'BROKEN' ? 'selected' : ''; ?>>
                            Broken
                        </option>
                        <option value="LEAKING" <?php echo $selectedCondition === 'LEAKING' ? 'selected' : ''; ?>>
                            Leaking
                        </option>
                        <option value="UNKNOWN" <?php echo $selectedCondition === 'UNKNOWN' ? 'selected' : ''; ?>>
                            Unknown
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Item Note</label>

                    <textarea name="note[]" placeholder="Optional"><?php echo htmlspecialchars($data['old']['note'][$index] ?? ''); ?></textarea>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="button-row">
            <button type="submit" class="btn">
                Submit Pickup Record
            </button>

            <a class="btn secondary" href="<?php echo url('collector/route-details/' . $data['stop']->route_id); ?>">
                Cancel
            </a>
        </div>
    </form>
</section>