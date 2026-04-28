<?php
/**
 * Event Detail — Organizer view of a single event's full details
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$eventId) { setFlash('danger', 'Event not found.'); redirect('/organizer/dashboard.php'); }

// Fetch event with category and creator info
$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name, u.name AS creator_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    LEFT JOIN users u ON e.created_by = u.user_id
    WHERE e.event_id = ?
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) { setFlash('danger', 'Event not found.'); redirect('/organizer/dashboard.php'); }

// Check ownership — only the creator can edit
$isOwner = ((int)$event['created_by'] === $userId);

// If organizer is not the owner, they shouldn't access this page at all
if (!$isOwner) { setFlash('danger', 'You do not have access to this event.'); redirect('/organizer/dashboard.php'); }

// Counts
$pStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND type='Participant'");
$pStmt->execute([$eventId]);
$participantCount = $pStmt->fetchColumn();

$vStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND type='Volunteer'");
$vStmt->execute([$eventId]);
$volunteerCount = $vStmt->fetchColumn();

$slotsLeft = max(0, $event['slots_left'] ?? 0);

$pageTitle = $event['title'] . ' — Details';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:800px">
        <?= renderFlash() ?>

        <a href="/organizer/dashboard.php" class="btn btn-secondary btn-sm mb-3">← Back to Dashboard</a>

        <div class="card fade-in" style="padding:2rem">
            <!-- Header row -->
            <div class="flex-between flex-wrap mb-3">
                <div class="flex gap-1 flex-wrap">
                    <span class="badge badge-accent"><?= sanitize($event['category_name']) ?></span>
                    <?php if ($event['is_published']): ?>
                        <span class="badge badge-success">Published</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending Approval</span>
                    <?php endif; ?>
                    <?php if ($event['is_volunteer_required']): ?>
                        <span class="badge badge-warning">Volunteers Needed</span>
                    <?php endif; ?>
                </div>
                <?php if ($isOwner): ?>
                    <a href="/organizer/edit_event.php?id=<?= $event['event_id'] ?>" class="btn btn-primary btn-sm">✏️ Edit Event</a>
                <?php endif; ?>
            </div>

            <h1 style="margin-bottom:.5rem"><?= sanitize($event['title']) ?></h1>
            <p class="text-secondary mb-3">Organized by <strong><?= sanitize($event['organizer'] ?? $event['creator_name'] ?? 'Unknown') ?></strong></p>

            <!-- Quick Stats -->
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem">
                <div class="stat-card">
                    <div class="stat-icon primary">📅</div>
                    <div>
                        <div class="stat-label">Date</div>
                        <div style="font-weight:600"><?= $event['event_date'] ? formatDate($event['event_date']) : 'TBA' ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon accent">🕐</div>
                    <div>
                        <div class="stat-label">Time</div>
                        <div style="font-weight:600">
                            <?= $event['start_time'] ? formatTime($event['start_time']) : '' ?>
                            <?= $event['end_time'] ? ' – ' . formatTime($event['end_time']) : '' ?>
                            <?php if (!$event['start_time'] && !$event['end_time']): ?>TBA<?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">📍</div>
                    <div>
                        <div class="stat-label">Venue</div>
                        <div style="font-weight:600"><?= sanitize($event['venue'] ?? 'TBA') ?></div>
                        <?php if ($event['place']): ?>
                            <div class="text-sm text-muted"><?= sanitize($event['place']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($event['total_slots']): ?>
                <div class="stat-card">
                    <div class="stat-icon warning">🎟️</div>
                    <div>
                        <div class="stat-label">Slots</div>
                        <div style="font-weight:600"><?= $slotsLeft ?> / <?= $event['total_slots'] ?> available</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Registration Stats -->
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem">
                <div class="stat-card">
                    <div class="stat-icon primary">👥</div>
                    <div>
                        <div class="stat-value"><?= $participantCount ?></div>
                        <div class="stat-label">Participants</div>
                    </div>
                </div>
                <?php if ($event['is_volunteer_required']): ?>
                <div class="stat-card">
                    <div class="stat-icon accent">🤝</div>
                    <div>
                        <div class="stat-value"><?= $volunteerCount ?></div>
                        <div class="stat-label">Volunteers</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Deadline -->
            <?php if ($event['application_deadline']): ?>
                <?php $deadlinePassed = strtotime($event['application_deadline']) < time(); ?>
                <div class="alert alert-info mb-3">
                    ⏰ Application deadline: <strong><?= date('M j, Y \a\t g:i A', strtotime($event['application_deadline'])) ?></strong>
                    <?= $deadlinePassed ? '<span class="badge badge-danger" style="margin-left:.5rem">Closed</span>' : '' ?>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <div class="mb-3">
                <h3 class="section-title">About This Event</h3>
                <p style="white-space:pre-line;color:var(--clr-text-secondary);line-height:1.8"><?= sanitize($event['description'] ?? 'No description provided.') ?></p>
            </div>

            <!-- Additional Details -->
            <div style="border-top:1px solid var(--clr-border-light);padding-top:1.5rem">
                <h3 class="section-title">Event Details</h3>
                <div class="detail-list">
                    <div class="detail-item">
                        <span class="detail-label-text">Created</span>
                        <span><?= date('M j, Y \a\t g:i A', strtotime($event['created_at'])) ?></span>
                    </div>
                    <?php if ($event['approval_doc_path']): ?>
                    <div class="detail-item">
                        <span class="detail-label-text">Approval Document</span>
                        <a href="/<?= sanitize($event['approval_doc_path']) ?>" target="_blank" class="btn btn-secondary btn-sm">📄 View Document</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- WhatsApp Groups -->
            <?php if ($event['participant_whatsapp_link'] || $event['volunteer_whatsapp_link']): ?>
                <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--clr-border-light)">
                    <h3 class="section-title">WhatsApp Groups</h3>
                    <div class="flex gap-2 flex-wrap">
                        <?php if ($event['participant_whatsapp_link']): ?>
                            <a href="<?= sanitize($event['participant_whatsapp_link']) ?>" target="_blank" class="btn btn-success btn-sm">💬 Participant Group</a>
                        <?php endif; ?>
                        <?php if ($event['volunteer_whatsapp_link']): ?>
                            <a href="<?= sanitize($event['volunteer_whatsapp_link']) ?>" target="_blank" class="btn btn-success btn-sm">💬 Volunteer Group</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--clr-border-light)" class="flex gap-2 flex-wrap">
                <a href="/organizer/manage_event.php?id=<?= $event['event_id'] ?>" class="btn btn-secondary">📋 Manage Registrations</a>
                <a href="/organizer/attendance.php?id=<?= $event['event_id'] ?>" class="btn btn-outline">✅ Attendance</a>
                <?php if ($event['is_volunteer_required']): ?>
                    <a href="/organizer/volunteers.php?id=<?= $event['event_id'] ?>" class="btn btn-warning btn-sm">🤝 Manage Volunteers</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.detail-list {
    display: flex;
    flex-direction: column;
    gap: .75rem;
}
.detail-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .6rem 0;
    border-bottom: 1px solid var(--clr-border-light);
    font-size: .9rem;
}
.detail-item:last-child {
    border-bottom: none;
}
.detail-label-text {
    color: var(--clr-text-muted);
    font-weight: 500;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
