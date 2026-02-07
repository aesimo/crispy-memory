<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireRole('moderator');

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$ideaId = $_GET['id'] ?? 0;

// Get idea details
$idea = $db->fetchOne("
    SELECT i.*, u.name as user_name, u.email as user_email, c.name as category_name, c.estimated_earning
    FROM ideas i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.id = ?
", [$ideaId]);

if (!$idea || $idea['status'] !== 'pending') {
    header('Location: /moderator/ideas.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db->beginTransaction();
        
        if ($action === 'approve') {
            $approvedAmount = floatval($_POST['approved_amount'] ?? 0);
            $moderatorNote = trim($_POST['moderator_note'] ?? '');
            
            if ($approvedAmount <= 0) {
                throw new Exception('Approved amount must be greater than 0');
            }
            
            // Update idea status
            $db->execute(
                "UPDATE ideas SET status = 'approved', approved_amount = ?, moderator_note = ?, updated_at = NOW() WHERE id = ?",
                [$approvedAmount, $moderatorNote, $ideaId]
            );
            
            // Add coins to user wallet
            $db->execute(
                "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?",
                [$approvedAmount, $idea['user_id']]
            );
            
            // Create wallet transaction
            $db->execute(
                "INSERT INTO wallet_transactions (user_id, type, amount, coins, status, description, created_at) 
                 VALUES (?, 'idea_earning', ?, 0, 'completed', ?, NOW())",
                [$idea['user_id'], $approvedAmount, 'Approved idea: ' . $idea['title']]
            );
            
            // Send message to user
            $db->execute(
                "INSERT INTO messages (sender_id, receiver_id, subject, content, read_status, created_at) 
                 VALUES (?, ?, ?, ?, FALSE, NOW())",
                [$user['id'], $idea['user_id'], 'Idea Approved!', 
                 'Congratulations! Your idea "' . $idea['title'] . '" has been approved. Amount: ₹' . number_format($approvedAmount, 2) . 
                 ($moderatorNote ? '\n\nModerator Note: ' . $moderatorNote : '')]
            );
            
            $success = 'Idea approved successfully! User has been notified.';
            
        } elseif ($action === 'reject') {
            $rejectionReason = trim($_POST['rejection_reason'] ?? '');
            
            if (empty($rejectionReason)) {
                throw new Exception('Please provide a rejection reason');
            }
            
            // Update idea status
            $db->execute(
                "UPDATE ideas SET status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?",
                [$rejectionReason, $ideaId]
            );
            
            // Send message to user
            $db->execute(
                "INSERT INTO messages (sender_id, receiver_id, subject, content, read_status, created_at) 
                 VALUES (?, ?, ?, ?, FALSE, NOW())",
                [$user['id'], $idea['user_id'], 'Idea Not Approved', 
                 'Your idea "' . $idea['title'] . '" was not approved.\n\nReason: ' . $rejectionReason]
            );
            
            $success = 'Idea rejected successfully. User has been notified.';
        }
        
        $db->commit();
        
        // Redirect after delay
        header('refresh:2;url=/moderator/ideas.php');
        
    } catch (Exception $e) {
        $db->rollback();
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Idea - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" style="border-bottom: 3px solid var(--warning);">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span> <span class="badge badge-warning">Moderator</span></a>
            <div class="nav-menu">
                <a href="/moderator/dashboard.php" class="nav-link">Dashboard</a>
                <a href="/moderator/ideas.php" class="nav-link">All Ideas</a>
                <a href="/moderator/reviewed.php" class="nav-link">Review History</a>
            </div>
            <div class="auth-buttons">
                <span class="nav-link">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Review Section -->
    <div class="container py-5">
        <div class="mb-4">
            <a href="/moderator/ideas.php" class="text-muted">← Back to Ideas List</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-4">
                <?php echo htmlspecialchars($success); ?>
                <p>Redirecting...</p>
            </div>
        <?php else: ?>

        <!-- Idea Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Idea Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col col-6">
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($idea['title']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($idea['category_name']); ?></p>
                        <p><strong>Estimated Earning:</strong> <?php echo htmlspecialchars($idea['estimated_earning']); ?></p>
                        <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($idea['user_name']); ?></p>
                        <p><strong>Submitted On:</strong> <?php echo date('M d, Y - g:i A', strtotime($idea['created_at'])); ?></p>
                    </div>
                    <div class="col col-6">
                        <?php if ($idea['file_path']): ?>
                        <p><strong>📎 Document:</strong> 
                            <a href="/uploads/ideas/<?php echo htmlspecialchars($idea['file_path']); ?>" target="_blank" class="btn btn-outline btn-sm">
                                View Document
                            </a>
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($idea['prototype_path']): ?>
                        <p><strong>🎬 Prototype:</strong> 
                            <a href="/uploads/prototypes/<?php echo htmlspecialchars($idea['prototype_path']); ?>" target="_blank" class="btn btn-outline btn-sm">
                                View Prototype
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="form-group">
                    <label class="form-label"><strong>Problem Statement:</strong></label>
                    <div class="card" style="background: var(--light-gray);">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($idea['problem'])); ?>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label class="form-label"><strong>Proposed Solution:</strong></label>
                    <div class="card" style="background: var(--light-gray);">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($idea['solution'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Actions -->
        <div class="row">
            <!-- Approve Form -->
            <div class="col col-6">
                <div class="card" style="border: 2px solid var(--secondary);">
                    <div class="card-header" style="background: #d1fae5;">
                        <h3 class="card-title" style="color: #065f46;">✅ Approve Idea</h3>
                    </div>
                    <form action="review.php?id=<?php echo $ideaId; ?>" method="POST">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label" for="approved_amount">Approved Amount (₹) *</label>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="approved_amount" 
                                    name="approved_amount" 
                                    placeholder="Enter amount"
                                    min="1"
                                    step="0.01"
                                    required
                                >
                                <small class="form-text">Estimated: <?php echo $idea['estimated_earning']; ?></small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="moderator_note">Moderator Note (Optional)</label>
                                <textarea 
                                    class="form-control" 
                                    id="moderator_note" 
                                    name="moderator_note" 
                                    rows="3"
                                    placeholder="Add any feedback or notes for the user..."
                                ></textarea>
                                <small class="form-text">This note will be sent to the user along with the approval.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-block">
                                Approve & Credit User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reject Form -->
            <div class="col col-6">
                <div class="card" style="border: 2px solid var(--danger);">
                    <div class="card-header" style="background: #fee2e2;">
                        <h3 class="card-title" style="color: #991b1b;">❌ Reject Idea</h3>
                    </div>
                    <form action="review.php?id=<?php echo $ideaId; ?>" method="POST">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label" for="rejection_reason">Rejection Reason *</label>
                                <textarea 
                                    class="form-control" 
                                    id="rejection_reason" 
                                    name="rejection_reason" 
                                    rows="5"
                                    placeholder="Explain why this idea is not approved. Be constructive and helpful..."
                                    required
                                ></textarea>
                                <small class="form-text">This reason will be sent to the user. Be specific and helpful.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger btn-block">
                                Reject Idea
                            </button>
                        </div>
                    </form>
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