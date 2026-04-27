<?php
/**
 * Database Configuration
 * 
 * Central database connection using PDO with prepared statements.
 * All credentials and platform constants are defined here.
 */

// ── Platform Constants ───────────────────────────────────────
define('COLLEGE_EMAIL_DOMAIN', 'tkmce.ac.in');
define('SITE_NAME', 'CampusEvents');
define('SITE_TAGLINE', 'Your Campus. Your Events.');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// ── Database Credentials ─────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'campus_events');
define('DB_USER', 'abhajkhan');
define('DB_PASS', 'abhajkhan');
define('DB_CHARSET', 'utf8mb4');

// ── PDO Connection ───────────────────────────────────────────
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("A database error occurred. Please try again later.");
        }
    }

    return $pdo;
}
