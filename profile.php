<?php
/**
 * User Profile Page
 */
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch full user data from DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) { setFlash('danger', 'User not found.'); redirect('/'); }

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:700px">
        <?= renderFlash() ?>

        <div class="card fade-in" style="padding:2.5rem">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar-wrapper">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img src="/<?= sanitize($user['profile_pic']) ?>" alt="<?= sanitize($user['name']) ?>" class="profile-avatar-img">
                    <?php else: ?>
                        <div class="profile-avatar-fallback">
                            <?= getInitials($user['name']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-header-info">
                    <h1 style="margin-bottom:.25rem"><?= sanitize($user['name']) ?></h1>
                    <p class="text-secondary" style="margin-bottom:.5rem"><?= sanitize($user['email']) ?></p>
                    <span class="badge badge-primary"><?= sanitize($user['role']) ?></span>
                </div>
            </div>

            <!-- Profile Details -->
            <div style="margin-top:2rem">
                <h3 class="section-title">Profile Details</h3>
                <div class="profile-details">
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Full Name</span>
                        <span class="profile-detail-value"><?= sanitize($user['name']) ?></span>
                    </div>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Email</span>
                        <span class="profile-detail-value"><?= sanitize($user['email']) ?></span>
                    </div>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Role</span>
                        <span class="profile-detail-value"><?= sanitize($user['role']) ?></span>
                    </div>
                    <?php if ($user['phone']): ?>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Phone</span>
                        <span class="profile-detail-value"><?= sanitize($user['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($user['department']): ?>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Department</span>
                        <span class="profile-detail-value"><?= sanitize($user['department']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($user['year']): ?>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Year</span>
                        <span class="profile-detail-value"><?= $user['year'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($user['admission_number']): ?>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Admission Number</span>
                        <span class="profile-detail-value"><?= sanitize($user['admission_number']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Account Status</span>
                        <span class="profile-detail-value">
                            <?php if ($user['is_verified']): ?>
                                <span class="badge badge-success">Verified</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending Verification</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Member Since</span>
                        <span class="profile-detail-value"><?= date('M j, Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Action -->
            <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--clr-border-light)">
                <a href="/edit_profile.php" class="btn btn-primary">✏️ Edit Profile</a>
            </div>
        </div>
    </div>
</div>

<style>
.profile-header {
    display: flex;
    align-items: center;
    gap: 1.75rem;
}
.profile-avatar-wrapper {
    flex-shrink: 0;
}
.profile-avatar-img {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--clr-border-light);
    box-shadow: var(--shadow);
}
.profile-avatar-fallback {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--clr-primary), var(--clr-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 2rem;
    box-shadow: var(--shadow);
}
.profile-header-info {
    flex: 1;
}
.profile-details {
    display: flex;
    flex-direction: column;
}
.profile-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .85rem 0;
    border-bottom: 1px solid var(--clr-border-light);
    font-size: .9rem;
}
.profile-detail-row:last-child {
    border-bottom: none;
}
.profile-detail-label {
    color: var(--clr-text-muted);
    font-weight: 500;
}
.profile-detail-value {
    font-weight: 600;
    color: var(--clr-text);
}
@media(max-width:480px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
