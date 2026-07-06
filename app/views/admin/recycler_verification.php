<section class="hero-card">
    <h1>Recycler Verification</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('admin/recycler-verification/pending'); ?>">Pending</a>
        <a class="btn secondary" href="<?php echo url('admin/recycler-verification/verified'); ?>">Verified</a>
        <a class="btn secondary" href="<?php echo url('admin/recycler-verification/rejected'); ?>">Rejected</a>
        <a class="btn secondary" href="<?php echo url('admin/recycler-verification/all'); ?>">All</a>
        <a class="btn secondary" href="<?php echo url('admin/dashboard'); ?>">Dashboard</a>
    </div>

    <p class="muted">Current filter: <?php echo htmlspecialchars($data['current_status']); ?></p>

    <?php if (empty($data['recyclers'])): ?>
        <p class="muted">No recycler profiles found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Profile ID</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>License</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Verified By</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['recyclers'] as $recycler): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($recycler->recycler_profile_id); ?></td>

                            <td><?php echo htmlspecialchars($recycler->company_name); ?></td>

                            <td>
                                <?php echo htmlspecialchars($recycler->full_name); ?>
                                <br>
                                <small><?php echo htmlspecialchars($recycler->email); ?></small>
                                <br>
                                <small><?php echo htmlspecialchars($recycler->phone ?? '-'); ?></small>
                            </td>

                            <td><?php echo htmlspecialchars($recycler->license_no ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($recycler->license_expiry_date ?? '-'); ?></td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($recycler->verification_status); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($recycler->verified_by_name ?? '-'); ?></td>

                            <td>
                                <a href="<?php echo url('admin/recycler-details/' . $recycler->recycler_profile_id); ?>">
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