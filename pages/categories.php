<?php
session_start();
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span></a>
            <div class="nav-menu">
                <a href="/" class="nav-link">Home</a>
                <a href="/pages/features.php" class="nav-link">Features</a>
                <a href="/pages/categories.php" class="nav-link active">Categories</a>
                <a href="/pages/earning.php" class="nav-link">How It Works</a>
                <a href="/pages/contact.php" class="nav-link">Contact</a>
            </div>
            <div class="auth-buttons">
                <a href="/auth/login.php" class="btn btn-outline">Login</a>
                <a href="/auth/register.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Categories Section -->
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1>Idea Categories</h1>
            <p class="text-muted">Explore 100+ categories with estimated earning ranges</p>
        </div>

        <div class="dashboard-stats">
            <?php foreach ($categories as $category): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                </div>
                <div class="card-body">
                    <p class="text-success font-weight-bold mb-2">
                        💰 <?php echo htmlspecialchars($category['estimated_earning']); ?>
                    </p>
                    <p class="text-muted mb-3">
                        <?php echo htmlspecialchars($category['description']); ?>
                    </p>
                </div>
                <div class="card-footer">
                    <a href="/auth/register.php" class="btn btn-primary btn-block btn-sm">
                        Submit Idea in this Category
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
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