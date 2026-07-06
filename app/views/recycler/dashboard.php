<section class="hero-card">
    <h1>Authorized Recycler Dashboard</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>
        Welcome, <?php echo htmlspecialchars(currentUserName()); ?>.
        <br>
        <strong>Company:</strong>
        <?php echo htmlspecialchars($data['profile']->company_name); ?>
        <br>
        <strong>License No:</strong>
        <?php echo htmlspecialchars($data['profile']->license_no ?? '-'); ?>
        <br>
        <strong>Verification Status:</strong>
        <span class="status-badge">
            <?php echo htmlspecialchars($data['profile']->verification_status); ?>
        </span>
    </p>

    <?php if ($data['profile']->verification_status !== 'VERIFIED'): ?>
        <div class="alert alert-danger">
            Your recycler account is not verified yet. You cannot view E-Lots or submit bids until admin approval is completed.
        </div>
    <?php endif; ?>

    <h2>Capabilities</h2>

    <?php if (empty($data['capabilities'])): ?>
        <p class="muted">No waste-handling capabilities have been assigned to your recycler profile yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Can Handle High Risk</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['capabilities'] as $capability): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($capability->category_name); ?></td>

                            <td>
                                <?php echo ((int) $capability->can_handle_high_risk === 1) ? 'Yes' : 'No'; ?>
                            </td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($capability->status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($data['profile']->verification_status === 'VERIFIED'): ?>
        <div class="dashboard-grid">
            <a class="dashboard-card link-card" href="<?php echo url('recycler/elots'); ?>">
                Eligible Open E-Lots:
                <strong><?php echo htmlspecialchars($data['stats']->eligible_open_elots ?? 0); ?></strong>
            </a>

            <a class="dashboard-card link-card" href="<?php echo url('recycler/my-bids'); ?>">
                My Bids:
                <strong><?php echo htmlspecialchars($data['stats']->my_bids ?? 0); ?></strong>
            </a>

            <a class="dashboard-card link-card" href="<?php echo url('recycler/my-elots'); ?>">
                My Awarded E-Lots:
                <strong><?php echo htmlspecialchars($data['stats']->my_awarded_elots ?? 0); ?></strong>
            </a>

            <div class="dashboard-card">
                Awaiting Handover:
                <strong><?php echo htmlspecialchars($data['stats']->awaiting_handover ?? 0); ?></strong>
            </div>

            <div class="dashboard-card">
                Handed Over:
                <strong><?php echo htmlspecialchars($data['stats']->handed_over ?? 0); ?></strong>
            </div>

            <div class="dashboard-card">
                Processing:
                <strong><?php echo htmlspecialchars($data['stats']->processing ?? 0); ?></strong>
            </div>

            <div class="dashboard-card">
                Completed:
                <strong><?php echo htmlspecialchars($data['stats']->completed ?? 0); ?></strong>
            </div>
        </div>
    <?php endif; ?>
</section>