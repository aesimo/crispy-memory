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