<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$isLoggedIn = $auth->isAuthenticated();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover IdeaOne's powerful features for submitting ideas, earning money, and connecting with moderators.">
    <title>Features - IdeaOne</title>
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
                <a href="/pages/features.php" class="nav-link active">Features</a>
                <a href="/pages/benefits.php" class="nav-link">Benefits</a>
                <a href="/pages/categories.php" class="nav-link">Categories</a>
                <a href="/pages/earning.php" class="nav-link">How It Works</a>
                <a href="/pages/pricing.php" class="nav-link">Pricing</a>
                <a href="/pages/contact.php" class="nav-link">Contact</a>
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
            <h1>Powerful Features</h1>
            <p class="text-muted">Everything you need to turn your ideas into earnings</p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon primary">💡</div>
                    <h3>Idea Submission</h3>
                    <p>Submit your innovative ideas across 100+ categories with detailed descriptions and supporting documents.</p>
                    <ul class="feature-list">
                        <li>Easy submission form</li>
                        <li>File upload support</li>
                        <li>Prototype sharing</li>
                        <li>Instant confirmation</li>
                    </ul>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card">
                    <div class="feature-icon success">👥</div>
                    <h3>Expert Review</h3>
                    <p>Get your ideas reviewed by experienced moderators who provide detailed feedback and fair evaluations.</p>
                    <ul class="feature-list">
                        <li>Professional moderators</li>
                        <li>Detailed feedback</li>
                        <li>Quick turnaround</li>
                        <li>Transparent process</li>
                    </ul>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card">
                    <div class="feature-icon info">💰</div>
                    <h3>Secure Payments</h3>
                    <p>Receive payments securely through multiple withdrawal options with transparent fee structures.</p>
                    <ul class="feature-list">
                        <li>Bank transfer</li>
                        <li>UPI payments</li>
                        <li>Transparent fees</li>
                        <li>Fast processing</li>
                    </ul>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card">
                    <div class="feature-icon warning">📊</div>
                    <h3>Detailed Analytics</h3>
                    <p>Track your performance with comprehensive statistics and insights on your ideas and earnings.</p>
                    <ul class="feature-list">
                        <li>Submission tracking</li>
                        <li>Earnings reports</li>
                        <li>Performance metrics</li>
                        <li>Activity history</li>
                    </ul>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card">
                    <div class="feature-icon danger">🔒</div>
                    <h3>Secure Platform</h3>
                    <p>Your ideas and data are protected with enterprise-grade security and privacy measures.</p>
                    <ul class="feature-list">
                        <li>Encrypted data</li>
                        <li>Secure authentication</li>
                        <li>Privacy protection</li>
                        <li>Regular audits</li>
                    </ul>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card">
                    <div class="feature-icon accent">🎁</div>
                    <h3>Referral System</h3>
                    <p>Earn extra coins by inviting friends and expanding your network on the platform.</p>
                    <ul class="feature-list">
                        <li>Unique referral codes</li>
                        <li>Tracking system</li>
                        <li>Bonus rewards</li>
                        <li>Unlimited referrals</li>
                    </ul>
                </div>

                <!-- Feature 7 -->
                <div class="feature-card">
                    <div class="feature-icon purple">📱</div>
                    <h3>Mobile Friendly</h3>
                    <p>Access the platform from any device with our fully responsive design optimized for mobile use.</p>
                    <ul class="feature-list">
                        <li>Responsive design</li>
                        <li>Mobile optimized</li>
                        <li>Touch-friendly</li>
                        <li>Fast loading</li>
                    </ul>
                </div>

                <!-- Feature 8 -->
                <div class="feature-card">
                    <div class="feature-icon teal">💬</div>
                    <h3>Instant Messaging</h3>
                    <p>Stay updated with notifications and messages about your submissions and platform updates.</p>
                    <ul class="feature-list">
                        <li>Real-time updates</li>
                        <li>Notification system</li>
                        <li>Direct messaging</li>
                        <li>Email alerts</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p class="text-muted">Join thousands of students already earning from their ideas</p>
            <?php if (!$isLoggedIn): ?>
                <div class="mt-4">
                    <a href="/auth/register.php" class="btn btn-lg btn-success">Get 6 Free Coins & Start Earning</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="/user/dashboard.php" class="btn btn-lg btn-success">Submit Your Idea</a>
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