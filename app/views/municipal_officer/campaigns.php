<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">08</p>
            <h1 class="page-title">Campaigns</h1>
            <p class="page-subtitle">Create and manage monthly municipal e-waste collection campaigns.</p>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p class="muted"><?php echo htmlspecialchars($data['profile']->council_name); ?></p>

    <div class="button-row">
        <a class="btn" href="<?php echo url('municipal-officer/create-campaign'); ?>">
            Create Campaign
        </a>

        <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">
            Back to Dashboard
        </a>
    </div>

    <?php if (empty($data['campaigns'])): ?>
        <p class="muted">No monthly campaigns have been created yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Campaign ID</th>
                        <th>Name</th>
                        <th>Month / Year</th>
                        <th>Cut-off Date</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['campaigns'] as $campaign): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($campaign->campaign_id); ?></td>
                            <td><?php echo htmlspecialchars($campaign->campaign_name); ?></td>
                            <td>
                                <?php echo htmlspecialchars($campaign->campaign_month . ' / ' . $campaign->campaign_year); ?>
                            </td>
                            <td><?php echo htmlspecialchars($campaign->request_cutoff_date); ?></td>
                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($campaign->status); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($campaign->created_by_name); ?></td>
                            <td><?php echo htmlspecialchars($campaign->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
