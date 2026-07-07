<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">11</p>
            <h1 class="page-title">E-Lot Details</h1>
            <p class="page-subtitle">Review lot items, status history, bids, and handover readiness.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/elots'); ?>">
            Back to E-Lots
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/create-elot'); ?>">
            Create Another E-Lot
        </a>

        <a class="btn" href="<?php echo url('municipal-officer/elot-bids/' . $data['elot']->elot_id); ?>">
    Review Bids
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

        <strong>Total Weight:</strong>
        <?php echo $data['elot']->total_weight_kg !== null
            ? htmlspecialchars(number_format((float)$data['elot']->total_weight_kg, 2)) . ' kg'
            : '-'; ?>
        <br>

        <strong>Status:</strong>
        <?php echo htmlspecialchars($data['elot']->status); ?>
        <br>

        <strong>Bidding Start:</strong>
        <?php echo htmlspecialchars($data['elot']->bidding_start ?? '-'); ?>
        <br>

        <strong>Bidding End:</strong>
        <?php echo htmlspecialchars($data['elot']->bidding_end ?? '-'); ?>
        <br>

        <strong>Winner:</strong>
        <?php echo htmlspecialchars($data['elot']->winner_company_name ?? '-'); ?>
        <br>

        <strong>Description:</strong>
        <?php echo nl2br(htmlspecialchars($data['elot']->description ?? '-')); ?>
    </div>

    <h2>E-Lot Items</h2>

    <?php if (empty($data['items'])): ?>
        <p class="muted">No items found in this E-Lot.</p>
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
                        <th>Request</th>
                        <th>Route</th>
                        <th>Area</th>
                        <th>Verified At</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['items'] as $item): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($item->pickup_item_id); ?></td>

                            <td><?php echo htmlspecialchars($item->category_name); ?></td>

                            <td><?php echo htmlspecialchars($item->item_name); ?></td>

                            <td><?php echo htmlspecialchars($item->quantity); ?></td>

                            <td>
                                <?php echo $item->weight_kg !== null
                                    ? htmlspecialchars(number_format((float)$item->weight_kg, 2)) . ' kg'
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

                            <td><?php echo htmlspecialchars($item->verified_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
        $bidCount = count($data['bids'] ?? []);
        $winningBid = null;

        foreach ($data['bids'] ?? [] as $bid) {
            if ($bid->status === 'WINNING_BID') {
                $winningBid = $bid;
                break;
            }
        }
    ?>

    <h2>Bid Summary</h2>

    <div class="info-box">
        <strong>Total Bids:</strong>
        <?php echo htmlspecialchars($bidCount); ?>
        <br>

        <strong>Winner:</strong>
        <?php echo $winningBid
            ? htmlspecialchars($winningBid->company_name . ' - Rs. ' . number_format((float) $winningBid->bid_amount, 2))
            : htmlspecialchars($data['elot']->winner_company_name ?? '-'); ?>
        <br>

        <strong>Bid Review:</strong>
        <a href="<?php echo url('municipal-officer/elot-bids/' . $data['elot']->elot_id); ?>">
            Open bid review for this E-Lot
        </a>
    </div>

    <?php if ($data['elot']->status === 'AWARDED'): ?>
    <h2>Handover Confirmation</h2>

    <div class="info-box">
        This E-Lot has been awarded to a recycler. Confirm handover only after the recycler is officially allowed to receive/process this E-Lot.
    </div>

    <form method="POST" action="<?php echo url('municipal-officer/mark-elot-handover/' . $data['elot']->elot_id); ?>">
        <div class="form-group">
            <label for="handover_note">Handover Note</label>

            <textarea
                id="handover_note"
                name="handover_note"
                placeholder="Example: E-Lot handed over after document verification."
            ></textarea>
        </div>

        <button type="submit" class="btn">
            Mark as Handed Over
        </button>
    </form>
<?php endif; ?>

<?php if (in_array($data['elot']->status, ['HANDED_OVER', 'PROCESSING', 'COMPLETED'], true)): ?>
    <div class="info-box">
        <strong>Processing Stage:</strong>
        <?php echo htmlspecialchars($data['elot']->status); ?>
    </div>
<?php endif; ?>

    <h2>Status History</h2>

    <?php if (empty($data['history'])): ?>
        <p class="muted">No E-Lot status history found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Old Status</th>
                        <th>New Status</th>
                        <th>Changed By</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['history'] as $history): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($history->old_status ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($history->new_status); ?></td>
                            <td><?php echo htmlspecialchars($history->changed_by_name ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($history->note ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($history->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
