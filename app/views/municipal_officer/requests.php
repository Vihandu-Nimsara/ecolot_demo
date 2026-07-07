<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">09</p>
            <h1 class="page-title">Request Review & Route Assignment</h1>
            <p class="page-subtitle">Review pickup requests and assign approved requests to collection routes.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p class="muted"><?php echo htmlspecialchars($data['profile']->council_name); ?></p>

    <form class="workflow-panel" method="GET" action="<?php echo url('municipal-officer/requests'); ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="campaign_id">Campaign</label>
                <select id="campaign_id" name="campaign_id" <?php echo empty($data['supports_schedule_campaigns']) ? 'disabled' : ''; ?>>
                    <option value="">All campaigns</option>
                    <?php foreach ($data['campaigns'] as $campaign): ?>
                        <option value="<?php echo htmlspecialchars($campaign->campaign_id); ?>"
                            <?php echo ((int)($data['filters']['campaign_id'] ?? 0) === (int)$campaign->campaign_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($campaign->campaign_name . ' (' . $campaign->campaign_month . '/' . $campaign->campaign_year . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($data['supports_schedule_campaigns'])): ?>
                    <small class="muted">Run the business-rules migration to enable campaign filtering.</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="area_id">Postal-code Area</label>
                <select id="area_id" name="area_id">
                    <option value="">All areas</option>
                    <?php foreach ($data['areas'] as $area): ?>
                        <option value="<?php echo htmlspecialchars($area->area_id); ?>"
                            <?php echo ((int)($data['filters']['area_id'] ?? 0) === (int)$area->area_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area->postal_code . ' - ' . $area->area_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="date_id">Schedule</label>
                <select id="date_id" name="date_id">
                    <option value="">All schedules</option>
                    <?php foreach ($data['schedules'] as $schedule): ?>
                        <option value="<?php echo htmlspecialchars($schedule->date_id); ?>"
                            <?php echo ((int)($data['filters']['date_id'] ?? 0) === (int)$schedule->date_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($schedule->postal_code . ' - ' . $schedule->collection_date); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="search">Search</label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="<?php echo htmlspecialchars($data['filters']['search'] ?? ''); ?>"
                    placeholder="Name, email, address, phone"
                >
            </div>
        </div>

        <div class="button-row">
            <button class="btn" type="submit">Apply Filters</button>
            <a class="btn secondary" href="<?php echo url('municipal-officer/requests'); ?>">Clear</a>
        </div>
    </form>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests'); ?>">All</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests/submitted'); ?>">Submitted</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests/approved'); ?>">Approved</a>
        <a class="btn secondary" href="<?php echo url('municipal-officer/requests/rejected'); ?>">Rejected</a>
    </div>

    <p class="muted">
        Current filter: <?php echo htmlspecialchars($data['current_status']); ?>
    </p>

    <?php if (empty($data['requests'])): ?>
        <p class="muted">No requests found for this filter.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User</th>
                        <th>Campaign</th>
                        <th>Schedule</th>
                        <th>Area</th>
                        <th>Contact</th>
                        <th>Items</th>
                        <th>Estimated Weight</th>
                        <th>Flags</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['requests'] as $request): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($request->request_id); ?></td>

                            <td>
                                <?php echo htmlspecialchars($request->public_user_name); ?>
                                <br>
                                <small><?php echo htmlspecialchars($request->public_user_email); ?></small>
                            </td>

                            <td>
                                <?php if (!empty($request->campaign_name)): ?>
                                    <?php echo htmlspecialchars($request->campaign_name); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($request->campaign_month . '/' . $request->campaign_year); ?></small>
                                <?php else: ?>
                                    <span class="muted">Not linked</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                #<?php echo htmlspecialchars($request->date_id); ?>
                                <br>
                                <small><?php echo htmlspecialchars($request->collection_date); ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($request->postal_code . ' - ' . $request->area_name); ?>
                            </td>

                            <td><?php echo htmlspecialchars($request->contact_phone ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($request->item_count); ?></td>

                            <td>
                                <?php echo htmlspecialchars(number_format((float)$request->total_estimated_weight, 2)); ?> kg
                            </td>

                            <td><?php echo htmlspecialchars($request->flag_count ?? 0); ?></td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($request->status); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($request->created_at); ?></td>

                            <td>
                                <a href="<?php echo url('municipal-officer/request-details/' . $request->request_id); ?>">
                                    Review
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
