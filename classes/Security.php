<?php
/**
 * Security Class
 * Handles CSRF protection, security headers, and rate limiting
 */

class Security {
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
    
    /**
     * Get CSRF token HTML input
     */
    public static function getCsrfInput() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://accounts.google.com https://oauth2.googleapis.com https://www.googleapis.com; frame-src 'self' https://accounts.google.com;");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // HSTS (only in production)
        if (Config::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * Sanitize input
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map('self::sanitizeInput', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Rate limiting
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        
        if (!isset($_SESSION['rate_limits'][$identifier])) {
            $_SESSION['rate_limits'][$identifier] = [
                'attempts' => 0,
                'first_attempt' => $now
            ];
        }
        
        $rateData = $_SESSION['rate_limits'][$identifier];
        
        // Reset if time window has passed
        if ($now - $rateData['first_attempt'] > $timeWindow) {
            $_SESSION['rate_limits'][$identifier] = [
                'attempts' => 1,
                'first_attempt' => $now
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($rateData['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION['rate_limits'][$identifier]['attempts']++;
        
        return true;
    }
    
    /**
     * Get rate limit remaining attempts
     */
    public static function getRateLimitRemaining($identifier, $maxAttempts = 5, $timeWindow = 300) {
        if (!isset($_SESSION['rate_limits'][$identifier])) {
            return $maxAttempts;
        }
        
        $rateData = $_SESSION['rate_limits'][$identifier];
        
        if (time() - $rateData['first_attempt'] > $timeWindow) {
            return $maxAttempts;
        }
        
        return max(0, $maxAttempts - $rateData['attempts']);
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        error_log('Security Event: ' . json_encode($logEntry));
    }
    
    /**
     * Check for SQL injection patterns
     */
    public static function detectSqlInjection($input) {
        $patterns = [
            '/\bUNION\b.*\bSELECT\b/i',
            '/\bSELECT\b.*\bFROM\b/i',
            '/\bINSERT\b.*\bINTO\b/i',
            '/\bUPDATE\b.*\bSET\b/i',
            '/\bDELETE\b.*\bFROM\b/i',
            '/\bDROP\b.*\bTABLE\b/i',
            '/\bALTER\b.*\bTABLE\b/i',
            '/\bCREATE\b.*\bTABLE\b/i',
            '/\'\s*OR\s*\'/i',
            '/"\s*OR\s*"/i',
            '/--/',
            '/\/\*/',
            '/;\s*$/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for XSS patterns
     */
    public static function detectXss($input) {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<.*?on\w+.*?>/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
}