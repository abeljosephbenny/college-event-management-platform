<?php
/**
 * Shared Utility Functions
 */

session_start();
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize user input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate that email belongs to the college domain
 */
function isCollegeEmail(string $email): bool {
    $domain = COLLEGE_EMAIL_DOMAIN;
    return str_ends_with(strtolower($email), '@' . $domain);
}

/**
 * Generate a unique registration code for tickets
 */
function generateRegistrationCode(): string {
    return 'CE-' . strtoupper(substr(uniqid(), -4)) . '-' . rand(1000, 9999);
}

/**
 * Set a flash message in the session
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render a flash message as HTML
 */
function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $type = sanitize($flash['type']);
    $msg = sanitize($flash['message']);
    return "<div class=\"alert alert-{$type}\" data-auto-dismiss>{$msg}</div>";
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data from session
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'user_id'     => $_SESSION['user_id'],
        'name'        => $_SESSION['user_name'],
        'email'       => $_SESSION['user_email'],
        'role'        => $_SESSION['user_role'],
        'profile_pic' => $_SESSION['profile_pic'] ?? null,
    ];
}

/**
 * Require login — redirect to login page if not authenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to continue.');
        redirect('/auth/login.php');
    }
}

/**
 * Require a specific role
 */
function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        setFlash('danger', 'Access denied.');
        redirect('/');
    }
}

/**
 * Handle file upload for approval documents
 * Returns the relative path on success, or false on failure
 */
function uploadApprovalDoc(array $file): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_DOC_TYPES)) return false;

    $ext = match($mime) {
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        default           => 'bin',
    };

    $dir = UPLOAD_DIR . 'approval_docs/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = uniqid('doc_') . '.' . $ext;
    $path = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return 'uploads/approval_docs/' . $filename;
    }
    return false;
}

/**
 * Handle profile picture upload
 * Returns the relative path on success, or false on failure
 */
function uploadProfilePic(array $file): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed)) return false;

    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'bin',
    };

    $dir = UPLOAD_DIR . 'profile_pics/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = uniqid('pfp_') . '.' . $ext;
    $path = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return 'uploads/profile_pics/' . $filename;
    }
    return false;
}

/**
 * Format a date string for display
 */
function formatDate(string $date): string {
    return date('M j, Y', strtotime($date));
}

/**
 * Format a time string for display
 */
function formatTime(string $time): string {
    return date('g:i A', strtotime($time));
}

/**
 * Get base URL for the site
 */
function baseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

/**
 * Get initials from a name (for avatar)
 */
function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $initials = strtoupper($parts[0][0] ?? '');
    if (count($parts) > 1) {
        $initials .= strtoupper(end($parts)[0] ?? '');
    }
    return $initials;
}
