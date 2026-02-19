<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Mark messages as read when viewed
$db->execute(
    "UPDATE messages SET read_status = TRUE WHERE receiver_id = ? AND read_status = FALSE",
    [$user['id']]
);

// Get messages
$messages = $db->fetchAll("
    SELECT m.*, u.name as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
    LIMIT 50
", [$user['id']]);

// Get unread count
$unreadCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND read_status = FALSE",
    [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - IdeaOne</title>
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
                <a href="/user/messages.php" class="nav-link active">Messages</a>
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
            <h1>Messages 📬</h1>
            <p>Important updates and notifications about your ideas</p>
        </div>
    </div>

    <!-- Messages Section -->
    <div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">

        <?php if (empty($messages)): ?>
            <div class="card text-center py-5">
                <h3>No Messages</h3>
                <p class="text-muted">You don't have any messages yet. We'll notify you about important updates here!</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($messages as $message): ?>
                <div class="col col-12">
                    <div class="card <?php echo $message['read_status'] ? '' : 'border-primary'; ?>">
                        <div class="card-header">
                            <div class="row justify-between">
                                <div>
                                    <h3 class="card-title"><?php echo htmlspecialchars($message['subject']); ?></h3>
                                    <p class="text-muted mb-0">
                                        From: <?php echo htmlspecialchars($message['sender_name']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <?php if (!$message['read_status']): ?>
                                    <span class="badge badge-primary">New</span>
                                    <?php endif; ?>
                                    <p class="text-muted mb-0">
                                        <?php echo date('M d, Y - g:i A', strtotime($message['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center" style="background: var(--dark); color: rgba(255,255,255,0.5);">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>