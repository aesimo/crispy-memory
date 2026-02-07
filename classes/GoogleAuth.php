<?php
/**
 * Google OAuth Authentication Class
 * Handles Google sign-in using OAuth 2.0 with cURL
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class GoogleAuth {
    private $db;
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->clientId = Config::get('GOOGLE_CLIENT_ID');
        $this->clientSecret = Config::get('GOOGLE_CLIENT_SECRET');
        $this->redirectUri = Config::get('GOOGLE_REDIRECT_URI', 'http://localhost/auth/google-callback.php');
        $this->auth = new Auth();
    }
    
    /**
     * Get Google OAuth URL
     */
    public function getAuthUrl() {
        if (empty($this->clientId)) {
            return null;
        }
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'state' => $this->generateState(),
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $params = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $response = $this->makePostRequest($url, $params);
        
        if ($response && isset($response['access_token'])) {
            return $response;
        }
        
        return null;
    }
    
    /**
     * Get user info from Google
     */
    public function getUserInfo($accessToken) {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $headers = [
            'Authorization: Bearer ' . $accessToken
        ];
        
        $response = $this->makeGetRequest($url, $headers);
        
        if ($response) {
            return $response;
        }
        
        return null;
    }
    
    /**
     * Handle Google sign-in
     */
    public function handleSignIn($code) {
        // Exchange code for token
        $tokenData = $this->exchangeCodeForToken($code);
        
        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Failed to exchange authorization code'
            ];
        }
        
        // Get user info
        $userInfo = $this->getUserInfo($tokenData['access_token']);
        
        if (!$userInfo) {
            return [
                'success' => false,
                'message' => 'Failed to get user information'
            ];
        }
        
        // Check if user exists
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE google_id = ? OR email = ?",
            [$userInfo['id'], $userInfo['email']]
        );
        
        if ($user) {
            // Update Google ID if not set
            if (empty($user['google_id'])) {
                $this->db->execute(
                    "UPDATE users SET google_id = ? WHERE id = ?",
                    [$userInfo['id'], $user['id']]
                );
                $user['google_id'] = $userInfo['id'];
            }
            
            // Log user in
            $this->auth->login($user['email'], $user['password_hash']);
            
            return [
                'success' => true,
                'message' => 'Login successful!',
                'user' => $user,
                'is_new' => false
            ];
        } else {
            // Create new user
            return $this->createUser($userInfo);
        }
    }
    
    /**
     * Create new user from Google data
     */
    private function createUser($userInfo) {
        try {
            $this->db->beginTransaction();
            
            // Generate random password (user will never use it)
            $password = bin2hex(random_bytes(32));
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            // Generate unique referral code
            $referralCode = $this->generateReferralCode();
            
            // Create user
            $this->db->execute(
                "INSERT INTO users (name, email, mobile, dob, password_hash, coins, wallet_balance, role, email_verified, google_id, referral_code, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'user', TRUE, ?, ?, NOW())",
                [
                    $userInfo['name'],
                    $userInfo['email'],
                    '0000000000', // Placeholder mobile
                    '2000-01-01', // Placeholder DOB
                    $passwordHash,
                    6, // Signup bonus coins
                    0,
                    $userInfo['id'],
                    $referralCode
                ]
            );
            
            $userId = $this->db->lastInsertId();
            
            // Create welcome transaction
            $this->db->execute(
                "INSERT INTO wallet_transactions (user_id, type, amount, coins, status, description, created_at) 
                 VALUES (?, 'signup_bonus', 0, 6, 'completed', 'Welcome bonus', NOW())",
                [$userId]
            );
            
            // Create referral code entry
            $this->db->execute(
                "INSERT INTO referral_codes (user_id, referral_code) VALUES (?, ?)",
                [$userId, $referralCode]
            );
            
            $this->db->commit();
            
            // Log the user in
            $this->auth->login($userInfo['email'], $passwordHash);
            
            return [
                'success' => true,
                'message' => 'Account created successfully!',
                'user_id' => $userId,
                'is_new' => true
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Google Auth Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create account. Please try again.'
            ];
        }
    }
    
    /**
     * Generate state parameter for CSRF protection
     */
    private function generateState() {
        $state = bin2hex(random_bytes(32));
        $_SESSION['google_oauth_state'] = $state;
        return $state;
    }
    
    /**
     * Verify state parameter
     */
    public function verifyState($state) {
        if (!isset($_SESSION['google_oauth_state']) || $_SESSION['google_oauth_state'] !== $state) {
            return false;
        }
        unset($_SESSION['google_oauth_state']);
        return true;
    }
    
    /**
     * Make POST request using cURL
     */
    private function makePostRequest($url, $params) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log("Google Auth cURL Error: " . $error);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("Google Auth HTTP Error: " . $httpCode);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Make GET request using cURL
     */
    private function makeGetRequest($url, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log("Google Auth cURL Error: " . $error);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("Google Auth HTTP Error: " . $httpCode);
            return null;
        }
        
        return json_decode($response, true);
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
}