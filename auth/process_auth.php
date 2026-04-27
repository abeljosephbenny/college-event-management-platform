<?php
/**
 * Process Authentication — Login & Signup handler
 */
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/auth/login.php');

$action = $_POST['action'] ?? '';
$pdo = getDBConnection();

// ── LOGIN ─────────────────────────────────────────────────────
if ($action === 'login') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlash('danger', 'Please fill in all fields.');
        redirect('/auth/login.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        setFlash('danger', 'Invalid email or password.');
        redirect('/auth/login.php');
    }

    // Check if organizer is verified
    if ($user['role'] === 'Organizer' && !$user['is_verified']) {
        setFlash('warning', 'Your organizer account is pending admin approval.');
        redirect('/auth/login.php');
    }

    // Create session
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    setFlash('success', 'Welcome back, ' . $user['name'] . '!');

    match($user['role']) {
        'Student'       => redirect('/student/dashboard.php'),
        'Organizer'     => redirect('/organizer/dashboard.php'),
        'Administrator' => redirect('/admin/dashboard.php'),
        default         => redirect('/'),
    };
}

// ── SIGNUP ────────────────────────────────────────────────────
if ($action === 'signup') {
    $role     = sanitize($_POST['role'] ?? '');
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($role) || empty($name) || empty($email) || empty($password)) {
        setFlash('danger', 'Please fill in all required fields.');
        redirect('/auth/signup.php');
    }

    // Validate role
    if (!in_array($role, ['Student', 'Organizer'])) {
        setFlash('danger', 'Invalid role selected.');
        redirect('/auth/signup.php');
    }

    // Validate college email
    if (!isCollegeEmail($email)) {
        setFlash('danger', 'Please use your @' . COLLEGE_EMAIL_DOMAIN . ' email address.');
        redirect('/auth/signup.php');
    }

    // Validate password match
    if ($password !== $confirm) {
        setFlash('danger', 'Passwords do not match.');
        redirect('/auth/signup.php');
    }

    if (strlen($password) < 6) {
        setFlash('danger', 'Password must be at least 6 characters.');
        redirect('/auth/signup.php');
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('danger', 'An account with this email already exists.');
        redirect('/auth/signup.php');
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Student-specific fields
    $year             = ($role === 'Student') ? intval($_POST['year'] ?? 0) : null;
    $department       = ($role === 'Student') ? sanitize($_POST['department'] ?? '') : null;
    $admissionNumber  = ($role === 'Student') ? sanitize($_POST['admission_number'] ?? '') : null;

    // Organizers are not verified by default (need admin approval)
    // Students are verified immediately
    $isVerified = ($role === 'Student') ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, is_verified, phone, year, department, admission_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $email, $hashedPassword, $role, $isVerified, $phone, $year, $department, $admissionNumber]);

    if ($role === 'Organizer') {
        setFlash('success', 'Account created! Your organizer account is pending admin approval.');
    } else {
        setFlash('success', 'Account created successfully! Please log in.');
    }
    redirect('/auth/login.php');
}

// Fallback
redirect('/auth/login.php');
