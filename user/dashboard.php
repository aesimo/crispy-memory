<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_ideas,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_ideas,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_ideas,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_ideas,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN approved_amount ELSE 0 END), 0) as total_earnings
    FROM ideas 
    WHERE user_id = ?
", [$user['id']]);

$unreadMessages = $db->fetchOne("
    SELECT COUNT(*) as count 
    FROM messages 
    WHERE receiver_id = ? AND read_status = FALSE
", [$user['id']]);

$recentIdeas = $db->fetchAll("
    SELECT id, title, category_id, status, approved_amount, created_at
    FROM ideas 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$user['id']]);

$recentTransactions = $db->fetchAll("
    SELECT type, amount, coins, status, created_at
    FROM wallet_transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$user['id']]);

$welcomeGoogle = isset($_GET['welcome']) && $_GET['welcome'] === 'google';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IdeaOne</title>
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
                <a href="/user/dashboard.php" class="nav-link active">Dashboard</a>
                <a href="/user/ideas.php" class="nav-link">My Ideas</a>
                <a href="/user/wallet.php" class="nav-link">Wallet</a>
                <a href="/user/messages.php" class="nav-link">
                    Messages
                    <?php if ($unreadMessages['count'] > 0): ?>
                        <span class="badge badge-danger"><?php echo $unreadMessages['count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/user/profile.php" class="nav-link">Profile</a>
            </div>
            <div class="auth-buttons">
                <span class="coin-badge">🪙 <?php echo $user['coins']; ?></span>
                <a href="/user/buy-coins.php" class="btn btn-success btn-sm">Buy Coins</a>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
            <p>Here's what's happening with your ideas today</p>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container" style="padding-top: 0.5rem; padding-bottom: 3rem;">

        <?php if ($welcomeGoogle): ?>
        <div class="alert alert-success mt-4">
            🎉 Successfully signed in with Google! Welcome to IdeaOne.
        </div>
        <?php endif; ?>

        <?php if ($unreadMessages['count'] > 0): ?>
        <div class="alert alert-info mt-4">
            <div>
                <strong>📬 You have <?php echo $unreadMessages['count']; ?> unread message(s)</strong>
                <br><small>Check your messages for updates on your idea submissions.</small>
            </div>
            <a href="/user/messages.php" class="btn btn-primary btn-sm" style="margin-left: auto; white-space: nowrap;">View Messages</a>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="dashboard-stats stagger-children">
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
                <div class="stat-icon warning">📊</div>
                <div class="stat-value"><?php echo $stats['total_ideas']; ?></div>
                <div class="stat-label">Total Ideas</div>
            </div>

            <div class="stat-card animate-in">
                <div class="stat-icon success">✅</div>
                <div class="stat-value"><?php echo $stats['approved_ideas']; ?></div>
                <div class="stat-label">Approved</div>
            </div>

            <div class="stat-card animate-in">
                <div class="stat-icon warning">⏳</div>
                <div class="stat-value"><?php echo $stats['pending_ideas']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>

            <div class="stat-card animate-in">
                <div class="stat-icon primary">💵</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_earnings'], 2); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4 animate-in">
            <div class="card-header">
                <h3 class="card-title">⚡ Quick Actions</h3>
            </div>
            <div class="row">
                <div class="col col-4">
                    <a href="/user/submit-idea.php" class="btn btn-primary btn-block">
                        💡 Submit New Idea <small style="opacity:0.8;">(2 Coins)</small>
                    </a>
                </div>
                <div class="col col-4">
                    <a href="/user/buy-coins.php" class="btn btn-success btn-block">
                        🪙 Buy More Coins
                    </a>
                </div>
                <div class="col col-4">
                    <a href="/user/withdraw.php" class="btn btn-outline btn-block">
                        💸 Withdraw Earnings
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Ideas & Transactions -->
        <div class="row">
            <div class="col col-6">
                <div class="card animate-in">
                    <div class="card-header">
                        <h3 class="card-title">Recent Ideas</h3>
                        <a href="/user/ideas.php" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentIdeas)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">💡</div>
                                <h3>No ideas yet</h3>
                                <p>Start submitting your first idea and begin earning!</p>
                                <a href="/user/submit-idea.php" class="btn btn-primary">Submit First Idea</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentIdeas as $idea): ?>
                                        <tr>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars(substr($idea['title'], 0, 28)) . (strlen($idea['title']) > 28 ? '…' : ''); ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = 'info';
                                                if ($idea['status'] === 'approved') $badgeClass = 'success';
                                                elseif ($idea['status'] === 'rejected') $badgeClass = 'danger';
                                                elseif ($idea['status'] === 'pending') $badgeClass = 'warning';
                                                ?>
                                                <span class="badge badge-<?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst($idea['status']); ?>
                                                </span>
                                            </td>
                                            <td style="color: var(--gray); font-size: 0.85rem;"><?php echo date('M d', strtotime($idea['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col col-6">
                <div class="card animate-in">
                    <div class="card-header">
                        <h3 class="card-title">Recent Transactions</h3>
                        <a href="/user/wallet.php" class="btn btn-outline btn-sm">View Wallet</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentTransactions)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">💳</div>
                                <h3>No transactions yet</h3>
                                <p>Your transaction history will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                        <tr>
                                            <td style="font-size: 0.875rem;"><?php echo ucfirst(str_replace('_', ' ', $transaction['type'])); ?></td>
                                            <td>
                                                <?php if ($transaction['coins'] > 0): ?>
                                                    <span class="text-success" style="font-weight: 600;">+<?php echo $transaction['coins']; ?> 🪙</span>
                                                <?php elseif ($transaction['amount'] > 0): ?>
                                                    <span style="font-weight: 600;">₹<?php echo number_format($transaction['amount'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-danger" style="font-weight: 600;"><?php echo $transaction['coins']; ?> 🪙</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
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
