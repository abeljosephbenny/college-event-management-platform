<?php
/**
 * Export Attendance CSV
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('Organizer');

$pdo = getDBConnection();
$eventId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT title FROM events WHERE event_id = ? AND created_by = ?");
$stmt->execute([$eventId, $userId]);
$event = $stmt->fetch();
if (!$event) { setFlash('danger', 'Event not found.'); redirect('/organizer/dashboard.php'); }

$stmt = $pdo->prepare("
    SELECT u.name, u.email, u.admission_number, u.department, u.phone,
           r.type, r.registration_code, r.vol_approval_status, r.attendance_marked, r.registered_at
    FROM registrations r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.event_id = ?
    ORDER BY r.type, u.name
");
$stmt->execute([$eventId]);
$rows = $stmt->fetchAll();

$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $event['title']) . '_attendance_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Name', 'Email', 'Admission Number', 'Department', 'Phone', 'Type', 'Registration Code', 'Volunteer Status', 'Attendance', 'Registered At']);

foreach ($rows as $row) {
    fputcsv($out, [
        $row['name'],
        $row['email'],
        $row['admission_number'] ?? '',
        $row['department'] ?? '',
        $row['phone'] ?? '',
        $row['type'],
        $row['registration_code'] ?? '',
        $row['vol_approval_status'],
        $row['attendance_marked'] ? 'Present' : 'Absent',
        $row['registered_at'],
    ]);
}

fclose($out);
exit;
