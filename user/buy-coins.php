<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Payment.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$payment = new Payment();

$message = $_GET['message'] ?? '';
$coinPackages = Payment::getCoinPackages();
$paymentEnabled = !empty(Config::get('RAZORPAY_KEY_ID')) && !empty(Config::get('RAZORPAY_KEY_SECRET'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Coins - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($paymentEnabled): ?>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span></a>
            <div class="nav-menu">
                <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
                <a href="/user/ideas.php" class="nav-link">My Ideas</a>
                <a href="/user/wallet.php" class="nav-link">Wallet</a>
                <a href="/user/messages.php" class="nav-link">Messages</a>
                <a href="/user/profile.php" class="nav-link">Profile</a>
            </div>
            <div class="auth-buttons">
                <span class="nav-link">
                    <span class="text-muted">Coins:</span>
                    <strong><?php echo $user['coins']; ?></strong>
                </span>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Buy Coins Section -->
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1>Buy Coins</h1>
            <p class="text-muted">Purchase coins to submit more ideas and maximize your earnings</p>
        </div>

        <!-- Current Balance -->
        <div class="alert alert-info text-center mb-4">
            <strong>Current Balance:</strong> <?php echo $user['coins']; ?> coins | 
            <strong>Submission Cost:</strong> 2 coins per idea
        </div>

        <?php if ($message === 'insufficient_coins'): ?>
            <div class="alert alert-danger mb-4">
                <strong>⚠️ Insufficient Coins</strong>
                <p>You don't have enough coins to submit an idea. Please purchase coins below.</p>
            </div>
        <?php endif; ?>

        <!-- Coin Packages -->
        <div class="dashboard-stats">
            <?php if (!$paymentEnabled): ?>
                <div class="alert alert-warning mb-4">
                    <strong>⚠️ Payment Gateway Not Configured</strong>
                    <p>The payment gateway is currently not available. Please contact the administrator or use free coins from referrals.</p>
                </div>
            <?php endif; ?>
            
            <?php foreach ($coinPackages as $index => $package): ?>
            <div class="card <?php echo $package['popular'] ? 'border-primary' : ''; ?>">
                <?php if ($package['popular']): ?>
                <div class="badge badge-primary" style="position: absolute; top: -10px; right: -10px;">Popular</div>
                <?php endif; ?>
                
                <div class="text-center">
                    <div class="stat-icon primary">🪙</div>
                    <h2 class="mb-2"><?php echo $package['coins']; ?> Coins</h2>
                    <p class="text-muted mb-2">
                        <?php if ($package['bonus'] > 0): ?>
                            +<?php echo $package['bonus']; ?> Bonus
                        <?php else: ?>
                            Starter Pack
                        <?php endif; ?>
                    </p>
                    <h3 class="mb-3">₹<?php echo $package['amount']; ?></h3>
                    
                    <?php if ($package['bonus'] > 0): ?>
                    <p class="text-success font-weight-bold mb-3">
                        Total: <?php echo $package['coins'] + $package['bonus']; ?> Coins
                    </p>
                    <?php endif; ?>
                    
                    <button 
                        onclick="buyPackage(<?php echo htmlspecialchars(json_encode($package)); ?>)"
                        class="btn <?php echo $package['popular'] ? 'btn-success' : 'btn-primary'; ?> btn-block"
                        <?php echo !$paymentEnabled ? 'disabled' : ''; ?>
                    >
                        <?php echo $paymentEnabled ? 'Buy Now' : 'Payment Disabled'; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Information -->
        <div class="card mt-5">
            <div class="card-header">
                <h3 class="card-title">Need Help?</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <strong>💰 How coins work:</strong> Each idea submission costs 2 coins. You'll get 6 free coins when you register!
                    </li>
                    <li class="mb-2">
                        <strong>💡 Idea submission:</strong> Submit your innovative ideas and get approved by our moderators to earn money.
                    </li>
                    <li class="mb-2">
                        <strong>🎁 Bonus coins:</strong> Some packages come with bonus coins for extra value.
                    </li>
                    <li class="mb-2">
                        <strong>💸 Earnings:</strong> Approved ideas earn money based on their potential and quality.
                    </li>
                    <li>
                        <strong>🔒 Secure payment:</strong> All payments are processed securely through Razorpay.
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function buyPackage(package) {
            const userId = <?php echo $user['id']; ?>;
            const totalCoins = package.coins + (package.bonus || 0);
            
            // Create order
            fetch('/api/create-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    amount: package.amount,
                    coins: totalCoins,
                    user_id: userId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Open Razorpay checkout
                    const options = {
                        key: result.key,
                        amount: result.amount * 100,
                        currency: 'INR',
                        name: 'IdeaOne',
                        description: `Buy ${totalCoins} Coins`,
                        order_id: result.order_id,
                        image: '/assets/images/logo.png',
                        handler: function(response) {
                            // Verify payment
                            fetch('/api/verify-payment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    order_id: response.razorpay_order_id,
                                    payment_id: response.razorpay_payment_id,
                                    signature: response.razorpay_signature
                                })
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    alert('Payment successful! Coins added to your wallet.');
                                    window.location.href = '/user/wallet.php';
                                } else {
                                    alert('Payment verification failed: ' + result.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Payment verification failed. Please contact support.');
                            });
                        },
                        prefill: {
                            name: '<?php echo htmlspecialchars($user['name']); ?>',
                            email: '<?php echo htmlspecialchars($user['email']); ?>',
                            contact: '<?php echo htmlspecialchars($user['mobile']); ?>'
                        },
                        theme: {
                            color: '#6366f1'
                        }
                    };
                    
                    const rzp = new Razorpay(options);
                    rzp.open();
                    
                    rzp.on('payment.failed', function(response) {
                        alert('Payment failed: ' + response.error.description);
                    });
                } else {
                    alert('Failed to create order: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create order. Please try again.');
            });
        }
    </script>
</body>
</html>