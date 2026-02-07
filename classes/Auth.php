<?php
/**
 * Authentication Class
 * Handles JWT token generation, validation, and user authentication
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ActivityLogger.php';

class Auth {
    private $db;
    private static $secret;
    private $emailService;
    private $smsService;
    private $activityLogger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        self::$secret = Config::get('JWT_SECRET');
        $this->activityLogger = new ActivityLogger();
        
        // Initialize optional services
        $this->emailService = $this->loadOptionalService('EmailService', 'SENDGRID_API_KEY');
        $this->smsService = $this->loadOptionalService('SmsService', 'TWILIO_ACCOUNT_SID');
    }
    
    /**
     * Load optional service if configured
     */
    private function loadOptionalService($className, $configKey) {
        if (Config::get($configKey)) {
            require_once __DIR__ . '/' . $className . '.php';
            return new $className();
        }
        return null;
    }
    
    /**
     * Generate JWT Token
     */
    public function generateToken($userId, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7) // 7 days
        ]);
        
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            self::$secret,
            true
        );
        
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Validate JWT Token
     */
    public function validateToken($token) {
        if (empty($token)) {
            return null;
        }
        
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            return null;
        }
        
        list($header, $payload, $signature) = $tokenParts;
        
        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $header . "." . $payload,
            self::$secret,
            true
        );
        
        $base64UrlExpectedSignature = $this->base64UrlEncode($expectedSignature);
        
        if ($base64UrlExpectedSignature !== $signature) {
            return null;
        }
        
        // Decode payload
        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);
        
        // Check expiration
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return null;
        }
        
        return $decodedPayload;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        
        $token = $this->getTokenFromRequest();
        if ($token) {
            $payload = $this->validateToken($token);
            if ($payload) {
                $user = $this->db->fetchOne(
                    "SELECT id, name, email, mobile, role, coins, wallet_balance FROM users WHERE id = ?",
                    [$payload['user_id']]
                );
                
                if ($user) {
                    $_SESSION['user'] = $user;
                    return $user;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return $this->getCurrentUser() !== null;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: /auth/login.php');
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            die('Access denied. You do not have permission to access this page.');
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? OR mobile = ?",
            [$email, $email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $token = $this->generateToken($user['id'], $user['role']);
            
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'mobile' => $user['mobile'],
                'role' => $user['role'],
                'coins' => $user['coins'],
                'wallet_balance' => $user['wallet_balance']
            ];
            
            $_SESSION['token'] = $token;
            
            return [
                'success' => true,
                'token' => $token,
                'user' => $_SESSION['user']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid credentials'
        ];
    }
    
    /**
     * Register new user
     */
    public function register($name, $email, $mobile, $dob, $password, $referralCode = null) {
        // Check if user already exists
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ? OR mobile = ?",
            [$email, $mobile]
        );
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'User with this email or mobile already exists'
            ];
        }
        
        // Validate referral code
        $referrerId = null;
        $referralBonus = 0;
        
        if ($referralCode) {
            $referrer = $this->db->fetchOne(
                "SELECT user_id FROM referral_codes WHERE referral_code = ? AND active = TRUE",
                [$referralCode]
            );
            
            if ($referrer) {
                $referrerId = $referrer['user_id'];
                $referralBonus = Config::get('REFERRAL_BONUS', 3);
            }
        }
        
        try {
            $this->db->beginTransaction();
            
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $signupBonus = Config::get('SIGNUP_BONUS', 6);
            $totalCoins = $signupBonus + $referralBonus;
            
            // Generate unique referral code for new user
            $userReferralCode = $this->generateReferralCode();
            
            $this->db->execute(
                "INSERT INTO users (name, email, mobile, dob, password_hash, coins, wallet_balance, role, referral_code, referred_by, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'user', ?, ?, NOW())",
                [$name, $email, $mobile, $dob, $passwordHash, $totalCoins, 0, $userReferralCode, $referrerId]
            );
            
            $userId = $this->db->lastInsertId();
            
            // Create welcome transaction
            $this->db->execute(
                "INSERT INTO wallet_transactions (user_id, type, amount, coins, status, description, created_at) 
                 VALUES (?, 'signup_bonus', 0, ?, 'completed', 'Welcome bonus', NOW())",
                [$userId, $signupBonus]
            );
            
            // Create referral bonus transaction if applicable
            if ($referralBonus > 0) {
                $this->db->execute(
                    "INSERT INTO wallet_transactions (user_id, type, amount, coins, status, description, created_at) 
                     VALUES (?, 'referral_bonus', 0, ?, 'completed', 'Referral bonus', NOW())",
                    [$userId, $referralBonus]
                );
                
                // Update referrer's stats
                $this->db->execute(
                    "UPDATE referral_codes SET total_referrals = total_referrals + 1 WHERE user_id = ?",
                    [$referrerId]
                );
                
                // Log referral
                $this->activityLogger->logReferralSignup($userId, $referrerId);
            }
            
            // Create referral code entry
            $this->db->execute(
                "INSERT INTO referral_codes (user_id, referral_code) VALUES (?, ?)",
                [$userId, $userReferralCode]
            );
            
            // Send email verification
            $emailVerificationToken = bin2hex(random_bytes(32));
            $emailExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $this->db->execute(
                "INSERT INTO email_verifications (user_id, token, email, expires_at) VALUES (?, ?, ?, ?)",
                [$userId, $emailVerificationToken, $email, $emailExpiry]
            );
            
            $this->emailService->sendVerificationEmail($email, $name, $emailVerificationToken);
            
            // Send welcome email
            $this->emailService->sendWelcomeEmail($email, $name, $totalCoins);
            
            // Log registration
            $this->activityLogger->logRegistration($userId, [
                'referral_code_used' => $referralCode,
                'referral_bonus' => $referralBonus
            ]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Registration successful! Check your email for verification link.',
                'coins' => $totalCoins,
                'referral_bonus' => $referralBonus,
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Registration Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }
    
    /**
     * Generate unique referral code
     */
    private function generateReferralCode() {
        do {
            $code = 'IDEA' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $exists = $this->db->fetchOne(
                "SELECT id FROM users WHERE referral_code = ?",
                [$code]
            );
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Verify email
     */
    public function verifyEmail($token) {
        $verification = $this->db->fetchOne(
            "SELECT * FROM email_verifications WHERE token = ? AND verified = FALSE AND expires_at > NOW()",
            [$token]
        );
        
        if (!$verification) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification token'
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Mark email as verified
            $this->db->execute(
                "UPDATE users SET email_verified = TRUE WHERE id = ?",
                [$verification['user_id']]
            );
            
            // Mark verification as used
            $this->db->execute(
                "UPDATE email_verifications SET verified = TRUE WHERE id = ?",
                [$verification['id']]
            );
            
            // Log verification
            $this->activityLogger->logEmailVerification($verification['user_id']);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Email verified successfully!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Email Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ];
        }
    }
    
    /**
     * Send SMS verification code
     */
    public function sendSmsVerification($mobile) {
        $verificationCode = rand(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        try {
            $this->db->execute(
                "INSERT INTO sms_verifications (mobile, code, expires_at) VALUES (?, ?, ?)",
                [$mobile, $verificationCode, $expiresAt]
            );
            
            // Send SMS
            $this->smsService->sendVerificationSms($mobile, 'User', $verificationCode);
            
            return [
                'success' => true,
                'message' => 'Verification code sent to your mobile'
            ];
            
        } catch (Exception $e) {
            error_log("SMS Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send verification code'
            ];
        }
    }
    
    /**
     * Verify SMS code
     */
    public function verifySmsCode($mobile, $code) {
        $verification = $this->db->fetchOne(
            "SELECT * FROM sms_verifications 
             WHERE mobile = ? AND code = ? AND verified = FALSE AND expires_at > NOW() 
             ORDER BY created_at DESC LIMIT 1",
            [$mobile, $code]
        );
        
        if (!$verification) {
            // Increment attempts
            $this->db->execute(
                "UPDATE sms_verifications SET attempts = attempts + 1 WHERE mobile = ? AND code = ?",
                [$mobile, $code]
            );
            
            return [
                'success' => false,
                'message' => 'Invalid or expired verification code'
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Mark SMS as verified
            $this->db->execute(
                "UPDATE sms_verifications SET verified = TRUE WHERE id = ?",
                [$verification['id']]
            );
            
            // Update user if user_id exists
            if ($verification['user_id']) {
                $this->db->execute(
                    "UPDATE users SET sms_verified = TRUE WHERE id = ?",
                    [$verification['user_id']]
                );
                
                $this->activityLogger->logSmsVerification($verification['user_id']);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Mobile number verified successfully!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("SMS Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ];
        }
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($identifier) {
        // Check if email or mobile
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE " . ($isEmail ? "email = ?" : "mobile = ?"),
            [$identifier]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'No account found with this ' . ($isEmail ? 'email' : 'mobile number')
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Create password reset record
            $this->db->execute(
                "INSERT INTO password_resets (user_id, token, " . ($isEmail ? "email" : "mobile") . ", expires_at) 
                 VALUES (?, ?, ?, ?)",
                [$user['id'], $resetToken, $identifier, $expiresAt]
            );
            
            // Send reset link/code
            if ($isEmail) {
                $this->emailService->sendPasswordResetEmail($user['email'], $user['name'], $resetToken);
            } else {
                $this->smsService->sendPasswordResetSms($user['mobile'], $resetToken);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Password reset ' . ($isEmail ? 'link' : 'code') . ' sent to your ' . ($isEmail ? 'email' : 'mobile')
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Password Reset Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send password reset. Please try again.'
            ];
        }
    }
    
    /**
     * Reset password
     */
    public function resetPassword($token, $newPassword) {
        $reset = $this->db->fetchOne(
            "SELECT * FROM password_resets 
             WHERE token = ? AND used = FALSE AND expires_at > NOW() 
             ORDER BY created_at DESC LIMIT 1",
            [$token]
        );
        
        if (!$reset) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // Update password
            $this->db->execute(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [$passwordHash, $reset['user_id']]
            );
            
            // Mark reset as used
            $this->db->execute(
                "UPDATE password_resets SET used = TRUE WHERE id = ?",
                [$reset['id']]
            );
            
            // Log password change
            $this->activityLogger->logPasswordChange($reset['user_id']);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Password reset successfully! You can now login with your new password.'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Password Reset Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Password reset failed. Please try again.'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        unset($_SESSION['user']);
        unset($_SESSION['token']);
        session_destroy();
    }
    
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    private function getTokenFromRequest() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return $_SESSION['token'] ?? null;
    }
}