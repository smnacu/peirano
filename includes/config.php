<?php
// includes/config.php

// Define Base Path
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('TEMPLATES_PATH', ROOT_PATH . 'templates/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Define Base URL (Adjust this based on your server setup)
// For local dev, it might be /peirano/peirano/
// For production, it might be /
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname(dirname($_SERVER['SCRIPT_NAME']));
// Ensure trailing slash
$baseUrl = rtrim($protocol . "://" . $host . $scriptDir, '/\\') . '/';
define('BASE_URL', $baseUrl);

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'peirano_wms');
define('DB_USER', 'c2031975_peirano');
define('DB_PASS', 'zoqhvewcbg5Khxi');

// Microsoft Graph API Credentials
define('TENANT_ID', 'common');
define('CLIENT_ID', 'YOUR_CLIENT_ID');
define('CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
define('CALENDAR_USER_ID', 'YOUR_EMAIL_ID');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>