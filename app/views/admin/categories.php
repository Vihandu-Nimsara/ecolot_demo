<section class="form-card large-form">
    <h1>Category & Item Management</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('admin/risk-rules'); ?>">Risk Rules</a>
        <a class="btn secondary" href="<?php echo url('admin/dashboard'); ?>">Dashboard</a>
    </div>

    <h2>Create Category</h2>

    <form method="POST" action="<?php echo url('admin/store-category'); ?>">
        <div class="form-group">
            <label>Category Name</label>
            <input type="text" name="category_name" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"></textarea>
        </div>

        <button type="submit" class="btn">Create Category</button>
    </form>

    <hr>

    <h2>Create E-Waste Item</h2>

    <form method="POST" action="<?php echo url('admin/store-item'); ?>">
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($data['categories'] as $category): ?>
                    <option value="<?php echo htmlspecialchars($category->category_id); ?>">
                        <?php echo htmlspecialchars($category->category_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Item Name</label>
            <input type="text" name="item_name" required>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Collection Status</label>
                <select name="collection_status" required>
                    <option value="ACCEPTED">Accepted</option>
                    <option value="REVIEW_REQUIRED">Review Required</option>
                    <option value="NOT_COLLECTED">Not Collected</option>
                </select>
            </div>

            <div class="form-group">
                <label>Default Risk Level</label>
                <select name="default_risk_level" required>
                    <option value="LOW">Low</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="HIGH">High</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn">Create Item</button>
    </form>
</section>

<section class="hero-card">
    <h2>Categories</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Category ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($data['categories'] as $category): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($category->category_id); ?></td>
                        <td><?php echo htmlspecialchars($category->category_name); ?></td>
                        <td><?php echo htmlspecialchars($category->description ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($category->item_count); ?></td>
                        <td>
                            <span class="status-badge">
                                <?php echo htmlspecialchars($category->status); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="<?php echo url('admin/update-category-status/' . $category->category_id); ?>">
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

    <h2>E-Waste Items</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Collection Status</th>
                    <th>Risk Level</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($data['items'] as $item): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($item->item_id); ?></td>
                        <td><?php echo htmlspecialchars($item->category_name); ?></td>
                        <td><?php echo htmlspecialchars($item->item_name); ?></td>
                        <td>
                            <span class="status-badge">
                                <?php echo htmlspecialchars($item->collection_status); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($item->default_risk_level); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>