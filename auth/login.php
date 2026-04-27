<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    match($role) {
        'Student'       => redirect('/student/dashboard.php'),
        'Organizer'     => redirect('/organizer/dashboard.php'),
        'Administrator' => redirect('/admin/dashboard.php'),
        default         => redirect('/'),
    };
}

$pageTitle = 'Log In';
$hideNav = true;
$bodyClass = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <div class="auth-brand"><span>⚡ <?= SITE_NAME ?></span></div>
        <h1>Welcome back</h1>
        <p class="auth-subtitle">Sign in to your campus account</p>

        <?= renderFlash() ?>

        <form method="POST" action="/auth/process_auth.php" id="login-form">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@<?= COLLEGE_EMAIL_DOMAIN ?>"
                       data-college-email="<?= COLLEGE_EMAIL_DOMAIN ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="/auth/signup.php">Create one</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
