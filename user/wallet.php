<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get wallet transactions
$transactions = $db->fetchAll("
    SELECT id, type, amount, coins, status, payment_id, description, created_at
    FROM wallet_transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT 50
", [$user['id']]);

// Get withdrawals
$withdrawals = $db->fetchAll("
    SELECT id, amount, fee, final_amount, status, admin_note, created_at, processed_at
    FROM withdrawals 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT 20
", [$user['id']]);

// Calculate statistics
$stats = $db->fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN type IN ('purchase', 'signup_bonus', 'idea_earning') THEN coins ELSE 0 END), 0) as total_coins_earned,
        COALESCE(SUM(CASE WHEN type = 'idea_submission' THEN coins ELSE 0 END), 0) as total_coins_spent,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_withdrawn
    FROM wallet_transactions 
    WHERE user_id = ?
", [$user['id']]);

$pendingWithdrawals = $db->fetchOne("
    SELECT COUNT(*) as count, COALESCE(SUM(final_amount), 0) as amount
    FROM withdrawals 
    WHERE user_id = ? AND status = 'pending'
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - IdeaOne</title>
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
                <a href="/user/wallet.php" class="nav-link active">Wallet</a>
                <a href="/user/messages.php" class="nav-link">Messages</a>
                <a href="/user/profile.php" class="nav-link">Profile</a>
            </div>
            <div class="auth-buttons">
                <span class="nav-link">
                    <span class="text-muted">Coins:</span>
                    <strong><?php echo $user['coins']; ?></strong>
                </span>
                <a href="/user/buy-coins.php" class="btn btn-success btn-sm">Buy Coins</a>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Wallet Section -->
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1>My Wallet</h1>
            <p class="text-muted">Manage your earnings and transactions</p>
        </div>

        <!-- Wallet Stats -->
        <div class="dashboard-stats mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">💰</div>
                <div class="stat-value">₹<?php echo number_format($user['wallet_balance'], 2); ?></div>
                <div class="stat-label">Wallet Balance</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">🪙</div>
                <div class="stat-value"><?php echo $user['coins']; ?></div>
                <div class="stat-label">Available Coins</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">📈</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_withdrawn'], 2); ?></div>
                <div class="stat-label">Total Withdrawn</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon primary">📊</div>
                <div class="stat-value"><?php echo $stats['total_coins_earned']; ?></div>
                <div class="stat-label">Coins Earned</div>
            </div>
        </div>

        <!-- Pending Withdrawals Alert -->
        <?php if ($pendingWithdrawals['count'] > 0): ?>
        <div class="alert alert-info mb-4">
            <strong>⏳ Pending Withdrawals:</strong> 
            <?php echo $pendingWithdrawals['count']; ?> request(s) totaling ₹<?php echo number_format($pendingWithdrawals['amount'], 2); ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="row">
                <div class="col col-6">
                    <a href="/user/buy-coins.php" class="btn btn-primary btn-block">
                        🪙 Buy More Coins
                    </a>
                </div>
                <div class="col col-6">
                    <?php if ($user['wallet_balance'] >= 500): ?>
                    <a href="/user/withdraw.php" class="btn btn-success btn-block">
                        💸 Request Withdrawal
                    </a>
                    <?php else: ?>
                    <button class="btn btn-outline btn-block" disabled>
                        💸 Withdraw (Min ₹500)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Withdrawal Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Withdrawal Information</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <strong>Minimum Withdrawal:</strong> ₹500
                    </li>
                    <li class="mb-2">
                        <strong>Platform Fee:</strong> 2% (deducted from withdrawal amount)
                    </li>
                    <li class="mb-2">
                        <strong>Processing Time:</strong> 1-3 business days
                    </li>
                    <li>
                        <strong>Note:</strong> Withdrawals are processed manually by admin. You'll receive a notification when approved.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Transaction History</h3>
            </div>
            <div class="card-body">
                <?php if (empty($transactions)): ?>
                    <p class="text-muted">No transactions yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Coins</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $typeLabels = [
                                            'signup_bonus' => 'Signup Bonus',
                                            'purchase' => 'Coin Purchase',
                                            'idea_submission' => 'Idea Submission',
                                            'idea_earning' => 'Idea Earning',
                                            'withdrawal' => 'Withdrawal'
                                        ];
                                        echo $typeLabels[$transaction['type']] ?? ucfirst(str_replace('_', ' ', $transaction['type']));
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($transaction['coins'] != 0): ?>
                                            <span class="<?php echo $transaction['coins'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $transaction['coins'] > 0 ? '+' : ''; ?><?php echo $transaction['coins']; ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($transaction['amount'] != 0): ?>
                                            ₹<?php echo number_format($transaction['amount'], 2); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y - g:i A', strtotime($transaction['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Withdrawal History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Withdrawal History</h3>
            </div>
            <div class="card-body">
                <?php if (empty($withdrawals)): ?>
                    <p class="text-muted">No withdrawal requests yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Fee (2%)</th>
                                    <th>Final Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                <tr>
                                    <td>₹<?php echo number_format($withdrawal['amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($withdrawal['fee'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($withdrawal['final_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php
                                        $badgeClass = 'info';
                                        if ($withdrawal['status'] === 'approved') $badgeClass = 'success';
                                        elseif ($withdrawal['status'] === 'rejected') $badgeClass = 'danger';
                                        elseif ($withdrawal['status'] === 'pending') $badgeClass = 'warning';
                                        ?>
                                        <span class="badge badge-<?php echo $badgeClass; ?>">
                                            <?php echo ucfirst($withdrawal['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($withdrawal['created_at'])); ?>
                                        <?php if ($withdrawal['processed_at']): ?>
                                            <br><small class="text-muted">Processed: <?php echo date('M d, Y', strtotime($withdrawal['processed_at'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>