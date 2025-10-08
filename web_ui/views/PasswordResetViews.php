<?php
// View class for rendering the forgot password form
class ForgotPasswordView {
    public static function render($error = '', $message = '') {
        ob_start();
        ?>
        <div class="forgot-password-container">
            <h2>Forgot Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <button type="submit" class="login-btn">Send Reset Link</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

// View class for rendering the reset password form
class ResetPasswordView {
    public static function render($token, $error = '', $message = '') {
        ob_start();
        ?>
        <div class="reset-password-container">
            <h2>Reset Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
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
