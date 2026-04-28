<?php
/**
 * Register for Event — Student action
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/');

$pdo = getDBConnection();
$userId  = $_SESSION['user_id'];
$eventId = intval($_POST['event_id'] ?? 0);
$type    = sanitize($_POST['type'] ?? 'Participant');

if (!$eventId || !in_array($type, ['Participant', 'Volunteer'])) {
    setFlash('danger', 'Invalid request.');
    redirect('/');
}

// Check event exists and is published
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND is_published = TRUE");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event not found.');
    redirect('/');
}

// Check deadline
if ($event['application_deadline'] && strtotime($event['application_deadline']) < time()) {
    setFlash('warning', 'Registration deadline has passed.');
    redirect("/event_detail.php?id=$eventId");
}

// Check if already registered
$stmt = $pdo->prepare("SELECT reg_id FROM registrations WHERE user_id = ? AND event_id = ?");
$stmt->execute([$userId, $eventId]);
if ($stmt->fetch()) {
    setFlash('warning', 'You are already registered for this event.');
    redirect("/event_detail.php?id=$eventId");
}

// Dual-registration check: cannot be both Participant and Volunteer
$otherType = ($type === 'Participant') ? 'Volunteer' : 'Participant';
$dualStmt = $pdo->prepare("SELECT reg_id FROM registrations WHERE user_id = ? AND event_id = ? AND type = ?");
$dualStmt->execute([$userId, $eventId, $otherType]);
if ($dualStmt->fetch()) {
    setFlash('danger', "You are already registered as a $otherType. You cannot register as both a Participant and a Volunteer.");
    redirect("/event_detail.php?id=$eventId");
}

// Check slots for participants
if ($type === 'Participant' && $event['total_slots'] && $event['slots_left'] <= 0) {
    setFlash('danger', 'Sorry, this event is full.');
    redirect("/event_detail.php?id=$eventId");
}

// Check volunteer requirement
if ($type === 'Volunteer' && !$event['is_volunteer_required']) {
    setFlash('danger', 'This event does not require volunteers.');
    redirect("/event_detail.php?id=$eventId");
}

try {
    $pdo->beginTransaction();

    $regCode = generateRegistrationCode();
    $volStatus = ($type === 'Volunteer') ? 'Pending' : 'Approved';

    $stmt = $pdo->prepare("
        INSERT INTO registrations (user_id, event_id, registration_code, type, vol_approval_status)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $eventId, $regCode, $type, $volStatus]);
    $regId = $pdo->lastInsertId();

    // Decrement slots for participants
    if ($type === 'Participant' && $event['total_slots']) {
        $pdo->prepare("UPDATE events SET slots_left = slots_left - 1 WHERE event_id = ?")->execute([$eventId]);
    }

    $pdo->commit();

    if ($type === 'Volunteer') {
        setFlash('success', 'Volunteer application submitted! Awaiting organizer approval.');
    } else {
        setFlash('success', 'Successfully registered! Your ticket is ready.');
    }
    redirect("/student/register_success.php?reg_id=$regId");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Registration error: " . $e->getMessage());
    setFlash('danger', 'Registration failed. Please try again.');
    redirect("/event_detail.php?id=$eventId");
}
