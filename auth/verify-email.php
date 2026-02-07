<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!empty($token)) {
    $result = $auth->verifyEmail($token);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
} else {
    $error = 'Invalid verification link';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span></a>
            <div class="nav-menu">
                <a href="/" class="nav-link">Home</a>
                <a href="/auth/login.php" class="nav-link">Login</a>
                <a href="/auth/register.php" class="nav-link">Register</a>
            </div>
        </div>
    </nav>

    <!-- Email Verification Section -->
    <section class="py-5">
        <div class="container-sm">
            <div class="text-center">
                <?php if ($success): ?>
                    <div style="font-size: 80px; margin-bottom: 20px;">✅</div>
                    <h1>Email Verified Successfully!</h1>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($success); ?></p>
                    <a href="/auth/login.php" class="btn btn-primary btn-lg">Login to Your Account</a>
                <?php else: ?>
                    <div style="font-size: 80px; margin-bottom: 20px;">❌</div>
                    <h1>Verification Failed</h1>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
                    <div class="card" style="max-width: 400px; margin: 0 auto;">
                        <div class="card-body">
                            <p>The verification link may have expired or is invalid.</p>
                            <a href="/auth/forgot-password.php" class="btn btn-outline">Request New Verification</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>