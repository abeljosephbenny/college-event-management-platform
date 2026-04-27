<?php
/**
 * Digital Ticket / Passcard
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Student');

$pdo = getDBConnection();
$regId = intval($_GET['reg_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.*, e.title, e.event_date, e.start_time, e.end_time, e.venue, e.place,
           e.organizer, c.name AS category_name, u.name AS student_name,
           u.admission_number, u.department
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN categories c ON e.category_id = c.category_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.reg_id = ? AND r.user_id = ?
");
$stmt->execute([$regId, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('danger', 'Ticket not found.');
    redirect('/student/dashboard.php');
}

$pageTitle = 'My Ticket';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <div style="text-align:center;margin-bottom:1.5rem">
            <a href="/student/dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
        </div>

        <div class="ticket fade-in">
            <div class="ticket-inner">
                <div class="ticket-header">
                    <div class="text-muted text-sm" style="margin-bottom:.25rem">⚡ <?= SITE_NAME ?></div>
                    <h2><?= sanitize($ticket['title']) ?></h2>
                    <span class="badge badge-accent"><?= sanitize($ticket['category_name']) ?></span>
                </div>

                <div class="ticket-code"><?= sanitize($ticket['registration_code']) ?></div>

                <div style="margin-bottom:1rem">
                    <span class="badge badge-<?= $ticket['type'] === 'Volunteer' ? 'warning' : 'primary' ?>" style="font-size:.85rem;padding:.35rem .85rem">
                        <?= $ticket['type'] ?>
                    </span>
                    <?php if ($ticket['type'] === 'Volunteer'): ?>
                        <span class="badge badge-<?= match($ticket['vol_approval_status']) {
                            'Approved' => 'success', 'Rejected' => 'danger', default => 'warning'
                        } ?>" style="font-size:.85rem;padding:.35rem .85rem">
                            <?= $ticket['vol_approval_status'] ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($ticket['attendance_marked']): ?>
                        <span class="badge badge-success" style="font-size:.85rem;padding:.35rem .85rem">✅ Attended</span>
                    <?php endif; ?>
                </div>

                <div class="ticket-details">
                    <div class="detail-row">
                        <span class="detail-label">Name</span>
                        <span><?= sanitize($ticket['student_name']) ?></span>
                    </div>
                    <?php if ($ticket['admission_number']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Admission No</span>
                        <span><?= sanitize($ticket['admission_number']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($ticket['department']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Department</span>
                        <span><?= sanitize($ticket['department']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Date</span>
                        <span><?= $ticket['event_date'] ? formatDate($ticket['event_date']) : 'TBA' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time</span>
                        <span>
                            <?= $ticket['start_time'] ? formatTime($ticket['start_time']) : '' ?>
                            <?= $ticket['end_time'] ? ' – ' . formatTime($ticket['end_time']) : '' ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Venue</span>
                        <span><?= sanitize($ticket['venue'] ?? 'TBA') ?><?= $ticket['place'] ? ', ' . sanitize($ticket['place']) : '' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Organizer</span>
                        <span><?= sanitize($ticket['organizer'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
