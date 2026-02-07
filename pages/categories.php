<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$isLoggedIn = $auth->isAuthenticated();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse all idea submission categories at IdeaOne">
    <title>Categories - IdeaOne</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">Idea<span>One</span></a>
            <div class="nav-menu">
                <a href="/pages/features.php" class="nav-link">Features</a>
                <a href="/pages/benefits.php" class="nav-link">Benefits</a>
                <a href="/pages/categories.php" class="nav-link active">Categories</a>
                <a href="/pages/earning.php" class="nav-link">How It Works</a>
                <a href="/pages/pricing.php" class="nav-link">Pricing</a>
                <a href="/pages/contact.php" class="nav-link">Contact</a>
            </div>
            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                    <span class="nav-link user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
                    <a href="/user/dashboard.php" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="/auth/login.php" class="btn btn-outline">Login</a>
                    <a href="/auth/register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Categories Section -->
    <section class="page-header">
        <div class="container">
            <h1>Idea Categories</h1>
            <p class="text-muted">Explore 100+ categories with estimated earning ranges</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div id="categories-container" class="dashboard-stats">
                <div class="text-center">
                    <p>Loading categories...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2>Ready to Submit Your Idea?</h2>
            <p class="text-muted">Join thousands of students earning from their creative ideas</p>
            <?php if (!$isLoggedIn): ?>
                <div class="mt-4">
                    <a href="/auth/register.php" class="btn btn-lg btn-success">Get 6 Free Coins & Start Earning</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="/user/dashboard.php" class="btn btn-lg btn-success">Submit Your Idea</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> IdeaOne. All rights reserved.</p>
        </div>
    </footer>

    <script>
    // Fetch categories from API (backend-controlled)
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/api/categories.php')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    displayCategories(result.data);
                } else {
                    displayError('Failed to load categories');
                }
            })
            .catch(error => {
                console.error('Error fetching categories:', error);
                displayError('Failed to load categories');
            });
    });

    function displayCategories(categories) {
        const container = document.getElementById('categories-container');
        
        if (categories.length === 0) {
            container.innerHTML = '<p class="text-center">No categories available at this time.</p>';
            return;
        }
        
        let html = '';
        categories.forEach(category => {
            html += `
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">${escapeHtml(category.name)}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-success font-weight-bold mb-2">
                            💰 ${escapeHtml(category.estimated_earning)}
                        </p>
                        <p class="text-muted mb-3">
                            ${escapeHtml(category.description)}
                        </p>
                    </div>
                    <div class="card-footer">
                        <a href="/auth/register.php" class="btn btn-primary btn-block btn-sm">
                            Submit Idea in this Category
                        </a>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function displayError(message) {
        const container = document.getElementById('categories-container');
        container.innerHTML = `<p class="text-center text-danger">${escapeHtml(message)}</p>`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>