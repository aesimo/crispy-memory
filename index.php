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
    <meta name="description" content="IdeaOne - Turn your innovative ideas into earnings. Submit ideas, get reviewed, and withdraw your earnings.">
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
    <section class="hero" style="padding: 6rem 0 8rem;">
        <div class="container hero-content">
            <div class="hero-badge">
                🎉 Join 5,000+ students already earning
            </div>
            <h1>Turn Your Innovative<br>Ideas Into <span style="opacity:0.9;">Real Earnings</span></h1>
            <p>Share your creative ideas across 100+ categories, get them reviewed by experts, and withdraw your earnings directly to your bank account.</p>

            <?php if (!$isLoggedIn): ?>
                <div class="hero-cta">
                    <a href="/auth/register.php" class="btn-hero-primary">
                        🚀 Get 6 Free Coins & Start
                    </a>
                    <a href="/pages/earning.php" class="btn-hero-secondary">
                        How It Works →
                    </a>
                </div>
            <?php else: ?>
                <div class="hero-cta">
                    <a href="/user/submit-idea.php" class="btn-hero-primary">
                        💡 Submit Your Idea
                    </a>
                    <a href="/user/dashboard.php" class="btn-hero-secondary">
                        Go to Dashboard →
                    </a>
                </div>
            <?php endif; ?>

            <div class="hero-stats" id="hero-stats" style="margin-top: 3.5rem;">
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
            <div class="section-header">
                <span class="section-tag">Why IdeaOne</span>
                <h2>Everything You Need to <span class="gradient-text">Monetize Your Creativity</span></h2>
                <p>A platform built for students who think differently — submit, get reviewed, and earn.</p>
            </div>

            <div class="features-grid stagger-children">
                <div class="feature-card animate-in">
                    <div class="feature-icon primary">💡</div>
                    <h3>Submit Ideas</h3>
                    <p>Share your innovative ideas across 100+ categories and get them reviewed by expert evaluators.</p>
                    <ul class="feature-list">
                        <li>100+ categories available</li>
                        <li>Structured submission format</li>
                        <li>File & prototype uploads</li>
                    </ul>
                </div>

                <div class="feature-card animate-in">
                    <div class="feature-icon success">💰</div>
                    <h3>Earn Money</h3>
                    <p>Get your ideas approved and earn money based on the quality and potential of your submission.</p>
                    <ul class="feature-list">
                        <li>Up to ₹25,000 per idea</li>
                        <li>Transparent earnings</li>
                        <li>Track all payouts</li>
                    </ul>
                </div>

                <div class="feature-card animate-in">
                    <div class="feature-icon warning">⚡</div>
                    <h3>Fast Payouts</h3>
                    <p>Withdraw your earnings easily with our streamlined payout system.</p>
                    <ul class="feature-list">
                        <li>Minimum withdrawal: ₹500</li>
                        <li>1-3 business day processing</li>
                        <li>Bank transfer support</li>
                    </ul>
                </div>

                <div class="feature-card animate-in">
                    <div class="feature-icon purple">🎯</div>
                    <h3>Expert Review</h3>
                    <p>Your ideas are reviewed by experienced evaluators who provide valuable feedback.</p>
                    <ul class="feature-list">
                        <li>Professional evaluation</li>
                        <li>Detailed feedback</li>
                        <li>Fair assessment criteria</li>
                    </ul>
                </div>

                <div class="feature-card animate-in">
                    <div class="feature-icon teal">🔒</div>
                    <h3>Secure Platform</h3>
                    <p>Your ideas and personal information are protected with enterprise-grade security.</p>
                    <ul class="feature-list">
                        <li>JWT authentication</li>
                        <li>Encrypted data storage</li>
                        <li>CSRF protection</li>
                    </ul>
                </div>

                <div class="feature-card animate-in">
                    <div class="feature-icon accent">🏆</div>
                    <h3>Referral Rewards</h3>
                    <p>Earn bonus coins by referring friends and growing the IdeaOne community.</p>
                    <ul class="feature-list">
                        <li>Bonus coins per referral</li>
                        <li>Unique referral link</li>
                        <li>Unlimited referrals</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5" style="background: linear-gradient(180deg, var(--light-gray) 0%, var(--lighter) 100%);">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Simple Process</span>
                <h2>Start Earning in <span class="gradient-text">4 Easy Steps</span></h2>
                <p>From registration to payout — the entire process is straightforward and transparent.</p>
            </div>

            <div class="how-it-works-grid stagger-children">
                <div class="how-step animate-in">
                    <div class="how-step-number">1</div>
                    <h3>Create Account</h3>
                    <p>Register for free and instantly receive 6 bonus coins to start submitting your first idea.</p>
                </div>

                <div class="how-step animate-in">
                    <div class="how-step-number">2</div>
                    <h3>Submit Your Idea</h3>
                    <p>Choose a category, describe the problem you're solving and your solution in detail.</p>
                </div>

                <div class="how-step animate-in">
                    <div class="how-step-number">3</div>
                    <h3>Get Reviewed</h3>
                    <p>Our expert team evaluates your idea and approves it with an earning amount if accepted.</p>
                </div>

                <div class="how-step animate-in">
                    <div class="how-step-number">4</div>
                    <h3>Withdraw Earnings</h3>
                    <p>Once you have ₹500+ in your wallet, request a withdrawal and get paid to your bank.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Preview -->
    <section class="py-5">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Categories</span>
                <h2>Explore <span class="gradient-text">100+ Categories</span></h2>
                <p>Find the perfect category for your idea and maximize your earning potential.</p>
            </div>

            <div class="features-grid" style="grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));">
                <div class="card">
                    <div class="feature-icon primary" style="margin-bottom: 1rem;">📱</div>
                    <h3 class="card-title">Mobile Apps</h3>
                    <p class="text-success font-weight-bold" style="margin: 0.5rem 0;">₹500 – ₹5,000</p>
                    <p class="card-body" style="font-size: 0.875rem;">Innovative mobile application ideas that solve real problems.</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm" style="margin-top: 1rem; display: inline-block;">Explore →</a>
                </div>

                <div class="card">
                    <div class="feature-icon purple" style="margin-bottom: 1rem;">🤖</div>
                    <h3 class="card-title">AI & Machine Learning</h3>
                    <p class="text-success font-weight-bold" style="margin: 0.5rem 0;">₹3,000 – ₹20,000</p>
                    <p class="card-body" style="font-size: 0.875rem;">Artificial Intelligence and ML solutions for tomorrow's challenges.</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm" style="margin-top: 1rem; display: inline-block;">Explore →</a>
                </div>

                <div class="card">
                    <div class="feature-icon teal" style="margin-bottom: 1rem;">🔗</div>
                    <h3 class="card-title">Blockchain</h3>
                    <p class="text-success font-weight-bold" style="margin: 0.5rem 0;">₹5,000 – ₹25,000</p>
                    <p class="card-body" style="font-size: 0.875rem;">Decentralized and blockchain-powered innovations.</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm" style="margin-top: 1rem; display: inline-block;">Explore →</a>
                </div>

                <div class="card">
                    <div class="feature-icon success" style="margin-bottom: 1rem;">🌿</div>
                    <h3 class="card-title">Green Tech</h3>
                    <p class="text-success font-weight-bold" style="margin: 0.5rem 0;">₹2,000 – ₹15,000</p>
                    <p class="card-body" style="font-size: 0.875rem;">Sustainable and environmentally-friendly technology ideas.</p>
                    <a href="/pages/categories.php" class="btn btn-outline btn-sm" style="margin-top: 1rem; display: inline-block;">Explore →</a>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="/pages/categories.php" class="btn btn-primary btn-lg">Browse All 100+ Categories</a>
            </div>
        </div>
    </section>

    <!-- Testimonials / Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="section-header" style="color: var(--white);">
                <h2 style="color: var(--white);">Platform at a Glance</h2>
                <p style="color: rgba(255,255,255,0.7);">Numbers that speak for themselves</p>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">10,000+</div>
                    <div class="stat-label">Ideas Submitted</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5,200+</div>
                    <div class="stat-label">Active Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">₹10L+</div>
                    <div class="stat-label">Total Paid Out</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.8★</div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: var(--white); text-align: center; padding: 5rem 0;">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="hero-badge" style="margin: 0 auto 1.5rem; width: fit-content;">
                    🎁 Free signup bonus — 6 coins!
                </div>
                <h2 style="font-size: 2.5rem; font-weight: 800; color: var(--white); margin-bottom: 1rem; letter-spacing: -0.02em;">
                    Ready to Start Earning?
                </h2>
                <p style="color: rgba(255,255,255,0.85); font-size: 1.1rem; margin-bottom: 2.5rem; line-height: 1.7;">
                    Join thousands of students who are already turning their ideas into real money. Start with 6 free coins on signup.
                </p>
                <?php if (!$isLoggedIn): ?>
                    <div class="hero-cta" style="justify-content: center;">
                        <a href="/auth/register.php" class="btn-hero-primary">
                            Create Free Account
                        </a>
                        <a href="/auth/login.php" class="btn-hero-secondary">
                            Already a member? Login
                        </a>
                    </div>
                <?php else: ?>
                    <a href="/user/submit-idea.php" class="btn-hero-primary">
                        💡 Submit an Idea Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5">
        <div class="container">
            <div class="row" style="margin-bottom: 2rem;">
                <div class="col col-3">
                    <div class="logo" style="font-size: 1.25rem; margin-bottom: 0.75rem; display: block;">Idea<span style="color: rgba(255,255,255,0.5);">One</span></div>
                    <p style="font-size: 0.875rem; line-height: 1.6;">Empowering students to monetize their creativity through idea submissions.</p>
                </div>
                <div class="col col-3">
                    <p style="font-weight: 700; color: rgba(255,255,255,0.9); margin-bottom: 0.75rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Platform</p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="/pages/features.php">Features</a>
                        <a href="/pages/benefits.php">Benefits</a>
                        <a href="/pages/categories.php">Categories</a>
                        <a href="/pages/pricing.php">Pricing</a>
                    </div>
                </div>
                <div class="col col-3">
                    <p style="font-weight: 700; color: rgba(255,255,255,0.9); margin-bottom: 0.75rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Learn More</p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="/pages/earning.php">How It Works</a>
                        <a href="/pages/about.php">About Us</a>
                        <a href="/pages/contact.php">Contact</a>
                    </div>
                </div>
                <div class="col col-3">
                    <p style="font-weight: 700; color: rgba(255,255,255,0.9); margin-bottom: 0.75rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Legal</p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="/pages/privacy.php">Privacy Policy</a>
                        <a href="/pages/terms.php">Terms & Conditions</a>
                    </div>
                </div>
            </div>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem; text-align: center;">
                <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved. Made with ❤️ for students.</p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Intersection Observer for scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('.animate-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(el);
        });

        // Fetch public stats silently
        fetch('/api/public-stats.php')
            .then(r => r.json())
            .then(() => {})
            .catch(() => {});
    });
    </script>
</body>
</html>
