<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">11</p>
            <h1 class="page-title">E-Lot & Bid Review</h1>
            <p class="page-subtitle">Create E-Lots using verified collection items and review recycler bids.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p class="muted"><?php echo htmlspecialchars($data['profile']->council_name); ?></p>

    <div class="button-row">
        <a class="btn" href="<?php echo url('municipal-officer/create-elot'); ?>">
            Create E-Lot
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/verified-pool'); ?>">
            Verified Pool
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">
            Back to Dashboard
        </a>
    </div>

    <?php if (empty($data['elots'])): ?>
        <p class="muted">No E-Lots have been created yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>E-Lot Code</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Items</th>
                        <th>Total Weight</th>
                        <th>Status</th>
                        <th>Bidding Period</th>
                        <th>Bids</th>
                        <th>Winner</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['elots'] as $elot): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($elot->elot_code); ?></td>

                            <td><?php echo htmlspecialchars($elot->title); ?></td>

                            <td><?php echo htmlspecialchars($elot->category_name); ?></td>

                            <td><?php echo htmlspecialchars($elot->item_count); ?></td>

                            <td>
                                <?php echo $elot->total_weight_kg !== null
                                    ? htmlspecialchars(number_format((float)$elot->total_weight_kg, 2)) . ' kg'
                                    : '-'; ?>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($elot->status); ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($elot->bidding_start && $elot->bidding_end): ?>
                                    <?php echo htmlspecialchars($elot->bidding_start); ?>
                                    <br>
                                    to
                                    <br>
                                    <?php echo htmlspecialchars($elot->bidding_end); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <td><?php echo htmlspecialchars($elot->bid_count); ?></td>

                            <td><?php echo htmlspecialchars($elot->winner_company_name ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($elot->created_by_name); ?></td>

                            <td>
                                <div class="button-row compact-actions">
                                    <a class="btn compact secondary" href="<?php echo url('municipal-officer/elot-details/' . $elot->elot_id); ?>">
                                        View
                                    </a>

                                    <?php if ($elot->status !== 'DRAFT'): ?>
                                        <a class="btn compact" href="<?php echo url('municipal-officer/elot-bids/' . $elot->elot_id); ?>">
                                            Review Bids
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
