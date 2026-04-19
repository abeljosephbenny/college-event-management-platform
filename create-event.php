<?php
require 'includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'event_organizer' ");
$stmt->execute();
$users = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * from categories");
$stmt->execute();
$categories = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="auth-container" style="padding: 4rem 2rem;">
    <div class="auth-card" style="max-width: 500px;">
        <h2>Create an Event</h2>

        <form action="process-event.php" method="POST">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">

                <!-- title -->
                <div class="form-group" style="flex: 1;">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>

                <!-- category -->
                <div class="form-group" style="flex: 1;">
                    <label for="title">Category</label>
                    <select name="category" id="category" class="form-control">
                        <?php foreach($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>">
                            <?= $category['name'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>     
            </div>

            <!-- description -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea cols="30" rows="5" id="description" name="description" class="form-control">
                    </textarea>
            </div>

            <!-- Volunteer Required? -->
            <div class="form-group oneline">
                <label>Is Volunteer required?</label>
                <div class="oneline">
                    <input type="radio" name="is_volunteer_required" id="yes" value="1" required>
                    <label for="yes">Yes</label>
                </div>
                <div class="oneline">
                    <input type="radio" name="is_volunteer_required" id="no" value="0">
                    <label for="no">No</label>
                </div>
            </div>

            <!-- Date and Time -->
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" class="form-control">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="start_time">Start Time</label>
                    <input type="time" id="start_time" name="start_time" class="form-control">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="end_time">End Time</label>
                    <input type="time" id="end_time" name="end_time" class="form-control">
                </div>
            </div>

            <!-- application deadline -->
            <div class="form-group">
                <label for="deadline">Application Deadline</label>
                <input type="date" id="deadline" name="deadline" class="form-control">
            </div>

            <div style="display: flex; gap: 1rem;">

                <!-- venue -->
                <div class="form-group" style="flex: 2;">
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" class="form-control" placeholder="e.g., PTA Hall">
                </div>

                <!-- total slots -->
                <div class="form-group" style="flex: 1;">
                    <label for="slots">Total Slots</label>
                    <input type="number" id="slots" name="slots" class="form-control" placeholder="e.g., 3">
                </div>
            </div>

            <!-- document upload -->
            <div class="form-group">
                <label for="doc">Approval Document</label>
                <input type="file" id="doc" name="doc" class="form-control">
            </div>

            <!-- organizer -->
            <div class="form-group">
                <label for="organizer">Organized By</label>
                <input type="text" id="organizer" name="organizer" class="form-control" placeholder="eg., IEDC TKMCE">
            </div>

            <!-- poc -->
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 2;">
                    <label for="poc">Point Of Contact</label>
                    <select id="poc" class="form-control">
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['name'].":".$user['phone']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- links -->
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 2;">
                    <label for="participant_whatsapp_link">Participant's WhatsApp Group Link</label>
                    <input type="text" id="participant_whatsapp_link" name="participant_whatsapp_link" class="form-control">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="volunteer_whatsapp_link">Volunteer's WhatsApp Group Link</label>
                    <input type="text" id="volunteer_whatsapp_link" name="volunteer_whatsapp_link" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Event</button>
        </form>
    </div>
</div>

<?php require 'includes/footer.php'; ?>