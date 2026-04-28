<?php
/**
 * Event Detail Page — Public
 */
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? 0);

if (!$eventId) { setFlash('danger', 'Event not found.'); redirect('/'); }

$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name, u.name AS creator_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    LEFT JOIN users u ON e.created_by = u.user_id
    WHERE e.event_id = ? AND e.is_published = TRUE
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) { setFlash('danger', 'Event not found or not published.'); redirect('/'); }

// Check if current user is already registered
$isRegistered = false;
$registration = null;
if (isLoggedIn()) {
    $regStmt = $pdo->prepare("SELECT * FROM registrations WHERE user_id = ? AND event_id = ?");
    $regStmt->execute([$_SESSION['user_id'], $eventId]);
    $registration = $regStmt->fetch();
    $isRegistered = (bool)$registration;
}

$slotsLeft = max(0, $event['slots_left'] ?? 0);
$deadlinePassed = $event['application_deadline'] && strtotime($event['application_deadline']) < time();

$pageTitle = $event['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:800px">
        <?= renderFlash() ?>

        <a href="/" class="btn btn-secondary btn-sm mb-3">← Back to Events</a>

        <div class="card fade-in" style="padding:2rem">
            <div class="flex-between flex-wrap mb-3">
                <span class="badge badge-accent"><?= sanitize($event['category_name']) ?></span>
                <?php if ($event['is_volunteer_required']): ?>
                    <span class="badge badge-warning">Volunteers Needed</span>
                <?php endif; ?>
            </div>

            <h1 style="margin-bottom:.5rem"><?= sanitize($event['title']) ?></h1>
            <p class="text-secondary mb-3">Organized by <strong><?= sanitize($event['organizer'] ?? $event['creator_name'] ?? 'Unknown') ?></strong></p>

            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem">
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
                <div class="stat-card">
                    <div class="stat-icon primary">💰</div>
                    <div>
                        <div class="stat-label">Registration Fee</div>
                        <div style="font-weight:600"><?= ($event['registration_fee'] > 0) ? '₹' . number_format($event['registration_fee'], 2) : 'Free' ?></div>
                    </div>
                </div>
            </div>

            <?php if ($event['application_deadline']): ?>
                <div class="alert alert-info mb-3">
                    ⏰ Application deadline: <strong><?= date('M j, Y \a\t g:i A', strtotime($event['application_deadline'])) ?></strong>
                    <?= $deadlinePassed ? '<span class="badge badge-danger" style="margin-left:.5rem">Closed</span>' : '' ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <h3 class="section-title">About This Event</h3>
                <p style="white-space:pre-line;color:var(--clr-text-secondary);line-height:1.8"><?= sanitize($event['description'] ?? 'No description provided.') ?></p>
            </div>

            <!-- Registration Actions -->
            <div style="padding-top:1.5rem;border-top:1px solid var(--clr-border-light)">
                <?php if (!isLoggedIn()): ?>
                    <a href="/auth/login.php" class="btn btn-primary btn-lg">Log in to Register</a>

                <?php elseif ($isRegistered): ?>
                    <div class="alert alert-success">
                        ✅ You're registered as a <strong><?= sanitize($registration['type']) ?></strong>
                        <?php if ($registration['type'] === 'Volunteer'): ?>
                            — Status: <span class="badge badge-<?= match($registration['vol_approval_status']) {
                                'Approved' => 'success', 'Rejected' => 'danger', default => 'warning'
                            } ?>"><?= $registration['vol_approval_status'] ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="/student/ticket.php?reg_id=<?= $registration['reg_id'] ?>" class="btn btn-primary">View Ticket</a>

                <?php elseif ($deadlinePassed): ?>
                    <div class="alert alert-warning">Registration deadline has passed.</div>

                <?php elseif ($slotsLeft <= 0 && $event['total_slots']): ?>
                    <div class="alert alert-danger">This event is full.</div>

                <?php elseif ($_SESSION['user_role'] === 'Student'): ?>
                    <div class="flex gap-2 flex-wrap">
                        <a href="/student/register_preview.php?event_id=<?= $event['event_id'] ?>&type=Participant" class="btn btn-primary btn-lg">
                            Register as Participant
                        </a>
                        <?php if ($event['is_volunteer_required']): ?>
                            <a href="/student/register_preview.php?event_id=<?= $event['event_id'] ?>&type=Volunteer" class="btn btn-outline btn-lg">
                                Apply as Volunteer
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php
                // Show WhatsApp link only to registered students, matching their type
                $showParticipantWA = $isRegistered && $registration && $registration['type'] === 'Participant' && $event['participant_whatsapp_link'];
                $showVolunteerWA   = $isRegistered && $registration && $registration['type'] === 'Volunteer' && $event['volunteer_whatsapp_link'];
            ?>
            <?php if ($showParticipantWA || $showVolunteerWA): ?>
                <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--clr-border-light)">
                    <h4 class="mb-2">💬 WhatsApp Group</h4>
                    <div class="flex gap-2 flex-wrap">
                        <?php if ($showParticipantWA): ?>
                            <a href="<?= sanitize($event['participant_whatsapp_link']) ?>" target="_blank" class="btn btn-success btn-sm">📱 Join Participant Group</a>
                        <?php endif; ?>
                        <?php if ($showVolunteerWA): ?>
                            <a href="<?= sanitize($event['volunteer_whatsapp_link']) ?>" target="_blank" class="btn btn-success btn-sm">📱 Join Volunteer Group</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
