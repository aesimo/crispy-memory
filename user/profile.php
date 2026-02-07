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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($name) || empty($mobile)) {
        $error = 'Name and mobile number are required';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = 'Invalid mobile number (must be 10 digits)';
    } else {
        try {
            $db->beginTransaction();
            
            // Update basic info
            $db->execute(
                "UPDATE users SET name = ?, mobile = ?, updated_at = NOW() WHERE id = ?",
                [$name, $mobile, $user['id']]
            );
            
            // Update password if provided
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    throw new Exception('Current password is required to change password');
                }
                
                // Verify current password
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
            
            // Update session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['mobile'] = $mobile;
            
            $success = 'Profile updated successfully!';
            
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
    
    // Reload user data
    $user = $auth->getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - IdeaOne</title>
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
                <a href="/user/profile.php" class="nav-link active">Profile</a>
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

    <!-- Profile Section -->
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1>My Profile</h1>
            <p class="text-muted">Manage your account settings</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Info -->
            <div class="col col-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="stat-icon primary" style="width: 100px; height: 100px; font-size: 3rem; margin: 0 auto 1rem;">
                            👤
                        </div>
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge badge-info">User</span>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="text-muted mb-1">Member Since</p>
                            <p class="font-weight-bold"><?php echo date('F Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                        </div>
                        <div class="mb-3">
                            <p class="text-muted mb-1">Date of Birth</p>
                            <p class="font-weight-bold"><?php echo date('M d, Y', strtotime($user['dob'])); ?></p>
                        </div>
                        <div class="mb-0">
                            <p class="text-muted mb-1">Wallet Balance</p>
                            <p class="font-weight-bold text-success">₹<?php echo number_format($user['wallet_balance'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Profile</h3>
                    </div>
                    <form action="profile.php" method="POST">
                        <div class="card-body">
                            <div class="row">
                                <div class="col col-6">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Full Name *</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="name" 
                                            name="name" 
                                            value="<?php echo htmlspecialchars($user['name']); ?>"
                                            required
                                        >
                                    </div>
                                </div>
                                <div class="col col-6">
                                    <div class="form-group">
                                        <label class="form-label" for="mobile">Mobile Number *</label>
                                        <input 
                                            type="tel" 
                                            class="form-control" 
                                            id="mobile" 
                                            name="mobile" 
                                            value="<?php echo htmlspecialchars($user['mobile']); ?>"
                                            pattern="[0-9]{10}"
                                            required
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email (Cannot be changed)</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    disabled
                                >
                                <small class="form-text">Contact support to change your email address</small>
                            </div>

                            <hr class="my-4">

                            <h4 class="card-title mb-3">Change Password</h4>
                            <p class="text-muted mb-3">Leave blank to keep current password</p>

                            <div class="row">
                                <div class="col col-4">
                                    <div class="form-group">
                                        <label class="form-label" for="current_password">Current Password</label>
                                        <input 
                                            type="password" 
                                            class="form-control" 
                                            id="current_password" 
                                            name="current_password"
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
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>