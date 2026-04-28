<?php
/**
 * Admin — Event Detail View
 * Full read-only view of any event with admin actions (approve/unpublish/reject)
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Administrator');

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? 0);

if (!$eventId) { setFlash('danger', 'Event not found.'); redirect('/admin/events.php'); }

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['admin_action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE events SET is_published = TRUE WHERE event_id = ?")->execute([$eventId]);
        setFlash('success', 'Event approved and published.');
    } elseif ($action === 'unpublish') {
        $pdo->prepare("UPDATE events SET is_published = FALSE WHERE event_id = ?")->execute([$eventId]);
        setFlash('success', 'Event has been unpublished.');
    } elseif ($action === 'reject') {
        $pdo->prepare("DELETE FROM events WHERE event_id = ? AND is_published = FALSE")->execute([$eventId]);
        setFlash('success', 'Event rejected and removed.');
        redirect('/admin/events.php');
    }
    redirect('/admin/event_detail.php?id=' . $eventId);
}

// Fetch event
$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name, u.name AS creator_name, u.email AS creator_email,
           u.phone AS creator_phone, u.user_id AS creator_user_id
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    LEFT JOIN users u ON e.created_by = u.user_id
    WHERE e.event_id = ?
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) { setFlash('danger', 'Event not found.'); redirect('/admin/events.php'); }

// Registration counts
$pStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND type='Participant'");
$pStmt->execute([$eventId]);
$participantCount = $pStmt->fetchColumn();

$vStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND type='Volunteer'");
$vStmt->execute([$eventId]);
$volunteerCount = $vStmt->fetchColumn();

$slotsLeft = max(0, $event['slots_left'] ?? 0);
$deadlinePassed = $event['application_deadline'] && strtotime($event['application_deadline']) < time();

$pageTitle = 'Event: ' . $event['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:860px">
        <?= renderFlash() ?>

        <a href="/admin/events.php" class="btn btn-secondary btn-sm mb-3">← Back to Events</a>

        <div class="card fade-in" style="padding:2rem">
            <!-- Header -->
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
            </div>

            <h1 style="margin-bottom:.5rem"><?= sanitize($event['title']) ?></h1>

            <!-- Organizer info — clickable -->
            <div class="admin-organizer-card mb-3">
                <div class="flex gap-1" style="align-items:center">
                    <span class="text-secondary">Organized by</span>
                    <?php if ($event['creator_user_id']): ?>
                        <a href="/admin/user_profile.php?id=<?= $event['creator_user_id'] ?>" class="organizer-link">
                            <strong><?= sanitize($event['creator_name'] ?? 'Unknown') ?></strong>
                            <span class="organizer-link-arrow">→</span>
                        </a>
                    <?php else: ?>
                        <strong><?= sanitize($event['organizer'] ?? 'Unknown') ?></strong>
                    <?php endif; ?>
                </div>
                <?php if ($event['creator_phone']): ?>
                    <span class="text-sm text-muted">📞 <?= sanitize($event['creator_phone']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem">
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
                <div class="stat-card">
                    <div class="stat-icon primary">💰</div>
                    <div>
                        <div class="stat-label">Registration Fee</div>
                        <div style="font-weight:600"><?= ($event['registration_fee'] > 0) ? '₹' . number_format($event['registration_fee'], 2) : 'Free' ?></div>
                    </div>
                </div>
            </div>

            <!-- Registration Stats -->
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem">
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
                <?php if ($event['total_slots']): ?>
                <div class="stat-card">
                    <div class="stat-icon warning">🎟️</div>
                    <div>
                        <div class="stat-value"><?= $slotsLeft ?> / <?= $event['total_slots'] ?></div>
                        <div class="stat-label">Slots Available</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Deadline -->
            <?php if ($event['application_deadline']): ?>
                <div class="alert alert-info mb-3">
                    ⏰ Application deadline: <strong><?= date('M j, Y \a\t g:i A', strtotime($event['application_deadline'])) ?></strong>
                    <?= $deadlinePassed ? '<span class="badge badge-danger" style="margin-left:.5rem">Closed</span>' : '' ?>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <div class="mb-3">
                <h3 class="section-title">Description</h3>
                <p style="white-space:pre-line;color:var(--clr-text-secondary);line-height:1.8"><?= sanitize($event['description'] ?? 'No description provided.') ?></p>
            </div>

            <!-- Approval Document -->
            <?php if ($event['approval_doc_path']): ?>
            <div class="mb-3" style="padding-top:1rem;border-top:1px solid var(--clr-border-light)">
                <h3 class="section-title">Approval Document</h3>
                <a href="/<?= sanitize($event['approval_doc_path']) ?>" target="_blank" class="btn btn-secondary btn-sm">📄 View Document</a>
            </div>
            <?php endif; ?>

            <!-- WhatsApp Groups -->
            <?php if ($event['participant_whatsapp_link'] || $event['volunteer_whatsapp_link']): ?>
                <div style="padding-top:1rem;border-top:1px solid var(--clr-border-light)" class="mb-3">
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

            <!-- Event Meta -->
            <div style="padding-top:1rem;border-top:1px solid var(--clr-border-light)" class="mb-3">
                <div class="flex-between text-sm text-muted">
                    <span>Created: <?= date('M j, Y \a\t g:i A', strtotime($event['created_at'])) ?></span>
                    <span>Event ID: #<?= $event['event_id'] ?></span>
                </div>
            </div>

            <!-- Admin Actions -->
            <div style="padding-top:1.5rem;border-top:2px solid var(--clr-border)" class="flex gap-2 flex-wrap">
                <?php if (!$event['is_published']): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="admin_action" value="approve">
                        <button type="submit" class="btn btn-success" data-confirm="Approve and publish this event?">✅ Approve & Publish</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="admin_action" value="reject">
                        <button type="submit" class="btn btn-danger" data-confirm="Reject and permanently delete this event? This cannot be undone.">🗑️ Reject & Remove</button>
                    </form>
                <?php else: ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="admin_action" value="unpublish">
                        <button type="submit" class="btn btn-warning" data-confirm="Unpublish this event? It will no longer be visible to students.">⏸️ Unpublish Event</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.admin-organizer-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
    padding: .75rem 1rem;
    background: var(--clr-surface-alt);
    border-radius: var(--radius);
    border: 1px solid var(--clr-border-light);
}
.organizer-link {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: var(--clr-primary);
    text-decoration: none;
    padding: .2rem .5rem;
    border-radius: var(--radius-sm);
    transition: all var(--transition);
}
.organizer-link:hover {
    background: rgba(79, 70, 229, .08);
    color: var(--clr-primary-dark);
}
.organizer-link-arrow {
    font-size: .85rem;
    transition: transform var(--transition);
}
.organizer-link:hover .organizer-link-arrow {
    transform: translateX(3px);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
