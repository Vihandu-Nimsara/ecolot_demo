<section class="form-card">
    <h1>Login</h1>

    <?php echo flash('auth_success'); ?>
    <?php echo flash('auth_error'); ?>

    <?php if (!empty($data['errors']['login'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($data['errors']['login']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo url('auth/login'); ?>">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="<?php echo htmlspecialchars($data['old']['email'] ?? ''); ?>"
                placeholder="Enter your email"
                required
            >

            <?php if (!empty($data['errors']['email'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($data['errors']['email']); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                placeholder="Enter your password"
                required
            >

            <?php if (!empty($data['errors']['password'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($data['errors']['password']); ?></small>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <p class="form-link">
        Do not have an account?
        <a href="<?php echo url('auth/register'); ?>">Register here</a>
    </p>
</section>