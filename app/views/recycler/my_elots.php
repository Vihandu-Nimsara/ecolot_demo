<section class="hero-card">
    <h1>My Awarded E-Lots</h1>

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
        <p class="muted">No awarded E-Lots found for your recycler account yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>E-Lot Code</th>
                        <th>Title</th>
                        <th>Council</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Weight</th>
                        <th>Winning Bid</th>
                        <th>Status</th>
                        <th>Bid Submitted</th>
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

                            <td><?php echo htmlspecialchars($elot->total_quantity); ?></td>

                            <td>
                                <?php echo $elot->calculated_weight !== null
                                    ? htmlspecialchars(number_format((float)$elot->calculated_weight, 2)) . ' kg'
                                    : '-'; ?>
                            </td>

                            <td>
                                Rs. <?php echo htmlspecialchars(number_format((float)$elot->bid_amount, 2)); ?>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($elot->status); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($elot->bid_submitted_at); ?></td>

                            <td>
                                <a href="<?php echo url('recycler/my-elot-details/' . $elot->elot_id); ?>">
                                    View / Update
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>