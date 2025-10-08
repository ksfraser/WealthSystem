<?php
// View class for rendering the admin password reset form
class AdminPasswordResetView {
    public static function render($users, $error = '', $message = '') {
        ob_start();
        ?>
        <div class="admin-password-reset-container">
            <h2>Admin: Reset User Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="user_id">Select User:</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo (int)$user['id']; ?>"><?php echo htmlspecialchars($user['username'] . ' (' . $user['email'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="login-btn">Reset Password</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
