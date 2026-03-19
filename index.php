<?php
require 'includes/db.php';
require 'includes/header.php';

// Fetch published events [cite: 6] grouped by category
$stmt = $pdo->query("SELECT * FROM events WHERE is_published = 1 ORDER BY category, event_date ASC");
$events = $stmt->fetchAll();

// Organize into an associative array: ['Cultural' => [...events], 'Sports' => [...events]]
$categorized_events = [];
foreach ($events as $event) {
    $categorized_events[$event['category']][] = $event;
}
?>

<div class="container">
    <?php if (empty($categorized_events)): ?>
        <p>No events currently scheduled.</p>
    <?php else: ?>
        <?php foreach ($categorized_events as $category => $categoryEvents): ?>
            <div class="category-section">
                <h2 class="category-title"><?= htmlspecialchars($category) ?></h2>
                <div class="event-row">
                    <?php foreach ($categoryEvents as $e): ?>
                        <a href="event.php?id=<?= $e['event_id'] ?>" class="event-card">
                            <span class="event-date">
                                <?= date('M d, Y', strtotime($e['event_date'])) ?> &bull; <?= date('H:i', strtotime($e['start_time'])) ?>
                            </span>
                            <h3 class="event-title"><?= htmlspecialchars($e['title']) ?></h3>
                            <span class="event-venue">📍 <?= htmlspecialchars($e['venue']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>