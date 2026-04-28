<?php
/**
 * Step 2 — Dummy Payment Page
 * Shows order summary with Pay Now / Cancel actions
 * On POST (Pay Now confirmed), performs the actual registration
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Student');

$pdo = getDBConnection();
$userId  = $_SESSION['user_id'];
$eventId = intval($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$type    = sanitize($_GET['type'] ?? $_POST['type'] ?? 'Participant');

if (!$eventId || !in_array($type, ['Participant', 'Volunteer'])) {
    setFlash('danger', 'Invalid request.');
    redirect('/');
}

// Fetch event
$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.event_id = ? AND e.is_published = TRUE
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) { setFlash('danger', 'Event not found.'); redirect('/'); }

// ── Handle POST — Process the actual registration ────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-validate everything server-side
    $deadlinePassed = $event['application_deadline'] && strtotime($event['application_deadline']) < time();
    if ($deadlinePassed) {
        setFlash('warning', 'Registration deadline has passed.');
        redirect("/event_detail.php?id=$eventId");
    }

    // Already registered?
    $regStmt = $pdo->prepare("SELECT reg_id FROM registrations WHERE user_id = ? AND event_id = ?");
    $regStmt->execute([$userId, $eventId]);
    if ($regStmt->fetch()) {
        setFlash('warning', 'You are already registered for this event.');
        redirect("/event_detail.php?id=$eventId");
    }

    // Dual-registration check
    $otherType = ($type === 'Participant') ? 'Volunteer' : 'Participant';
    $dualStmt = $pdo->prepare("SELECT reg_id FROM registrations WHERE user_id = ? AND event_id = ? AND type = ?");
    $dualStmt->execute([$userId, $eventId, $otherType]);
    if ($dualStmt->fetch()) {
        setFlash('danger', "You are already registered as a $otherType. You cannot register as both.");
        redirect("/event_detail.php?id=$eventId");
    }

    // Slots check
    if ($type === 'Participant' && $event['total_slots'] && $event['slots_left'] <= 0) {
        setFlash('danger', 'Sorry, this event is full.');
        redirect("/event_detail.php?id=$eventId");
    }

    // Volunteer check
    if ($type === 'Volunteer' && !$event['is_volunteer_required']) {
        setFlash('danger', 'This event does not require volunteers.');
        redirect("/event_detail.php?id=$eventId");
    }

    try {
        $pdo->beginTransaction();

        $regCode = generateRegistrationCode();
        $volStatus = ($type === 'Volunteer') ? 'Pending' : 'Approved';

        $insertStmt = $pdo->prepare("
            INSERT INTO registrations (user_id, event_id, registration_code, type, vol_approval_status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$userId, $eventId, $regCode, $type, $volStatus]);
        $regId = $pdo->lastInsertId();

        // Decrement slots for participants
        if ($type === 'Participant' && $event['total_slots']) {
            $pdo->prepare("UPDATE events SET slots_left = slots_left - 1 WHERE event_id = ?")->execute([$eventId]);
        }

        $pdo->commit();

        // Redirect to success page
        redirect("/student/register_success.php?reg_id=$regId");

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Registration error: " . $e->getMessage());
        setFlash('danger', 'Registration failed. Please try again.');
        redirect("/event_detail.php?id=$eventId");
    }
}

// ── GET — Show payment page ──────────────────────────────
$pageTitle = 'Payment';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:700px">
        <?= renderFlash() ?>

        <!-- Step indicator -->
        <div class="reg-steps mb-3">
            <div class="reg-step done">
                <span class="reg-step-num">✓</span>
                <span class="reg-step-label">Preview</span>
            </div>
            <div class="reg-step-line" style="background:var(--clr-success)"></div>
            <div class="reg-step active">
                <span class="reg-step-num">2</span>
                <span class="reg-step-label">Payment</span>
            </div>
            <div class="reg-step-line"></div>
            <div class="reg-step">
                <span class="reg-step-num">3</span>
                <span class="reg-step-label">Confirmed</span>
            </div>
        </div>

        <div class="card fade-in" style="padding:2rem">
            <!-- Payment Header -->
            <div class="payment-header">
                <div class="payment-icon">💳</div>
                <h1>Payment</h1>
                <p class="text-secondary">Complete your registration</p>
            </div>

            <!-- Order Summary -->
            <div class="payment-summary">
                <h3 class="reg-section-title">Order Summary</h3>
                <div class="payment-line-item">
                    <span>Event</span>
                    <strong><?= sanitize($event['title']) ?></strong>
                </div>
                <div class="payment-line-item">
                    <span>Category</span>
                    <span class="badge badge-accent"><?= sanitize($event['category_name']) ?></span>
                </div>
                <div class="payment-line-item">
                    <span>Date</span>
                    <span><?= $event['event_date'] ? formatDate($event['event_date']) : 'TBA' ?></span>
                </div>
                <div class="payment-line-item">
                    <span>Registration Type</span>
                    <span class="badge badge-<?= $type === 'Volunteer' ? 'warning' : 'primary' ?>"><?= $type ?></span>
                </div>
                <div class="payment-line-item payment-total">
                    <span>Total Amount</span>
                    <?php if ($event['registration_fee'] > 0): ?>
                        <span class="payment-amount">₹<?= number_format($event['registration_fee'], 2) ?></span>
                    <?php else: ?>
                        <span class="payment-amount">₹0 <small class="text-success">(Free)</small></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Actions -->
            <div class="payment-actions">
                <form method="POST" id="payment-form">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <input type="hidden" name="type" value="<?= $type ?>">
                    <button type="submit" class="btn btn-success btn-lg payment-btn" data-confirm="Confirm payment and complete registration?">
                        <span class="payment-btn-icon">🔒</span> Pay Now
                    </button>
                </form>
                <a href="/event_detail.php?id=<?= $eventId ?>" class="btn btn-secondary btn-lg" style="width:100%">Cancel</a>
            </div>

            <!-- Security Note -->
            <div class="payment-note">
                <span>🔐</span>
                <span>This is a secure dummy payment gateway. No real charges will be made.</span>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Reuse step indicator from preview ───────────────────── */
.reg-steps { display:flex; align-items:center; justify-content:center; }
.reg-step { display:flex; align-items:center; gap:.5rem; padding:.5rem 1rem; }
.reg-step-num {
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.85rem;
    background:var(--clr-border-light); color:var(--clr-text-muted);
}
.reg-step.active .reg-step-num { background:var(--clr-primary); color:#fff; box-shadow:0 2px 8px rgba(79,70,229,.3); }
.reg-step.done .reg-step-num { background:var(--clr-success); color:#fff; }
.reg-step-label { font-size:.85rem; font-weight:600; color:var(--clr-text-muted); }
.reg-step.active .reg-step-label { color:var(--clr-primary); }
.reg-step.done .reg-step-label { color:var(--clr-success); }
.reg-step-line { flex:1; height:2px; background:var(--clr-border-light); max-width:60px; }

/* ── Payment Styles ──────────────────────────────────────── */
.payment-header {
    text-align: center;
    margin-bottom: 2rem;
}
.payment-icon {
    font-size: 3rem;
    margin-bottom: .5rem;
}
.payment-summary {
    background: var(--clr-surface-alt);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.payment-line-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .75rem 0;
    border-bottom: 1px solid var(--clr-border-light);
    font-size: .9rem;
}
.payment-line-item:last-child {
    border-bottom: none;
}
.payment-total {
    font-weight: 700;
    font-size: 1rem;
    padding-top: 1rem;
    margin-top: .5rem;
    border-top: 2px solid var(--clr-border);
}
.payment-amount {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--clr-text);
}
.payment-actions {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    margin-bottom: 1.5rem;
}
.payment-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    font-size: 1.1rem;
    padding: 1rem;
}
.payment-btn-icon {
    font-size: 1.2rem;
}
.payment-note {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .8rem;
    color: var(--clr-text-muted);
    text-align: center;
    justify-content: center;
    padding-top: 1rem;
    border-top: 1px solid var(--clr-border-light);
}

@media(max-width:500px) {
    .reg-step-label { display:none; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
