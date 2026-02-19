<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/GoogleAuth.php';

$auth = new Auth();
$googleAuth = new GoogleAuth();

// If already logged in, redirect to dashboard
if ($auth->isAuthenticated()) {
    header('Location: /user/dashboard.php');
    exit;
}

$error = '';
$errorParam = $_GET['error'] ?? '';

// Handle query parameter errors
if ($errorParam) {
    switch ($errorParam) {
        case 'google_access_denied':
            $error = 'Google sign-in was cancelled';
            break;
        case 'google_auth_failed':
            $error = 'Google authentication failed';
            break;
        case 'invalid_state':
            $error = 'Invalid request. Please try again.';
            break;
        default:
            $error = htmlspecialchars($errorParam);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = $auth->login($email, $password);

        if ($result['success']) {
            header('Location: /user/dashboard.php');
            exit;
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
    <meta name="description" content="Login to your IdeaOne account to start submitting ideas and earning money.">
    <title>Login - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span></a>
            <div class="nav-menu">
                <a href="/" class="nav-link">Home</a>
                <a href="/pages/features.php" class="nav-link">Features</a>
                <a href="/pages/pricing.php" class="nav-link">Pricing</a>
                <a href="/pages/contact.php" class="nav-link">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="py-5">
        <div class="container-sm">
            <div class="text-center mb-4">
                <h1>Welcome Back</h1>
                <p class="text-muted">Login to your IdeaOne account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="email">Email or Mobile</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your email or mobile number"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <a href="/auth/forgot-password.php" class="text-muted">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Login
                    </button>
                </form>

                <div class="divider mt-4 mb-4">
                    <span>OR</span>
                </div>

                <?php 
                $googleAuthUrl = $googleAuth->getAuthUrl();
                if ($googleAuthUrl): 
                ?>
                    <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="btn btn-google btn-block btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Sign in with Google
                    </a>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <p class="text-muted">
                        Don't have an account? 
                        <a href="/auth/register.php" class="text-primary">Register here</a>
                    </p>
                </div>
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