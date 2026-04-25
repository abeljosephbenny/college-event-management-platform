<?php
require 'includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$event_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND is_published = 0"); //TO BE CHANGED TO TRUE IN PRODUCTION
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found or not yet published.");
}

require 'includes/header.php';
?>

<div class="container">
    <div class="event-header">
        <span class="event-date"><?= htmlspecialchars($event['category']) ?></span>
        <h1 style="font-size: 2.5rem; margin-top: 0.5rem;"><?= htmlspecialchars($event['title']) ?></h1>
        <div class="event-meta">
            <span>📅 <?= date('l, F j, Y', strtotime($event['event_date'])) ?></span>
            <span>⏰ <?= date('H:i', strtotime($event['start_time'])) ?> -
                <?= date('H:i', strtotime($event['end_time'])) ?></span>
            <span>📍 <?= htmlspecialchars($event['venue']) ?>, <?= htmlspecialchars($event['place']) ?></span>
        </div>
    </div>

    <div class="content-grid">
        <div class="event-description">
            <h3>About this event</h3>
            <p style="margin-top: 1rem; color: var(--text-muted); white-space: pre-wrap;">
                <?= htmlspecialchars($event['description']) ?></p>
        </div>

        <div>
            <div class="registration-box">
                <h3 style="margin-bottom: 1rem;">Registration</h3>
                <p style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                    <?= $event['slots_left'] ?> spots remaining out of <?= $event['total_slots'] ?>
                </p>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="process_registration.php" method="POST"
                        style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                        <button type="submit" name="type" value="Participant" class="btn btn-primary"
                            style="width: 100%;">Register as Participant</button>

                        <?php if ($event['is_volunteer_required']): ?>
                            <button type="submit" name="type" value="Volunteer" class="btn btn-text"
                                style="width: 100%; border: 1px solid var(--border-color);">Apply to Volunteer</button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <a href="/auth/login.php" class="btn btn-primary" style="display: block; text-align: center;">Log In to
                        Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>