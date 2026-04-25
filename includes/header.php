<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Events</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

    <nav class="navbar">
        <div class="logo">
            <a href="/index.php">Campus<strong>Events</strong></a>
        </div>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === "Organizer"): ?>
                    <a href="/create-event.php" class="btn btn-primary"> Create Event</a>
                <?php endif; ?>
                <div class="avatar-dropdown" style="position: relative;">
                    <div class="avatar" onclick="toggleDropdown(event)" style="cursor: pointer;">
                        <?= strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <div id="profileDropdown" class="dropdown-content" style="display: none; position: absolute; right: 0; background-color: var(--surface); min-width: 150px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1); z-index: 1000; border: 1px solid var(--border-color); border-radius: 8px; margin-top: 10px; overflow: hidden;">
                        <a href="/profile.php" style="display: block; padding: 12px 16px; color: var(--text-main); font-size: 0.9rem; font-weight: 500; border-bottom: 1px solid var(--border-color); text-decoration: none;" onmouseover="this.style.backgroundColor='var(--bg-color)'" onmouseout="this.style.backgroundColor='transparent'">Profile</a>
                        <a href="/auth/logout.php" style="display: block; padding: 12px 16px; color: #dc3545; font-size: 0.9rem; font-weight: 500; text-decoration: none;" onmouseover="this.style.backgroundColor='var(--bg-color)'" onmouseout="this.style.backgroundColor='transparent'">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/auth/login.php" class="btn btn-text">Log In</a>
                <a href="/auth/signup.php" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        function toggleDropdown(event) {
            event.stopPropagation();
            var dropdown = document.getElementById("profileDropdown");
            if (dropdown.style.display === "block") {
                dropdown.style.display = "none";
            } else {
                dropdown.style.display = "block";
            }
        }
        document.addEventListener('click', function(event) {
            var dropdown = document.getElementById("profileDropdown");
            if (dropdown && dropdown.style.display === "block") {
                dropdown.style.display = "none";
            }
        });
    </script>