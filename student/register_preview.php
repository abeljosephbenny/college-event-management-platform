<?php
/**
 * Step 1 — Registration Preview
 * Shows event summary + student details before proceeding to payment
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Student');

$pdo = getDBConnection();
$userId  = $_SESSION['user_id'];
$eventId = intval($_GET['event_id'] ?? 0);
$type    = sanitize($_GET['type'] ?? 'Participant');

if (!$eventId || !in_array($type, ['Participant', 'Volunteer'])) {
    setFlash('danger', 'Invalid request.');
    redirect('/');
}

// Fetch event
$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name, u.name AS creator_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    LEFT JOIN users u ON e.created_by = u.user_id
    WHERE e.event_id = ? AND e.is_published = TRUE
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) { setFlash('danger', 'Event not found.'); redirect('/'); }

// Fetch student details
$studentStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$studentStmt->execute([$userId]);
$student = $studentStmt->fetch();

// Check deadline
$deadlinePassed = $event['application_deadline'] && strtotime($event['application_deadline']) < time();
if ($deadlinePassed) {
    setFlash('warning', 'Registration deadline has passed.');
    redirect("/event_detail.php?id=$eventId");
}

// Check slots
$slotsLeft = max(0, $event['slots_left'] ?? 0);
if ($type === 'Participant' && $event['total_slots'] && $slotsLeft <= 0) {
    setFlash('danger', 'Sorry, this event is full.');
    redirect("/event_detail.php?id=$eventId");
}

// Check volunteer requirement
if ($type === 'Volunteer' && !$event['is_volunteer_required']) {
    setFlash('danger', 'This event does not require volunteers.');
    redirect("/event_detail.php?id=$eventId");
}

// Check if already registered (same type)
$regStmt = $pdo->prepare("SELECT * FROM registrations WHERE user_id = ? AND event_id = ?");
$regStmt->execute([$userId, $eventId]);
$existingReg = $regStmt->fetch();

if ($existingReg) {
    setFlash('warning', 'You are already registered for this event.');
    redirect("/event_detail.php?id=$eventId");
}

// Check dual-registration: already registered as the OTHER type
$otherType = ($type === 'Participant') ? 'Volunteer' : 'Participant';
$dualStmt = $pdo->prepare("SELECT reg_id FROM registrations WHERE user_id = ? AND event_id = ? AND type = ?");
$dualStmt->execute([$userId, $eventId, $otherType]);
$dualReg = $dualStmt->fetch();

$pageTitle = 'Confirm Registration';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:700px">
        <?= renderFlash() ?>

        <!-- Step indicator -->
        <div class="reg-steps mb-3">
            <div class="reg-step active">
                <span class="reg-step-num">1</span>
                <span class="reg-step-label">Preview</span>
            </div>
            <div class="reg-step-line"></div>
            <div class="reg-step">
                <span class="reg-step-num">2</span>
                <span class="reg-step-label">Payment</span>
            </div>
            <div class="reg-step-line"></div>
            <div class="reg-step">
                <span class="reg-step-num">3</span>
                <span class="reg-step-label">Confirmed</span>
            </div>
        </div>

        <?php if ($dualReg): ?>
            <div class="alert alert-danger mb-3">
                ⚠️ You are already registered as a <strong><?= $otherType ?></strong> for this event. You cannot register as both a Participant and a Volunteer for the same event.
            </div>
            <a href="/event_detail.php?id=<?= $eventId ?>" class="btn btn-secondary">← Back to Event</a>
        <?php else: ?>
            <div class="card fade-in" style="padding:2rem">
                <h1 style="margin-bottom:.25rem">Confirm Registration</h1>
                <p class="text-secondary mb-3">Review the details below before proceeding</p>

                <!-- Event Summary -->
                <div class="reg-section">
                    <h3 class="reg-section-title">📅 Event Details</h3>
                    <div class="reg-detail-grid">
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Event</span>
                            <span class="reg-detail-value"><?= sanitize($event['title']) ?></span>
                        </div>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Category</span>
                            <span class="reg-detail-value"><span class="badge badge-accent"><?= sanitize($event['category_name']) ?></span></span>
                        </div>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Date</span>
                            <span class="reg-detail-value"><?= $event['event_date'] ? formatDate($event['event_date']) : 'TBA' ?></span>
                        </div>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Time</span>
                            <span class="reg-detail-value">
                                <?= $event['start_time'] ? formatTime($event['start_time']) : '' ?>
                                <?= $event['end_time'] ? ' – ' . formatTime($event['end_time']) : '' ?>
                                <?php if (!$event['start_time']): ?>TBA<?php endif; ?>
                            </span>
                        </div>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Venue</span>
                            <span class="reg-detail-value"><?= sanitize($event['venue'] ?? 'TBA') ?><?= $event['place'] ? ', ' . sanitize($event['place']) : '' ?></span>
                        </div>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Organizer</span>
                            <span class="reg-detail-value"><?= sanitize($event['organizer'] ?? $event['creator_name'] ?? 'Unknown') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Student Info -->
                <div class="reg-section">
                    <h3 class="reg-section-title">👤 Your Details</h3>
                    <div class="reg-detail-grid">
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Name</span>
                            <span class="reg-detail-value"><?= sanitize($student['name']) ?></span>
                        </div>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Email</span>
                            <span class="reg-detail-value"><?= sanitize($student['email']) ?></span>
                        </div>
                        <?php if ($student['admission_number']): ?>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Admission No.</span>
                            <span class="reg-detail-value"><?= sanitize($student['admission_number']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($student['department']): ?>
                        <div class="reg-detail-item">
                            <span class="reg-detail-label">Department</span>
                            <span class="reg-detail-value"><?= sanitize($student['department']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Registration Type -->
                <div class="reg-section">
                    <h3 class="reg-section-title">🎫 Registration</h3>
                    <div class="reg-type-badge">
                        <span class="badge badge-<?= $type === 'Volunteer' ? 'warning' : 'primary' ?>" style="font-size:1rem;padding:.5rem 1.25rem">
                            Registering as <?= $type ?>
                        </span>
                        <?php if ($type === 'Volunteer'): ?>
                            <p class="text-muted text-sm mt-1">Your volunteer application will be reviewed by the organizer.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 mt-3" style="padding-top:1.5rem;border-top:1px solid var(--clr-border-light)">
                    <a href="/student/register_payment.php?event_id=<?= $eventId ?>&type=<?= $type ?>" class="btn btn-primary btn-lg" style="flex:1">Proceed to Payment →</a>
                    <a href="/event_detail.php?id=<?= $eventId ?>" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ── Step Indicator ──────────────────────────────────────── */
.reg-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
}
.reg-step {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem 1rem;
}
.reg-step-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .85rem;
    background: var(--clr-border-light);
    color: var(--clr-text-muted);
    transition: all var(--transition);
}
.reg-step.active .reg-step-num {
    background: var(--clr-primary);
    color: #fff;
    box-shadow: 0 2px 8px rgba(79,70,229,.3);
}
.reg-step.done .reg-step-num {
    background: var(--clr-success);
    color: #fff;
}
.reg-step-label {
    font-size: .85rem;
    font-weight: 600;
    color: var(--clr-text-muted);
}
.reg-step.active .reg-step-label {
    color: var(--clr-primary);
}
.reg-step.done .reg-step-label {
    color: var(--clr-success);
}
.reg-step-line {
    flex: 1;
    height: 2px;
    background: var(--clr-border-light);
    max-width: 60px;
}

/* ── Preview Sections ────────────────────────────────────── */
.reg-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--clr-border-light);
}
.reg-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 0;
}
.reg-section-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: .75rem;
    color: var(--clr-text);
}
.reg-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .5rem;
}
.reg-detail-item {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    padding: .5rem .75rem;
    background: var(--clr-surface-alt);
    border-radius: var(--radius);
}
.reg-detail-label {
    font-size: .7rem;
    color: var(--clr-text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.reg-detail-value {
    font-weight: 600;
    font-size: .9rem;
    color: var(--clr-text);
}
.reg-type-badge {
    text-align: center;
    padding: 1rem;
    background: var(--clr-surface-alt);
    border-radius: var(--radius);
}

@media(max-width:500px) {
    .reg-detail-grid {
        grid-template-columns: 1fr;
    }
    .reg-step-label {
        display: none;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
