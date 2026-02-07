<?php
/**
 * CSRF Protection Class
 * Handles Cross-Site Request Forgery protection
 */

class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $tokenLength = 32;
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION[self::$tokenName])) {
            $_SESSION[self::$tokenName] = bin2hex(random_bytes(self::$tokenLength));
        }
        
        return $_SESSION[self::$tokenName];
    }
    
    /**
     * Get CSRF token (generates if not exists)
     */
    public static function getToken() {
        return self::generateToken();
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token) {
        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::$tokenName], $token);
    }
    
    /**
     * Check request for valid CSRF token
     * Exits with error if invalid
     */
    public static function checkToken() {
        $token = $_POST[self::$tokenName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!self::validateToken($token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ]);
            exit;
        }
    }
    
    /**
     * Generate hidden input field with CSRF token
     */
    public static function getHiddenInput() {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get CSRF token for AJAX requests
     */
    public static function getMetaTag() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Regenerate CSRF token
     */
    public static function regenerateToken() {
        $_SESSION[self::$tokenName] = bin2hex(random_bytes(self::$tokenLength));
        return $_SESSION[self::$tokenName];
    }
}