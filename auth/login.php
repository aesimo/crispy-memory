<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// If already logged in, redirect to dashboard
if ($auth->isAuthenticated()) {
    $user = $auth->getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'moderator':
            header('Location: /moderator/dashboard.php');
            break;
        default:
            header('Location: /user/dashboard.php');
    }
    exit;
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            $user = $auth->getCurrentUser();
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: /admin/dashboard.php');
                    break;
                case 'moderator':
                    header('Location: /moderator/dashboard.php');
                    break;
                default:
                    header('Location: /user/dashboard.php');
            }
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
    <title>Login - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
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