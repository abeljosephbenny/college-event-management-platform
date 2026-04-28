<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Administrator');

$pdo = getDBConnection();

$totalUsers  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalRegs   = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$pendingOrgs = $pdo->query("SELECT COUNT(*) FROM users WHERE role='Organizer' AND is_verified=FALSE")->fetchColumn();
$pendingEvts = $pdo->query("SELECT COUNT(*) FROM events WHERE is_published=FALSE")->fetchColumn();

// Recent events
$recentEvents = $pdo->query("
    SELECT e.*, c.name AS category_name, u.name AS creator_name
    FROM events e
    JOIN categories c ON e.category_id = c.category_id
    LEFT JOIN users u ON e.created_by = u.user_id
    ORDER BY e.created_at DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container">
        <?= renderFlash() ?>

        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p class="text-secondary">Platform overview and management</p>
        </div>

        <div class="grid grid-4 mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">👥</div>
                <div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon accent">📅</div>
                <div><div class="stat-value"><?= $totalEvents ?></div><div class="stat-label">Total Events</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">🎫</div>
                <div><div class="stat-value"><?= $totalRegs ?></div><div class="stat-label">Registrations</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">⏳</div>
                <div><div class="stat-value"><?= $pendingOrgs + $pendingEvts ?></div><div class="stat-label">Pending Approvals</div></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-2 mb-4">
            <a href="/admin/organizers.php" class="card" style="text-decoration:none;color:inherit">
                <div class="flex-between">
                    <div>
                        <h3>Organizer Requests</h3>
                        <p class="text-secondary text-sm">Review and approve organizer accounts</p>
                    </div>
                    <span class="badge badge-warning" style="font-size:1rem;padding:.4rem .85rem"><?= $pendingOrgs ?></span>
                </div>
            </a>
            <a href="/admin/events.php" class="card" style="text-decoration:none;color:inherit">
                <div class="flex-between">
                    <div>
                        <h3>Event Submissions</h3>
                        <p class="text-secondary text-sm">Review and publish submitted events</p>
                    </div>
                    <span class="badge badge-warning" style="font-size:1rem;padding:.4rem .85rem"><?= $pendingEvts ?></span>
                </div>
            </a>
        </div>

        <!-- Recent Events -->
        <h2 class="section-title">Recent Events</h2>
        <?php if (empty($recentEvents)): ?>
            <div class="card"><div class="empty-state"><p class="text-muted">No events yet.</p></div></div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Event</th><th>Created By</th><th>Category</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentEvents as $e): ?>
                        <tr class="clickable-row" onclick="window.location='/admin/event_detail.php?id=<?= $e['event_id'] ?>'">
                            <td><strong><?= sanitize($e['title']) ?></strong></td>
                            <td onclick="event.stopPropagation()">
                                <?php if ($e['created_by']): ?>
                                    <a href="/admin/user_profile.php?id=<?= $e['created_by'] ?>" class="organizer-link"><?= sanitize($e['creator_name'] ?? 'Unknown') ?></a>
                                <?php else: ?>
                                    <?= sanitize($e['creator_name'] ?? 'Unknown') ?>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-accent"><?= sanitize($e['category_name']) ?></span></td>
                            <td><?= $e['event_date'] ? formatDate($e['event_date']) : 'TBA' ?></td>
                            <td><span class="badge badge-<?= $e['is_published'] ? 'success' : 'warning' ?>"><?= $e['is_published'] ? 'Published' : 'Pending' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
