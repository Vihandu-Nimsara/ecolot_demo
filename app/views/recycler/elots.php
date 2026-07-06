<section class="hero-card">
    <h1>Eligible Open E-Lots</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        <strong>Company:</strong>
        <?php echo htmlspecialchars($data['profile']->company_name); ?>
    </p>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('recycler/dashboard'); ?>">
            Back to Dashboard
        </a>

        <a class="btn secondary" href="<?php echo url('recycler/my-bids'); ?>">
            My Bids
        </a>
    </div>

    <?php if (empty($data['elots'])): ?>
        <p class="muted">No eligible open E-Lots are available right now.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>E-Lot Code</th>
                        <th>Title</th>
                        <th>Council</th>
                        <th>Category</th>
                        <th>Items</th>
                        <th>Quantity</th>
                        <th>Weight</th>
                        <th>High Risk</th>
                        <th>Bidding Ends</th>
                        <th>My Bid</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['elots'] as $elot): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($elot->elot_code); ?></td>

                            <td><?php echo htmlspecialchars($elot->title); ?></td>

                            <td><?php echo htmlspecialchars($elot->council_name); ?></td>

                            <td><?php echo htmlspecialchars($elot->category_name); ?></td>

                            <td><?php echo htmlspecialchars($elot->item_count); ?></td>

                            <td><?php echo htmlspecialchars($elot->total_quantity); ?></td>

                            <td>
                                <?php echo $elot->calculated_weight !== null
                                    ? htmlspecialchars(number_format((float)$elot->calculated_weight, 2)) . ' kg'
                                    : '-'; ?>
                            </td>

                            <td>
                                <?php echo ((int) $elot->has_high_risk_item === 1) ? 'Yes' : 'No'; ?>
                            </td>

                            <td><?php echo htmlspecialchars($elot->bidding_end); ?></td>

                            <td>
                                <?php if (!empty($elot->my_bid_id)): ?>
                                    <span class="status-badge">
                                        <?php echo htmlspecialchars($elot->my_bid_status); ?>
                                    </span>
                                    <br>
                                    Rs. <?php echo htmlspecialchars(number_format((float)$elot->my_bid_amount, 2)); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?php echo url('recycler/elot-details/' . $elot->elot_id); ?>">
                                    View / Bid
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>