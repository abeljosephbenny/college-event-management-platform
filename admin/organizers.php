<?php
/**
 * Admin — Manage Organizer Registrations
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Administrator');

$pdo = getDBConnection();

// Handle approve/reject/deactivate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgUserId = intval($_POST['user_id'] ?? 0);
    $action    = $_POST['org_action'] ?? '';

    if ($orgUserId && in_array($action, ['approve', 'reject', 'deactivate'])) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE users SET is_verified = TRUE WHERE user_id = ? AND role = 'Organizer'")
                ->execute([$orgUserId]);
            setFlash('success', 'Organizer approved.');
        } elseif ($action === 'deactivate') {
            $pdo->prepare("UPDATE users SET is_verified = FALSE WHERE user_id = ? AND role = 'Organizer'")
                ->execute([$orgUserId]);
            setFlash('success', 'Organizer has been deactivated.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'Organizer' AND is_verified = FALSE")
                ->execute([$orgUserId]);
            setFlash('success', 'Organizer registration rejected and removed.');
        }
    }
    redirect('/admin/organizers.php');
}

// Fetch organizers — pending first
$organizers = $pdo->query("
    SELECT * FROM users WHERE role = 'Organizer'
    ORDER BY is_verified ASC, created_at DESC
")->fetchAll();

$pageTitle = 'Manage Organizers';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm mb-3">← Back to Dashboard</a>

        <div class="page-header">
            <h1>Organizer Management</h1>
        </div>

        <?php if (empty($organizers)): ?>
            <div class="card"><div class="empty-state"><h3>No organizer registrations</h3></div></div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($organizers as $org): ?>
                        <tr class="clickable-row" onclick="window.location='/admin/user_profile.php?id=<?= $org['user_id'] ?>'">
                            <td><strong><?= sanitize($org['name']) ?></strong></td>
                            <td><?= sanitize($org['email']) ?></td>
                            <td><?= sanitize($org['phone'] ?? '-') ?></td>
                            <td><?= formatDate($org['created_at']) ?></td>
                            <td>
                                <span class="badge badge-<?= $org['is_verified'] ? 'success' : 'warning' ?>">
                                    <?= $org['is_verified'] ? 'Active' : 'Pending' ?>
                                </span>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <?php if (!$org['is_verified']): ?>
                                    <form method="POST" style="display:inline-flex;gap:.35rem">
                                        <input type="hidden" name="user_id" value="<?= $org['user_id'] ?>">
                                        <button name="org_action" value="approve" class="btn btn-success btn-sm" data-confirm="Approve this organizer?">Approve</button>
                                        <button name="org_action" value="reject" class="btn btn-danger btn-sm" data-confirm="Reject and permanently remove this organizer? This cannot be undone.">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="user_id" value="<?= $org['user_id'] ?>">
                                        <button name="org_action" value="deactivate" class="btn btn-warning btn-sm" data-confirm="Deactivate this organizer? They will no longer be able to log in.">Deactivate</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
