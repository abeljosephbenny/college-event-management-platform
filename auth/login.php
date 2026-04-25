<?php
require '../includes/db.php';
require '../includes/header.php';


if ($_SERVER['REQUEST_METHOD'] === "POST") {

    session_start();

    $email = $_POST['email'];
    $password = $_POST['password'];

    //fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user and password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['usrename'] = $user['email'];

        echo "Login Successful!!";

        header("Location: /index.php");
        exit;
    } else {
        echo "Invalid Username or Password!!";
    }
}
?>


<div class="auth-container">
    <div class="auth-card">
        <h2>Welcome back</h2>
        <p>Enter your credentials to access your account.</p>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
            <div class="form-group">
                <label for="email">College Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="student@college.edu"
                    required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                    required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Log In</button>
        </form>

        <p style="text-align: center; margin-top: 1.5rem; margin-bottom: 0;">
            Don't have an account? <a href="/auth/signup.php" style="color: var(--accent); font-weight: 500;">Sign
                up</a>
        </p>
    </div>
</div>