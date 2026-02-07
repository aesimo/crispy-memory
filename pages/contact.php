<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$isLoggedIn = $auth->isAuthenticated();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // In production, you would send this email or save to database
        $success = 'Thank you for your message! We will get back to you soon.';
        
        // Log the contact (you can implement email sending here)
        error_log("Contact Form: $name ($email) - $subject");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact IdeaOne support team for any questions, feedback, or assistance.">
    <title>Contact Us - IdeaOne</title>
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
                <a href="/pages/features.php" class="nav-link">Features</a>
                <a href="/pages/benefits.php" class="nav-link">Benefits</a>
                <a href="/pages/categories.php" class="nav-link">Categories</a>
                <a href="/pages/earning.php" class="nav-link">How It Works</a>
                <a href="/pages/pricing.php" class="nav-link">Pricing</a>
                <a href="/pages/contact.php" class="nav-link active">Contact</a>
            </div>
            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                    <span class="nav-link user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
                    <a href="/user/dashboard.php" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="/auth/login.php" class="btn btn-outline">Login</a>
                    <a href="/auth/register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <section class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p class="text-muted">Get in touch with our support team</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <div class="contact-container">
                <!-- Contact Form -->
                <div class="contact-form-section">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <h3>Send us a Message</h3>
                        <p class="text-muted mb-4">Fill out the form below and we'll get back to you as soon as possible.</p>
                        
                        <form action="contact.php" method="POST">
                            <div class="form-group">
                                <label class="form-label" for="name">Your Name</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="name" 
                                    name="name" 
                                    placeholder="Enter your name"
                                    value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="Enter your email"
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="subject">Subject</label>
                                <select class="form-control" id="subject" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="general" <?php echo (isset($subject) && $subject === 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                                    <option value="support" <?php echo (isset($subject) && $subject === 'support') ? 'selected' : ''; ?>>Technical Support</option>
                                    <option value="billing" <?php echo (isset($subject) && $subject === 'billing') ? 'selected' : ''; ?>>Billing & Payments</option>
                                    <option value="feedback" <?php echo (isset($subject) && $subject === 'feedback') ? 'selected' : ''; ?>>Feedback & Suggestions</option>
                                    <option value="partnership" <?php echo (isset($subject) && $subject === 'partnership') ? 'selected' : ''; ?>>Partnership Inquiry</option>
                                    <option value="other" <?php echo (isset($subject) && $subject === 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="message">Message</label>
                                <textarea 
                                    class="form-control" 
                                    id="message" 
                                    name="message" 
                                    rows="5"
                                    placeholder="Type your message here..."
                                    required
                                ><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="contact-info-section">
                    <div class="info-card">
                        <div class="info-icon">📧</div>
                        <h4>Email Us</h4>
                        <p>support@ideaone.com</p>
                        <p class="text-muted">We'll respond within 24 hours</p>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">📱</div>
                        <h4>Call Us</h4>
                        <p>+91 98765 43210</p>
                        <p class="text-muted">Mon-Fri, 9AM-6PM IST</p>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">💬</div>
                        <h4>Live Chat</h4>
                        <p>Available in dashboard</p>
                        <p class="text-muted">Chat with our support team</p>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">📍</div>
                        <h4>Office</h4>
                        <p>Mumbai, India</p>
                        <p class="text-muted">Remote-first company</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Quick Links -->
            <div class="quick-links mt-5">
                <h3 class="text-center mb-4">Quick Links</h3>
                <div class="links-grid">
                    <a href="/pages/features.php" class="quick-link-card">
                        <div class="quick-link-icon">✨</div>
                        <h4>Features</h4>
                        <p class="text-muted">Learn about our features</p>
                    </a>
                    <a href="/pages/earning.php" class="quick-link-card">
                        <div class="quick-link-icon">💰</div>
                        <h4>How It Works</h4>
                        <p class="text-muted">Understand the earning process</p>
                    </a>
                    <a href="/pages/pricing.php" class="quick-link-card">
                        <div class="quick-link-icon">💳</div>
                        <h4>Pricing</h4>
                        <p class="text-muted">View our transparent pricing</p>
                    </a>
                    <a href="/auth/forgot-password.php" class="quick-link-card">
                        <div class="quick-link-icon">🔑</div>
                        <h4>Password Help</h4>
                        <p class="text-muted">Reset your password</p>
                    </a>
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