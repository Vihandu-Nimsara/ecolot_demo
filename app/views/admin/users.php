<section class="form-card large-form">
    <h1>User Management</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <div class="button-row">
        <a class="btn secondary" href="<?php echo url('admin/users'); ?>">All</a>
        <a class="btn secondary" href="<?php echo url('admin/users/public_user'); ?>">Public Users</a>
        <a class="btn secondary" href="<?php echo url('admin/users/municipal_officer'); ?>">Officers</a>
        <a class="btn secondary" href="<?php echo url('admin/users/collector'); ?>">Collectors</a>
        <a class="btn secondary" href="<?php echo url('admin/users/authorized_recycler'); ?>">Recyclers</a>
        <a class="btn secondary" href="<?php echo url('admin/dashboard'); ?>">Dashboard</a>
    </div>

    <p class="muted">Current filter: <?php echo htmlspecialchars($data['current_role']); ?></p>

    <h2>Create Privileged User</h2>

    <form method="POST" action="<?php echo url('admin/store-user'); ?>">
        <div class="grid-2">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($data['old']['full_name'] ?? ''); ?>" required>
                <?php if (!empty($data['errors']['full_name'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['full_name']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($data['old']['email'] ?? ''); ?>" required>
                <?php if (!empty($data['errors']['email'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['email']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($data['old']['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
                <?php if (!empty($data['errors']['password'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['password']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role" required>
                    <option value="">Select role</option>
                    <option value="MUNICIPAL_OFFICER">Municipal Officer</option>
                    <option value="COLLECTOR">Collector</option>
                    <option value="ADMIN">Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Council</label>
                <select name="council_id">
                    <option value="">Select council</option>
                    <?php foreach ($data['councils'] as $council): ?>
                        <option value="<?php echo htmlspecialchars($council->council_id); ?>">
                            <?php echo htmlspecialchars($council->council_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Employee No</label>
                <input type="text" name="employee_no" placeholder="Required for officer/collector">
            </div>

            <div class="form-group">
                <label>Designation</label>
                <input type="text" name="designation" placeholder="For municipal officer">
            </div>
        </div>

        <button type="submit" class="btn">Create User</button>
    </form>
</section>

<section class="hero-card">
    <h2>Users</h2>

    <?php if (empty($data['users'])): ?>
        <p class="muted">No users found.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Update Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data['users'] as $user): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($user->user_id); ?></td>
                            <td><?php echo htmlspecialchars($user->full_name); ?></td>
                            <td><?php echo htmlspecialchars($user->email); ?></td>
                            <td><?php echo htmlspecialchars($user->phone ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user->role); ?></td>
                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($user->status); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user->created_at); ?></td>
                            <td>
                                <?php if ((int)$user->user_id === (int)$_SESSION['user_id']): ?>
                                    <span class="muted">Current admin</span>
                                <?php else: ?>
                                    <form method="POST" action="<?php echo url('admin/update-user-status/' . $user->user_id); ?>">
                                        <select name="status">
                                            <option value="ACTIVE">ACTIVE</option>
                                            <option value="SUSPENDED">SUSPENDED</option>
                                            <option value="REJECTED">REJECTED</option>
                                        </select>

                                        <button type="submit" class="btn secondary">Update</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>