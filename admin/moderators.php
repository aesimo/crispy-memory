<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireRole('admin');

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get all moderators
$moderators = $db->fetchAll("
    SELECT * FROM users 
    WHERE role = 'moderator' 
    ORDER BY created_at DESC
");

// Get users who can be promoted to moderator
$potentialModerators = $db->fetchAll("
    SELECT * FROM users 
    WHERE role = 'user' AND email_verified = TRUE
    ORDER BY created_at DESC
    LIMIT 20
");

// Get moderator activity stats
$modStats = [];
foreach ($moderators as $mod) {
    $stats = $db->fetchOne("
        SELECT 
            COUNT(*) as total_reviews,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM ideas 
        WHERE status IN ('approved', 'rejected')
        AND updated_at > NOW() - INTERVAL '7 days'
        AND user_id IN (
            SELECT user_id FROM activity_logs 
            WHERE action = 'idea_approved' OR action = 'idea_rejected'
            LIMIT 1000
        )
    ");
    $modStats[$mod['id']] = $stats;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Management - IdeaOne Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" style="border-bottom: 3px solid var(--primary);">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span> <span class="badge badge-primary">Admin</span></a>
            <div class="nav-menu">
                <a href="/admin/dashboard.php" class="nav-link">Dashboard</a>
                <a href="/admin/users.php" class="nav-link">Users</a>
                <a href="/admin/moderators.php" class="nav-link active">Moderators</a>
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

    <!-- Moderator Management Section -->
    <div class="container py-5">
        <div class="mb-4">
            <h1>Moderator Management</h1>
            <p class="text-muted">Manage platform moderators</p>
        </div>

        <!-- Current Moderators -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Current Moderators</h3>
            </div>
            <div class="card-body">
                <?php if (empty($moderators)): ?>
                    <p class="text-muted">No moderators assigned yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Reviews (7 days)</th>
                                    <th>Approved</th>
                                    <th>Rejected</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moderators as $moderator): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($moderator['name']); ?></td>
                                    <td><?php echo htmlspecialchars($moderator['email']); ?></td>
                                    <td><?php echo htmlspecialchars($moderator['mobile']); ?></td>
                                    <td><?php echo $modStats[$moderator['id']]['total_reviews'] ?? 0; ?></td>
                                    <td><?php echo $modStats[$moderator['id']]['approved'] ?? 0; ?></td>
                                    <td><?php echo $modStats[$moderator['id']]['rejected'] ?? 0; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($moderator['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="removeModerator(<?php echo $moderator['id']; ?>)">Remove</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add New Moderator -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add New Moderator</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Select a verified user to promote to moderator role:</p>
                
                <?php if (empty($potentialModerators)): ?>
                    <p class="text-muted">No verified users available for promotion.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Coins</th>
                                    <th>Ideas Submitted</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($potentialModerators as $potential): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($potential['name']); ?></td>
                                    <td><?php echo htmlspecialchars($potential['email']); ?></td>
                                    <td><?php echo $potential['coins']; ?></td>
                                    <td><?php echo $db->fetchOne("SELECT COUNT(*) as count FROM ideas WHERE user_id = ?", [$potential['id']])['count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($potential['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-success btn-sm" onclick="addModerator(<?php echo $potential['id']; ?>, '<?php echo htmlspecialchars($potential['name']); ?>')">Make Moderator</button>
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
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved. | Admin Panel</p>
        </div>
    </footer>

    <script>
        function addModerator(userId, userName) {
            if (confirm('Are you sure you want to make ' + userName + ' a moderator?')) {
                fetch('/api/admin/add-moderator.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert(result.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }

        function removeModerator(userId) {
            if (confirm('Are you sure you want to remove this moderator? They will be demoted to regular user.')) {
                fetch('/api/admin/remove-moderator.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert(result.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }
    </script>
</body>
</html>