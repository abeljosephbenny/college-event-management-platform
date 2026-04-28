<?php
/**
 * Step 3 — Registration Success / Event Pass
 * Shows vibrant event pass with WhatsApp group CTA
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Student');

$pdo = getDBConnection();
$regId = intval($_GET['reg_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.*, e.title, e.event_date, e.start_time, e.end_time, e.venue, e.place,
           e.organizer, e.participant_whatsapp_link, e.volunteer_whatsapp_link,
           c.name AS category_name, u.name AS student_name,
           u.admission_number, u.department, u.email AS student_email
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN categories c ON e.category_id = c.category_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.reg_id = ? AND r.user_id = ?
");
$stmt->execute([$regId, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('danger', 'Registration not found.');
    redirect('/student/dashboard.php');
}

// Determine relevant WhatsApp link
$whatsappLink = null;
$whatsappLabel = '';
if ($ticket['type'] === 'Volunteer' && $ticket['volunteer_whatsapp_link']) {
    $whatsappLink = $ticket['volunteer_whatsapp_link'];
    $whatsappLabel = 'Volunteer WhatsApp Group';
} elseif ($ticket['type'] === 'Participant' && $ticket['participant_whatsapp_link']) {
    $whatsappLink = $ticket['participant_whatsapp_link'];
    $whatsappLabel = 'Participant WhatsApp Group';
}

$pageTitle = 'Registration Confirmed';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:700px">

        <!-- Step indicator -->
        <div class="reg-steps mb-3">
            <div class="reg-step done">
                <span class="reg-step-num">✓</span>
                <span class="reg-step-label">Preview</span>
            </div>
            <div class="reg-step-line" style="background:var(--clr-success)"></div>
            <div class="reg-step done">
                <span class="reg-step-num">✓</span>
                <span class="reg-step-label">Payment</span>
            </div>
            <div class="reg-step-line" style="background:var(--clr-success)"></div>
            <div class="reg-step active">
                <span class="reg-step-num" style="background:var(--clr-success);color:#fff;box-shadow:0 2px 8px rgba(34,197,94,.3)">✓</span>
                <span class="reg-step-label" style="color:var(--clr-success)">Confirmed</span>
            </div>
        </div>

        <!-- Success Banner -->
        <div class="success-banner fade-in">
            <div class="success-icon">🎉</div>
            <h1>Registration Successful!</h1>
            <p class="text-secondary">Your event pass is ready. Save or screenshot it for entry.</p>
        </div>

        <!-- Vibrant Event Pass -->
        <div class="epass fade-in">
            <!-- Gradient header -->
            <div class="epass-top">
                <div class="epass-confetti"></div>
                <div class="epass-top-content">
                    <div class="epass-site"><?= SITE_NAME ?></div>
                    <h2 class="epass-title"><?= sanitize($ticket['title']) ?></h2>
                    <div class="epass-badges">
                        <span class="epass-badge epass-badge-cat"><?= sanitize($ticket['category_name']) ?></span>
                        <span class="epass-badge epass-badge-type <?= $ticket['type'] === 'Volunteer' ? 'vol' : 'part' ?>">
                            <?= $ticket['type'] ?>
                        </span>
                        <?php if ($ticket['type'] === 'Volunteer'): ?>
                            <span class="epass-badge epass-badge-status <?= strtolower($ticket['vol_approval_status']) ?>">
                                <?= $ticket['vol_approval_status'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tear line -->
            <div class="epass-tear">
                <div class="epass-tear-circle left"></div>
                <div class="epass-tear-line"></div>
                <div class="epass-tear-circle right"></div>
            </div>

            <!-- Pass body -->
            <div class="epass-body">
                <div class="epass-code-section">
                    <div class="epass-code-label">REGISTRATION CODE</div>
                    <div class="epass-code"><?= sanitize($ticket['registration_code']) ?></div>
                </div>

                <div class="epass-grid">
                    <div class="epass-field">
                        <div class="epass-field-label">👤 Name</div>
                        <div class="epass-field-value"><?= sanitize($ticket['student_name']) ?></div>
                    </div>
                    <?php if ($ticket['admission_number']): ?>
                    <div class="epass-field">
                        <div class="epass-field-label">🪪 Admission No</div>
                        <div class="epass-field-value"><?= sanitize($ticket['admission_number']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($ticket['department']): ?>
                    <div class="epass-field">
                        <div class="epass-field-label">🏛️ Department</div>
                        <div class="epass-field-value"><?= sanitize($ticket['department']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="epass-field">
                        <div class="epass-field-label">📅 Date</div>
                        <div class="epass-field-value"><?= $ticket['event_date'] ? formatDate($ticket['event_date']) : 'TBA' ?></div>
                    </div>
                    <div class="epass-field">
                        <div class="epass-field-label">🕐 Time</div>
                        <div class="epass-field-value">
                            <?= $ticket['start_time'] ? formatTime($ticket['start_time']) : '' ?>
                            <?= $ticket['end_time'] ? ' – ' . formatTime($ticket['end_time']) : '' ?>
                            <?php if (!$ticket['start_time']): ?>TBA<?php endif; ?>
                        </div>
                    </div>
                    <div class="epass-field">
                        <div class="epass-field-label">📍 Venue</div>
                        <div class="epass-field-value"><?= sanitize($ticket['venue'] ?? 'TBA') ?><?= $ticket['place'] ? ', ' . sanitize($ticket['place']) : '' ?></div>
                    </div>
                    <div class="epass-field" style="grid-column:1/-1">
                        <div class="epass-field-label">🎤 Organizer</div>
                        <div class="epass-field-value"><?= sanitize($ticket['organizer'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- WhatsApp Group CTA -->
        <?php if ($whatsappLink): ?>
        <div class="whatsapp-cta fade-in">
            <div class="whatsapp-cta-icon">💬</div>
            <div class="whatsapp-cta-content">
                <h3>Join the <?= $whatsappLabel ?></h3>
                <p>Stay updated with the latest event announcements, schedule changes, and important instructions.</p>
                <a href="<?= sanitize($whatsappLink) ?>" target="_blank" class="btn btn-success" style="margin-top:.75rem">
                    📱 Join WhatsApp Group
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="flex gap-2 justify-center mt-3 mb-3">
            <a href="/student/ticket.php?reg_id=<?= $regId ?>" class="btn btn-primary btn-lg">🎫 View My Ticket</a>
            <a href="/student/dashboard.php" class="btn btn-secondary btn-lg">← Dashboard</a>
        </div>
    </div>
</div>

<style>
/* ── Step Indicator ──────────────────────────────────────── */
.reg-steps { display:flex; align-items:center; justify-content:center; }
.reg-step { display:flex; align-items:center; gap:.5rem; padding:.5rem 1rem; }
.reg-step-num {
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.85rem;
    background:var(--clr-border-light); color:var(--clr-text-muted);
}
.reg-step.active .reg-step-num { background:var(--clr-primary); color:#fff; }
.reg-step.done .reg-step-num { background:var(--clr-success); color:#fff; }
.reg-step-label { font-size:.85rem; font-weight:600; color:var(--clr-text-muted); }
.reg-step.active .reg-step-label { color:var(--clr-primary); }
.reg-step.done .reg-step-label { color:var(--clr-success); }
.reg-step-line { flex:1; height:2px; background:var(--clr-border-light); max-width:60px; }

/* ── Success Banner ──────────────────────────────────────── */
.success-banner { text-align:center; padding:2rem 1rem 1rem; }
.success-icon { font-size:4rem; animation:successBounce .6s ease; }
@keyframes successBounce {
    0% { transform:scale(0); }
    50% { transform:scale(1.3); }
    70% { transform:scale(0.9); }
    100% { transform:scale(1); }
}
.success-banner h1 { margin:.5rem 0 .25rem; color:var(--clr-success); }

/* ── Vibrant Event Pass ──────────────────────────────────── */
.epass {
    max-width: 460px;
    margin: 0 auto;
    border-radius: 20px;
    overflow: hidden;
    box-shadow:
        0 4px 6px rgba(0,0,0,.07),
        0 12px 28px rgba(99,102,241,.15),
        0 0 0 1px rgba(99,102,241,.08);
}
.epass-top {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 35%, #a855f7 65%, #06b6d4 100%);
    padding: 2rem 2rem 1.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.epass-confetti {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle 2px, rgba(255,255,255,.3) 100%, transparent 100%),
        radial-gradient(circle 1.5px, rgba(255,255,255,.2) 100%, transparent 100%),
        radial-gradient(circle 3px, rgba(255,255,255,.15) 100%, transparent 100%);
    background-size: 60px 60px, 40px 40px, 80px 80px;
    background-position: 0 0, 20px 30px, 40px 10px;
    animation: confettiDrift 3s linear infinite;
}
@keyframes confettiDrift {
    to { background-position: 60px 60px, 60px 70px, 120px 70px; }
}
.epass-top-content { position:relative; z-index:1; }
.epass-site {
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: rgba(255,255,255,.75);
    margin-bottom: .5rem;
}
.epass-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: .75rem;
    text-shadow: 0 2px 4px rgba(0,0,0,.15);
}
.epass-badges { display:flex; gap:.5rem; justify-content:center; flex-wrap:wrap; }
.epass-badge {
    padding: .3rem .75rem;
    border-radius: 50px;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.epass-badge-cat { background:rgba(255,255,255,.2); color:#fff; backdrop-filter:blur(4px); }
.epass-badge-type.part { background:#fff; color:#6366f1; }
.epass-badge-type.vol { background:#fbbf24; color:#78350f; }
.epass-badge-status { color:#fff; }
.epass-badge-status.approved { background:#22c55e; }
.epass-badge-status.pending  { background:#f59e0b; }
.epass-badge-status.rejected { background:#ef4444; }

/* Tear line */
.epass-tear {
    display:flex; align-items:center;
    position:relative; height:20px;
    background: var(--clr-surface);
}
.epass-tear-circle {
    width:20px; height:20px; border-radius:50%;
    background: var(--clr-bg);
    position:absolute; top:50%; transform:translateY(-50%);
}
.epass-tear-circle.left { left:-10px; }
.epass-tear-circle.right { right:-10px; }
.epass-tear-line { flex:1; margin:0 15px; border-top:2px dashed var(--clr-border-light); }

/* Pass body */
.epass-body { background:var(--clr-surface); padding:1.5rem 2rem 2rem; }
.epass-code-section { text-align:center; margin-bottom:1.5rem; }
.epass-code-label {
    font-size:.65rem; font-weight:700;
    letter-spacing:.15em; color:var(--clr-text-muted);
    margin-bottom:.35rem;
}
.epass-code {
    font-size: 1.8rem;
    font-weight: 900;
    letter-spacing: .12em;
    font-family: 'Courier New', monospace;
    color: #6366f1;
    padding: .75rem;
    border: 2px solid rgba(99,102,241,.15);
    border-radius: var(--radius-lg);
    background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(168,85,247,.06));
}
.epass-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:.5rem;
}
.epass-field {
    padding:.6rem .75rem;
    background: var(--clr-surface-alt);
    border-radius: var(--radius);
    border-left: 3px solid transparent;
}
.epass-field:nth-child(1) { border-left-color:#6366f1; }
.epass-field:nth-child(2) { border-left-color:#8b5cf6; }
.epass-field:nth-child(3) { border-left-color:#a855f7; }
.epass-field:nth-child(4) { border-left-color:#06b6d4; }
.epass-field:nth-child(5) { border-left-color:#f59e0b; }
.epass-field:nth-child(6) { border-left-color:#22c55e; }
.epass-field:nth-child(7) { border-left-color:#6366f1; }
.epass-field-label { font-size:.7rem; color:var(--clr-text-muted); font-weight:600; margin-bottom:.1rem; }
.epass-field-value { font-weight:700; font-size:.9rem; color:var(--clr-text); }

/* ── WhatsApp CTA ────────────────────────────────────────── */
.whatsapp-cta {
    margin-top: 1.5rem;
    background: linear-gradient(135deg, rgba(37,211,102,.08) 0%, rgba(37,211,102,.03) 100%);
    border: 2px solid rgba(37,211,102,.25);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.whatsapp-cta-icon { font-size:2.5rem; flex-shrink:0; }
.whatsapp-cta-content h3 { font-size:1.1rem; margin-bottom:.25rem; color:#128C7E; }
.whatsapp-cta-content p { font-size:.85rem; color:var(--clr-text-muted); margin:0; }
.justify-center { justify-content:center; }

@media(max-width:500px) {
    .reg-step-label { display:none; }
    .epass-top { padding:1.5rem; }
    .epass-body { padding:1.25rem; }
    .epass-grid { grid-template-columns:1fr; }
    .epass-code { font-size:1.4rem; }
    .whatsapp-cta { flex-direction:column; text-align:center; align-items:center; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
