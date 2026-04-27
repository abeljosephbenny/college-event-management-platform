<?php
/**
 * Volunteer Management — Approve/Reject volunteers
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

// Verify event ownership
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND created_by = ?");
$stmt->execute([$eventId, $userId]);
$event = $stmt->fetch();
if (!$event) { setFlash('danger', 'Event not found.'); redirect('/organizer/dashboard.php'); }

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regId  = intval($_POST['reg_id'] ?? 0);
    $action = $_POST['vol_action'] ?? '';

    if ($regId && in_array($action, ['Approved', 'Rejected'])) {
        $stmt = $pdo->prepare("
            UPDATE registrations SET vol_approval_status = ?
            WHERE reg_id = ? AND event_id = ? AND type = 'Volunteer'
        ");
        $stmt->execute([$action, $regId, $eventId]);
        setFlash('success', "Volunteer $action successfully.");
    }
    redirect("/organizer/volunteers.php?id=$eventId");
}

// Get volunteers
$vStmt = $pdo->prepare("
    SELECT r.*, u.name, u.email, u.department, u.phone, u.admission_number
    FROM registrations r JOIN users u ON r.user_id = u.user_id
    WHERE r.event_id = ? AND r.type = 'Volunteer'
    ORDER BY FIELD(r.vol_approval_status, 'Pending', 'Approved', 'Rejected'), r.registered_at
");
$vStmt->execute([$eventId]);
$volunteers = $vStmt->fetchAll();

$pageTitle = 'Manage Volunteers';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <a href="/organizer/manage_event.php?id=<?= $eventId ?>" class="btn btn-secondary btn-sm mb-3">← Back to Event</a>

        <div class="page-header">
            <div>
                <h1>Volunteer Management</h1>
                <p class="text-secondary"><?= sanitize($event['title']) ?></p>
            </div>
        </div>

        <?php if (empty($volunteers)): ?>
            <div class="card"><div class="empty-state"><div class="empty-icon">🤝</div><h3>No volunteer applications</h3><p>No students have applied as volunteers yet.</p></div></div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($volunteers as $v): ?>
                        <tr>
                            <td><strong><?= sanitize($v['name']) ?></strong></td>
                            <td><?= sanitize($v['email']) ?></td>
                            <td><?= sanitize($v['department'] ?? '-') ?></td>
                            <td><?= sanitize($v['phone'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= match($v['vol_approval_status']) {
                                    'Approved' => 'success', 'Rejected' => 'danger', default => 'warning'
                                } ?>"><?= $v['vol_approval_status'] ?></span>
                            </td>
                            <td>
                                <?php if ($v['vol_approval_status'] === 'Pending'): ?>
                                    <form method="POST" style="display:inline-flex;gap:.35rem">
                                        <input type="hidden" name="reg_id" value="<?= $v['reg_id'] ?>">
                                        <button name="vol_action" value="Approved" class="btn btn-success btn-sm">Approve</button>
                                        <button name="vol_action" value="Rejected" class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted text-sm">—</span>
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
