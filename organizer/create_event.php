<?php
/**
 * Create Event — Organizer
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();

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
    $pWhatsapp   = sanitize($_POST['participant_whatsapp_link'] ?? '');
    $vWhatsapp   = sanitize($_POST['volunteer_whatsapp_link'] ?? '');

    // Validate
    if (empty($title) || !$categoryId) {
        setFlash('danger', 'Title and category are required.');
        redirect('/organizer/create_event.php');
    }

    // Upload approval document
    $docPath = null;
    if (isset($_FILES['approval_doc']) && $_FILES['approval_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
        $docPath = uploadApprovalDoc($_FILES['approval_doc']);
        if ($docPath === false) {
            setFlash('danger', 'Invalid file. Please upload a PDF or image (max 5MB).');
            redirect('/organizer/create_event.php');
        }
    }

    if (!$docPath) {
        setFlash('danger', 'Approval document is required.');
        redirect('/organizer/create_event.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO events (title, category_id, description, event_date, start_time, end_time,
            application_deadline, venue, place, total_slots, slots_left, organizer, created_by,
            is_volunteer_required, approval_doc_path, participant_whatsapp_link, volunteer_whatsapp_link)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $title, $categoryId, $description,
        $eventDate ?: null, $startTime ?: null, $endTime ?: null,
        $deadline ?: null, $venue, $place,
        $totalSlots, $totalSlots, $organizer, $_SESSION['user_id'],
        $volRequired, $docPath, $pWhatsapp, $vWhatsapp
    ]);

    setFlash('success', 'Event created! It will be published once approved by an admin.');
    redirect('/organizer/dashboard.php');
}

$pageTitle = 'Create Event';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:720px">
        <?= renderFlash() ?>

        <a href="/organizer/dashboard.php" class="btn btn-secondary btn-sm mb-3">← Back to Dashboard</a>

        <div class="card fade-in" style="padding:2rem">
            <h1 style="margin-bottom:.25rem">Create New Event</h1>
            <p class="text-secondary mb-3">Fill in the details below. Events require admin approval before publishing.</p>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g. TechFest 2026" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="organizer_name">Organizer / Club Name</label>
                        <input type="text" id="organizer_name" name="organizer_name" class="form-control" value="<?= sanitize($_SESSION['user_name']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Describe the event…"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Event Date</label>
                        <input type="date" id="event_date" name="event_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="application_deadline">Registration Deadline</label>
                        <input type="datetime-local" id="application_deadline" name="application_deadline" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="venue">Venue</label>
                        <input type="text" id="venue" name="venue" class="form-control" placeholder="e.g. Main Auditorium">
                    </div>
                    <div class="form-group">
                        <label for="place">Place / Building</label>
                        <input type="text" id="place" name="place" class="form-control" placeholder="e.g. Block A">
                    </div>
                </div>

                <div class="form-group">
                    <label for="total_slots">Total Slots</label>
                    <input type="number" id="total_slots" name="total_slots" class="form-control" min="0" placeholder="Leave 0 for unlimited">
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="is_volunteer_required" value="1"> Volunteers required for this event
                    </label>
                </div>

                <div class="form-group">
                    <label for="approval_doc">Approval Document * <span class="text-muted text-sm">(PDF, JPG, PNG — max 5MB)</span></label>
                    <input type="file" id="approval_doc" name="approval_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="participant_whatsapp_link">Participant WhatsApp Link</label>
                        <input type="url" id="participant_whatsapp_link" name="participant_whatsapp_link" class="form-control" placeholder="https://chat.whatsapp.com/...">
                    </div>
                    <div class="form-group">
                        <label for="volunteer_whatsapp_link">Volunteer WhatsApp Link</label>
                        <input type="url" id="volunteer_whatsapp_link" name="volunteer_whatsapp_link" class="form-control" placeholder="https://chat.whatsapp.com/...">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">Submit Event for Approval</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
