<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireRole('moderator');

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get pending ideas for review
$pendingIdeas = $db->fetchAll("
    SELECT i.*, u.name as user_name, c.name as category_name
    FROM ideas i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.status = 'pending'
    ORDER BY i.created_at DESC
    LIMIT 20
");

// Get statistics
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_pending,
        COUNT(CASE WHEN created_at > NOW() - INTERVAL '24 hours' THEN 1 END) as today_submissions
    FROM ideas 
    WHERE status = 'pending'
");

// Get recently reviewed ideas
$recentlyReviewed = $db->fetchAll("
    SELECT i.*, u.name as user_name, c.name as category_name
    FROM ideas i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.status IN ('approved', 'rejected')
    AND i.updated_at > NOW() - INTERVAL '7 days'
    ORDER BY i.updated_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" style="border-bottom: 3px solid var(--warning);">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span> <span class="badge badge-warning">Moderator</span></a>
            <div class="nav-menu">
                <a href="/moderator/dashboard.php" class="nav-link active">Dashboard</a>
                <a href="/moderator/ideas.php" class="nav-link">All Ideas</a>
                <a href="/moderator/reviewed.php" class="nav-link">Review History</a>
            </div>
            <div class="auth-buttons">
                <span class="nav-link">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header" style="background: linear-gradient(135deg, var(--warning) 0%, #f97316 100%); color: var(--white);">
        <div class="container">
            <h1 style="color: var(--white);">Moderator Dashboard</h1>
            <p style="color: var(--white); opacity: 0.9;">Review and manage submitted ideas</p>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container py-5">
        <!-- Stats -->
        <div class="dashboard-stats mb-4">
            <div class="stat-card">
                <div class="stat-icon warning">⏳</div>
                <div class="stat-value"><?php echo $stats['total_pending']; ?></div>
                <div class="stat-label">Pending Ideas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon primary">📅</div>
                <div class="stat-value"><?php echo $stats['today_submissions']; ?></div>
                <div class="stat-label">Today's Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">✅</div>
                <div class="stat-value"><?php echo count($recentlyReviewed); ?></div>
                <div class="stat-label">Reviewed This Week</div>
            </div>
        </div>

        <!-- Pending Ideas Alert -->
        <?php if ($stats['total_pending'] > 0): ?>
        <div class="alert alert-warning mb-4">
            <strong>⚠️ You have <?php echo $stats['total_pending']; ?> pending idea(s) to review</strong>
            <a href="/moderator/ideas.php" class="btn btn-primary btn-sm ml-2">Review Now</a>
        </div>
        <?php endif; ?>

        <!-- Pending Ideas Section -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="row justify-between">
                    <h3 class="card-title">📋 Pending Ideas for Review</h3>
                    <a href="/moderator/ideas.php" class="btn btn-primary btn-sm">View All</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($pendingIdeas)): ?>
                    <p class="text-muted text-center py-3">No pending ideas to review. Great job!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Submitted By</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingIdeas as $idea): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($idea['title']); ?></td>
                                    <td><?php echo htmlspecialchars($idea['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($idea['user_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($idea['created_at'])); ?></td>
                                    <td>
                                        <a href="/moderator/review.php?id=<?php echo $idea['id']; ?>" class="btn btn-primary btn-sm">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recently Reviewed Section -->
        <?php if (!empty($recentlyReviewed)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 Recently Reviewed Ideas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Reviewed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentlyReviewed as $idea): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($idea['title']); ?></td>
                                <td><?php echo htmlspecialchars($idea['category_name']); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = $idea['status'] === 'approved' ? 'success' : 'danger';
                                    ?>
                                    <span class="badge badge-<?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($idea['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($idea['status'] === 'approved'): ?>
                                        ₹<?php echo number_format($idea['approved_amount'], 2); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($idea['updated_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved. | Moderator Panel</p>
        </div>
    </footer>
</body>
</html>