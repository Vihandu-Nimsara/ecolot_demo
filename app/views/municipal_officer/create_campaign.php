<section class="form-card large-form">
    <h1>Create Monthly Collection Campaign</h1>

    <?php if (!empty($data['errors']['campaign'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['campaign']); ?>
        </div>
    <?php endif; ?>

    <div class="info-box">
        <strong>Council:</strong>
        <?php echo htmlspecialchars($data['profile']->council_name); ?>
    </div>

    <form method="POST" action="<?php echo url('municipal-officer/store-campaign'); ?>">
        <div class="form-group">
            <label for="campaign_name">Campaign Name</label>
            <input
                type="text"
                id="campaign_name"
                name="campaign_name"
                value="<?php echo htmlspecialchars($data['old']['campaign_name'] ?? ''); ?>"
                placeholder="Example: August 2026 E-Waste Collection Campaign"
                required
            >

            <?php if (!empty($data['errors']['campaign_name'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['campaign_name']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="campaign_month">Campaign Month</label>
                <select id="campaign_month" name="campaign_month" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>"
                            <?php echo ((int)($data['old']['campaign_month'] ?? 0) === $m) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <?php if (!empty($data['errors']['campaign_month'])): ?>
                    <small class="error-text">
                        <?php echo htmlspecialchars($data['errors']['campaign_month']); ?>
                    </small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="campaign_year">Campaign Year</label>
                <input
                    type="number"
                    id="campaign_year"
                    name="campaign_year"
                    min="<?php echo date('Y'); ?>"
                    max="<?php echo date('Y') + 2; ?>"
                    value="<?php echo htmlspecialchars($data['old']['campaign_year'] ?? date('Y')); ?>"
                    required
                >

                <?php if (!empty($data['errors']['campaign_year'])): ?>
                    <small class="error-text">
                        <?php echo htmlspecialchars($data['errors']['campaign_year']); ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="request_cutoff_date">Request Cut-off Date</label>
            <input
                type="date"
                id="request_cutoff_date"
                name="request_cutoff_date"
                value="<?php echo htmlspecialchars($data['old']['request_cutoff_date'] ?? ''); ?>"
                required
            >

            <?php if (!empty($data['errors']['request_cutoff_date'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['request_cutoff_date']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="status">Campaign Status</label>
            <select id="status" name="status" required>
                <option value="DRAFT"
                    <?php echo (($data['old']['status'] ?? '') === 'DRAFT') ? 'selected' : ''; ?>>
                    Draft
                </option>
                <option value="OPEN"
                    <?php echo (($data['old']['status'] ?? '') === 'OPEN') ? 'selected' : ''; ?>>
                    Open
                </option>
            </select>

            <?php if (!empty($data['errors']['status'])): ?>
                <small class="error-text">
                    <?php echo htmlspecialchars($data['errors']['status']); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="button-row">
            <button type="submit" class="btn">Create Campaign</button>

            <a class="btn secondary" href="<?php echo url('municipal-officer/campaigns'); ?>">
                Cancel
            </a>
        </div>
    </form>
</section>