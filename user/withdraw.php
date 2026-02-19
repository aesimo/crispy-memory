<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$minWithdrawal = 500;
$platformFee = 0.02; // 2%

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    
    if ($amount < $minWithdrawal) {
        $error = 'Minimum withdrawal amount is ₹' . $minWithdrawal;
    } elseif ($amount > $user['wallet_balance']) {
        $error = 'Insufficient wallet balance';
    } else {
        try {
            $db->beginTransaction();
            
            $fee = $amount * $platformFee;
            $finalAmount = $amount - $fee;
            
            // Create withdrawal request
            $db->execute(
                "INSERT INTO withdrawals (user_id, amount, fee, final_amount, status, created_at) 
                 VALUES (?, ?, ?, ?, 'pending', NOW())",
                [$user['id'], $amount, $fee, $finalAmount]
            );
            
            // Deduct from wallet
            $db->execute(
                "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?",
                [$amount, $user['id']]
            );
            
            // Create transaction record
            $db->execute(
                "INSERT INTO wallet_transactions (user_id, type, amount, coins, status, description, created_at) 
                 VALUES (?, 'withdrawal', ?, 0, 'pending', 'Withdrawal request: ₹' . number_format($finalAmount, 2), NOW())",
                [$user['id'], $amount]
            );
            
            $db->commit();
            
            // Update session
            $_SESSION['user']['wallet_balance'] -= $amount;
            
            $success = 'Withdrawal request submitted successfully! Amount will be processed within 1-3 business days.';
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Failed to submit withdrawal request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Withdrawal - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
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
                <span class="coin-badge">🪙 <?php echo $user['coins']; ?></span>
                <a href="/user/buy-coins.php" class="btn btn-success btn-sm">Buy Coins</a>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Withdraw Section -->
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1>Request Withdrawal</h1>
            <p class="text-muted">Withdraw your earnings securely</p>
        </div>

        <!-- Balance Card -->
        <div class="card mb-4" style="border: 2px solid var(--secondary);">
            <div class="text-center">
                <h3 class="card-title mb-2">Available Balance</h3>
                <div class="stat-value" style="color: var(--secondary);">₹<?php echo number_format($user['wallet_balance'], 2); ?></div>
                <p class="text-muted">Minimum withdrawal: ₹<?php echo $minWithdrawal; ?></p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-4">
                <?php echo htmlspecialchars($success); ?>
                <p class="mt-2"><a href="/user/wallet.php" class="btn btn-primary">View Wallet</a></p>
            </div>
        <?php else: ?>

        <!-- Withdrawal Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Withdrawal Details</h3>
            </div>
            <form action="withdraw.php" method="POST">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="amount">Withdrawal Amount (₹) *</label>
                        <input 
                            type="number" 
                            class="form-control" 
                            id="amount" 
                            name="amount" 
                            placeholder="Enter amount to withdraw"
                            min="<?php echo $minWithdrawal; ?>"
                            max="<?php echo $user['wallet_balance']; ?>"
                            step="0.01"
                            required
                        >
                        <small class="form-text">
                            Maximum: ₹<?php echo number_format($user['wallet_balance'], 2); ?>
                        </small>
                    </div>

                    <div class="card" style="background: var(--light-gray);">
                        <div class="card-body">
                            <div class="row">
                                <div class="col col-6">
                                    <p class="mb-2"><strong>Withdrawal Amount:</strong></p>
                                    <p class="mb-2"><strong>Platform Fee (2%):</strong></p>
                                    <p class="mb-0"><strong>You'll Receive:</strong></p>
                                </div>
                                <div class="col col-6 text-right">
                                    <p class="mb-2" id="display-amount">₹0.00</p>
                                    <p class="mb-2 text-danger" id="display-fee">-₹0.00</p>
                                    <p class="mb-0 text-success" style="font-size: 1.5rem;" id="display-final">₹0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <strong>ℹ️ Important Information:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Withdrawal requests are processed within 1-3 business days</li>
                            <li>Platform fee of 2% is deducted from the withdrawal amount</li>
                            <li>Admin will review and approve your request</li>
                            <li>Amount will be credited to your registered bank account</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block btn-lg">
                        💸 Submit Withdrawal Request
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="/user/wallet.php" class="text-muted">← Back to Wallet</a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const amountInput = document.getElementById('amount');
        const displayAmount = document.getElementById('display-amount');
        const displayFee = document.getElementById('display-fee');
        const displayFinal = document.getElementById('display-final');
        const platformFee = <?php echo $platformFee; ?>;

        amountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const fee = amount * platformFee;
            const final = amount - fee;

            displayAmount.textContent = '₹' + amount.toFixed(2);
            displayFee.textContent = '-₹' + fee.toFixed(2);
            displayFinal.textContent = '₹' + final.toFixed(2);
        });
    </script>
</body>
</html>