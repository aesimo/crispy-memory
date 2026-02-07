<?php
session_start();
require_once __DIR__ . '/../classes/GoogleAuth.php';

$googleAuth = new GoogleAuth();
$error = '';

// Check for errors in callback
if (isset($_GET['error'])) {
    $error = $_GET['error'];
    
    if ($error === 'access_denied') {
        header('Location: /auth/login.php?error=google_access_denied');
        exit;
    }
    
    header('Location: /auth/login.php?error=google_auth_failed');
    exit;
}

// Verify state parameter for CSRF protection
if (!isset($_GET['state']) || !$googleAuth->verifyState($_GET['state'])) {
    header('Location: /auth/login.php?error=invalid_state');
    exit;
}

// Get authorization code
$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: /auth/login.php?error=no_code');
    exit;
}

// Handle Google sign-in
$result = $googleAuth->handleSignIn($code);

if ($result['success']) {
    // Redirect based on user role
    $user = $_SESSION['user'] ?? null;
    
    if ($user) {
        switch ($user['role']) {
            case 'admin':
                header('Location: /admin/dashboard.php?welcome=google');
                break;
            case 'moderator':
                header('Location: /moderator/dashboard.php?welcome=google');
                break;
            default:
                header('Location: /user/dashboard.php?welcome=google');
        }
    } else {
        header('Location: /user/dashboard.php?welcome=google');
    }
    exit;
} else {
    // Handle error
    header('Location: /auth/login.php?error=' . urlencode($result['message']));
    exit;
}