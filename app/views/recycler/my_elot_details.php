<section class="hero-card">
    <h1>My E-Lot Details</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('recycler/my-elots'); ?>">
            Back to My E-Lots
        </a>

        <a class="btn secondary" href="<?php echo url('recycler/dashboard'); ?>">
            Dashboard
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

        <strong>Winning Bid:</strong>
        Rs. <?php echo htmlspecialchars(number_format((float)$data['elot']->bid_amount, 2)); ?>
        <br>

        <strong>Current Status:</strong>
        <span class="status-badge">
            <?php echo htmlspecialchars($data['elot']->status); ?>
        </span>
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

    <h2>Processing Update</h2>

    <?php if ($data['elot']->status === 'AWARDED'): ?>
        <div class="info-box">
            This E-Lot has been awarded to your company, but municipal officer has not confirmed handover yet.
            You can start processing only after status becomes <strong>HANDED_OVER</strong>.
        </div>
    <?php elseif ($data['elot']->status === 'HANDED_OVER'): ?>
        <form method="POST" action="<?php echo url('recycler/update-processing-status/' . $data['elot']->elot_id); ?>">
            <input type="hidden" name="new_status" value="PROCESSING">

            <div class="form-group">
                <label for="processing_note">Processing Start Note</label>

                <textarea
                    id="processing_note"
                    name="processing_note"
                    placeholder="Example: E-Lot received and processing started at facility."
                    required
                ></textarea>
            </div>

            <button type="submit" class="btn">
                Mark as Processing
            </button>
        </form>
    <?php elseif ($data['elot']->status === 'PROCESSING'): ?>
        <form method="POST" action="<?php echo url('recycler/update-processing-status/' . $data['elot']->elot_id); ?>">
            <input type="hidden" name="new_status" value="COMPLETED">

            <div class="form-group">
                <label for="processing_note">Completion Note</label>

                <textarea
                    id="processing_note"
                    name="processing_note"
                    placeholder="Example: Processing completed and items handled according to recycler process."
                    required
                ></textarea>
            </div>

            <button type="submit" class="btn">
                Mark as Completed
            </button>
        </form>
    <?php elseif ($data['elot']->status === 'COMPLETED'): ?>
        <div class="info-box">
            This E-Lot processing workflow is completed.
        </div>
    <?php else: ?>
        <div class="info-box">
            Processing update is not available for current status:
            <strong><?php echo htmlspecialchars($data['elot']->status); ?></strong>
        </div>
    <?php endif; ?>

    <h2>Status History</h2>

    <?php if (empty($data['history'])): ?>
        <p class="muted">No status history found.</p>
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