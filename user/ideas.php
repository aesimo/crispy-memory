<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Build query
$sql = "SELECT i.*, c.name as category_name, c.estimated_earning 
        FROM ideas i 
        LEFT JOIN categories c ON i.category_id = c.id 
        WHERE i.user_id = ?";

$params = [$user['id']];

if ($statusFilter !== 'all') {
    $sql .= " AND i.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY i.created_at DESC";

$ideas = $db->fetchAll($sql, $params);

// Get statistics
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM ideas WHERE user_id = ?
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ideas - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span></a>
            <div class="nav-menu">
                <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
                <a href="/user/ideas.php" class="nav-link active">My Ideas</a>
                <a href="/user/wallet.php" class="nav-link">Wallet</a>
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

    <!-- Ideas Page -->
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1>My Ideas</h1>
            <p class="text-muted">Track all your submitted ideas and their status</p>
        </div>

        <!-- Stats -->
        <div class="dashboard-stats mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">📊</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Ideas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">✅</div>
                <div class="stat-value"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">⏳</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger">❌</div>
                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- Filter and Action -->
        <div class="card mb-4">
            <div class="row justify-between align-center">
                <div class="col col-6">
                    <div class="form-group">
                        <label class="form-label">Filter by Status:</label>
                        <select class="form-control" onchange="window.location.href='?status=' + this.value">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Ideas</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="col col-6 text-right">
                    <a href="/user/submit-idea.php" class="btn btn-primary">
                        💡 Submit New Idea
                    </a>
                </div>
            </div>
        </div>

        <!-- Ideas List -->
        <?php if (empty($ideas)): ?>
            <div class="card text-center py-5">
                <h3>No Ideas Yet</h3>
                <p class="text-muted mb-3">You haven't submitted any ideas yet. Start by submitting your first idea!</p>
                <a href="/user/submit-idea.php" class="btn btn-primary">Submit Your First Idea</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($ideas as $idea): ?>
                <div class="col col-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="row justify-between">
                                <div>
                                    <h3 class="card-title"><?php echo htmlspecialchars($idea['title']); ?></h3>
                                    <p class="text-muted mb-2">
                                        <?php echo htmlspecialchars($idea['category_name']); ?>
                                    </p>
                                </div>
                                <div>
                                    <?php
                                    $badgeClass = 'info';
                                    if ($idea['status'] === 'approved') $badgeClass = 'success';
                                    elseif ($idea['status'] === 'rejected') $badgeClass = 'danger';
                                    elseif ($idea['status'] === 'pending') $badgeClass = 'warning';
                                    ?>
                                    <span class="badge badge-<?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($idea['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Problem:</strong> 
                                <?php echo htmlspecialchars(substr($idea['problem'], 0, 100)) . (strlen($idea['problem']) > 100 ? '...' : ''); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Solution:</strong> 
                                <?php echo htmlspecialchars(substr($idea['solution'], 0, 100)) . (strlen($idea['solution']) > 100 ? '...' : ''); ?>
                            </p>
                            <p class="text-muted mb-2">
                                Submitted: <?php echo date('M d, Y - g:i A', strtotime($idea['created_at'])); ?>
                            </p>
                            
                            <?php if ($idea['status'] === 'approved'): ?>
                            <div class="alert alert-success">
                                <strong>💰 Approved Amount:</strong> ₹<?php echo number_format($idea['approved_amount'], 2); ?>
                                <?php if ($idea['moderator_note']): ?>
                                <br><strong>Note:</strong> <?php echo htmlspecialchars($idea['moderator_note']); ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($idea['status'] === 'rejected'): ?>
                            <div class="alert alert-danger">
                                <strong>❌ Rejection Reason:</strong> <?php echo htmlspecialchars($idea['rejection_reason']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($idea['file_path']): ?>
                            <p class="mb-2">
                                <strong>📎 File:</strong> 
                                <a href="/uploads/ideas/<?php echo htmlspecialchars($idea['file_path']); ?>" target="_blank">
                                    View Document
                                </a>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($idea['prototype_path']): ?>
                            <p class="mb-2">
                                <strong>🎬 Prototype:</strong> 
                                <a href="/uploads/prototypes/<?php echo htmlspecialchars($idea['prototype_path']); ?>" target="_blank">
                                    View Prototype
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>