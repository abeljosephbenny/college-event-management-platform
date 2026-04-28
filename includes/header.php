<?php
/**
 * Common Header — included on all pages
 * 
 * Variables available before include:
 *   $pageTitle  — Page title (required)
 *   $bodyClass  — Optional body class
 *   $hideNav    — Set true to hide navbar (e.g., auth pages)
 */

if (!isset($pageTitle))
    $pageTitle = SITE_NAME;
if (!isset($bodyClass))
    $bodyClass = '';
if (!isset($hideNav))
    $hideNav = false;

$currentUser = getCurrentUser();
$currentRole = $currentUser['role'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= sanitize(SITE_TAGLINE) ?> — Campus Event Management Platform">
    <title><?= sanitize($pageTitle) ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body class="<?= sanitize($bodyClass) ?>">

    <?php if (!$hideNav): ?>
        <nav class="navbar">
            <div class="container">
                <a href="/" class="navbar-brand">
                    <span>⌗ <?= SITE_NAME ?></span>
                </a>

                <div class="nav-links" id="nav-links">
                    <a href="/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Events</a>

                    <?php if ($currentRole === 'Student'): ?>
                        <a href="/student/dashboard.php"
                            class="<?= strpos($_SERVER['REQUEST_URI'], '/student/') !== false ? 'active' : '' ?>">My
                            Dashboard</a>
                    <?php elseif ($currentRole === 'Organizer'): ?>
                        <a href="/organizer/dashboard.php"
                            class="<?= strpos($_SERVER['REQUEST_URI'], '/organizer/') !== false ? 'active' : '' ?>">Organizer
                            Panel</a>
                    <?php elseif ($currentRole === 'Administrator'): ?>
                        <a href="/admin/dashboard.php"
                            class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : '' ?>">Admin Panel</a>
                    <?php endif; ?>
                </div>

                <div class="nav-user">
                    <?php if ($currentUser): ?>
                        <a href="/profile.php" class="user-avatar-link" title="<?= sanitize($currentUser['name']) ?>">
                            <?php if (!empty($currentUser['profile_pic'])): ?>
                                <img src="/<?= sanitize($currentUser['profile_pic']) ?>" alt="<?= sanitize($currentUser['name']) ?>" class="user-avatar-img">
                            <?php else: ?>
                                <div class="user-avatar"><?= getInitials($currentUser['name']) ?></div>
                            <?php endif; ?>
                        </a>
                        <a href="/auth/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="/auth/login.php" class="btn btn-outline btn-sm">Log In</a>
                        <a href="/auth/signup.php" class="btn btn-primary btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>

                <div class="hamburger" onclick="document.getElementById('nav-links').classList.toggle('open')">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </nav>
    <?php endif; ?>