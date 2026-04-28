<?php
/**
 * Admin — View User Profile
 * Read-only view of any user's profile for admin reference.
 * Uses a distinct card-based layout (not the same as the user's own profile page).
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Administrator');

$pdo = getDBConnection();
$userId = intval($_GET['id'] ?? 0);

if (!$userId) { setFlash('danger', 'User not found.'); redirect('/admin/dashboard.php'); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) { setFlash('danger', 'User not found.'); redirect('/admin/dashboard.php'); }

// Get user's events (if organizer)
$events = [];
if ($user['role'] === 'Organizer') {
    $evtStmt = $pdo->prepare("
        SELECT e.event_id, e.title, e.event_date, e.is_published, c.name AS category_name
        FROM events e
        JOIN categories c ON e.category_id = c.category_id
        WHERE e.created_by = ?
        ORDER BY e.created_at DESC
    ");
    $evtStmt->execute([$userId]);
    $events = $evtStmt->fetchAll();
}

// Get registrations count (if student)
$regCount = 0;
if ($user['role'] === 'Student') {
    $regStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = ?");
    $regStmt->execute([$userId]);
    $regCount = $regStmt->fetchColumn();
}

$pageTitle = 'User: ' . $user['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:800px">
        <?= renderFlash() ?>

        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mb-3">← Back</a>

        <!-- User Info Card — distinct design from user's own profile -->
        <div class="admin-user-card fade-in">
            <div class="auc-header">
                <div class="auc-avatar-col">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img src="/<?= sanitize($user['profile_pic']) ?>" alt="<?= sanitize($user['name']) ?>" class="auc-avatar-img">
                    <?php else: ?>
                        <div class="auc-avatar-fallback">
                            <?= getInitials($user['name']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="auc-info-col">
                    <h1 class="auc-name"><?= sanitize($user['name']) ?></h1>
                    <div class="auc-meta">
                        <span class="badge badge-primary"><?= sanitize($user['role']) ?></span>
                        <?php if ($user['is_verified']): ?>
                            <span class="badge badge-success">Verified</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Unverified</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Contact Strip -->
            <div class="auc-contact-strip">
                <div class="auc-contact-item">
                    <span class="auc-contact-icon">✉️</span>
                    <div>
                        <div class="auc-contact-label">Email</div>
                        <a href="mailto:<?= sanitize($user['email']) ?>" class="auc-contact-value"><?= sanitize($user['email']) ?></a>
                    </div>
                </div>
                <div class="auc-contact-item">
                    <span class="auc-contact-icon">📞</span>
                    <div>
                        <div class="auc-contact-label">Phone</div>
                        <?php if ($user['phone']): ?>
                            <a href="tel:<?= sanitize($user['phone']) ?>" class="auc-contact-value"><?= sanitize($user['phone']) ?></a>
                        <?php else: ?>
                            <span class="auc-contact-value text-muted">Not provided</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="auc-details-grid">
                <?php if ($user['department']): ?>
                <div class="auc-detail-box">
                    <div class="auc-detail-icon">🏛️</div>
                    <div class="auc-detail-label">Department</div>
                    <div class="auc-detail-value"><?= sanitize($user['department']) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($user['year']): ?>
                <div class="auc-detail-box">
                    <div class="auc-detail-icon">📚</div>
                    <div class="auc-detail-label">Year</div>
                    <div class="auc-detail-value"><?= $user['year'] ?></div>
                </div>
                <?php endif; ?>

                <?php if ($user['admission_number']): ?>
                <div class="auc-detail-box">
                    <div class="auc-detail-icon">🪪</div>
                    <div class="auc-detail-label">Admission No.</div>
                    <div class="auc-detail-value"><?= sanitize($user['admission_number']) ?></div>
                </div>
                <?php endif; ?>

                <div class="auc-detail-box">
                    <div class="auc-detail-icon">📅</div>
                    <div class="auc-detail-label">Member Since</div>
                    <div class="auc-detail-value"><?= date('M j, Y', strtotime($user['created_at'])) ?></div>
                </div>

                <?php if ($user['role'] === 'Organizer'): ?>
                <div class="auc-detail-box">
                    <div class="auc-detail-icon">📋</div>
                    <div class="auc-detail-label">Events Created</div>
                    <div class="auc-detail-value"><?= count($events) ?></div>
                </div>
                <?php elseif ($user['role'] === 'Student'): ?>
                <div class="auc-detail-box">
                    <div class="auc-detail-icon">🎫</div>
                    <div class="auc-detail-label">Registrations</div>
                    <div class="auc-detail-value"><?= $regCount ?></div>
                </div>
                <?php endif; ?>

                <div class="auc-detail-box">
                    <div class="auc-detail-icon">🆔</div>
                    <div class="auc-detail-label">User ID</div>
                    <div class="auc-detail-value">#<?= $user['user_id'] ?></div>
                </div>
            </div>
        </div>

        <!-- Organizer's Events -->
        <?php if ($user['role'] === 'Organizer' && !empty($events)): ?>
        <div class="card fade-in mt-3" style="padding:1.5rem">
            <h3 class="section-title">Events by <?= sanitize($user['name']) ?></h3>
            <div class="table-wrapper" style="border:none">
                <table class="data-table">
                    <thead><tr><th>Event</th><th>Category</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($events as $evt): ?>
                        <tr class="clickable-row" onclick="window.location='/admin/event_detail.php?id=<?= $evt['event_id'] ?>'">
                            <td><strong><?= sanitize($evt['title']) ?></strong></td>
                            <td><span class="badge badge-accent"><?= sanitize($evt['category_name']) ?></span></td>
                            <td><?= $evt['event_date'] ? formatDate($evt['event_date']) : 'TBA' ?></td>
                            <td><span class="badge badge-<?= $evt['is_published'] ? 'success' : 'warning' ?>"><?= $evt['is_published'] ? 'Published' : 'Pending' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ── Admin User Card — distinct design from profile.php ───── */
.admin-user-card {
    background: var(--clr-surface);
    border-radius: var(--radius-xl);
    border: 1px solid var(--clr-border-light);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.auc-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 2rem 2rem 1.5rem;
    background: linear-gradient(135deg, rgba(79,70,229,.04) 0%, rgba(6,182,212,.04) 100%);
    border-bottom: 1px solid var(--clr-border-light);
}
.auc-avatar-img {
    width: 80px;
    height: 80px;
    border-radius: var(--radius-lg);
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: var(--shadow);
}
.auc-avatar-fallback {
    width: 80px;
    height: 80px;
    border-radius: var(--radius-lg);
    background: linear-gradient(135deg, var(--clr-primary), var(--clr-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 1.75rem;
    box-shadow: var(--shadow);
}
.auc-name {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: .35rem;
}
.auc-meta {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}

/* Contact Strip */
.auc-contact-strip {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid var(--clr-border-light);
}
.auc-contact-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: 1.25rem 2rem;
}
.auc-contact-item:first-child {
    border-right: 1px solid var(--clr-border-light);
}
.auc-contact-icon {
    font-size: 1.25rem;
}
.auc-contact-label {
    font-size: .75rem;
    color: var(--clr-text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.auc-contact-value {
    font-weight: 600;
    color: var(--clr-text);
    font-size: .9rem;
    text-decoration: none;
}
a.auc-contact-value:hover {
    color: var(--clr-primary);
}

/* Details Grid */
.auc-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0;
    padding: .5rem;
}
.auc-detail-box {
    padding: 1.25rem 1.5rem;
    text-align: center;
    border-right: 1px solid var(--clr-border-light);
    border-bottom: 1px solid var(--clr-border-light);
}
.auc-detail-box:last-child {
    border-right: none;
}
.auc-detail-icon {
    font-size: 1.5rem;
    margin-bottom: .35rem;
}
.auc-detail-label {
    font-size: .7rem;
    color: var(--clr-text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .2rem;
}
.auc-detail-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--clr-text);
}

@media(max-width:600px) {
    .auc-header {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }
    .auc-contact-strip {
        grid-template-columns: 1fr;
    }
    .auc-contact-item:first-child {
        border-right: none;
        border-bottom: 1px solid var(--clr-border-light);
    }
    .auc-details-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
