<?php
/**
 * Signup Page
 */
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect('/');

$pageTitle = 'Sign Up';
$hideNav = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card fade-in" style="max-width:520px">
        <div class="auth-brand"><span>⚡ <?= SITE_NAME ?></span></div>
        <h1>Create Account</h1>
        <p class="auth-subtitle">Join your campus event community</p>

        <?= renderFlash() ?>

        <form method="POST" action="/auth/process_auth.php" id="signup-form">
            <input type="hidden" name="action" value="signup">

            <div class="form-group">
                <label for="role">I am a…</label>
                <select id="role" name="role" class="form-control" required onchange="toggleRoleFields(this.value)">
                    <option value="">Select role</option>
                    <option value="Student">Student</option>
                    <option value="Organizer">Event Organizer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Your full name" required>
            </div>

            <div class="form-group">
                <label for="email">College Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@<?= COLLEGE_EMAIL_DOMAIN ?>"
                       data-college-email="<?= COLLEGE_EMAIL_DOMAIN ?>" required>
                <div class="form-hint">Use your @<?= COLLEGE_EMAIL_DOMAIN ?> email</div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit mobile number">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>
            </div>

            <!-- Student-specific fields -->
            <div id="student-fields" style="display:none">
                <div class="form-row">
                    <div class="form-group">
                        <label for="admission_number">Admission Number</label>
                        <input type="text" id="admission_number" name="admission_number" class="form-control" placeholder="e.g. TKMCE2024001">
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year" class="form-control">
                            <option value="">Select year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" class="form-control" placeholder="e.g. Computer Science">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="/auth/login.php">Sign in</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
