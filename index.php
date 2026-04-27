<?php
/**
 * Home Page — Public Event Discovery
 */
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

// Fetch all published events
$stmt = $pdo->query("
    SELECT e.*, c.name AS category_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.is_published = TRUE
    ORDER BY e.event_date ASC
");
$events = $stmt->fetchAll();

// Fetch categories for filter tabs
$catStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $catStmt->fetchAll();

$pageTitle = 'Discover Events';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Discover Campus Events</h1>
            <p>Find workshops, seminars, competitions, and more happening at your campus. Register in seconds.</p>
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="event-search" placeholder="Search events…" autocomplete="off">
            </div>
        </section>

        <!-- Category Tabs -->
        <div class="category-tabs">
            <span class="category-tab active" data-category="all">All Events</span>
            <?php foreach ($categories as $cat): ?>
                <span class="category-tab" data-category="<?= sanitize($cat['name']) ?>">
                    <?= sanitize($cat['name']) ?>
                </span>
            <?php endforeach; ?>
        </div>

        <?= renderFlash() ?>

        <!-- Events Grid -->
        <?php if (empty($events)): ?>
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>No events yet</h3>
                <p>Check back soon for upcoming campus events!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($events as $event): ?>
                    <div>
                        <div class="card event-card" data-category="<?= sanitize($event['category_name']) ?>">
                            <span class="badge badge-accent event-category"><?= sanitize($event['category_name']) ?></span>
                            <div class="card-body">
                                <h3 class="event-title"><?= sanitize($event['title']) ?></h3>
                                <div class="event-meta">
                                    <span>📅 <?= $event['event_date'] ? formatDate($event['event_date']) : 'TBA' ?></span>
                                    <span>🕐 <?= $event['start_time'] ? formatTime($event['start_time']) : '' ?></span>
                                    <span>📍 <?= sanitize($event['venue'] ?? 'TBA') ?></span>
                                </div>
                                <p class="event-desc"><?= sanitize($event['description'] ?? '') ?></p>

                                <?php if ($event['total_slots']): ?>
                                    <?php
                                        $slotsLeft = max(0, $event['slots_left']);
                                        $fillPercent = (($event['total_slots'] - $slotsLeft) / $event['total_slots']) * 100;
                                    ?>
                                    <div class="event-slots">
                                        <span><?= $slotsLeft ?> slots left</span>
                                        <div class="slots-bar"><div class="slots-fill" style="width:<?= $fillPercent ?>%"></div></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <span class="text-sm text-muted">By <?= sanitize($event['organizer'] ?? 'Unknown') ?></span>
                                <a href="/event_detail.php?id=<?= $event['event_id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
