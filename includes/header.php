<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Events</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="index.php">Campus<strong>Events</strong></a>
    </div>
    <div class="nav-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="avatar-link">
                <div class="avatar">
                    <?= strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                </div>
            </a>
        <?php else: ?>
            <a href="create-event.php" class="btn btn-primary"> Create Event</a>
            <a href="auth/login.php" class="btn btn-text">Log In</a>
            <a href="auth/signup.php" class="btn btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>