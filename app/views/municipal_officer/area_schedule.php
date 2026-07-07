<section class="form-card large-form">
    <div class="page-header">
        <div>
            <p class="page-kicker">08</p>
            <h1 class="page-title">Area Collection Schedule</h1>
            <p class="page-subtitle">Assign collection dates and capacity limits for postal-code areas.</p>
            <p class="muted"><?php echo htmlspecialchars($data['profile']->council_name); ?></p>
        </div>

        <div class="page-actions">
            <a class="btn secondary" href="<?php echo url('municipal-officer/campaigns'); ?>">Campaigns</a>
            <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">Dashboard</a>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="workflow-panel">
        <h2 class="section-title">Create Collection Date</h2>

        <form method="POST" action="<?php echo url('municipal-officer/store-area-date'); ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label for="area_id">Postal-code Area</label>
                    <select id="area_id" name="area_id" required>
                        <option value="">Select area</option>
                        <?php foreach ($data['areas'] as $area): ?>
                            <option value="<?php echo htmlspecialchars($area->area_id); ?>"
                                <?php echo ((int)($data['old']['area_id'] ?? 0) === (int)$area->area_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($area->postal_code . ' - ' . $area->area_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($data['errors']['area_id'])): ?>
                        <small class="error-text"><?php echo htmlspecialchars($data['errors']['area_id']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="collection_date">Collection Date</label>
                    <input
                        type="date"
                        id="collection_date"
                        name="collection_date"
                        value="<?php echo htmlspecialchars($data['old']['collection_date'] ?? ''); ?>"
                        required
                    >
                    <?php if (!empty($data['errors']['collection_date'])): ?>
                        <small class="error-text"><?php echo htmlspecialchars($data['errors']['collection_date']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="max_requests">Maximum Public Requests</label>
                    <input
                        type="number"
                        id="max_requests"
                        name="max_requests"
                        min="1"
                        max="1000"
                        value="<?php echo htmlspecialchars($data['old']['max_requests'] ?? 100); ?>"
                        required
                    >
                    <?php if (!empty($data['errors']['max_requests'])): ?>
                        <small class="error-text"><?php echo htmlspecialchars($data['errors']['max_requests']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="status">Initial Status</label>
                    <select id="status" name="status" required>
                        <?php foreach (['OPEN', 'CLOSED', 'FULL', 'CANCELLED'] as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>"
                                <?php echo (($data['old']['status'] ?? 'OPEN') === $status) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($data['errors']['status'])): ?>
                        <small class="error-text"><?php echo htmlspecialchars($data['errors']['status']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="button-row">
                <button class="btn" type="submit">Create Date</button>
            </div>
        </form>
    </div>
</section>

<section class="hero-card">
    <h2 class="section-title">Scheduled Area Dates</h2>

    <?php if (empty($data['area_dates'])): ?>
        <div class="empty-state">No area collection dates have been created for this council.</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date ID</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Requests</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Change Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['area_dates'] as $areaDate): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($areaDate->date_id); ?></td>
                            <td><?php echo htmlspecialchars($areaDate->postal_code . ' - ' . $areaDate->area_name); ?></td>
                            <td><?php echo htmlspecialchars($areaDate->collection_date); ?></td>
                            <td><?php echo htmlspecialchars($areaDate->request_count); ?></td>
                            <td><?php echo htmlspecialchars($areaDate->max_requests); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($areaDate->status); ?></span></td>
                            <td>
                                <form class="inline-form" method="POST" action="<?php echo url('municipal-officer/update-area-date-status/' . $areaDate->date_id); ?>">
                                    <select name="status" aria-label="Schedule status">
                                        <?php foreach (['OPEN', 'CLOSED', 'FULL', 'CANCELLED'] as $status): ?>
                                            <option value="<?php echo htmlspecialchars($status); ?>"
                                                <?php echo $areaDate->status === $status ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn secondary compact" type="submit">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
