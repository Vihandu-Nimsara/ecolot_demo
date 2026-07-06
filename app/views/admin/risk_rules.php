<section class="form-card large-form">
    <h1>Risk Rule Management</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('admin/categories'); ?>">Categories & Items</a>
        <a class="btn secondary" href="<?php echo url('admin/dashboard'); ?>">Dashboard</a>
    </div>

    <h2>Create Risk Rule</h2>

    <form method="POST" action="<?php echo url('admin/store-risk-rule'); ?>">
        <div class="grid-2">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">Optional category</option>
                    <?php foreach ($data['categories'] as $category): ?>
                        <option value="<?php echo htmlspecialchars($category->category_id); ?>">
                            <?php echo htmlspecialchars($category->category_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Specific Item</label>
                <select name="item_id">
                    <option value="">Optional item</option>
                    <?php foreach ($data['items'] as $item): ?>
                        <option value="<?php echo htmlspecialchars($item->item_id); ?>">
                            <?php echo htmlspecialchars($item->category_name . ' - ' . $item->item_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Condition</label>
                <select name="condition_status" required>
                    <option value="UNKNOWN">Unknown</option>
                    <option value="WORKING">Working</option>
                    <option value="DAMAGED">Damaged</option>
                    <option value="BROKEN">Broken</option>
                    <option value="LEAKING">Leaking</option>
                </select>
            </div>

            <div class="form-group">
                <label>Risk Level</label>
                <select name="risk_level" required>
                    <option value="LOW">Low</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="HIGH">High</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Rule Description</label>
            <textarea name="rule_description" placeholder="Example: Leaking batteries must be reviewed before collection."></textarea>
        </div>

        <button type="submit" class="btn">Create Risk Rule</button>
    </form>
</section>

<section class="hero-card">
    <h2>Risk Rules</h2>

    <?php if (empty($data['rules'])): ?>
        <p class="muted">No risk rules found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Rule ID</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Condition</th>
                        <th>Risk Level</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['rules'] as $rule): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($rule->risk_rule_id); ?></td>

                            <td><?php echo htmlspecialchars($rule->category_name ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($rule->item_name ?? '-'); ?></td>

                            <td><?php echo htmlspecialchars($rule->condition_status ?? '-'); ?></td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($rule->risk_level ?? '-'); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($rule->rule_description ?? '-'); ?></td>

                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($rule->status ?? '-'); ?>
                                </span>
                            </td>

                            <td>
                                <form method="POST" action="<?php echo url('admin/update-risk-rule-status/' . $rule->risk_rule_id); ?>">
                                    <select name="status">
                                        <option value="ACTIVE">ACTIVE</option>
                                        <option value="INACTIVE">INACTIVE</option>
                                    </select>

                                    <button type="submit" class="btn secondary">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>