<?php
/**
 * Input Validator Class
 * Handles input validation and sanitization
 */

class InputValidator {
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email input
     */
    public static function sanitizeEmail($input) {
        return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     * Minimum 8 characters, at least one uppercase, one lowercase, one number
     */
    public static function validatePassword($password) {
        if (strlen($password) < 8) {
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate numeric input
     */
    public static function validateNumeric($input) {
        return is_numeric($input);
    }
    
    /**
     * Validate integer
     */
    public static function validateInteger($input) {
        return filter_var($input, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validate float/decimal
     */
    public static function validateFloat($input) {
        return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    /**
     * Validate URL
     */
    public static function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate file upload
     */
    public static function validateFile($file, $allowedTypes, $maxSize) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'message' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'message' => 'File size exceeds limit'];
        }
        
        // Check file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileType, $allowedTypes)) {
            return ['valid' => false, 'message' => 'Invalid file type'];
        }
        
        // Check MIME type
        $allowedMimeTypes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'mp4' => 'video/mp4'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (isset($allowedMimeTypes[$fileType]) && $allowedMimeTypes[$fileType] !== $mimeType) {
            return ['valid' => false, 'message' => 'Invalid file MIME type'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Sanitize array input
     */
    public static function sanitizeArray($array) {
        return array_map(function($value) {
            if (is_array($value)) {
                return self::sanitizeArray($value);
            }
            return self::sanitizeString($value);
        }, $array);
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($data, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
    
    /**
     * Clean SQL injection attempts
     */
    public static function cleanSQLInjection($input) {
        // Remove common SQL injection patterns
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|EXEC|UNION|WHERE)\b)/i',
            '/(--)|(\/\*)|(\*\/)/',
            '/(\'\s*OR\s*\')|(\'\s*AND\s*\')/i',
            '/(\'\s*=\s*\')|(\|\||&&)/i'
        ];
        
        return preg_replace($patterns, '', $input);
    }
    
    /**
     * Clean XSS attempts
     */
    public static function cleanXSS($input) {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/<object\b[^>]*>(.*?)<\/object>/is',
            '/<embed\b[^>]*>(.*?)<\/embed>/is',
            '/javascript:/i',
            '/on\w+\s*=/i'  // onclick, onload, etc.
        ];
        
        return preg_replace($patterns, '', $input);
    }
    
    /**
     * Comprehensive sanitization
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return self::sanitizeArray($input);
        }
        
        $sanitized = self::sanitizeString($input);
        $sanitized = self::cleanSQLInjection($sanitized);
        $sanitized = self::cleanXSS($sanitized);
        
        return $sanitized;
    }
}