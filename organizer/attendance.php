<?php
/**
 * Attendance Marking — Organizer
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND created_by = ?");
$stmt->execute([$eventId, $userId]);
$event = $stmt->fetch();
if (!$event) { setFlash('danger', 'Event not found.'); redirect('/organizer/dashboard.php'); }

// Handle attendance toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regId = intval($_POST['reg_id'] ?? 0);
    $marked = intval($_POST['mark'] ?? 0);

    if ($regId) {
        $stmt = $pdo->prepare("UPDATE registrations SET attendance_marked = ? WHERE reg_id = ? AND event_id = ?");
        $stmt->execute([$marked, $regId, $eventId]);
    }

    // Handle bulk marking
    if (isset($_POST['bulk_mark'])) {
        $regIds = $_POST['attendance'] ?? [];
        // First, unmark all
        $pdo->prepare("UPDATE registrations SET attendance_marked = FALSE WHERE event_id = ? AND type = 'Participant'")->execute([$eventId]);
        // Then mark selected
        if (!empty($regIds)) {
            $placeholders = implode(',', array_fill(0, count($regIds), '?'));
            $pdo->prepare("UPDATE registrations SET attendance_marked = TRUE WHERE reg_id IN ($placeholders) AND event_id = ?")
                ->execute([...array_map('intval', $regIds), $eventId]);
        }
        setFlash('success', 'Attendance saved.');
    }

    redirect("/organizer/attendance.php?id=$eventId");
}

// Get participants
$pStmt = $pdo->prepare("
    SELECT r.*, u.name, u.email, u.admission_number, u.department
    FROM registrations r JOIN users u ON r.user_id = u.user_id
    WHERE r.event_id = ? AND r.type = 'Participant'
    ORDER BY u.name
");
$pStmt->execute([$eventId]);
$participants = $pStmt->fetchAll();

$pageTitle = 'Mark Attendance';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <a href="/organizer/manage_event.php?id=<?= $eventId ?>" class="btn btn-secondary btn-sm mb-3">← Back to Event</a>

        <div class="page-header">
            <div>
                <h1>Mark Attendance</h1>
                <p class="text-secondary"><?= sanitize($event['title']) ?> — <?= $event['event_date'] ? formatDate($event['event_date']) : 'TBA' ?></p>
            </div>
            <a href="/organizer/export_csv.php?id=<?= $eventId ?>" class="btn btn-success btn-sm">📥 Export CSV</a>
        </div>

        <?php if (empty($participants)): ?>
            <div class="card"><div class="empty-state"><h3>No participants</h3><p>No one has registered yet.</p></div></div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="bulk_mark" value="1">
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all" onclick="document.querySelectorAll('.att-check').forEach(c=>c.checked=this.checked)"></th>
                                <th>Name</th>
                                <th>Admission No</th>
                                <th>Department</th>
                                <th>Reg Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $p): ?>
                            <tr>
                                <td><input type="checkbox" class="att-check" name="attendance[]" value="<?= $p['reg_id'] ?>" <?= $p['attendance_marked'] ? 'checked' : '' ?>></td>
                                <td><strong><?= sanitize($p['name']) ?></strong></td>
                                <td><?= sanitize($p['admission_number'] ?? '-') ?></td>
                                <td><?= sanitize($p['department'] ?? '-') ?></td>
                                <td><code><?= sanitize($p['registration_code'] ?? '-') ?></code></td>
                                <td><span class="badge badge-<?= $p['attendance_marked'] ? 'success' : 'neutral' ?>"><?= $p['attendance_marked'] ? 'Present' : 'Absent' ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3" style="text-align:right">
                    <button type="submit" class="btn btn-primary btn-lg">Save Attendance</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
