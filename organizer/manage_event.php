<?php
/**
 * Manage Event — Organizer view of a single event
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

// Verify event belongs to this organizer
$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name
    FROM events e JOIN categories c ON e.category_id = c.category_id
    WHERE e.event_id = ? AND e.created_by = ?
");
$stmt->execute([$eventId, $userId]);
$event = $stmt->fetch();

if (!$event) { setFlash('danger', 'Event not found.'); redirect('/organizer/dashboard.php'); }

// Get participants
$pStmt = $pdo->prepare("
    SELECT r.*, u.name, u.email, u.admission_number, u.department, u.phone
    FROM registrations r JOIN users u ON r.user_id = u.user_id
    WHERE r.event_id = ? AND r.type = 'Participant'
    ORDER BY r.registered_at
");
$pStmt->execute([$eventId]);
$participants = $pStmt->fetchAll();

// Get volunteers
$vStmt = $pdo->prepare("
    SELECT r.*, u.name, u.email, u.admission_number, u.department, u.phone
    FROM registrations r JOIN users u ON r.user_id = u.user_id
    WHERE r.event_id = ? AND r.type = 'Volunteer'
    ORDER BY r.registered_at
");
$vStmt->execute([$eventId]);
$volunteers = $vStmt->fetchAll();

$pageTitle = 'Manage: ' . $event['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <a href="/organizer/dashboard.php" class="btn btn-secondary btn-sm mb-3">← Back to Dashboard</a>

        <div class="page-header">
            <div>
                <h1><?= sanitize($event['title']) ?></h1>
                <div class="flex gap-1 mt-1">
                    <span class="badge badge-accent"><?= sanitize($event['category_name']) ?></span>
                    <span class="badge badge-<?= $event['is_published'] ? 'success' : 'warning' ?>">
                        <?= $event['is_published'] ? 'Published' : 'Pending Approval' ?>
                    </span>
                </div>
            </div>
            <div class="flex gap-1">
                <?php if ($event['is_volunteer_required']): ?>
                    <a href="/organizer/volunteers.php?id=<?= $eventId ?>" class="btn btn-warning btn-sm">Manage Volunteers</a>
                <?php endif; ?>
                <a href="/organizer/attendance.php?id=<?= $eventId ?>" class="btn btn-primary btn-sm">Attendance</a>
                <a href="/organizer/export_csv.php?id=<?= $eventId ?>" class="btn btn-success btn-sm">Export CSV</a>
            </div>
        </div>

        <!-- Event Stats -->
        <div class="grid grid-3 mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">👥</div>
                <div><div class="stat-value"><?= count($participants) ?></div><div class="stat-label">Participants</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">🤝</div>
                <div><div class="stat-value"><?= count($volunteers) ?></div><div class="stat-label">Volunteers</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">🎟️</div>
                <div><div class="stat-value"><?= $event['slots_left'] ?? '∞' ?> / <?= $event['total_slots'] ?: '∞' ?></div><div class="stat-label">Slots Left</div></div>
            </div>
        </div>

        <!-- Participants Table -->
        <h2 class="section-title">Participants (<?= count($participants) ?>)</h2>
        <?php if (empty($participants)): ?>
            <div class="card mb-4"><div class="empty-state"><p class="text-muted">No participants yet.</p></div></div>
        <?php else: ?>
            <div class="table-wrapper mb-4">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Admission No</th><th>Department</th><th>Phone</th><th>Attendance</th></tr></thead>
                    <tbody>
                        <?php foreach ($participants as $p): ?>
                        <tr>
                            <td><strong><?= sanitize($p['name']) ?></strong></td>
                            <td><?= sanitize($p['email']) ?></td>
                            <td><?= sanitize($p['admission_number'] ?? '-') ?></td>
                            <td><?= sanitize($p['department'] ?? '-') ?></td>
                            <td><?= sanitize($p['phone'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $p['attendance_marked'] ? 'success' : 'neutral' ?>">
                                    <?= $p['attendance_marked'] ? 'Present' : 'Not marked' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Volunteers Table -->
        <?php if ($event['is_volunteer_required']): ?>
            <h2 class="section-title">Volunteers (<?= count($volunteers) ?>)</h2>
            <?php if (empty($volunteers)): ?>
                <div class="card"><div class="empty-state"><p class="text-muted">No volunteer applications yet.</p></div></div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($volunteers as $v): ?>
                            <tr>
                                <td><strong><?= sanitize($v['name']) ?></strong></td>
                                <td><?= sanitize($v['email']) ?></td>
                                <td><?= sanitize($v['department'] ?? '-') ?></td>
                                <td>
                                    <span class="badge badge-<?= match($v['vol_approval_status']) {
                                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning'
                                    } ?>"><?= $v['vol_approval_status'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
