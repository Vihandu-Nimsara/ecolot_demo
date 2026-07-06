<section class="hero-card">
    <h1>Administrator Dashboard</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <p>Welcome, <?php echo htmlspecialchars(currentUserName()); ?>.</p>

    <div class="dashboard-grid">

    <a class="dashboard-card link-card" href="<?php echo url('admin/reports'); ?>">
    Reports & Analytics
</a>


        <a class="dashboard-card link-card" href="<?php echo url('admin/users'); ?>">
            Total Users:
            <strong><?php echo htmlspecialchars($data['stats']->total_users ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('admin/users/municipal_officer'); ?>">
            Municipal Officers:
            <strong><?php echo htmlspecialchars($data['stats']->municipal_officers ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('admin/users/collector'); ?>">
            Collectors:
            <strong><?php echo htmlspecialchars($data['stats']->collectors ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('admin/recycler-verification/pending'); ?>">
            Pending Recyclers:
            <strong><?php echo htmlspecialchars($data['stats']->pending_recyclers ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('admin/recycler-verification/verified'); ?>">
            Verified Recyclers:
            <strong><?php echo htmlspecialchars($data['stats']->verified_recyclers ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('admin/categories'); ?>">
            E-Waste Categories:
            <strong><?php echo htmlspecialchars($data['stats']->total_categories ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('admin/categories'); ?>">
            E-Waste Items:
            <strong><?php echo htmlspecialchars($data['stats']->total_items ?? 0); ?></strong>
        </a>

        <a class="dashboard-card link-card" href="<?php echo url('admin/risk-rules'); ?>">
            Risk Rules:
            <strong><?php echo htmlspecialchars($data['stats']->total_risk_rules ?? 0); ?></strong>
        </a>
    </div>
</section>