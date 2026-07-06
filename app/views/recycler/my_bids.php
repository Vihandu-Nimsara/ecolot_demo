<section class="hero-card">
    <h1>My Bids</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        <strong>Company:</strong>
        <?php echo htmlspecialchars($data['profile']->company_name); ?>
    </p>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('recycler/elots'); ?>">
            Eligible E-Lots
        </a>

        <a class="btn secondary" href="<?php echo url('recycler/dashboard'); ?>">
            Back to Dashboard
        </a>

        <a class="btn secondary" href="<?php echo url('recycler/my-elots'); ?>">
    My Awarded E-Lots
</a>


    </div>

    <?php if (empty($data['bids'])): ?>
        <p class="muted">You have not submitted any bids yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Bid ID</th>
                        <th>E-Lot Code</th>
                        <th>E-Lot Title</th>
                        <th>Council</th>
                        <th>Category</th>
                        <th>Bid Amount</th>
                        <th>Bid Status</th>
                        <th>E-Lot Status</th>
                        <th>Bidding Period</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['bids'] as $bid): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($bid->bid_id); ?></td>

                            <td><?php echo htmlspecialchars($bid->elot_code); ?></td>

                            <td><?php echo htmlspecialchars($bid->elot_title); ?></td>

                            <td><?php echo htmlspecialchars($bid->council_name); ?></td>

                            <td><?php echo htmlspecialchars($bid->category_name); ?></td>

                            <td>
                                Rs. <?php echo htmlspecialchars(number_format((float)$bid->bid_amount, 2)); ?>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($bid->status); ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($bid->elot_status); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($bid->bidding_start ?? '-'); ?>
                                <br>
                                to
                                <br>
                                <?php echo htmlspecialchars($bid->bidding_end ?? '-'); ?>
                            </td>

                            <td><?php echo htmlspecialchars($bid->submitted_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>