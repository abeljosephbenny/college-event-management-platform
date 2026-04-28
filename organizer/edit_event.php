<?php
/**
 * Edit Event — Organizer can update their own event details
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? $_POST['event_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$eventId) { setFlash('danger', 'Event not found.'); redirect('/organizer/dashboard.php'); }

// Verify ownership
$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.event_id = ? AND e.created_by = ?
");
$stmt->execute([$eventId, $userId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event not found or you are not the organizer of this event.');
    redirect('/organizer/dashboard.php');
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $categoryId  = intval($_POST['category_id'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $eventDate   = $_POST['event_date'] ?? '';
    $startTime   = $_POST['start_time'] ?? '';
    $endTime     = $_POST['end_time'] ?? '';
    $deadline    = $_POST['application_deadline'] ?? '';
    $venue       = sanitize($_POST['venue'] ?? '');
    $place       = sanitize($_POST['place'] ?? '');
    $totalSlots  = intval($_POST['total_slots'] ?? 0);
    $organizer   = sanitize($_POST['organizer_name'] ?? $_SESSION['user_name']);
    $volRequired = isset($_POST['is_volunteer_required']) ? 1 : 0;
    $regFee      = floatval($_POST['registration_fee'] ?? 0);
    $pWhatsapp   = sanitize($_POST['participant_whatsapp_link'] ?? '');
    $vWhatsapp   = sanitize($_POST['volunteer_whatsapp_link'] ?? '');

    // Validate
    if (empty($title) || !$categoryId) {
        setFlash('danger', 'Title and category are required.');
        redirect('/organizer/edit_event.php?id=' . $eventId);
    }

    // Calculate slots_left adjustment
    $slotsDiff = $totalSlots - ($event['total_slots'] ?? 0);
    $newSlotsLeft = max(0, ($event['slots_left'] ?? 0) + $slotsDiff);

    // Handle optional new approval document upload
    $docPath = $event['approval_doc_path'];
    if (isset($_FILES['approval_doc']) && $_FILES['approval_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadedPath = uploadApprovalDoc($_FILES['approval_doc']);
        if ($uploadedPath === false) {
            setFlash('danger', 'Invalid file. Please upload a PDF or image (max 5MB).');
            redirect('/organizer/edit_event.php?id=' . $eventId);
        }
        $docPath = $uploadedPath;
    }

    $updateStmt = $pdo->prepare("
        UPDATE events SET
            title = ?, category_id = ?, description = ?, event_date = ?,
            start_time = ?, end_time = ?, application_deadline = ?,
            venue = ?, place = ?, total_slots = ?, slots_left = ?,
            organizer = ?, is_volunteer_required = ?, registration_fee = ?, approval_doc_path = ?,
            participant_whatsapp_link = ?, volunteer_whatsapp_link = ?
        WHERE event_id = ? AND created_by = ?
    ");
    $updateStmt->execute([
        $title, $categoryId, $description,
        $eventDate ?: null, $startTime ?: null, $endTime ?: null,
        $deadline ?: null, $venue, $place,
        $totalSlots, $newSlotsLeft, $organizer, $volRequired, $regFee, $docPath,
        $pWhatsapp, $vWhatsapp,
        $eventId, $userId
    ]);

    setFlash('success', 'Event updated successfully.');
    redirect('/organizer/event_detail.php?id=' . $eventId);
}

$pageTitle = 'Edit: ' . $event['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:720px">
        <?= renderFlash() ?>

        <a href="/organizer/event_detail.php?id=<?= $eventId ?>" class="btn btn-secondary btn-sm mb-3">← Back to Event Details</a>

        <div class="card fade-in" style="padding:2rem">
            <h1 style="margin-bottom:.25rem">Edit Event</h1>
            <p class="text-secondary mb-3">Update the details for <strong><?= sanitize($event['title']) ?></strong></p>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="event_id" value="<?= $eventId ?>">

                <div class="form-group">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($event['title']) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $event['category_id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="organizer_name">Organizer / Club Name</label>
                        <input type="text" id="organizer_name" name="organizer_name" class="form-control" value="<?= sanitize($event['organizer'] ?? $_SESSION['user_name']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"><?= sanitize($event['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Event Date</label>
                        <input type="date" id="event_date" name="event_date" class="form-control" value="<?= $event['event_date'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="application_deadline">Registration Deadline</label>
                        <input type="datetime-local" id="application_deadline" name="application_deadline" class="form-control" value="<?= $event['application_deadline'] ? date('Y-m-d\TH:i', strtotime($event['application_deadline'])) : '' ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" value="<?= $event['start_time'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control" value="<?= $event['end_time'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="venue">Venue</label>
                        <input type="text" id="venue" name="venue" class="form-control" value="<?= sanitize($event['venue'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="place">Place / Building</label>
                        <input type="text" id="place" name="place" class="form-control" value="<?= sanitize($event['place'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="total_slots">Total Slots</label>
                        <input type="number" id="total_slots" name="total_slots" class="form-control" min="0" value="<?= $event['total_slots'] ?? 0 ?>">
                        <div class="form-hint">Currently <?= $event['slots_left'] ?? 0 ?> slots remaining. Changing total slots will adjust remaining accordingly.</div>
                    </div>
                    <div class="form-group">
                        <label for="registration_fee">Registration Fee (₹)</label>
                        <input type="number" id="registration_fee" name="registration_fee" class="form-control" min="0" step="0.01" value="<?= $event['registration_fee'] ?? 0 ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="is_volunteer_required" value="1" <?= $event['is_volunteer_required'] ? 'checked' : '' ?>> Volunteers required for this event
                    </label>
                </div>

                <div class="form-group">
                    <label for="approval_doc">Approval Document <span class="text-muted text-sm">(PDF, JPG, PNG — max 5MB)</span></label>
                    <?php if ($event['approval_doc_path']): ?>
                        <div class="flex gap-1 mb-1" style="align-items:center">
                            <span class="text-sm text-muted">Current: </span>
                            <a href="/<?= sanitize($event['approval_doc_path']) ?>" target="_blank" class="btn btn-secondary btn-sm">📄 View Current</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="approval_doc" name="approval_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="form-hint">Upload only if you want to replace the existing document.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="participant_whatsapp_link">Participant WhatsApp Link</label>
                        <input type="url" id="participant_whatsapp_link" name="participant_whatsapp_link" class="form-control" value="<?= sanitize($event['participant_whatsapp_link'] ?? '') ?>" placeholder="https://chat.whatsapp.com/...">
                    </div>
                    <div class="form-group">
                        <label for="volunteer_whatsapp_link">Volunteer WhatsApp Link</label>
                        <input type="url" id="volunteer_whatsapp_link" name="volunteer_whatsapp_link" class="form-control" value="<?= sanitize($event['volunteer_whatsapp_link'] ?? '') ?>" placeholder="https://chat.whatsapp.com/...">
                    </div>
                </div>

                <div class="flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-lg" style="flex:1">Save Changes</button>
                    <a href="/organizer/event_detail.php?id=<?= $eventId ?>" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
