<?php
session_start();
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$isLoggedIn = $auth->isAuthenticated();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaOne - Turn Your Ideas Into Earnings</title>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Turn Your Innovative Ideas Into Earnings</h1>
            <p>Join thousands of students earning money by sharing their creative ideas. Submit your ideas, get approved, and withdraw your earnings.</p>
            
            <?php if (!$isLoggedIn): ?>
                <div class="mt-4">
                    <a href="/auth/register.php" class="btn btn-lg btn-success">Get 6 Free Coins & Start Earning</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="/user/dashboard.php" class="btn btn-lg btn-success">Submit Your Idea</a>
                </div>
            <?php endif; ?>

            <div class="hero-stats" id="hero-stats">
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
                    <div class="stat-number">100+</div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Why Choose IdeaOne?</h2>
                <p class="text-muted">Everything you need to monetize your creativity</p>
            </div>

            <div class="dashboard-stats">
                <div class="card">
                    <div class="stat-icon primary">💡</div>
                    <h3 class="card-title">Submit Ideas</h3>
                    <p class="card-body">Share your innovative ideas across 100+ categories and get them reviewed by expert moderators.</p>
                </div>

                <div class="card">
                    <div class="stat-icon success">💰</div>
                    <h3 class="card-title">Earn Money</h3>
                    <p class="card-body">Get your ideas approved and earn money based on the quality and potential of your submission.</p>
                </div>

                <div class="card">
                    <div class="stat-icon warning">⚡</div>
                    <h3 class="card-title">Fast Payouts</h3>
                    <p class="card-body">Withdraw your earnings easily with our streamlined payout system. Minimum withdrawal: ₹500.</p>
                </div>

                <div class="card">
                    <div class="stat-icon primary">🎯</div>
                    <h3 class="card-title">Expert Review</h3>
                    <p class="card-body">Your ideas are reviewed by experienced moderators who provide valuable feedback.</p>
                </div>

                <div class="card">
                    <div class="stat-icon success">🔒</div>
                    <h3 class="card-title">Secure Platform</h3>
                    <p class="card-body">Your ideas and personal information are protected with enterprise-grade security.</p>
                </div>

                <div class="card">
                    <div class="stat-icon warning">🏆</div>
                    <h3 class="card-title">Rewards</h3>
                    <p class="card-body">Earn bonuses and rewards for consistent quality submissions and active participation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5" style="background: var(--light-gray);">
        <div class="container">
            <div class="text-center mb-5">
                <h2>How It Works</h2>
                <p class="text-muted">Simple 4-step process to start earning</p>
            </div>

            <div class="dashboard-stats">
                <div class="card text-center">
                    <div class="stat-number" style="color: var(--primary);">1</div>
                    <h3 class="card-title">Register</h3>
                    <p class="card-body">Create your account and get 6 free coins to start submitting ideas immediately.</p>
                </div>

                <div class="card text-center">
                    <div class="stat-number" style="color: var(--primary);">2</div>
                    <h3 class="card-title">Submit Idea</h3>
                    <p class="card-body">Choose a category, describe your problem and solution, and submit your idea.</p>
                </div>

                <div class="card text-center">
                    <div class="stat-number" style="color: var(--primary);">3</div>
                    <h3 class="card-title">Get Approved</h3>
                    <p class="card-body">Our expert moderators review your idea and approve it with an earning amount.</p>
                </div>

                <div class="card text-center">
                    <div class="stat-number" style="color: var(--primary);">4</div>
                    <h3 class="card-title">Withdraw</h3>
                    <p class="card-body">Once you have ₹500+ in your wallet, request a withdrawal and get paid.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Preview -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Popular Categories</h2>
                <p class="text-muted">Explore trending categories with high earning potential</p>
            </div>

            <div class="dashboard-stats">
                <div class="card">
                    <h3 class="card-title">Mobile Apps</h3>
                    <p class="text-success font-weight-bold">₹500 - ₹5,000</p>
                    <p class="card-body">Innovative mobile application ideas</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm">View All</a>
                </div>

                <div class="card">
                    <h3 class="card-title">AI & ML</h3>
                    <p class="text-success font-weight-bold">₹3,000 - ₹20,000</p>
                    <p class="card-body">Artificial Intelligence solutions</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm">View All</a>
                </div>

                <div class="card">
                    <h3 class="card-title">Blockchain</h3>
                    <p class="text-success font-weight-bold">₹5,000 - ₹25,000</p>
                    <p class="card-body">Blockchain innovations</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm">View All</a>
                </div>

                <div class="card">
                    <h3 class="card-title">IoT Solutions</h3>
                    <p class="text-success font-weight-bold">₹2,000 - ₹15,000</p>
                    <p class="card-body">Internet of Things ideas</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm">View All</a>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="/pages/categories.php" class="btn btn-primary btn-lg">View All 100+ Categories</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: var(--white);">
        <div class="container text-center">
            <h2 style="color: var(--white);">Ready to Start Earning?</h2>
            <p style="color: var(--white); opacity: 0.9; margin-bottom: 2rem;">Join thousands of students who are already earning with their ideas</p>
            
            <?php if (!$isLoggedIn): ?>
                <a href="/auth/register.php" class="btn btn-lg" style="background: var(--white); color: var(--primary);">
                    Get 6 Free Coins Now
                </a>
            <?php else: ?>
                <a href="/user/dashboard.php" class="btn btn-lg" style="background: var(--white); color: var(--primary);">
                    Go to Dashboard
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5 text-center text-muted">
        <div class="container">
            <div class="row justify-center mb-4">
                <div class="col col-3">
                    <a href="/pages/about.php" class="text-muted">About Us</a>
                </div>
                <div class="col col-3">
                    <a href="/pages/privacy.php" class="text-muted">Privacy Policy</a>
                </div>
                <div class="col col-3">
                    <a href="/pages/terms.php" class="text-muted">Terms & Conditions</a>
                </div>
                <div class="col col-3">
                    <a href="/pages/contact.php" class="text-muted">Contact</a>
                </div>
            </div>
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>

    <script>
    // Fetch public stats from secure API
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/api/public-stats.php')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Stats are already safe to display as they come from secured API
                    // No sensitive data is exposed
                }
            })
            .catch(error => {
                console.error('Error fetching stats:', error);
            });
    });
    </script>
</body>
</html>