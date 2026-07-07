<?php $account = $data['account']; ?>
<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">Municipal Officer</p>
            <h1 class="page-title">Officer Profile</h1>
            <p class="page-subtitle muted">Account and council assignment details.</p>
        </div>

        <div class="page-actions">
            <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">Dashboard</a>
        </div>
    </div>

    <div class="content-card">
        <h3>Account</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Full Name</label>
                <p><?php echo htmlspecialchars($account->full_name); ?></p>
            </div>
            <div class="form-group">
                <label>Email</label>
                <p><?php echo htmlspecialchars($account->email); ?></p>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <p><?php echo htmlspecialchars($account->phone ?: '-'); ?></p>
            </div>
            <div class="form-group">
                <label>Role</label>
                <p><span class="status-badge"><?php echo htmlspecialchars($account->role); ?></span></p>
            </div>
            <div class="form-group">
                <label>Account Status</label>
                <p><span class="status-badge status-success"><?php echo htmlspecialchars($account->account_status); ?></span></p>
            </div>
            <div class="form-group">
                <label>Member Since</label>
                <p><?php echo htmlspecialchars($account->account_created_at); ?></p>
            </div>
        </div>
    </div>

    <div class="content-card">
        <h3>Council Assignment</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Council</label>
                <p><?php echo htmlspecialchars($account->council_name); ?></p>
            </div>
            <div class="form-group">
                <label>District</label>
                <p><?php echo htmlspecialchars($account->district ?: '-'); ?></p>
            </div>
            <div class="form-group">
                <label>Province</label>
                <p><?php echo htmlspecialchars($account->province ?: '-'); ?></p>
            </div>
            <div class="form-group">
                <label>Employee No.</label>
                <p><?php echo htmlspecialchars($account->employee_no ?: '-'); ?></p>
            </div>
            <div class="form-group">
                <label>Designation</label>
                <p><?php echo htmlspecialchars($account->designation ?: '-'); ?></p>
            </div>
        </div>
    </div>

    <div class="info-box">
        Profile editing is not enabled in this prototype. Contact your system administrator to update account or council assignment details.
    </div>
</section>
