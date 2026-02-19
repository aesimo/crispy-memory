<?php
/**
 * API Security Middleware
 * Handles authentication, authorization, and security checks for all API endpoints
 */

// Prevent direct access
if (!defined('API_ACCESS')) {
    define('API_ACCESS', true);
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// CORS configuration (adjust domains as needed)
$allowedOrigins = [
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:8080',
    'https://your-railway-app.railway.app', // Update with your Railway URL
    'https://your-production-domain.com'    // Update with your production domain
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Rate limiting (simple implementation)
function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
    $rateLimitFile = __DIR__ . '/../outputs/rate_limits/' . md5($identifier) . '.json';
    $rateLimitDir = dirname($rateLimitFile);
    
    // Create directory if it doesn't exist
    if (!is_dir($rateLimitDir)) {
        mkdir($rateLimitDir, 0755, true);
    }
    
    $now = time();
    $data = ['count' => 0, 'start_time' => $now];
    
    // Load existing rate limit data
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        
        // Reset if time window has passed
        if ($now - $data['start_time'] > $timeWindow) {
            $data = ['count' => 0, 'start_time' => $now];
        }
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $maxRequests) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please try again later.'
        ]);
        exit;
    }
    
    // Increment counter
    $data['count']++;
    file_put_contents($rateLimitFile, json_encode($data));
    
    // Clean up old files
    $files = glob($rateLimitDir . '/*.json');
    foreach ($files as $file) {
        if ($now - filemtime($file) > $timeWindow * 2) {
            @unlink($file);
        }
    }
}

// Get client identifier for rate limiting
function getClientIdentifier() {
    // Try to get user ID if authenticated
    if (isset($_SESSION['user_id'])) {
        return 'user_' . $_SESSION['user_id'];
    }
    
    // Fall back to IP address
    return 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

// Sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate required fields
function validateRequired($data, $requiredFields) {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

// Check if request is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Require authentication (exits if not authenticated)
function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}