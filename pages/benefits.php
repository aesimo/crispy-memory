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
    <meta name="description" content="Learn about the benefits of using IdeaOne for submitting ideas, earning money, and building your portfolio.">
    <title>Benefits - IdeaOne</title>
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
                <a href="/pages/benefits.php" class="nav-link active">Benefits</a>
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
            <h1>Why Choose IdeaOne?</h1>
            <p class="text-muted">Discover the benefits of joining our platform</p>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-5">
        <div class="container">
            <!-- For Students -->
            <div class="mb-5">
                <h2 class="text-center mb-4">For Students</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-number">01</div>
                        <h3>Earn Money</h3>
                        <p>Turn your creative ideas into real earnings. Get paid for your innovative thoughts and solutions.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">02</div>
                        <h3>Build Portfolio</h3>
                        <p>Create a portfolio of approved ideas that showcases your creativity and problem-solving skills.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">03</div>
                        <h3>Get Feedback</h3>
                        <p>Receive professional feedback from experienced moderators to improve your ideas and skills.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">04</div>
                        <h3>Flexible Work</h3>
                        <p>Submit ideas on your own schedule. No deadlines, no pressure - just pure creativity.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">05</div>
                        <h3>Low Barrier to Entry</h3>
                        <p>Start with just 2 coins per submission. Get 6 free coins on registration!</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">06</div>
                        <h3>Multiple Categories</h3>
                        <p>Choose from 100+ categories across various domains and industries.</p>
                    </div>
                </div>
            </div>

            <!-- For Moderators -->
            <div class="mb-5">
                <h2 class="text-center mb-4">For Moderators</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-number">07</div>
                        <h3>Flexible Schedule</h3>
                        <p>Review ideas at your convenience. Set your own working hours and pace.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">08</div>
                        <h3>Earn Extra Income</h3>
                        <p>Get paid for every idea you review. The more you review, the more you earn.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">09</div>
                        <h3>Impact Innovation</h3>
                        <p>Help shape the future by identifying and nurturing innovative ideas.</p>
                    </div>
                </div>
            </div>

            <!-- Platform Benefits -->
            <div>
                <h2 class="text-center mb-4">Platform Benefits</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-number">10</div>
                        <h3>Secure & Trustworthy</h3>
                        <p>Enterprise-grade security protects your ideas and personal information.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">11</div>
                        <h3>Transparent Process</h3>
                        <p>Clear guidelines, transparent review process, and fair evaluations.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-number">12</div>
                        <h3>Supportive Community</h3>
                        <p>Join a community of creative minds and collaborate with like-minded individuals.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Ideas Submitted</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5K+</div>
                    <div class="stat-label">Active Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">₹10L+</div>
                    <div class="stat-label">Paid Out</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Experience These Benefits?</h2>
            <p class="text-muted">Join IdeaOne today and start your journey</p>
            <?php if (!$isLoggedIn): ?>
                <div class="mt-4">
                    <a href="/auth/register.php" class="btn btn-lg btn-success">Get Started Now - It's Free!</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="/user/dashboard.php" class="btn btn-lg btn-success">Go to Dashboard</a>
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