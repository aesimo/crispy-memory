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
                
                <div id="pricing-container" class="pricing-grid">
                    <div class="text-center">
                        <p>Loading pricing...</p>
                    </div>
                </div>
            </div>

            <!-- Usage Pricing -->
            <div class="mb-5">
                <h2 class="text-center mb-4">Platform Usage</h2>
                <div id="usage-container" class="usage-grid">
                    <div class="text-center">
                        <p>Loading usage information...</p>
                    </div>
                </div>
            </div>

            <!-- Withdrawal Fees -->
            <div>
                <h2 class="text-center mb-4">Withdrawal Fees</h2>
                <div id="fees-container" class="fees-table-container">
                    <p class="text-center">Loading fees...</p>
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

    <script>
    let pricingData = null;
    let isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    document.addEventListener('DOMContentLoaded', function() {
        fetch('/api/public-pricing.php')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    pricingData = result.data;
                    displayPricing();
                    displayUsage();
                    displayWithdrawalFees();
                } else {
                    displayError('Failed to load pricing information');
                }
            })
            .catch(error => {
                console.error('Error fetching pricing:', error);
                displayError('Failed to load pricing information');
            });
    });

    function displayPricing() {
        const container = document.getElementById('pricing-container');
        
        if (!pricingData || !pricingData.coin_packages) {
            container.innerHTML = '<p class="text-center">No pricing available at this time.</p>';
            return;
        }
        
        const packageNames = {
            10: 'Starter',
            25: 'Basic',
            50: 'Standard',
            100: 'Premium',
            200: 'Ultimate'
        };
        
        let html = '';
        pricingData.coin_packages.forEach((pkg, index) => {
            const name = packageNames[pkg.coins] || `${pkg.coins} Coins`;
            const submissions = Math.floor(pkg.coins / 2);
            const isPopular = pkg.popular;
            const totalCoins = pkg.coins + pkg.bonus;
            
            html += `
                <div class="pricing-card ${isPopular ? 'featured' : ''}">
                    ${isPopular ? '<div class="badge badge-warning">Popular</div>' : ''}
                    <div class="pricing-header">
                        <h3>${escapeHtml(name)}</h3>
                        <div class="price">₹${pkg.amount}</div>
                        <div class="coins">${totalCoins} Coins ${pkg.bonus > 0 ? `(${pkg.bonus} bonus)` : ''}</div>
                    </div>
                    <ul class="pricing-features">
                        <li>✓ ${totalCoins} Total Coins</li>
                        <li>✓ ${submissions} Idea Submissions</li>
                        <li>✓ ${isPopular ? 'Priority Support' : 'Standard Support'}</li>
                        <li>✓ ${isPopular ? 'Advanced Analytics' : 'Basic Analytics'}</li>
                        ${pkg.bonus > 0 ? `<li>✓ ${pkg.bonus} Bonus Coins</li>` : ''}
                    </ul>
                    <div class="pricing-footer">
                        ${isLoggedIn 
                            ? `<a href="/user/buy-coins.php?package=${name.toLowerCase()}" class="btn ${isPopular ? 'btn-primary' : 'btn-outline'} btn-block">Buy Now</a>`
                            : `<a href="/auth/register.php" class="btn ${isPopular ? 'btn-primary' : 'btn-outline'} btn-block">Get Started</a>`
                        }
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function displayUsage() {
        const container = document.getElementById('usage-container');
        
        if (!pricingData || !pricingData.usage_pricing) {
            container.innerHTML = '<p class="text-center">No usage information available.</p>';
            return;
        }
        
        const usage = pricingData.usage_pricing;
        
        container.innerHTML = `
            <div class="usage-item">
                <h4>Idea Submission</h4>
                <div class="usage-price">${usage.submission_cost} Coins</div>
                <p class="text-muted">Cost per idea submission</p>
            </div>
            <div class="usage-item">
                <h4>Signup Bonus</h4>
                <div class="usage-price">${usage.signup_bonus} Coins</div>
                <p class="text-muted">Free coins on registration</p>
            </div>
            <div class="usage-item">
                <h4>Referral Bonus</h4>
                <div class="usage-price">${usage.referral_bonus} Coins</div>
                <p class="text-muted">Bonus per successful referral</p>
            </div>
            <div class="usage-item">
                <h4>Minimum Withdrawal</h4>
                <div class="usage-price">₹${usage.minimum_withdrawal}</div>
                <p class="text-muted">Minimum wallet balance</p>
            </div>
        `;
    }

    function displayWithdrawalFees() {
        const container = document.getElementById('fees-container');
        
        if (!pricingData || !pricingData.withdrawal_fees) {
            container.innerHTML = '<p class="text-center">No fee information available.</p>';
            return;
        }
        
        const fees = pricingData.withdrawal_fees;
        
        let html = '<table class="fees-table"><thead><tr><th>Withdrawal Amount</th><th>Processing Fee</th><th>You Receive</th><th>Processing Time</th></tr></thead><tbody>';
        
        fees.forEach(fee => {
            const range = fee.max 
                ? `₹${fee.min.toLocaleString()} - ₹${fee.max.toLocaleString()}`
                : `₹${fee.min.toLocaleString()}+`;
            const youReceive = fee.max 
                ? `Amount - ₹${fee.fee}`
                : `Amount - ₹${fee.fee}`;
            
            html += `
                <tr>
                    <td>${escapeHtml(range)}</td>
                    <td>₹${fee.fee}</td>
                    <td>${escapeHtml(youReceive)}</td>
                    <td>${escapeHtml(fee.time)}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function displayError(message) {
        document.getElementById('pricing-container').innerHTML = `<p class="text-center text-danger">${escapeHtml(message)}</p>`;
        document.getElementById('usage-container').innerHTML = '';
        document.getElementById('fees-container').innerHTML = '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>