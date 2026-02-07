<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get user statistics
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

// Get unread messages count
$unreadMessages = $db->fetchOne("
    SELECT COUNT(*) as count 
    FROM messages 
    WHERE receiver_id = ? AND read_status = FALSE
", [$user['id']]);

// Get recent ideas
$recentIdeas = $db->fetchAll("
    SELECT id, title, category_id, status, approved_amount, created_at
    FROM ideas 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$user['id']]);

// Get recent transactions
$recentTransactions = $db->fetchAll("
    SELECT type, amount, coins, status, created_at
    FROM wallet_transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
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
                <span class="nav-link">
                    <span class="text-muted">Coins:</span>
                    <strong><?php echo $user['coins']; ?></strong>
                </span>
                <a href="/user/buy-coins.php" class="btn btn-success btn-sm">Buy Coins</a>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p class="text-muted">Here's what's happening with your ideas</p>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container">
        <!-- Stats Cards -->
        <div class="dashboard-stats">
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
                <div class="stat-icon warning">📊</div>
                <div class="stat-value"><?php echo $stats['total_ideas']; ?></div>
                <div class="stat-label">Total Ideas</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon primary">✅</div>
                <div class="stat-value"><?php echo $stats['approved_ideas']; ?></div>
                <div class="stat-label">Approved Ideas</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">⏳</div>
                <div class="stat-value"><?php echo $stats['pending_ideas']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">💵</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_earnings'], 2); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="row">
                <div class="col col-4">
                    <a href="/user/submit-idea.php" class="btn btn-primary btn-block">
                        💡 Submit New Idea (2 Coins)
                    </a>
                </div>
                <div class="col col-4">
                    <a href="/user/buy-coins.php" class="btn btn-success btn-block">
                        🪙 Buy Coins
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
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Ideas</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentIdeas)): ?>
                            <p class="text-muted">No ideas submitted yet. <a href="/user/submit-idea.php">Submit your first idea!</a></p>
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
                                            <td><?php echo htmlspecialchars(substr($idea['title'], 0, 30)) . (strlen($idea['title']) > 30 ? '...' : ''); ?></td>
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
                                            <td><?php echo date('M d, Y', strtotime($idea['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="/user/ideas.php" class="btn btn-outline btn-sm">View All Ideas</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Transactions</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentTransactions)): ?>
                            <p class="text-muted">No transactions yet.</p>
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
                                            <td><?php echo ucfirst(str_replace('_', ' ', $transaction['type'])); ?></td>
                                            <td>
                                                <?php if ($transaction['coins'] > 0): ?>
                                                    <span class="text-success">+<?php echo $transaction['coins']; ?> Coins</span>
                                                <?php else: ?>
                                                    <span>₹<?php echo number_format($transaction['amount'], 2); ?></span>
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
                            <div class="text-center mt-3">
                                <a href="/user/wallet.php" class="btn btn-outline btn-sm">View Wallet</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unread Messages Alert -->
        <?php if ($unreadMessages['count'] > 0): ?>
        <div class="alert alert-info mt-4">
            <strong>📬 You have <?php echo $unreadMessages['count']; ?> unread message(s)</strong>
            <a href="/user/messages.php" class="btn btn-primary btn-sm ml-2">View Messages</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted mt-5">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>