<?php $fb = $data['feedback']; ?>
<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">Municipal Officer</p>
            <h1 class="page-title">Feedback #<?php echo htmlspecialchars($fb->feedback_id); ?></h1>
            <p class="page-subtitle muted"><?php echo htmlspecialchars($fb->subject); ?></p>
        </div>

        <div class="page-actions">
            <a class="btn secondary" href="<?php echo url('municipal-officer/feedback'); ?>">Back to Feedback</a>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="content-card">
        <h3>Details</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Submitted By</label>
                <p><?php echo htmlspecialchars($fb->submitted_by_name); ?> (<?php echo htmlspecialchars($fb->submitted_by_email); ?>)</p>
            </div>
            <div class="form-group">
                <label>Related Request</label>
                <p>
                    <?php if (!empty($fb->request_id)): ?>
                        <a href="<?php echo url('municipal-officer/request-details/' . $fb->request_id); ?>">
                            Request #<?php echo htmlspecialchars($fb->request_id); ?>
                        </a>
                    <?php else: ?>
                        <span class="muted">General feedback (not tied to a request)</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="form-group">
                <label>Submitted At</label>
                <p><?php echo htmlspecialchars($fb->created_at); ?></p>
            </div>
            <div class="form-group">
                <label>Current Status</label>
                <p><span class="status-badge"><?php echo htmlspecialchars($fb->status); ?></span></p>
            </div>
        </div>

        <div class="form-group">
            <label>Message</label>
            <p><?php echo nl2br(htmlspecialchars($fb->message)); ?></p>
        </div>
    </div>

    <div class="form-card">
        <h3>Officer Response</h3>
        <form class="stacked-form" method="POST" action="<?php echo url('municipal-officer/update-feedback-status/' . $fb->feedback_id); ?>">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <?php foreach (['OPEN', 'IN_REVIEW', 'RESOLVED', 'CLOSED'] as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo $fb->status === $status ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(str_replace('_', ' ', $status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="officer_reply">Officer Reply</label>
                <textarea id="officer_reply" name="officer_reply" rows="4"><?php echo htmlspecialchars($fb->officer_reply ?? ''); ?></textarea>
            </div>

            <div class="button-row">
                <button class="btn primary" type="submit">Save Response</button>
            </div>
        </form>
    </div>
</section>
