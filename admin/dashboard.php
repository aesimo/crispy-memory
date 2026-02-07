<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireRole('admin');

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get platform statistics
$stats = $db->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM users WHERE role = 'moderator') as total_moderators,
        (SELECT COUNT(*) FROM ideas) as total_ideas,
        (SELECT COUNT(*) FROM ideas WHERE status = 'pending') as pending_ideas,
        (SELECT COUNT(*) FROM ideas WHERE status = 'approved') as approved_ideas,
        (SELECT COUNT(*) FROM ideas WHERE status = 'rejected') as rejected_ideas,
        (SELECT COALESCE(SUM(wallet_balance), 0) FROM users) as total_user_balances,
        (SELECT COUNT(*) FROM withdrawals WHERE status = 'pending') as pending_withdrawals,
        (SELECT COALESCE(SUM(final_amount), 0) FROM withdrawals WHERE status = 'approved') as total_paid_out
");

// Get recent users
$recentUsers = $db->fetchAll("
    SELECT id, name, email, coins, wallet_balance, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");

// Get pending withdrawals
$pendingWithdrawals = $db->fetchAll("
    SELECT w.*, u.name as user_name, u.email as user_email
    FROM withdrawals w
    JOIN users u ON w.user_id = u.id
    WHERE w.status = 'pending'
    ORDER BY w.created_at DESC
    LIMIT 5
");

// Get recent ideas
$recentIdeas = $db->fetchAll("
    SELECT i.*, u.name as user_name, c.name as category_name
    FROM ideas i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN categories c ON i.category_id = c.id
    ORDER BY i.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" style="border-bottom: 3px solid var(--primary);">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span> <span class="badge badge-primary">Admin</span></a>
            <div class="nav-menu">
                <a href="/admin/dashboard.php" class="nav-link active">Dashboard</a>
                <a href="/admin/users.php" class="nav-link">Users</a>
                <a href="/admin/moderators.php" class="nav-link">Moderators</a>
                <a href="/admin/ideas.php" class="nav-link">Ideas</a>
                <a href="/admin/withdrawals.php" class="nav-link">Withdrawals</a>
                <a href="/admin/categories.php" class="nav-link">Categories</a>
                <a href="/admin/messages.php" class="nav-link">Broadcast</a>
                <a href="/admin/settings.php" class="nav-link">Settings</a>
            </div>
            <div class="auth-buttons">
                <span class="nav-link">Admin: <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: var(--white);">
        <div class="container">
            <h1 style="color: var(--white);">Admin Dashboard</h1>
            <p style="color: var(--white); opacity: 0.9;">Manage your IdeaOne platform</p>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container py-5">
        <!-- Platform Stats -->
        <div class="dashboard-stats mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">👥</div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">👨‍💼</div>
                <div class="stat-value"><?php echo $stats['total_moderators']; ?></div>
                <div class="stat-label">Moderators</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon primary">💡</div>
                <div class="stat-value"><?php echo $stats['total_ideas']; ?></div>
                <div class="stat-label">Total Ideas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">⏳</div>
                <div class="stat-value"><?php echo $stats['pending_ideas']; ?></div>
                <div class="stat-label">Pending Ideas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">✅</div>
                <div class="stat-value"><?php echo $stats['approved_ideas']; ?></div>
                <div class="stat-label">Approved Ideas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger">❌</div>
                <div class="stat-value"><?php echo $stats['rejected_ideas']; ?></div>
                <div class="stat-label">Rejected Ideas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon primary">💰</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_user_balances'], 2); ?></div>
                <div class="stat-label">User Balances</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">💸</div>
                <div class="stat-value"><?php echo $stats['pending_withdrawals']; ?></div>
                <div class="stat-label">Pending Withdrawals</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">💵</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_paid_out'], 2); ?></div>
                <div class="stat-label">Total Paid Out</div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($stats['pending_withdrawals'] > 0): ?>
        <div class="alert alert-warning mb-4">
            <strong>⚠️ Action Required:</strong> 
            <?php echo $stats['pending_withdrawals']; ?> withdrawal request(s) pending approval
            <a href="/admin/withdrawals.php" class="btn btn-primary btn-sm ml-2">Review Now</a>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Pending Withdrawals -->
            <?php if (!empty($pendingWithdrawals)): ?>
            <div class="col col-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row justify-between">
                            <h3 class="card-title">💸 Pending Withdrawals</h3>
                            <a href="/admin/withdrawals.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingWithdrawals as $withdrawal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($withdrawal['user_name']); ?></td>
                                        <td>₹<?php echo number_format($withdrawal['final_amount'], 2); ?></td>
                                        <td><?php echo date('M d', strtotime($withdrawal['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Users -->
            <div class="col col-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row justify-between">
                            <h3 class="card-title">👥 Recent Users</h3>
                            <a href="/admin/users.php" class="btn btn-outline btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Balance</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $recentUser): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($recentUser['name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $recentUser['role'] === 'admin' ? 'primary' : ($recentUser['role'] === 'moderator' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($recentUser['role']); ?>
                                            </span>
                                        </td>
                                        <td>₹<?php echo number_format($recentUser['wallet_balance'], 2); ?></td>
                                        <td><?php echo date('M d', strtotime($recentUser['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Ideas -->
        <div class="card">
            <div class="card-header">
                <div class="row justify-between">
                    <h3 class="card-title">💡 Recent Ideas</h3>
                    <a href="/admin/ideas.php" class="btn btn-outline btn-sm">View All</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>User</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentIdeas as $idea): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($idea['title'], 0, 30)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($idea['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($idea['category_name']); ?></td>
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
                                <td><?php echo date('M d', strtotime($idea['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved. | Admin Panel</p>
        </div>
    </footer>
</body>
</html>