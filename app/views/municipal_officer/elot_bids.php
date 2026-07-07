<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">11</p>
            <h1 class="page-title">E-Lot Bids</h1>
            <p class="page-subtitle">Close bidding when ready and select a winning verified recycler bid.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/elot-details/' . $data['elot']->elot_id); ?>">
            Back to E-Lot
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/elots'); ?>">
            All E-Lots
        </a>
    </div>

    <div class="info-box">
        <strong>E-Lot Code:</strong>
        <?php echo htmlspecialchars($data['elot']->elot_code); ?>
        <br>

        <strong>Title:</strong>
        <?php echo htmlspecialchars($data['elot']->title); ?>
        <br>

        <strong>Category:</strong>
        <?php echo htmlspecialchars($data['elot']->category_name); ?>
        <br>

        <strong>Status:</strong>
        <span class="status-badge">
            <?php echo htmlspecialchars($data['elot']->status); ?>
        </span>
        <br>

        <strong>Bidding Start:</strong>
        <?php echo htmlspecialchars($data['elot']->bidding_start ?? '-'); ?>
        <br>

        <strong>Bidding End:</strong>
        <?php echo htmlspecialchars($data['elot']->bidding_end ?? '-'); ?>
        <br>

        <strong>Winner:</strong>
        <?php echo htmlspecialchars($data['elot']->winner_company_name ?? '-'); ?>
    </div>

    <?php if ($data['elot']->status === 'OPEN_FOR_BIDDING'): ?>
        <h2>Close Bidding</h2>

        <div class="info-box">
            Close bidding before selecting a winner. After closing, recyclers cannot submit new bids.
        </div>

        <form method="POST" action="<?php echo url('municipal-officer/close-elot-bidding/' . $data['elot']->elot_id); ?>">
            <div class="form-group">
                <label for="note">Closing Note</label>
                <textarea
                    id="note"
                    name="note"
                    placeholder="Optional note"
                ></textarea>
            </div>

            <button type="submit" class="btn danger">
                Close Bidding
            </button>
        </form>
    <?php endif; ?>

    <h2>Submitted Bids</h2>

    <?php if (empty($data['bids'])): ?>
        <p class="muted">No bids have been submitted for this E-Lot yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Bid ID</th>
                        <th>Recycler Company</th>
                        <th>License</th>
                        <th>Contact</th>
                        <th>Bid Amount</th>
                        <th>Bid Status</th>
                        <th>Verification</th>
                        <th>Submitted At</th>
                        <th>Note</th>
                        <th>Decision</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['bids'] as $index => $bid): ?>
                        <tr>
                            <td>
                                #<?php echo htmlspecialchars($index + 1); ?>
                            </td>

                            <td>
                                #<?php echo htmlspecialchars($bid->bid_id); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($bid->company_name); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($bid->license_no ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($bid->recycler_contact_name); ?>
                                <br>
                                <small><?php echo htmlspecialchars($bid->recycler_email); ?></small>
                                <br>
                                <small><?php echo htmlspecialchars($bid->recycler_phone ?? '-'); ?></small>
                            </td>

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
                                    <?php echo htmlspecialchars($bid->verification_status); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($bid->submitted_at); ?>
                            </td>

                            <td>
                                <?php echo nl2br(htmlspecialchars($bid->bid_note ?? '-')); ?>
                            </td>

                            <td>
                                <?php if ($data['elot']->status === 'BIDDING_CLOSED' && $bid->status === 'SUBMITTED'): ?>
                                    <form
                                        method="POST"
                                        action="<?php echo url('municipal-officer/award-bid/' . $data['elot']->elot_id . '/' . $bid->bid_id); ?>"
                                        onsubmit="return confirm('Are you sure you want to award this E-Lot to this recycler?');"
                                    >
                                        <div class="form-group">
                                            <textarea
                                                name="officer_note"
                                                placeholder="Optional award note"
                                            ></textarea>
                                        </div>

                                        <button type="submit" class="btn">
                                            Select Winner
                                        </button>
                                    </form>
                                <?php elseif ($bid->status === 'WINNING_BID'): ?>
                                    <span class="status-badge">Selected Winner</span>
                                <?php else: ?>
                                    <span class="muted">Not available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($data['elot']->status === 'AWARDED'): ?>
        <div class="info-box">
            This E-Lot has already been awarded. Next step is handover and processing status tracking.
        </div>
    <?php endif; ?>
</section>
