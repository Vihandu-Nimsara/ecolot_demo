<section class="hero-card">
    <h1>E-Lot Details</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <?php if (!empty($data['errors']['bid'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['bid']); ?>
        </div>
    <?php endif; ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('recycler/elots'); ?>">
            Back to E-Lots
        </a>

        <a class="btn secondary" href="<?php echo url('recycler/my-bids'); ?>">
            My Bids
        </a>
    </div>

    <div class="info-box">
        <strong>E-Lot Code:</strong>
        <?php echo htmlspecialchars($data['elot']->elot_code); ?>
        <br>

        <strong>Title:</strong>
        <?php echo htmlspecialchars($data['elot']->title); ?>
        <br>

        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['elot']->council_name); ?>
        <br>

        <strong>Category:</strong>
        <?php echo htmlspecialchars($data['elot']->category_name); ?>
        <br>

        <strong>Total Quantity:</strong>
        <?php echo htmlspecialchars($data['elot']->total_quantity); ?>
        <br>

        <strong>Total Weight:</strong>
        <?php echo $data['elot']->calculated_weight !== null
            ? htmlspecialchars(number_format((float)$data['elot']->calculated_weight, 2)) . ' kg'
            : '-'; ?>
        <br>

        <strong>High Risk Items:</strong>
        <?php echo ((int) $data['elot']->has_high_risk_item === 1) ? 'Yes' : 'No'; ?>
        <br>

        <strong>Bidding Start:</strong>
        <?php echo htmlspecialchars($data['elot']->bidding_start); ?>
        <br>

        <strong>Bidding End:</strong>
        <?php echo htmlspecialchars($data['elot']->bidding_end); ?>
        <br>

        <strong>Description:</strong>
        <?php echo nl2br(htmlspecialchars($data['elot']->description ?? '-')); ?>
    </div>

    <h2>E-Lot Items</h2>

    <?php if (empty($data['items'])): ?>
        <p class="muted">No items found for this E-Lot.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Weight</th>
                        <th>Condition</th>
                        <th>Risk Level</th>
                        <th>Collection Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->category_name); ?></td>

                            <td><?php echo htmlspecialchars($item->item_name); ?></td>

                            <td><?php echo htmlspecialchars($item->quantity); ?></td>

                            <td>
                                <?php echo $item->weight_kg !== null
                                    ? htmlspecialchars(number_format((float)$item->weight_kg, 2)) . ' kg'
                                    : '-'; ?>
                            </td>

                            <td><?php echo htmlspecialchars($item->condition_status); ?></td>

                            <td><?php echo htmlspecialchars($item->default_risk_level); ?></td>

                            <td><?php echo htmlspecialchars($item->collection_status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Bid Submission</h2>

    <?php if (!empty($data['existing_bid'])): ?>
        <div class="info-box">
            You have already submitted a bid for this E-Lot.
            <br>
            <strong>Bid Amount:</strong>
            Rs. <?php echo htmlspecialchars(number_format((float)$data['existing_bid']->bid_amount, 2)); ?>
            <br>
            <strong>Status:</strong>
            <?php echo htmlspecialchars($data['existing_bid']->status); ?>
            <br>
            <strong>Submitted At:</strong>
            <?php echo htmlspecialchars($data['existing_bid']->submitted_at); ?>
            <br>
            <strong>Note:</strong>
            <?php echo nl2br(htmlspecialchars($data['existing_bid']->bid_note ?? '-')); ?>
        </div>
    <?php else: ?>
        <form method="POST" action="<?php echo url('recycler/store-bid/' . $data['elot']->elot_id); ?>">
            <div class="form-group">
                <label for="bid_amount">Bid Amount Rs.</label>

                <input
                    type="number"
                    id="bid_amount"
                    name="bid_amount"
                    min="1"
                    step="0.01"
                    value="<?php echo htmlspecialchars($data['old']['bid_amount'] ?? ''); ?>"
                    required
                >

                <?php if (!empty($data['errors']['bid_amount'])): ?>
                    <small class="error-text">
                        <?php echo htmlspecialchars($data['errors']['bid_amount']); ?>
                    </small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="bid_note">Bid Note</label>

                <textarea
                    id="bid_note"
                    name="bid_note"
                    placeholder="Optional message for municipal officer"
                ><?php echo htmlspecialchars($data['old']['bid_note'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn">
                Submit Bid
            </button>
        </form>
    <?php endif; ?>
</section>