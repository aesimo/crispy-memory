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
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $referralCode = trim($_POST['referral_code'] ?? '');
    
    // Check for referral code in URL
    if (empty($referralCode) && isset($_GET['ref'])) {
        $referralCode = trim($_GET['ref']);
    }
    
    // Validation
    if (empty($name) || empty($email) || empty($mobile) || empty($dob) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = 'Invalid mobile number (must be 10 digits)';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($name, $email, $mobile, $dob, $password, $referralCode);
        
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
    <title>Register - IdeaOne</title>
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

    <!-- Register Section -->
    <section class="py-5">
        <div class="container-sm">
            <div class="text-center mb-4">
                <h1>Create Account</h1>
                <p class="text-muted">Join IdeaOne and start earning with your ideas</p>
            </div>

            <!-- Bonus Banner -->
            <div class="alert alert-success text-center mb-4">
                <strong>🎁 Special Offer!</strong> Get <strong>6 FREE coins</strong> when you register!
                Start submitting your ideas today.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2"><a href="/auth/login.php" class="btn btn-primary">Login Now</a></p>
                </div>
            <?php else: ?>

            <div class="card">
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="name" 
                            name="name" 
                            placeholder="Enter your full name"
                            required
                            <?php echo $success ? 'disabled' : ''; ?>
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your email address"
                            required
                            <?php echo $success ? 'disabled' : ''; ?>
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mobile">Mobile Number</label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="mobile" 
                            name="mobile" 
                            placeholder="Enter your 10-digit mobile number"
                            pattern="[0-9]{10}"
                            required
                            <?php echo $success ? 'disabled' : ''; ?>
                        >
                        <small class="form-text">We'll send important updates to this number</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input 
                            type="date" 
                            class="form-control" 
                            id="dob" 
                            name="dob" 
                            required
                            <?php echo $success ? 'disabled' : ''; ?>
                        >
                        <small class="form-text">You must be at least 13 years old to register</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Create a strong password"
                            minlength="6"
                            required
                            <?php echo $success ? 'disabled' : ''; ?>
                        >
                        <small class="form-text">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Confirm your password"
                            minlength="6"
                            required
                            <?php echo $success ? 'disabled' : ''; ?>
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="referral_code">Referral Code (Optional)</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="referral_code" 
                            name="referral_code" 
                            placeholder="Enter referral code"
                            value="<?php echo htmlspecialchars($referralCode); ?>"
                            <?php echo $success ? 'disabled' : ''; ?>
                        >
                        <small class="form-text">Have a referral code? Enter it to get bonus coins!</small>
                    </div>

                    <div class="form-group">
                        <label class="d-flex align-center">
                            <input type="checkbox" required <?php echo $success ? 'disabled' : ''; ?>>
                            <span class="ml-2">
                                I agree to the <a href="/pages/terms.php" target="_blank">Terms & Conditions</a> 
                                and <a href="/pages/privacy.php" target="_blank">Privacy Policy</a>
                            </span>
                        </label>
                    </div>

                    <button 
                        type="submit" 
                        class="btn btn-primary btn-block btn-lg"
                        <?php echo $success ? 'disabled' : ''; ?>
                    >
                        Create Account
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
                           Sign up with Google
                       </a>
                   <?php endif; ?>

                <div class="text-center mt-3">
                    <p class="text-muted">
                        Already have an account? 
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