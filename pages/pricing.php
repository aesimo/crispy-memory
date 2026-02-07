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
    <meta name="description" content="View IdeaOne's transparent pricing structure for coin purchases and withdrawals.">
    <title>Pricing - IdeaOne</title>
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
                <a href="/pages/pricing.php" class="nav-link active">Pricing</a>
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
            <h1>Transparent Pricing</h1>
            <p class="text-muted">Simple, affordable pricing for everyone</p>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-5">
        <div class="container">
            <!-- Coin Pricing -->
            <div class="mb-5">
                <h2 class="text-center mb-4">Coin Packages</h2>
                <p class="text-center text-muted mb-4">Buy coins to submit more ideas. The more you buy, the more you save!</p>
                
                <div class="pricing-grid">
                    <!-- Basic -->
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3>Starter</h3>
                            <div class="price">₹99</div>
                            <div class="coins">10 Coins</div>
                        </div>
                        <ul class="pricing-features">
                            <li>✓ 10 Coins</li>
                            <li>✓ 5 Idea Submissions</li>
                            <li>✓ Standard Support</li>
                            <li>✓ Basic Analytics</li>
                        </ul>
                        <div class="pricing-footer">
                            <?php if ($isLoggedIn): ?>
                                <a href="/user/buy-coins.php?package=starter" class="btn btn-outline btn-block">Buy Now</a>
                            <?php else: ?>
                                <a href="/auth/register.php" class="btn btn-outline btn-block">Get Started</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Standard -->
                    <div class="pricing-card featured">
                        <div class="badge badge-warning">Popular</div>
                        <div class="pricing-header">
                            <h3>Standard</h3>
                            <div class="price">₹249</div>
                            <div class="coins">30 Coins</div>
                        </div>
                        <ul class="pricing-features">
                            <li>✓ 30 Coins</li>
                            <li>✓ 15 Idea Submissions</li>
                            <li>✓ Priority Support</li>
                            <li>✓ Advanced Analytics</li>
                            <li>✓ 3 Bonus Coins</li>
                        </ul>
                        <div class="pricing-footer">
                            <?php if ($isLoggedIn): ?>
                                <a href="/user/buy-coins.php?package=standard" class="btn btn-primary btn-block">Buy Now</a>
                            <?php else: ?>
                                <a href="/auth/register.php" class="btn btn-primary btn-block">Get Started</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Premium -->
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3>Premium</h3>
                            <div class="price">₹499</div>
                            <div class="coins">70 Coins</div>
                        </div>
                        <ul class="pricing-features">
                            <li>✓ 70 Coins</li>
                            <li>✓ 35 Idea Submissions</li>
                            <li>✓ VIP Support</li>
                            <li>✓ Premium Analytics</li>
                            <li>✓ 10 Bonus Coins</li>
                            <li>✓ Early Access</li>
                        </ul>
                        <div class="pricing-footer">
                            <?php if ($isLoggedIn): ?>
                                <a href="/user/buy-coins.php?package=premium" class="btn btn-outline btn-block">Buy Now</a>
                            <?php else: ?>
                                <a href="/auth/register.php" class="btn btn-outline btn-block">Get Started</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Pricing -->
            <div class="mb-5">
                <h2 class="text-center mb-4">Platform Usage</h2>
                <div class="usage-grid">
                    <div class="usage-item">
                        <h4>Idea Submission</h4>
                        <div class="usage-price">2 Coins</div>
                        <p class="text-muted">Cost per idea submission</p>
                    </div>
                    <div class="usage-item">
                        <h4>Signup Bonus</h4>
                        <div class="usage-price">6 Coins</div>
                        <p class="text-muted">Free coins on registration</p>
                    </div>
                    <div class="usage-item">
                        <h4>Referral Bonus</h4>
                        <div class="usage-price">3 Coins</div>
                        <p class="text-muted">Bonus per successful referral</p>
                    </div>
                    <div class="usage-item">
                        <h4>Minimum Withdrawal</h4>
                        <div class="usage-price">₹500</div>
                        <p class="text-muted">Minimum wallet balance</p>
                    </div>
                </div>
            </div>

            <!-- Withdrawal Fees -->
            <div>
                <h2 class="text-center mb-4">Withdrawal Fees</h2>
                <div class="fees-table-container">
                    <table class="fees-table">
                        <thead>
                            <tr>
                                <th>Withdrawal Amount</th>
                                <th>Processing Fee</th>
                                <th>You Receive</th>
                                <th>Processing Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>₹500 - ₹999</td>
                                <td>₹25</td>
                                <td>Amount - ₹25</td>
                                <td>24-48 hours</td>
                            </tr>
                            <tr>
                                <td>₹1,000 - ₹4,999</td>
                                <td>₹50</td>
                                <td>Amount - ₹50</td>
                                <td>24-48 hours</td>
                            </tr>
                            <tr>
                                <td>₹5,000 - ₹9,999</td>
                                <td>₹75</td>
                                <td>Amount - ₹75</td>
                                <td>24-48 hours</td>
                            </tr>
                            <tr>
                                <td>₹10,000+</td>
                                <td>₹100</td>
                                <td>Amount - ₹100</td>
                                <td>24-48 hours</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 class="text-center mb-4">Frequently Asked Questions</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <h4>How do I earn coins?</h4>
                    <p>You get 6 free coins on signup, 3 coins for each successful referral, and you can purchase more coin packages.</p>
                </div>
                <div class="faq-item">
                    <h4>How much can I earn per idea?</h4>
                    <p>Earnings vary based on idea quality and potential, typically ranging from ₹50 to ₹500 per approved idea.</p>
                </div>
                <div class="faq-item">
                    <h4>When can I withdraw my earnings?</h4>
                    <p>You can withdraw once your wallet balance reaches ₹500 or more.</p>
                </div>
                <div class="faq-item">
                    <h4>What payment methods are available?</h4>
                    <p>We support bank transfers, UPI payments, and other popular payment methods for withdrawals.</p>
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
                    <a href="/user/buy-coins.php" class="btn btn-lg btn-success">Buy Coins Now</a>
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