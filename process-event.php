<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST["title"];
    $description = $_POST['description'];
    $category_id = $_POST['category'] ;
    $is_volunteer_required = $_POST['is_volunteer_required'];
    $event_date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $deadline = $_POST['deadline'];
    $venue = $_POST['venue'];
    $total_slots = !empty($_POST['slots']) ? $_POST['slots'] : null;
    $organizer = $_POST['organizer'];
    $poc_id = $_POST['poc'] ?? null;
    $participant_whatsapp_link = $_POST['participant_whatsapp_link'];
    $volunteer_whatsapp_link = $_POST['volunteer_whatsapp_link'];

    // Handle file upload for approval document
    $doc_path = null;
    if (isset($_FILES['doc']) && $_FILES['doc']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/docs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['doc']['name']);
        $doc_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['doc']['tmp_name'], $doc_path);
    }

    $sql = "INSERT INTO events (title, description, category_id, is_volunteer_required, event_date, start_time, end_time, application_deadline, venue, total_slots, slots_left, organizer, poc_id, participant_whatsapp_link, volunteer_whatsapp_link, approval_doc_path, is_published) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title, 
        $description, 
        $category_id,
        $is_volunteer_required, 
        $event_date, 
        $start_time, 
        $end_time, 
        $deadline, 
        $venue, 
        $total_slots, 
        $total_slots, 
        $organizer, 
        $poc_id,
        $participant_whatsapp_link,
        $volunteer_whatsapp_link,
        $doc_path]);    
    
    error_log("DEBUG: ###---------- Event created: ".$pdo->lastInsertId());    
    header("Location: index.php?success=event_created");
    exit;
    
}
?>