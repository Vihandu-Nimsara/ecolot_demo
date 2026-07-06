<section class="hero-card">
    <h1>Recycler Details</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('admin/recycler-verification'); ?>">
            Back to Verification List
        </a>
    </div>

    <div class="info-box">
        <strong>Company:</strong>
        <?php echo htmlspecialchars($data['recycler']->company_name); ?>
        <br>

        <strong>Contact:</strong>
        <?php echo htmlspecialchars($data['recycler']->full_name); ?>
        |
        <?php echo htmlspecialchars($data['recycler']->email); ?>
        <br>

        <strong>Phone:</strong>
        <?php echo htmlspecialchars($data['recycler']->phone ?? '-'); ?>
        <br>

        <strong>License No:</strong>
        <?php echo htmlspecialchars($data['recycler']->license_no ?? '-'); ?>
        <br>

        <strong>License Expiry:</strong>
        <?php echo htmlspecialchars($data['recycler']->license_expiry_date ?? '-'); ?>
        <br>

        <strong>Address:</strong>
        <?php echo nl2br(htmlspecialchars($data['recycler']->address ?? '-')); ?>
        <br>

        <strong>Verification Status:</strong>
        <span class="status-badge">
            <?php echo htmlspecialchars($data['recycler']->verification_status); ?>
        </span>
    </div>

    <h2>Current Capabilities</h2>

    <?php if (empty($data['capabilities'])): ?>
        <p class="muted">No capabilities assigned yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>High Risk</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['capabilities'] as $capability): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($capability->category_name); ?></td>
                            <td><?php echo ((int)$capability->can_handle_high_risk === 1) ? 'Yes' : 'No'; ?></td>
                            <td><?php echo htmlspecialchars($capability->status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Verification Decision</h2>

    <form method="POST" action="<?php echo url('admin/verify-recycler/' . $data['recycler']->recycler_profile_id); ?>">
        <div class="info-box">
            Select the e-waste categories this recycler is legally/operationally allowed to handle.
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Allow</th>
                        <th>Category</th>
                        <th>Can Handle High Risk</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['categories'] as $category): ?>
                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    name="category_ids[]"
                                    value="<?php echo htmlspecialchars($category->category_id); ?>"
                                >
                            </td>

                            <td><?php echo htmlspecialchars($category->category_name); ?></td>

                            <td>
                                <input
                                    type="checkbox"
                                    name="can_handle_high_risk[<?php echo htmlspecialchars($category->category_id); ?>]"
                                    value="1"
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="button-row">
            <button type="submit" name="decision" value="VERIFIED" class="btn">
                Verify Recycler
            </button>

            <button type="submit" name="decision" value="REJECTED" class="btn danger">
                Reject Recycler
            </button>
        </div>
    </form>
</section>