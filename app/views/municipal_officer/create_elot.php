<section class="form-card large-form">
    <div class="page-header">
        <div>
            <p class="page-kicker">11</p>
            <h1 class="page-title">Create E-Lot</h1>
            <p class="page-subtitle">Build a lot from verified collection items and optionally open bidding.</p>
        </div>
    </div>

    <?php if (!empty($data['errors']['elot'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['elot']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['errors']['pickup_item_ids'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['pickup_item_ids']); ?>
        </div>
    <?php endif; ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/elots'); ?>">
            Back to E-Lots
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/verified-pool'); ?>">
            View Verified Pool
        </a>
    </div>

    <div class="info-box">
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
        <br>
        Only verified pickup items that are not already assigned to another E-Lot are shown here.
    </div>

    <?php if (empty($data['pool_items'])): ?>
        <div class="alert alert-danger">
            No verified pool items are available for E-Lot creation.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo url('municipal-officer/store-elot'); ?>">
        <div class="form-group">
            <label for="title">E-Lot Title</label>

            <input
                type="text"
                id="title"
                name="title"
                value="<?php echo htmlspecialchars($data['old']['title'] ?? ''); ?>"
                placeholder="Example: Domestic E-Waste Lot - August 2026"
                required
            >

            <?php if (!empty($data['errors']['title'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['title']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="category_id">E-Waste Category</label>

            <select id="category_id" name="category_id" required>
                <option value="">Select category</option>

                <?php foreach ($data['categories'] as $category): ?>
                    <option value="<?php echo htmlspecialchars($category->category_id); ?>"
                        <?php echo ((int)($data['old']['category_id'] ?? 0) === (int)$category->category_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(
                            $category->category_name .
                            ' | Items: ' . $category->available_items .
                            ' | Weight: ' . number_format((float)$category->total_weight, 2) . ' kg'
                        ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($data['errors']['category_id'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['category_id']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                placeholder="Optional details about this E-Lot"
            ><?php echo htmlspecialchars($data['old']['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="status">Initial E-Lot Status</label>

            <select id="status" name="status" required>
                <option value="DRAFT"
                    <?php echo (($data['old']['status'] ?? '') === 'DRAFT') ? 'selected' : ''; ?>>
                    Draft
                </option>

                <option value="OPEN_FOR_BIDDING"
                    <?php echo (($data['old']['status'] ?? '') === 'OPEN_FOR_BIDDING') ? 'selected' : ''; ?>>
                    Open for Bidding
                </option>
            </select>

            <?php if (!empty($data['errors']['status'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['status']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div id="bidding-fields" class="role-fields">
            <h2>Bidding Period</h2>

            <div class="grid-2">
                <div class="form-group">
                    <label for="bidding_start">Bidding Start</label>

                    <input
                        type="datetime-local"
                        id="bidding_start"
                        name="bidding_start"
                        value="<?php echo htmlspecialchars($data['old']['bidding_start'] ?? ''); ?>"
                    >

                    <?php if (!empty($data['errors']['bidding_start'])): ?>
                        <small class="error-text">
                            <?php echo htmlspecialchars($data['errors']['bidding_start']); ?>
                        </small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="bidding_end">Bidding End</label>

                    <input
                        type="datetime-local"
                        id="bidding_end"
                        name="bidding_end"
                        value="<?php echo htmlspecialchars($data['old']['bidding_end'] ?? ''); ?>"
                    >

                    <?php if (!empty($data['errors']['bidding_end'])): ?>
                        <small class="error-text">
                            <?php echo htmlspecialchars($data['errors']['bidding_end']); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr>

        <h2>Select Verified Pool Items</h2>

        <p class="muted">
            Select only items from the chosen category. The system validates this again on the server.
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Pickup Item ID</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Weight</th>
                        <th>Condition</th>
                        <th>Request</th>
                        <th>Route</th>
                        <th>Area</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['pool_items'] as $item): ?>
                        <?php
                            $checked = in_array(
                                (string) $item->pickup_item_id,
                                array_map('strval', $data['old']['pickup_item_ids'] ?? []),
                                true
                            );
                        ?>

                        <tr
                            class="pool-row"
                            data-category-id="<?php echo htmlspecialchars($item->category_id); ?>"
                        >
                            <td>
                                <input
                                    type="checkbox"
                                    name="pickup_item_ids[]"
                                    value="<?php echo htmlspecialchars($item->pickup_item_id); ?>"
                                    <?php echo $checked ? 'checked' : ''; ?>
                                >
                            </td>

                            <td>#<?php echo htmlspecialchars($item->pickup_item_id); ?></td>

                            <td><?php echo htmlspecialchars($item->category_name); ?></td>

                            <td><?php echo htmlspecialchars($item->item_name); ?></td>

                            <td><?php echo htmlspecialchars($item->collected_quantity); ?></td>

                            <td>
                                <?php echo $item->collected_weight_kg !== null
                                    ? htmlspecialchars(number_format((float)$item->collected_weight_kg, 2)) . ' kg'
                                    : '-'; ?>
                            </td>

                            <td><?php echo htmlspecialchars($item->condition_status); ?></td>

                            <td>#<?php echo htmlspecialchars($item->request_id); ?></td>

                            <td>
                                <?php echo htmlspecialchars($item->route_name); ?>
                                <br>
                                <small><?php echo htmlspecialchars($item->collection_date); ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($item->postal_code . ' - ' . $item->area_name); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="button-row">
            <button
                type="submit"
                class="btn"
                <?php echo empty($data['pool_items']) ? 'disabled' : ''; ?>
            >
                Create E-Lot
            </button>

            <a class="btn secondary" href="<?php echo url('municipal-officer/elots'); ?>">
                Cancel
            </a>
        </div>
    </form>
</section>

<script>
    const categorySelect = document.getElementById('category_id');
    const statusSelect = document.getElementById('status');
    const biddingFields = document.getElementById('bidding-fields');

    function filterPoolRows() {
        const selectedCategory = categorySelect.value;

        document.querySelectorAll('.pool-row').forEach(row => {
            const rowCategory = row.dataset.categoryId;
            const checkbox = row.querySelector('input[type="checkbox"]');

            if (selectedCategory === '' || rowCategory === selectedCategory) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
                checkbox.checked = false;
            }
        });
    }

    function toggleBiddingFields() {
        biddingFields.style.display = statusSelect.value === 'OPEN_FOR_BIDDING'
            ? 'block'
            : 'none';
    }

    categorySelect.addEventListener('change', filterPoolRows);
    statusSelect.addEventListener('change', toggleBiddingFields);

    filterPoolRows();
    toggleBiddingFields();
</script>
