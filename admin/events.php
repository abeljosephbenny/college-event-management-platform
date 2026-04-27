<?php
/**
 * Admin — Manage Event Submissions
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Administrator');

$pdo = getDBConnection();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evtId  = intval($_POST['event_id'] ?? 0);
    $action = $_POST['evt_action'] ?? '';

    if ($evtId && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE events SET is_published = TRUE WHERE event_id = ?")->execute([$evtId]);
            setFlash('success', 'Event approved and published.');
        } else {
            $pdo->prepare("DELETE FROM events WHERE event_id = ? AND is_published = FALSE")->execute([$evtId]);
            setFlash('success', 'Event rejected and removed.');
        }
    }
    redirect('/admin/events.php');
}

// Fetch events — pending first
$events = $pdo->query("
    SELECT e.*, c.name AS category_name, u.name AS creator_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    LEFT JOIN users u ON e.created_by = u.user_id
    ORDER BY e.is_published ASC, e.created_at DESC
")->fetchAll();

$pageTitle = 'Manage Events';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm mb-3">← Back to Dashboard</a>

        <div class="page-header">
            <h1>Event Submissions</h1>
        </div>

        <?php if (empty($events)): ?>
            <div class="card"><div class="empty-state"><h3>No event submissions</h3></div></div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Event</th><th>Organizer</th><th>Category</th><th>Date</th><th>Doc</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                        <tr>
                            <td><strong><?= sanitize($e['title']) ?></strong></td>
                            <td><?= sanitize($e['creator_name'] ?? $e['organizer'] ?? 'Unknown') ?></td>
                            <td><span class="badge badge-accent"><?= sanitize($e['category_name']) ?></span></td>
                            <td><?= $e['event_date'] ? formatDate($e['event_date']) : 'TBA' ?></td>
                            <td>
                                <?php if ($e['approval_doc_path']): ?>
                                    <a href="/<?= sanitize($e['approval_doc_path']) ?>" target="_blank" class="btn btn-secondary btn-sm">View Doc</a>
                                <?php else: ?>
                                    <span class="text-muted text-sm">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $e['is_published'] ? 'success' : 'warning' ?>">
                                    <?= $e['is_published'] ? 'Published' : 'Pending' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!$e['is_published']): ?>
                                    <form method="POST" style="display:inline-flex;gap:.35rem">
                                        <input type="hidden" name="event_id" value="<?= $e['event_id'] ?>">
                                        <button name="evt_action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                        <button name="evt_action" value="reject" class="btn btn-danger btn-sm" data-confirm="Reject and remove this event?">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted text-sm">Live</span>
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
