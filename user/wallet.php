<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$transactions = $db->fetchAll("
    SELECT id, type, amount, coins, status, payment_id, description, created_at
    FROM wallet_transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT 50
", [$user['id']]);

$withdrawals = $db->fetchAll("
    SELECT id, amount, fee, final_amount, status, admin_note, created_at, processed_at
    FROM withdrawals 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT 20
", [$user['id']]);

$stats = $db->fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN type IN ('purchase', 'signup_bonus', 'idea_earning', 'referral_bonus') THEN coins ELSE 0 END), 0) as total_coins_earned,
        COALESCE(SUM(CASE WHEN type = 'idea_submission' THEN coins ELSE 0 END), 0) as total_coins_spent,
        COALESCE(SUM(CASE WHEN status = 'completed' AND type = 'withdrawal' THEN amount ELSE 0 END), 0) as total_withdrawn
    FROM wallet_transactions 
    WHERE user_id = ?
", [$user['id']]);

$pendingWithdrawals = $db->fetchOne("
    SELECT COUNT(*) as count, COALESCE(SUM(final_amount), 0) as amount
    FROM withdrawals 
    WHERE user_id = ? AND status = 'pending'
", [$user['id']]);

$typeLabels = [
    'signup_bonus'   => '🎁 Signup Bonus',
    'referral_bonus' => '👥 Referral Bonus',
    'purchase'       => '🛒 Coin Purchase',
    'idea_submission'=> '💡 Idea Submission',
    'idea_earning'   => '💰 Idea Earning',
    'withdrawal'     => '💸 Withdrawal',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <span class="coin-badge">🪙 <?php echo $user['coins']; ?></span>
                <a href="/user/buy-coins.php" class="btn btn-success btn-sm">Buy Coins</a>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>My Wallet 💰</h1>
            <p>Manage your earnings, transactions, and withdrawals</p>
        </div>
    </div>

    <div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">

        <!-- Wallet Stats -->
        <div class="dashboard-stats stagger-children" style="margin-bottom: 0.5rem;">
            <div class="stat-card animate-in">
                <div class="stat-icon primary">💰</div>
                <div class="stat-value">₹<?php echo number_format($user['wallet_balance'], 2); ?></div>
                <div class="stat-label">Wallet Balance</div>
            </div>
            <div class="stat-card animate-in">
                <div class="stat-icon success">🪙</div>
                <div class="stat-value"><?php echo $user['coins']; ?></div>
                <div class="stat-label">Available Coins</div>
            </div>
            <div class="stat-card animate-in">
                <div class="stat-icon warning">📈</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_withdrawn'], 2); ?></div>
                <div class="stat-label">Total Withdrawn</div>
            </div>
            <div class="stat-card animate-in">
                <div class="stat-icon primary">🏅</div>
                <div class="stat-value"><?php echo $stats['total_coins_earned']; ?></div>
                <div class="stat-label">Coins Earned</div>
            </div>
        </div>

        <?php if ($pendingWithdrawals['count'] > 0): ?>
        <div class="alert alert-warning mt-4 animate-in">
            <strong>⏳ Pending Withdrawal:</strong>
            <?php echo $pendingWithdrawals['count']; ?> request(s) totaling
            ₹<?php echo number_format($pendingWithdrawals['amount'], 2); ?> — processing within 1-3 business days.
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card mb-4 animate-in">
            <div class="card-header">
                <h3 class="card-title">⚡ Quick Actions</h3>
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
                        <button class="btn btn-outline btn-block" disabled style="cursor: not-allowed; opacity: 0.6;">
                            💸 Withdraw (Need ₹500 min)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Withdrawal Info -->
        <div class="card mb-4 animate-in">
            <div class="card-header">
                <h3 class="card-title">ℹ️ Withdrawal Policy</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="padding: 1rem; background: var(--lighter); border-radius: var(--radius-lg); text-align: center;">
                    <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">₹500</div>
                    <div style="font-size: 0.8rem; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Minimum</div>
                </div>
                <div style="padding: 1rem; background: var(--lighter); border-radius: var(--radius-lg); text-align: center;">
                    <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">2%</div>
                    <div style="font-size: 0.8rem; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Platform Fee</div>
                </div>
                <div style="padding: 1rem; background: var(--lighter); border-radius: var(--radius-lg); text-align: center;">
                    <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">1–3</div>
                    <div style="font-size: 0.8rem; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Business Days</div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="card mb-4 animate-in">
            <div class="card-header">
                <h3 class="card-title">Transaction History</h3>
                <span class="badge badge-info"><?php echo count($transactions); ?> records</span>
            </div>
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No transactions yet</h3>
                    <p>Your transaction history will appear here once you start submitting ideas or buying coins.</p>
                    <a href="/user/buy-coins.php" class="btn btn-primary">Buy Coins</a>
                </div>
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
                                <td style="font-weight: 500;">
                                    <?php echo $typeLabels[$transaction['type']] ?? ucfirst(str_replace('_', ' ', $transaction['type'])); ?>
                                </td>
                                <td>
                                    <?php if ($transaction['coins'] != 0): ?>
                                        <span style="font-weight: 600;" class="<?php echo $transaction['coins'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $transaction['coins'] > 0 ? '+' : ''; ?><?php echo $transaction['coins']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transaction['amount'] != 0): ?>
                                        <span style="font-weight: 600;">₹<?php echo number_format($transaction['amount'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td style="color: var(--gray); font-size: 0.85rem;">
                                    <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
                                    <br><small><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Withdrawal History -->
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">Withdrawal History</h3>
                <?php if (!empty($withdrawals)): ?>
                    <span class="badge badge-info"><?php echo count($withdrawals); ?> requests</span>
                <?php endif; ?>
            </div>
            <?php if (empty($withdrawals)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💸</div>
                    <h3>No withdrawals yet</h3>
                    <p>Once you reach ₹500 in your wallet, you can request a withdrawal.</p>
                    <?php if ($user['wallet_balance'] >= 500): ?>
                        <a href="/user/withdraw.php" class="btn btn-success">Request Withdrawal</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Fee (2%)</th>
                                <th>You Receive</th>
                                <th>Status</th>
                                <th>Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr>
                                <td>₹<?php echo number_format($withdrawal['amount'], 2); ?></td>
                                <td class="text-muted">₹<?php echo number_format($withdrawal['fee'], 2); ?></td>
                                <td><strong class="text-success">₹<?php echo number_format($withdrawal['final_amount'], 2); ?></strong></td>
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
                                    <?php if ($withdrawal['admin_note']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($withdrawal['admin_note']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--gray); font-size: 0.85rem;">
                                    <?php echo date('M d, Y', strtotime($withdrawal['created_at'])); ?>
                                    <?php if ($withdrawal['processed_at']): ?>
                                        <br><small class="text-success">Processed: <?php echo date('M d, Y', strtotime($withdrawal['processed_at'])); ?></small>
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

    <!-- Footer -->
    <footer class="py-4 text-center" style="background: var(--dark); color: rgba(255,255,255,0.5);">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.05 });

        document.querySelectorAll('.animate-in').forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(16px)';
            el.style.transition = `opacity 0.4s ease ${i * 0.06}s, transform 0.4s ease ${i * 0.06}s`;
            observer.observe(el);
        });
    });
    </script>
</body>
</html>
