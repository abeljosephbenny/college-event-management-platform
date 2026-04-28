<?php
/**
 * Edit Profile — User can update their profile details
 */
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) { setFlash('danger', 'User not found.'); redirect('/'); }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = sanitize($_POST['name'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $year       = intval($_POST['year'] ?? 0);
    $admissionNumber = sanitize($_POST['admission_number'] ?? '');

    if (empty($name)) {
        setFlash('danger', 'Name is required.');
        redirect('/edit_profile.php');
    }

    // Handle profile picture upload
    $profilePicPath = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadedPath = uploadProfilePic($_FILES['profile_pic']);
        if ($uploadedPath === false) {
            setFlash('danger', 'Invalid image. Please upload a JPG, PNG, GIF or WebP (max 5MB).');
            redirect('/edit_profile.php');
        }
        $profilePicPath = $uploadedPath;
    }

    // Handle profile picture removal
    if (isset($_POST['remove_profile_pic']) && $_POST['remove_profile_pic'] === '1') {
        $profilePicPath = null;
    }

    // Handle password change
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmNew      = $_POST['confirm_new_password'] ?? '';

    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmNew)) {
        // All three fields must be filled
        if (empty($currentPassword) || empty($newPassword) || empty($confirmNew)) {
            setFlash('danger', 'Please fill in all password fields to change your password.');
            redirect('/edit_profile.php');
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            setFlash('danger', 'Current password is incorrect.');
            redirect('/edit_profile.php');
        }

        if ($newPassword !== $confirmNew) {
            setFlash('danger', 'New passwords do not match.');
            redirect('/edit_profile.php');
        }

        if (strlen($newPassword) < 6) {
            setFlash('danger', 'New password must be at least 6 characters.');
            redirect('/edit_profile.php');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $pwStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $pwStmt->execute([$hashedPassword, $userId]);
    }

    // Update user profile
    $updateStmt = $pdo->prepare("
        UPDATE users SET name = ?, phone = ?, department = ?, year = ?,
            admission_number = ?, profile_pic = ?
        WHERE user_id = ?
    ");
    $updateStmt->execute([
        $name, $phone ?: null, $department ?: null,
        $year ?: null, $admissionNumber ?: null,
        $profilePicPath, $userId
    ]);

    // Update session with new data
    $_SESSION['user_name']  = $name;
    $_SESSION['profile_pic'] = $profilePicPath;

    setFlash('success', 'Profile updated successfully.');
    redirect('/profile.php');
}

$pageTitle = 'Edit Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:600px">
        <?= renderFlash() ?>

        <a href="/profile.php" class="btn btn-secondary btn-sm mb-3">← Back to Profile</a>

        <div class="card fade-in" style="padding:2rem">
            <h1 style="margin-bottom:.25rem">Edit Profile</h1>
            <p class="text-secondary mb-3">Update your personal details</p>

            <form method="POST" enctype="multipart/form-data">
                <!-- Current Profile Pic -->
                <div class="form-group">
                    <label>Profile Picture</label>
                    <div class="profile-pic-edit">
                        <?php if (!empty($user['profile_pic'])): ?>
                            <div class="profile-pic-preview">
                                <img src="/<?= sanitize($user['profile_pic']) ?>" alt="Current profile picture" class="profile-pic-current">
                                <label class="profile-pic-remove">
                                    <input type="checkbox" name="remove_profile_pic" value="1" style="display:none" id="remove-pic-cb">
                                    <span class="btn btn-danger btn-sm" onclick="toggleRemovePic()">✕ Remove</span>
                                </label>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="profile_pic" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-hint">Upload a new picture to replace the current one. JPG, PNG, GIF or WebP (max 5MB).</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                    <div class="form-hint">Email address cannot be changed.</div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="10-digit mobile number">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="admission_number">Admission Number</label>
                        <input type="text" id="admission_number" name="admission_number" class="form-control" value="<?= sanitize($user['admission_number'] ?? '') ?>" placeholder="e.g. TKMCE2024001">
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year" class="form-control">
                            <option value="">Select year</option>
                            <option value="1" <?= ($user['year'] ?? 0) == 1 ? 'selected' : '' ?>>1st Year</option>
                            <option value="2" <?= ($user['year'] ?? 0) == 2 ? 'selected' : '' ?>>2nd Year</option>
                            <option value="3" <?= ($user['year'] ?? 0) == 3 ? 'selected' : '' ?>>3rd Year</option>
                            <option value="4" <?= ($user['year'] ?? 0) == 4 ? 'selected' : '' ?>>4th Year</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" class="form-control" value="<?= sanitize($user['department'] ?? '') ?>" placeholder="e.g. Computer Science">
                </div>

                <!-- Change Password -->
                <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--clr-border-light)">
                    <h3 class="section-title">Change Password</h3>
                    <p class="text-muted text-sm mb-2">Leave blank if you don't want to change your password.</p>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter your current password">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min 6 characters" minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirm_new_password">Confirm New Password</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" placeholder="Re-enter new password">
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-lg" style="flex:1">Save Changes</button>
                    <a href="/profile.php" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.profile-pic-edit {
    display: flex;
    flex-direction: column;
    gap: .75rem;
}
.profile-pic-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.profile-pic-current {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--clr-border-light);
}
.profile-pic-remove {
    cursor: pointer;
}
</style>

<script>
function toggleRemovePic() {
    const cb = document.getElementById('remove-pic-cb');
    cb.checked = !cb.checked;
    const preview = document.querySelector('.profile-pic-preview');
    if (cb.checked) {
        preview.style.opacity = '0.4';
    } else {
        preview.style.opacity = '1';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
