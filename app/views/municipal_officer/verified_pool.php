<section class="hero-card">
    <h1>Verified E-Waste Pool</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
    </p>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/pickup-records/pending'); ?>">
            Pending Verification
        </a>

        <a class="btn" href="<?php echo url('municipal-officer/create-elot'); ?>">
            Create E-Lot
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">
            Back to Dashboard
        </a>
    </div>

    <div class="info-box">
        These are verified collected items that are not yet assigned to any E-Lot.
        Step 09 will use this pool to create categorized E-Lots.
    </div>

    <?php if (empty($data['pool_items'])): ?>
        <p class="muted">No verified items are currently available for E-Lot creation.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Pickup Item ID</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Weight</th>
                        <th>Condition</th>
                        <th>Risk</th>
                        <th>Request</th>
                        <th>Route</th>
                        <th>Area</th>
                        <th>Verified At</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['pool_items'] as $item): ?>
                        <tr>
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

                            <td><?php echo htmlspecialchars($item->default_risk_level); ?></td>

                            <td>#<?php echo htmlspecialchars($item->request_id); ?></td>

                            <td>
                                <?php echo htmlspecialchars($item->route_name); ?>
                                <br>
                                <small><?php echo htmlspecialchars($item->collection_date); ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($item->postal_code . ' - ' . $item->area_name); ?>
                            </td>

                            <td><?php echo htmlspecialchars($item->verified_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>