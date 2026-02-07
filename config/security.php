<?php
/**
 * Security Configuration
 * Centralized security settings and utilities
 */

class SecurityConfig {
    
    // Security headers
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (adjust as needed)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://checkout.razorpay.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.razorpay.com; frame-src 'self' https://api.razorpay.com https://checkout.razorpay.com;");
        
        // HSTS (only in production with HTTPS)
        if (Config::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    // Error reporting settings
    public static function setErrorReporting() {
        if (Config::isProduction()) {
            error_reporting(0);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('log_errors', '1');
        }
    }
    
    // Session security settings
    public static function secureSession() {
        // Prevent session fixation
        ini_set('session.use_strict_mode', '1');
        
        // Only use cookies
        ini_set('session.use_only_cookies', '1');
        
        // Secure cookie in production
        if (Config::isProduction()) {
            ini_set('session.cookie_secure', '1');
        }
        
        // HTTP only cookies
        ini_set('session.cookie_httponly', '1');
        
        // SameSite cookie
        ini_set('session.cookie_samesite', 'Strict');
        
        // Session garbage collection
        ini_set('session.gc_maxlifetime', Config::get('SESSION_LIFETIME', '7200'));
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } else if (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    // Check for secure connection
    public static function requireHTTPS() {
        if (Config::isProduction() && !isset($_SERVER['HTTPS'])) {
            $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $httpsUrl);
            exit;
        }
    }
    
    // Validate request method
    public static function validateRequestMethod($allowedMethods) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $allowedMethods)) {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            exit;
        }
    }
    
    // Get client IP (with proxy support)
    public static function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return $ip;
    }
    
    // Log security event
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../outputs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }
    
    // Block suspicious IPs (basic implementation)
    public static function isIPBlocked($ip) {
        $blocklistFile = __DIR__ . '/../outputs/ip_blocklist.json';
        
        if (!file_exists($blocklistFile)) {
            return false;
        }
        
        $blocklist = json_decode(file_get_contents($blocklistFile), true);
        return in_array($ip, $blocklist['blocked_ips'] ?? []);
    }
    
    // Block an IP address
    public static function blockIP($ip, $reason = '') {
        $blocklistFile = __DIR__ . '/../outputs/ip_blocklist.json';
        $blocklist = ['blocked_ips' => []];
        
        if (file_exists($blocklistFile)) {
            $blocklist = json_decode(file_get_contents($blocklistFile), true);
        }
        
        if (!in_array($ip, $blocklist['blocked_ips'])) {
            $blocklist['blocked_ips'][] = $ip;
            file_put_contents($blocklistFile, json_encode($blocklist, JSON_PRETTY_PRINT));
        }
        
        self::logSecurityEvent('IP_BLOCKED', ['ip' => $ip, 'reason' => $reason]);
    }
    
    // Initialize security measures
    public static function initialize() {
        self::setSecurityHeaders();
        self::setErrorReporting();
        self::secureSession();
        
        if (Config::isProduction()) {
            self::requireHTTPS();
        }
        
        // Check for blocked IP
        if (self::isIPBlocked(self::getClientIP())) {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }
    }
}

// Auto-initialize security
SecurityConfig::initialize();