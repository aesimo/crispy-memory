<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// If already logged in, redirect to dashboard
if ($auth->isAuthenticated()) {
    header('Location: /user/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    
    if (empty($identifier)) {
        $error = 'Please enter your email or mobile number';
    } else {
        $result = $auth->requestPasswordReset($identifier);
        
        if ($result['success']) {
            $success = $result['message'];
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
    <title>Forgot Password - IdeaOne</title>
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

    <!-- Forgot Password Section -->
    <section class="py-5">
        <div class="container-sm">
            <div class="text-center mb-4">
                <h1>Forgot Password?</h1>
                <p class="text-muted">No worries, we'll send you reset instructions</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2"><a href="/auth/login.php" class="btn btn-primary">Back to Login</a></p>
                </div>
            <?php else: ?>

            <div class="card">
                <form action="forgot-password.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="identifier">Email Address or Mobile Number</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="identifier" 
                            name="identifier" 
                            placeholder="Enter your email or mobile number"
                            required
                            autofocus
                        >
                        <small class="form-text">We'll send you a reset link or code</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Send Reset Link
                    </button>
                </form>

                <div class="text-center mt-3">
                    <p class="text-muted">
                        Remember your password? 
                        <a href="/auth/login.php" class="text-primary">Login here</a>
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