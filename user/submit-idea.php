<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/FileUpload.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$submissionCost = 2; // Cost to submit an idea

// Check if user has enough coins
if ($user['coins'] < $submissionCost) {
    header('Location: /user/buy-coins.php?message=insufficient_coins');
    exit;
}

// Get categories
$categories = $db->fetchAll("SELECT id, name, estimated_earning FROM categories ORDER BY name ASC");

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['category_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $problem = trim($_POST['problem'] ?? '');
    $solution = trim($_POST['solution'] ?? '');
    
    // Validate required fields
    if (empty($categoryId) || empty($title) || empty($problem) || empty($solution)) {
        $error = 'Please fill in all required fields';
    } elseif (strlen($title) < 10) {
        $error = 'Title must be at least 10 characters';
    } elseif (strlen($problem) < 50) {
        $error = 'Problem description must be at least 50 characters';
    } elseif (strlen($solution) < 50) {
        $error = 'Solution description must be at least 50 characters';
    } else {
        try {
            $db->beginTransaction();
            
            // Handle file upload
            $filePath = null;
            if (isset($_FILES['idea_file']) && $_FILES['idea_file']['error'] === UPLOAD_ERR_OK) {
                $fileUpload = new FileUpload(__DIR__ . '/../uploads/ideas');
                $uploadResult = $fileUpload->upload($_FILES['idea_file'], 'user_' . $user['id']);
                
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                
                $filePath = $uploadResult['filename'];
            }
            
            // Handle prototype upload
            $prototypePath = null;
            if (isset($_FILES['prototype_file']) && $_FILES['prototype_file']['error'] === UPLOAD_ERR_OK) {
                $fileUpload = new FileUpload(__DIR__ . '/../uploads/prototypes');
                $uploadResult = $fileUpload->upload($_FILES['prototype_file'], 'user_' . $user['id']);
                
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                
                $prototypePath = $uploadResult['filename'];
            }
            
            // Insert idea
            $db->execute(
                "INSERT INTO ideas (user_id, category_id, title, problem, solution, file_path, prototype_path, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
                [$user['id'], $categoryId, $title, $problem, $solution, $filePath, $prototypePath]
            );
            
            // Deduct coins
            $db->execute(
                "UPDATE users SET coins = coins - ? WHERE id = ?",
                [$submissionCost, $user['id']]
            );
            
            // Create transaction record
            $db->execute(
                "INSERT INTO wallet_transactions (user_id, type, coins, status, created_at) 
                 VALUES (?, 'idea_submission', ?, 'completed', NOW())",
                [$user['id'], $submissionCost]
            );
            
            $db->commit();
            
            // Update session user data
            $_SESSION['user']['coins'] -= $submissionCost;
            
            $success = 'Idea submitted successfully! It will be reviewed by our moderators.';
            
            // Redirect after delay
            header('refresh:3;url=/user/ideas.php');
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Failed to submit idea: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Idea - IdeaOne</title>
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

    <!-- Submit Idea Section -->
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1>Submit Your Idea</h1>
            <p class="text-muted">Share your innovative idea and start earning</p>
        </div>

        <!-- Coin Balance Alert -->
        <div class="alert alert-info mb-4">
            <strong>💰 Current Balance:</strong> <?php echo $user['coins']; ?> coins | 
            <strong>💡 Submission Cost:</strong> <?php echo $submissionCost; ?> coins
            <?php if ($user['coins'] >= $submissionCost): ?>
                <span class="text-success">✓ You have enough coins to submit</span>
            <?php else: ?>
                <span class="text-danger">✗ Insufficient coins</span>
                <a href="/user/buy-coins.php" class="btn btn-primary btn-sm">Buy Coins</a>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <p>Redirecting to your ideas page...</p>
            </div>
        <?php else: ?>

        <div class="card">
            <form action="submit-idea.php" method="POST" enctype="multipart/form-data">
                <!-- Category Selection -->
                <div class="form-group">
                    <label class="form-label" for="category_id">Category *</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?> 
                            (<?php echo htmlspecialchars($category['estimated_earning']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text">Choose the category that best fits your idea</small>
                </div>

                <!-- Title -->
                <div class="form-group">
                    <label class="form-label" for="title">Idea Title *</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="title" 
                        name="title" 
                        placeholder="Give your idea a catchy title"
                        required
                        minlength="10"
                    >
                    <small class="form-text">Minimum 10 characters. Make it descriptive and engaging.</small>
                </div>

                <!-- Problem -->
                <div class="form-group">
                    <label class="form-label" for="problem">Problem Statement *</label>
                    <textarea 
                        class="form-control" 
                        id="problem" 
                        name="problem" 
                        rows="5"
                        placeholder="Describe the problem your idea solves..."
                        required
                        minlength="50"
                    ></textarea>
                    <small class="form-text">Minimum 50 characters. Explain what problem exists and why it needs to be solved.</small>
                </div>

                <!-- Solution -->
                <div class="form-group">
                    <label class="form-label" for="solution">Your Solution *</label>
                    <textarea 
                        class="form-control" 
                        id="solution" 
                        name="solution" 
                        rows="5"
                        placeholder="Describe how your idea solves the problem..."
                        required
                        minlength="50"
                    ></textarea>
                    <small class="form-text">Minimum 50 characters. Explain your solution and how it works.</small>
                </div>

                <!-- File Upload -->
                <div class="form-group">
                    <label class="form-label" for="idea_file">Supporting Document (Optional)</label>
                    <input 
                        type="file" 
                        class="form-control" 
                        id="idea_file" 
                        name="idea_file"
                        accept=".pdf,.docx,.pptx"
                    >
                    <small class="form-text">Upload PDF, DOCX, or PPTX file (max 10MB). Include detailed information about your idea.</small>
                </div>

                <!-- Prototype Upload -->
                <div class="form-group">
                    <label class="form-label" for="prototype_file">Prototype/Media (Optional)</label>
                    <input 
                        type="file" 
                        class="form-control" 
                        id="prototype_file" 
                        name="prototype_file"
                        accept=".jpg,.jpeg,.png,.mp4"
                    >
                    <small class="form-text">Upload image or video of your prototype (max 10MB).</small>
                </div>

                <!-- Terms Agreement -->
                <div class="form-group">
                    <label class="d-flex align-center">
                        <input type="checkbox" required>
                        <span class="ml-2">
                            I agree that once I submit this idea, full ownership transfers to IdeaOne. 
                            I will not reuse, sell, publish, distribute, or reproduce this idea elsewhere. 
                            I understand that coins are non-refundable.
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    💡 Submit Idea (<?php echo $submissionCost; ?> Coins)
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="/user/dashboard.php" class="text-muted">Cancel and return to dashboard</a>
            </div>
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