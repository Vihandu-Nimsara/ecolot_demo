<section class="hero-card">
    <div class="page-header">
        <div>
            <p class="page-kicker">Municipal Officer</p>
            <h1 class="page-title">Feedback &amp; Complaints</h1>
            <p class="page-subtitle muted">
                <?php echo htmlspecialchars($data['profile']->council_name); ?>
            </p>
        </div>

        <div class="page-actions">
            <a class="btn secondary" href="<?php echo url('municipal-officer/dashboard'); ?>">Dashboard</a>
        </div>
    </div>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="filter-tabs">
        <?php foreach (['OPEN', 'IN_REVIEW', 'RESOLVED', 'CLOSED', 'ALL'] as $status): ?>
            <a
                class="<?php echo $data['current_status'] === $status ? 'active' : ''; ?>"
                href="<?php echo url('municipal-officer/feedback') . '?status=' . urlencode($status); ?>"
            >
                <?php echo htmlspecialchars(str_replace('_', ' ', $status)); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($data['feedback_list'])): ?>
        <div class="empty-state">No feedback found for this filter.</div>
    <?php else: ?>
        <div class="table-card">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Feedback</th>
                            <th>Submitted By</th>
                            <th>Related Request</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['feedback_list'] as $fb): ?>
                            <?php
                                $statusClass = match ($fb->status) {
                                    'OPEN' => 'status-warning',
                                    'IN_REVIEW' => 'status-badge',
                                    'RESOLVED' => 'status-success',
                                    'CLOSED' => 'status-muted',
                                    default => 'status-badge'
                                };
                            ?>
                            <tr>
                                <td>
                                    #<?php echo htmlspecialchars($fb->feedback_id); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($fb->created_at); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($fb->submitted_by_name); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($fb->submitted_by_email); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($fb->request_id)): ?>
                                        <a href="<?php echo url('municipal-officer/request-details/' . $fb->request_id); ?>">
                                            Request #<?php echo htmlspecialchars($fb->request_id); ?>
                                        </a>
                                    <?php else: ?>
                                        <small class="muted">General feedback</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($fb->subject); ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($fb->status); ?></span></td>
                                <td>
                                    <a class="btn compact secondary" href="<?php echo url('municipal-officer/feedback-details/' . $fb->feedback_id); ?>">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
