<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/FileUpload.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$submissionCost = 2;

if ($user['coins'] < $submissionCost) {
    header('Location: /user/buy-coins.php?message=insufficient_coins');
    exit;
}

$categories = $db->fetchAll("SELECT id, name, estimated_earning FROM categories ORDER BY name ASC");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['category_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $problem = trim($_POST['problem'] ?? '');
    $solution = trim($_POST['solution'] ?? '');

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

            $filePath = null;
            if (isset($_FILES['idea_file']) && $_FILES['idea_file']['error'] === UPLOAD_ERR_OK) {
                $fileUpload = new FileUpload(__DIR__ . '/../uploads/ideas');
                $uploadResult = $fileUpload->upload($_FILES['idea_file'], 'user_' . $user['id']);

                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }

                $filePath = $uploadResult['filename'];
            }

            $prototypePath = null;
            if (isset($_FILES['prototype_file']) && $_FILES['prototype_file']['error'] === UPLOAD_ERR_OK) {
                $fileUpload = new FileUpload(__DIR__ . '/../uploads/prototypes');
                $uploadResult = $fileUpload->upload($_FILES['prototype_file'], 'user_' . $user['id']);

                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }

                $prototypePath = $uploadResult['filename'];
            }

            $db->execute(
                "INSERT INTO ideas (user_id, category_id, title, problem, solution, file_path, prototype_path, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
                [$user['id'], $categoryId, $title, $problem, $solution, $filePath, $prototypePath]
            );

            $db->execute(
                "UPDATE users SET coins = coins - ? WHERE id = ?",
                [$submissionCost, $user['id']]
            );

            $db->execute(
                "INSERT INTO wallet_transactions (user_id, type, coins, status, created_at) 
                 VALUES (?, 'idea_submission', ?, 'completed', NOW())",
                [$user['id'], $submissionCost]
            );

            $db->commit();

            $_SESSION['user']['coins'] -= $submissionCost;

            $success = 'Your idea has been submitted successfully! It will be reviewed shortly.';
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <span class="coin-badge">🪙 <?php echo $user['coins']; ?></span>
                <a href="/user/buy-coins.php" class="btn btn-success btn-sm">Buy Coins</a>
                <a href="/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>Submit Your Idea 💡</h1>
            <p>Share your innovation and start earning when it gets approved</p>
        </div>
    </div>

    <div class="container" style="padding-top: 2rem; padding-bottom: 3rem; max-width: 860px;">

        <!-- Coin Balance Info -->
        <div class="alert alert-info animate-in mb-4">
            <div>
                <strong>💰 Your Balance:</strong> <?php echo $user['coins']; ?> coins &nbsp;|&nbsp;
                <strong>💡 Submission Cost:</strong> <?php echo $submissionCost; ?> coins
                <?php if ($user['coins'] >= $submissionCost): ?>
                    &nbsp;<span class="text-success" style="font-weight: 600;">✓ Sufficient balance</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger animate-in">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success animate-in">
                <div>
                    <strong>🎉 <?php echo htmlspecialchars($success); ?></strong>
                    <br><small>Redirecting to your ideas page in a moment…</small>
                </div>
            </div>
        <?php else: ?>

        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">Idea Submission Form</h3>
                <span class="badge badge-info">Costs <?php echo $submissionCost; ?> coins</span>
            </div>

            <form action="submit-idea.php" method="POST" enctype="multipart/form-data" id="ideaForm">
                <div class="form-group">
                    <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">— Select a category —</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?> 
                            (<?php echo htmlspecialchars($category['estimated_earning']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-text">Choose the category that best describes your idea.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="title">Idea Title <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="title"
                        name="title"
                        placeholder="Give your idea a clear, descriptive title"
                        required
                        minlength="10"
                        maxlength="200"
                    >
                    <span class="form-text">Minimum 10 characters. Make it specific and engaging.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="problem">Problem Statement <span class="text-danger">*</span></label>
                    <textarea
                        class="form-control"
                        id="problem"
                        name="problem"
                        rows="5"
                        placeholder="Describe the problem your idea addresses. What pain point does it solve? Who is affected?"
                        required
                        minlength="50"
                    ></textarea>
                    <span class="form-text" id="problemCount">Minimum 50 characters. Be specific about the problem.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="solution">Your Solution <span class="text-danger">*</span></label>
                    <textarea
                        class="form-control"
                        id="solution"
                        name="solution"
                        rows="5"
                        placeholder="Explain how your idea solves the problem. Include how it works, key features, and potential impact."
                        required
                        minlength="50"
                    ></textarea>
                    <span class="form-text" id="solutionCount">Minimum 50 characters. Explain your solution clearly.</span>
                </div>

                <div class="row">
                    <div class="col col-6">
                        <div class="form-group">
                            <label class="form-label" for="idea_file">Supporting Document <span class="text-muted">(Optional)</span></label>
                            <input
                                type="file"
                                class="form-control"
                                id="idea_file"
                                name="idea_file"
                                accept=".pdf,.docx,.pptx"
                            >
                            <span class="form-text">PDF, DOCX, or PPTX — max 10MB</span>
                        </div>
                    </div>
                    <div class="col col-6">
                        <div class="form-group">
                            <label class="form-label" for="prototype_file">Prototype / Media <span class="text-muted">(Optional)</span></label>
                            <input
                                type="file"
                                class="form-control"
                                id="prototype_file"
                                name="prototype_file"
                                accept=".jpg,.jpeg,.png,.mp4"
                            >
                            <span class="form-text">Image or video of your prototype — max 10MB</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" required style="margin-top: 0.2rem; width: 16px; height: 16px; flex-shrink: 0;">
                        <span style="font-size: 0.875rem; color: var(--gray); line-height: 1.5;">
                            I agree that upon submission, full ownership of this idea transfers to IdeaOne. 
                            I will not reuse, sell, publish, distribute, or reproduce this idea elsewhere. 
                            I understand that the submission fee is non-refundable.
                        </span>
                    </label>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" id="submitBtn">
                        💡 Submit Idea — <?php echo $submissionCost; ?> Coins
                    </button>
                    <div class="text-center mt-3">
                        <a href="/user/dashboard.php" class="text-muted" style="font-size: 0.875rem;">Cancel and return to dashboard</a>
                    </div>
                </div>
            </form>
        </div>

        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center" style="background: var(--dark); color: rgba(255,255,255,0.5);">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Character counter for textareas
        function updateCounter(textarea, counter, min) {
            const len = textarea.value.length;
            const remaining = min - len;
            if (remaining > 0) {
                counter.textContent = `${remaining} more characters needed (minimum ${min})`;
                counter.style.color = 'var(--danger)';
            } else {
                counter.textContent = `${len} characters — ✓ Good length`;
                counter.style.color = 'var(--secondary)';
            }
        }

        const problemEl = document.getElementById('problem');
        const solutionEl = document.getElementById('solution');
        const problemCount = document.getElementById('problemCount');
        const solutionCount = document.getElementById('solutionCount');

        if (problemEl && problemCount) {
            problemEl.addEventListener('input', () => updateCounter(problemEl, problemCount, 50));
        }
        if (solutionEl && solutionCount) {
            solutionEl.addEventListener('input', () => updateCounter(solutionEl, solutionCount, 50));
        }

        // Loading state on form submit
        const form = document.getElementById('ideaForm');
        const submitBtn = document.getElementById('submitBtn');
        if (form && submitBtn) {
            form.addEventListener('submit', function() {
                submitBtn.classList.add('btn-loading');
                submitBtn.textContent = 'Submitting…';
            });
        }

        // Animate in
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
            el.style.transition = `opacity 0.4s ease ${i * 0.08}s, transform 0.4s ease ${i * 0.08}s`;
            observer.observe(el);
        });
    });
    </script>
</body>
</html>
