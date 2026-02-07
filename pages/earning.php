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
    <meta name="description" content="Learn how IdeaOne works and how you can earn money by submitting your innovative ideas.">
    <title>How It Works - IdeaOne</title>
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
                <a href="/pages/earning.php" class="nav-link active">How It Works</a>
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
            <h1>How It Works</h1>
            <p class="text-muted">Simple steps to start earning from your ideas</p>
        </div>
    </section>

    <!-- How It Works Steps -->
    <section class="py-5">
        <div class="container">
            <div class="steps-container">
                <!-- Step 1 -->
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Register & Get Free Coins</h3>
                        <p>Sign up for a free account and receive 6 free coins to get started. No credit card required!</p>
                        <div class="step-details">
                            <span class="badge badge-success">6 Free Coins</span>
                            <span class="badge badge-info">Instant Access</span>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Choose Your Category</h3>
                        <p>Browse through 100+ categories and select the one that best fits your idea. From technology to healthcare, we have it all.</p>
                        <div class="step-details">
                            <span class="badge badge-primary">100+ Categories</span>
                            <span class="badge badge-warning">Varied Topics</span>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Submit Your Idea</h3>
                        <p>Fill out the idea submission form with detailed description, problem statement, and proposed solution. Upload supporting documents if needed.</p>
                        <div class="step-details">
                            <span class="badge badge-danger">2 Coins Cost</span>
                            <span class="badge badge-info">File Upload</span>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Expert Review</h3>
                        <p>Your idea is reviewed by our team of experienced moderators who evaluate its potential and provide detailed feedback.</p>
                        <div class="step-details">
                            <span class="badge badge-success">Quick Review</span>
                            <span class="badge badge-warning">Expert Feedback</span>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="step-item">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h3>Get Approved & Earn</h3>
                        <p>If approved, your idea is credited to your wallet based on its quality and potential. Track your earnings in real-time.</p>
                        <div class="step-details">
                            <span class="badge badge-success">Earn Money</span>
                            <span class="badge badge-info">Real-time Tracking</span>
                        </div>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="step-item">
                    <div class="step-number">6</div>
                    <div class="step-content">
                        <h3>Withdraw Your Earnings</h3>
                        <p>Once your wallet balance reaches ₹500, you can request a withdrawal. We process withdrawals within 24-48 hours.</p>
                        <div class="step-details">
                            <span class="badge badge-primary">Min ₹500</span>
                            <span class="badge badge-success">Fast Payout</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earning Breakdown -->
            <div class="earning-breakdown mt-5">
                <h2 class="text-center mb-4">Earning Breakdown</h2>
                <div class="breakdown-grid">
                    <div class="breakdown-card">
                        <h4>Signup Bonus</h4>
                        <div class="breakdown-value">6 Coins</div>
                        <p class="text-muted">Free coins on registration</p>
                    </div>
                    <div class="breakdown-card">
                        <h4>Idea Submission</h4>
                        <div class="breakdown-value">-2 Coins</div>
                        <p class="text-muted">Cost per idea submission</p>
                    </div>
                    <div class="breakdown-card">
                        <h4>Approved Idea</h4>
                        <div class="breakdown-value">₹50 - ₹500</div>
                        <p class="text-muted">Earnings based on quality</p>
                    </div>
                    <div class="breakdown-card">
                        <h4>Referral Bonus</h4>
                        <div class="breakdown-value">3 Coins</div>
                        <p class="text-muted">Bonus per successful referral</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tips Section -->
    <section class="tips-section">
        <div class="container">
            <h2 class="text-center mb-4">Tips for Success</h2>
            <div class="tips-grid">
                <div class="tip-card">
                    <div class="tip-icon">📝</div>
                    <h4>Be Detailed</h4>
                    <p>Provide clear and detailed descriptions of your ideas. The more information, the better the review.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">🎯</div>
                    <h4>Be Specific</h4>
                    <p>Focus on solving specific problems. Clear problem statements lead to better evaluations.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">📎</div>
                    <h4>Add Attachments</h4>
                    <p>Include diagrams, prototypes, or supporting documents to strengthen your submission.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">🔄</div>
                    <h4>Iterate & Improve</h4>
                    <p>Use feedback from rejections to improve your ideas and submit better versions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Start Earning?</h2>
            <p class="text-muted">Join IdeaOne today and turn your ideas into income</p>
            <?php if (!$isLoggedIn): ?>
                <div class="mt-4">
                    <a href="/auth/register.php" class="btn btn-lg btn-success">Get Started with 6 Free Coins</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="/user/submit-idea.php" class="btn btn-lg btn-success">Submit Your First Idea</a>
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