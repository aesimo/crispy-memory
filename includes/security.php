<?php
/**
 * Security Middleware
 * Include this file at the top of all PHP pages to enable security features
 */

require_once __DIR__ . '/../classes/Security.php';

// Set security headers
Security::setSecurityHeaders();

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', Config::isProduction());
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Handle POST requests - validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Skip CSRF validation for API callbacks
    $skipCsrf = [
        '/api/verify-payment.php',
        '/auth/google-callback.php'
    ];
    
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if (!in_array($currentPath, $skipCsrf)) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!Security::validateCsrfToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}