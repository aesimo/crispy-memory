<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireRole('admin');

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (name ILIKE ? OR email ILIKE ? OR mobile ILIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($role) {
    $sql .= " AND role = ?";
    $params[] = $role;
}

if ($status === 'verified') {
    $sql .= " AND email_verified = TRUE";
} elseif ($status === 'unverified') {
    $sql .= " AND email_verified = FALSE";
}

$sql .= " ORDER BY created_at DESC LIMIT 50";

$users = $db->fetchAll($sql, $params);

// Get statistics
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'user' THEN 1 END) as regular_users,
        COUNT(CASE WHEN role = 'moderator' THEN 1 END) as moderators,
        COUNT(CASE WHEN email_verified = TRUE THEN 1 END) as verified_users,
        COUNT(CASE WHEN created_at > NOW() - INTERVAL '7 days' THEN 1 END) as new_users_this_week
    FROM users
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - IdeaOne Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" style="border-bottom: 3px solid var(--primary);">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span> <span class="badge badge-primary">Admin</span></a>
            <div class="nav-menu">
                <a href="/admin/dashboard.php" class="nav-link">Dashboard</a>
                <a href="/admin/users.php" class="nav-link active">Users</a>
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

    <!-- User Management Section -->
    <div class="container py-5">
        <div class="mb-4">
            <h1>User Management</h1>
            <p class="text-muted">Manage all platform users</p>
        </div>

        <!-- Stats -->
        <div class="dashboard-stats mb-4">
            <div class="stat-card">
                <div class="stat-icon primary">👥</div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">✅</div>
                <div class="stat-value"><?php echo $stats['verified_users']; ?></div>
                <div class="stat-label">Verified Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">👨‍💼</div>
                <div class="stat-value"><?php echo $stats['moderators']; ?></div>
                <div class="stat-label">Moderators</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon primary">🆕</div>
                <div class="stat-value"><?php echo $stats['new_users_this_week']; ?></div>
                <div class="stat-label">New This Week</div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="card mb-4">
            <form action="users.php" method="GET">
                <div class="row">
                    <div class="col col-4">
                        <div class="form-group">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, or mobile">
                        </div>
                    </div>
                    <div class="col col-3">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role">
                                <option value="">All Roles</option>
                                <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="moderator" <?php echo $role === 'moderator' ? 'selected' : ''; ?>>Moderator</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="col col-3">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                <option value="unverified" <?php echo $status === 'unverified' ? 'selected' : ''; ?>>Unverified</option>
                            </select>
                        </div>
                    </div>
                    <div class="col col-2" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary btn-block">Search</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Role</th>
                            <th>Verified</th>
                            <th>Coins</th>
                            <th>Balance</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userItem): ?>
                        <tr>
                            <td>#<?php echo $userItem['id']; ?></td>
                            <td><?php echo htmlspecialchars($userItem['name']); ?></td>
                            <td><?php echo htmlspecialchars($userItem['email']); ?></td>
                            <td><?php echo htmlspecialchars($userItem['mobile']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $userItem['role'] === 'admin' ? 'primary' : ($userItem['role'] === 'moderator' ? 'warning' : 'info'); ?>">
                                    <?php echo ucfirst($userItem['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($userItem['email_verified']): ?>
                                    <span class="badge badge-success">✓ Email</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">✗</span>
                                <?php endif; ?>
                                <?php if ($userItem['sms_verified']): ?>
                                    <span class="badge badge-success">✓ SMS</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">✗</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $userItem['coins']; ?></td>
                            <td>₹<?php echo number_format($userItem['wallet_balance'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($userItem['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-outline btn-sm" onclick="viewUser(<?php echo $userItem['id']; ?>)">View</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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