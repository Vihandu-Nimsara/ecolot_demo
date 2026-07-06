<section class="form-card large-form">
    <h1>Create Account</h1>

    <?php if (!empty($data['errors']['register'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['register']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo url('auth/register'); ?>">
        <div class="form-group">
            <label for="role">Account Type</label>
            <select id="role" name="role" required>
                <option value="">Select account type</option>
                <option value="PUBLIC_USER"
                    <?php echo (($data['old']['role'] ?? '') === 'PUBLIC_USER') ? 'selected' : ''; ?>>
                    Public User
                </option>
                <option value="AUTHORIZED_RECYCLER"
                    <?php echo (($data['old']['role'] ?? '') === 'AUTHORIZED_RECYCLER') ? 'selected' : ''; ?>>
                    Authorized Recycler
                </option>
            </select>

            <?php if (!empty($data['errors']['role'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($data['errors']['role']); ?></small>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?php echo htmlspecialchars($data['old']['full_name'] ?? ''); ?>"
                    required
                >

                <?php if (!empty($data['errors']['full_name'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['full_name']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($data['old']['phone'] ?? ''); ?>"
                    required
                >

                <?php if (!empty($data['errors']['phone'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['phone']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($data['old']['email'] ?? ''); ?>"
                required
            >

            <?php if (!empty($data['errors']['email'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($data['errors']['email']); ?></small>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >

                <?php if (!empty($data['errors']['password'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['password']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >

                <?php if (!empty($data['errors']['confirm_password'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['confirm_password']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div id="public-user-fields" class="role-fields">
            <h2>Public User Details</h2>

            <div class="form-group">
                <label for="area_id">Postal Code / Collection Area</label>
                <select id="area_id" name="area_id">
                    <option value="">Select your postal code area</option>

                    <?php foreach ($data['areas'] as $area): ?>
                        <option value="<?php echo htmlspecialchars($area->area_id); ?>"
                            <?php echo ((int)($data['old']['area_id'] ?? 0) === (int)$area->area_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area->postal_code . ' - ' . $area->area_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($data['errors']['area_id'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['area_id']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address_line1">Address Line 1</label>
                <input
                    type="text"
                    id="address_line1"
                    name="address_line1"
                    value="<?php echo htmlspecialchars($data['old']['address_line1'] ?? ''); ?>"
                >

                <?php if (!empty($data['errors']['address_line1'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['address_line1']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address_line2">Address Line 2</label>
                <input
                    type="text"
                    id="address_line2"
                    name="address_line2"
                    value="<?php echo htmlspecialchars($data['old']['address_line2'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <input
                    type="text"
                    id="city"
                    name="city"
                    value="<?php echo htmlspecialchars($data['old']['city'] ?? ''); ?>"
                >

                <?php if (!empty($data['errors']['city'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['city']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div id="recycler-fields" class="role-fields">
            <h2>Recycler Company Details</h2>

            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input
                    type="text"
                    id="company_name"
                    name="company_name"
                    value="<?php echo htmlspecialchars($data['old']['company_name'] ?? ''); ?>"
                >

                <?php if (!empty($data['errors']['company_name'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['company_name']); ?></small>
                <?php endif; ?>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="license_no">License Number</label>
                    <input
                        type="text"
                        id="license_no"
                        name="license_no"
                        value="<?php echo htmlspecialchars($data['old']['license_no'] ?? ''); ?>"
                    >

                    <?php if (!empty($data['errors']['license_no'])): ?>
                        <small class="error-text"><?php echo htmlspecialchars($data['errors']['license_no']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="license_expiry_date">License Expiry Date</label>
                    <input
                        type="date"
                        id="license_expiry_date"
                        name="license_expiry_date"
                        value="<?php echo htmlspecialchars($data['old']['license_expiry_date'] ?? ''); ?>"
                    >

                    <?php if (!empty($data['errors']['license_expiry_date'])): ?>
                        <small class="error-text"><?php echo htmlspecialchars($data['errors']['license_expiry_date']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="recycler_address">Company Address</label>
                <textarea id="recycler_address" name="recycler_address"><?php echo htmlspecialchars($data['old']['recycler_address'] ?? ''); ?></textarea>

                <?php if (!empty($data['errors']['recycler_address'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($data['errors']['recycler_address']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn">Register</button>
    </form>

    <p class="form-link">
        Already have an account?
        <a href="<?php echo url('auth/login'); ?>">Login here</a>
    </p>
</section>

<script>
    const roleSelect = document.getElementById('role');
    const publicFields = document.getElementById('public-user-fields');
    const recyclerFields = document.getElementById('recycler-fields');

    function toggleRoleFields() {
        const role = roleSelect.value;

        publicFields.style.display = role === 'PUBLIC_USER' ? 'block' : 'none';
        recyclerFields.style.display = role === 'AUTHORIZED_RECYCLER' ? 'block' : 'none';
    }

    roleSelect.addEventListener('change', toggleRoleFields);
    toggleRoleFields();
</script>