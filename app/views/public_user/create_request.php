<section class="form-card large-form">
    <h1>Submit E-Waste Request</h1>

    <?php if (!empty($data['errors']['submit'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['submit']); ?>
        </div>
    <?php endif; ?>

    <div class="info-box">
        <strong>Your Collection Area:</strong>
        <?php echo htmlspecialchars($data['profile']->postal_code . ' - ' . $data['profile']->area_name); ?>
        <br>
        <strong>Local Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name ?? 'Not assigned'); ?>
    </div>

    <?php if (empty($data['dates'])): ?>
        <div class="alert alert-danger">
            No open collection dates are available for your postal code area right now.
        </div>
    <?php endif; ?>

    <?php if (!empty($data['errors']['items'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['items']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo url('public-user/store-request'); ?>">
        <div class="form-group">
            <label for="preferred_date_id">Preferred Collection Date</label>

            <select id="preferred_date_id" name="preferred_date_id" required>
                <option value="">Select collection date</option>

                <?php foreach ($data['dates'] as $date): ?>
                    <option value="<?php echo htmlspecialchars($date->date_id); ?>"
                        <?php echo ((int)($data['old']['preferred_date_id'] ?? 0) === (int)$date->date_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($date->collection_date); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($data['errors']['preferred_date_id'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['preferred_date_id']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="pickup_address">Pickup Address</label>

            <textarea id="pickup_address" name="pickup_address" required><?php echo htmlspecialchars($data['old']['pickup_address'] ?? ''); ?></textarea>

            <?php if (!empty($data['errors']['pickup_address'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['pickup_address']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="contact_phone">Contact Phone</label>

            <input
                type="text"
                id="contact_phone"
                name="contact_phone"
                value="<?php echo htmlspecialchars($data['old']['contact_phone'] ?? ''); ?>"
                required
            >

            <?php if (!empty($data['errors']['contact_phone'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['contact_phone']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="special_note">Special Note</label>

            <textarea id="special_note" name="special_note"><?php echo htmlspecialchars($data['old']['special_note'] ?? ''); ?></textarea>
        </div>

        <hr>

        <h2>Request Items</h2>

        <p class="muted">
            Items marked as review-required, damaged, broken, or leaking will be automatically flagged for municipal officer review.
        </p>

        <div id="items-wrapper">
            <?php
                $oldItemIds = $data['old']['item_id'] ?? [''];
                $rowCount = max(1, is_array($oldItemIds) ? count($oldItemIds) : 1);
            ?>

            <?php for ($i = 0; $i < $rowCount; $i++): ?>
                <div class="item-row">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Item</label>

                            <select name="item_id[]" required>
                                <option value="">Select item</option>

                                <?php foreach ($data['items'] as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item->item_id); ?>"
                                        <?php echo ((int)($data['old']['item_id'][$i] ?? 0) === (int)$item->item_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item->category_name . ' - ' . $item->item_name); ?>

                                        <?php if ($item->collection_status === 'REVIEW_REQUIRED'): ?>
                                            <?php echo ' (Review Required)'; ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Quantity</label>

                            <input
                                type="number"
                                name="quantity[]"
                                min="1"
                                value="<?php echo htmlspecialchars($data['old']['quantity'][$i] ?? '1'); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Estimated Weight kg</label>

                            <input
                                type="number"
                                name="estimated_weight_kg[]"
                                min="0"
                                step="0.01"
                                value="<?php echo htmlspecialchars($data['old']['estimated_weight_kg'][$i] ?? ''); ?>"
                                placeholder="Optional"
                            >
                        </div>

                        <div class="form-group">
                            <label>Condition</label>

                            <?php $selectedCondition = $data['old']['condition_status'][$i] ?? 'UNKNOWN'; ?>

                            <select name="condition_status[]">
                                <option value="WORKING" <?php echo $selectedCondition === 'WORKING' ? 'selected' : ''; ?>>Working</option>
                                <option value="DAMAGED" <?php echo $selectedCondition === 'DAMAGED' ? 'selected' : ''; ?>>Damaged</option>
                                <option value="BROKEN" <?php echo $selectedCondition === 'BROKEN' ? 'selected' : ''; ?>>Broken</option>
                                <option value="LEAKING" <?php echo $selectedCondition === 'LEAKING' ? 'selected' : ''; ?>>Leaking</option>
                                <option value="UNKNOWN" <?php echo $selectedCondition === 'UNKNOWN' ? 'selected' : ''; ?>>Unknown</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Item Note</label>

                        <textarea name="note[]" placeholder="Optional"><?php echo htmlspecialchars($data['old']['note'][$i] ?? ''); ?></textarea>
                    </div>

                    <button type="button" class="btn danger remove-item-btn">Remove Item</button>
                </div>
            <?php endfor; ?>
        </div>

        <button type="button" class="btn secondary" id="add-item-btn">
            Add Another Item
        </button>

        <div class="button-row">
            <button type="submit" class="btn" <?php echo empty($data['dates']) ? 'disabled' : ''; ?>>
                Submit Request
            </button>

            <a class="btn secondary" href="<?php echo url('public-user/dashboard'); ?>">
                Cancel
            </a>
        </div>
    </form>
</section>

<script>
    const wrapper = document.getElementById('items-wrapper');
    const addBtn = document.getElementById('add-item-btn');

    function bindRemoveButtons() {
        document.querySelectorAll('.remove-item-btn').forEach(button => {
            button.onclick = function () {
                const rows = document.querySelectorAll('.item-row');

                if (rows.length > 1) {
                    this.closest('.item-row').remove();
                }
            };
        });
    }

    addBtn.addEventListener('click', function () {
        const firstRow = document.querySelector('.item-row');
        const clone = firstRow.cloneNode(true);

        clone.querySelectorAll('input, textarea, select').forEach(input => {
            if (input.name === 'quantity[]') {
                input.value = '1';
            } else if (input.name === 'condition_status[]') {
                input.value = 'UNKNOWN';
            } else {
                input.value = '';
            }
        });

        wrapper.appendChild(clone);
        bindRemoveButtons();
    });

    bindRemoveButtons();
</script>