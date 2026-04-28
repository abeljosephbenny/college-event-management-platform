<?php
/**
 * Student Dashboard
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get student's registrations with event data
$stmt = $pdo->prepare("
    SELECT r.*, e.title, e.event_date, e.start_time, e.venue, e.is_volunteer_required,
           c.name AS category_name
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN categories c ON e.category_id = c.category_id
    WHERE r.user_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$userId]);
$registrations = $stmt->fetchAll();

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <div class="dashboard-header">
            <h1>👋 Hey, <?= sanitize($_SESSION['user_name']) ?>!</h1>
            <p class="text-secondary">Here are your event registrations</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-4 mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">🎫</div>
                <div>
                    <div class="stat-value"><?= count($registrations) ?></div>
                    <div class="stat-label">Total Registrations</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">✅</div>
                <div>
                    <div class="stat-value">
                        <?= count(array_filter($registrations, fn($r) => $r['type'] === 'Participant')) ?>
                    </div>
                    <div class="stat-label">As Participant</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">🤝</div>
                <div>
                    <div class="stat-value">
                        <?= count(array_filter($registrations, fn($r) => $r['type'] === 'Volunteer')) ?>
                    </div>
                    <div class="stat-label">As Volunteer</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon accent">📋</div>
                <div>
                    <div class="stat-value">
                        <?= count(array_filter($registrations, fn($r) => $r['attendance_marked'])) ?>
                    </div>
                    <div class="stat-label">Attended</div>
                </div>
            </div>
        </div>

        <div class="page-header">
            <h2 class="section-title">My Registrations</h2>
            <a href="/" class="btn btn-primary btn-sm">Browse Events</a>
        </div>

        <?php if (empty($registrations)): ?>
            <div class="empty-state card">
                <div class="empty-icon">📅</div>
                <h3>No registrations yet</h3>
                <p>Browse campus events and register to see them here.</p>
                <a href="/" class="btn btn-primary mt-2">Explore Events</a>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($registrations as $reg): ?>
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3><?= sanitize($reg['title']) ?></h3>
                                <span class="badge badge-accent"><?= sanitize($reg['category_name']) ?></span>
                            </div>
                            <span class="badge badge-<?= $reg['type'] === 'Volunteer' ? 'warning' : 'primary' ?>">
                                <?= $reg['type'] ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="event-meta">
                                <span>📅 <?= $reg['event_date'] ? formatDate($reg['event_date']) : 'TBA' ?></span>
                                <span>🕐 <?= $reg['start_time'] ? formatTime($reg['start_time']) : '' ?></span>
                                <span>📍 <?= sanitize($reg['venue'] ?? 'TBA') ?></span>
                            </div>
                            <?php if ($reg['type'] === 'Volunteer'): ?>
                                <div class="mt-1">
                                    Volunteer Status:
                                    <span class="badge badge-<?= match ($reg['vol_approval_status']) {
                                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning'
                                    } ?>">
                                        <?= $reg['vol_approval_status'] ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if ($reg['attendance_marked']): ?>
                                <div class="mt-1"><span class="badge badge-success">✅ Attended</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="/event_detail.php?id=<?= $reg['event_id'] ?>" class="btn btn-secondary btn-sm">View
                                Event</a>
                            <a href="/student/ticket.php?reg_id=<?= $reg['reg_id'] ?>" class="btn btn-primary btn-sm">View
                                Ticket</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>