<?php
/**
 * Organizer Dashboard
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get organizer's events
$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name,
           (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND type='Participant') AS participant_count,
           (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND type='Volunteer') AS volunteer_count
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.created_by = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$userId]);
$events = $stmt->fetchAll();

$totalParticipants = array_sum(array_column($events, 'participant_count'));
$totalVolunteers = array_sum(array_column($events, 'volunteer_count'));
$publishedCount = count(array_filter($events, fn($e) => $e['is_published']));
$pendingCount = count(array_filter($events, fn($e) => !$e['is_published']));

$pageTitle = 'Organizer Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <div class="page-header">
            <div>
                <h1>Organizer Dashboard</h1>
                <p class="text-secondary">Manage your events and registrations</p>
            </div>
            <a href="/organizer/create_event.php" class="btn btn-primary">+ Create Event</a>
        </div>

        <div class="grid grid-4 mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">📅</div>
                <div><div class="stat-value"><?= count($events) ?></div><div class="stat-label">Total Events</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">✅</div>
                <div><div class="stat-value"><?= $publishedCount ?></div><div class="stat-label">Published</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">👥</div>
                <div><div class="stat-value"><?= $totalParticipants ?></div><div class="stat-label">Participants</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon accent">🤝</div>
                <div><div class="stat-value"><?= $totalVolunteers ?></div><div class="stat-label">Volunteers</div></div>
            </div>
        </div>

        <h2 class="section-title">Your Events</h2>

        <?php if (empty($events)): ?>
            <div class="empty-state card">
                <div class="empty-icon">📝</div>
                <h3>No events created yet</h3>
                <p>Start by creating your first event.</p>
                <a href="/organizer/create_event.php" class="btn btn-primary mt-2">Create Event</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Registrations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td><strong><?= sanitize($event['title']) ?></strong></td>
                            <td><?= $event['event_date'] ? formatDate($event['event_date']) : 'TBA' ?></td>
                            <td><span class="badge badge-accent"><?= sanitize($event['category_name']) ?></span></td>
                            <td>
                                <?php if ($event['is_published']): ?>
                                    <span class="badge badge-success">Published</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending Approval</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $event['participant_count'] ?> participants
                                <?php if ($event['is_volunteer_required']): ?>
                                    <br><span class="text-sm text-muted"><?= $event['volunteer_count'] ?> volunteers</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-1 flex-wrap">
                                    <a href="/organizer/manage_event.php?id=<?= $event['event_id'] ?>" class="btn btn-secondary btn-sm">Manage</a>
                                    <a href="/organizer/attendance.php?id=<?= $event['event_id'] ?>" class="btn btn-outline btn-sm">Attendance</a>
                                </div>
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
