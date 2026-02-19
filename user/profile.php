<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($mobile)) {
        $error = 'Name and mobile number are required';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = 'Invalid mobile number (must be 10 digits)';
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } else {
        try {
            $db->beginTransaction();

            $db->execute(
                "UPDATE users SET name = ?, mobile = ?, updated_at = NOW() WHERE id = ?",
                [$name, $mobile, $user['id']]
            );

            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    throw new Exception('Current password is required to change password');
                }

                $userRecord = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
                if (!password_verify($currentPassword, $userRecord['password_hash'])) {
                    throw new Exception('Current password is incorrect');
                }

                if (strlen($newPassword) < 6) {
                    throw new Exception('New password must be at least 6 characters');
                }

                $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $db->execute(
                    "UPDATE users SET password_hash = ? WHERE id = ?",
                    [$passwordHash, $user['id']]
                );
            }

            $db->commit();

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['mobile'] = $mobile;

            $success = 'Profile updated successfully!';

        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }

    $user = $auth->getCurrentUser();
}

$memberSince = $db->fetchOne("SELECT created_at FROM users WHERE id = ?", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - IdeaOne</title>
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
                <a href="/user/profile.php" class="nav-link active">Profile</a>
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
            <h1>My Profile</h1>
            <p>Manage your account settings and personal information</p>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="container" style="padding-top: 2rem; padding-bottom: 3rem;">

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-4">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col col-4">
                <div class="card text-center animate-in" style="margin-bottom: 1.25rem;">
                    <div class="stat-icon primary" style="width: 88px; height: 88px; font-size: 2.5rem; margin: 0 auto 1.25rem;">
                        👤
                    </div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge badge-primary">Member</span>
                </div>

                <div class="card animate-in">
                    <h4 style="font-weight: 700; margin-bottom: 1.25rem; color: var(--dark);">Account Details</h4>

                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--lighter); border-radius: var(--radius);">
                            <span class="text-muted" style="font-size: 0.875rem;">Coins</span>
                            <span class="coin-badge">🪙 <?php echo $user['coins']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--lighter); border-radius: var(--radius);">
                            <span class="text-muted" style="font-size: 0.875rem;">Wallet</span>
                            <strong class="text-success">₹<?php echo number_format($user['wallet_balance'], 2); ?></strong>
                        </div>
                        <?php if ($memberSince && $memberSince['created_at']): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--lighter); border-radius: var(--radius);">
                            <span class="text-muted" style="font-size: 0.875rem;">Member Since</span>
                            <strong style="font-size: 0.875rem;"><?php echo date('M Y', strtotime($memberSince['created_at'])); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/user/wallet.php" class="btn btn-outline btn-block">💰 View Wallet</a>
                        <a href="/user/ideas.php" class="btn btn-outline btn-block">💡 My Ideas</a>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col col-8">
                <div class="card animate-in">
                    <div class="card-header">
                        <h3 class="card-title">Edit Profile</h3>
                    </div>
                    <form action="profile.php" method="POST">
                        <div class="row">
                            <div class="col col-6">
                                <div class="form-group">
                                    <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="name"
                                        name="name"
                                        value="<?php echo htmlspecialchars($user['name']); ?>"
                                        required
                                        placeholder="Enter your full name"
                                    >
                                </div>
                            </div>
                            <div class="col col-6">
                                <div class="form-group">
                                    <label class="form-label" for="mobile">Mobile Number <span class="text-danger">*</span></label>
                                    <input
                                        type="tel"
                                        class="form-control"
                                        id="mobile"
                                        name="mobile"
                                        value="<?php echo htmlspecialchars($user['mobile']); ?>"
                                        pattern="[0-9]{10}"
                                        required
                                        placeholder="10-digit number"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input
                                type="email"
                                class="form-control"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                disabled
                                style="background: var(--lighter); cursor: not-allowed; opacity: 0.7;"
                            >
                            <span class="form-text">Email cannot be changed. Contact support if needed.</span>
                        </div>

                        <hr style="margin: 1.5rem 0;">

                        <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: var(--dark);">Change Password</h4>
                        <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 1.25rem;">Leave blank to keep your current password unchanged.</p>

                        <div class="row">
                            <div class="col col-4">
                                <div class="form-group">
                                    <label class="form-label" for="current_password">Current Password</label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="current_password"
                                        name="current_password"
                                        placeholder="Current password"
                                    >
                                </div>
                            </div>
                            <div class="col col-4">
                                <div class="form-group">
                                    <label class="form-label" for="new_password">New Password</label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="new_password"
                                        name="new_password"
                                        minlength="6"
                                        placeholder="Min. 6 characters"
                                    >
                                </div>
                            </div>
                            <div class="col col-4">
                                <div class="form-group">
                                    <label class="form-label" for="confirm_password">Confirm Password</label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="confirm_password"
                                        name="confirm_password"
                                        minlength="6"
                                        placeholder="Repeat new password"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center" style="background: var(--dark); color: rgba(255,255,255,0.5);">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
