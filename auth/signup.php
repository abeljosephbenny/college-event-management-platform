<?php
require '../includes/db.php';
require '../includes/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // Student or Organizer
    $phone = $_POST['phone'];
    $year = !empty($_POST['year']) ? $_POST['year'] : null;
    $dept = $_POST['department'];
    $adm_no = $_POST['admission_number'];
    
    // Default verification status
    $is_verified = ($role === 'Student') ? 1 : 0;

    // Check if email exists
    $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $message = "Error: This email is already registered.";
    } else {
        $sql = "INSERT INTO users (name, email, password, role, is_verified, phone, year, department, admission_number) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$name, $email, $pass, $role, $is_verified, $phone, $year, $dept, $adm_no])) {
            $message = "Registration successful! " . ($role === 'Organizer' ? "Wait for admin verification." : "");
        } else {
            $message = "Something went wrong. Please try again.";
        }
    }
}
?>
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Create Account</h2>
        <p>Join the community to manage and attend events.</p>

        <?php if (!empty($message)): ?>
            <div style="padding: 1rem; margin-bottom: 1.5rem; background: var(--bg-color); font-size: 0.9rem; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="John Doe">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="hello@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="role">I am a...</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="Student">Student</option>
                    <option value="Organizer">Event Organizer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="+91 0000000000">
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" class="form-control" placeholder="Computer Science">
            </div>

            <div class="oneline">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" class="form-control" placeholder="2024">
                </div>
                <div class="form-group" style="flex: 2; margin-bottom: 0;">
                    <label for="admission_number">Admission No.</label>
                    <input type="text" id="admission_number" name="admission_number" class="form-control" placeholder="ADM-102">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Sign Up</button>
        </form>

        <p style="text-align: center; margin-top: 1.5rem; margin-bottom: 0;">
            Already have an account? <a href="/auth/login.php" style="color: var(--accent); font-weight: 500;">Log in</a>
        </p>
    </div>
</div>