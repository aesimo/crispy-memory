<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// If already logged in, redirect to dashboard
if ($auth->isAuthenticated()) {
    header('Location: /user/dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Check if token is provided
if (empty($token)) {
    $error = 'Invalid reset link. Please request a new password reset.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->resetPassword($token, $newPassword);
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirect after delay
            header('refresh:3;url=/auth/login.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - IdeaOne</title>
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

    <!-- Reset Password Section -->
    <section class="py-5">
        <div class="container-sm">
            <div class="text-center mb-4">
                <h1>Reset Password</h1>
                <p class="text-muted">Enter your new password below</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2">Redirecting to login page...</p>
                </div>
            <?php else: ?>

            <div class="card">
                <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password *</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Enter your new password"
                            minlength="6"
                            required
                            autofocus
                        >
                        <small class="form-text">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password *</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Confirm your new password"
                            minlength="6"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Reset Password
                    </button>
                </form>

                <div class="text-center mt-3">
                    <p class="text-muted">
                        <a href="/auth/login.php" class="text-muted">← Back to Login</a>
                    </p>
                </div>
            </div>
            <?php endif; ?>
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