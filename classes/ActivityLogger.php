<?php
/**
 * Activity Logger Class
 * Logs user activities for analytics and security
 */

require_once __DIR__ . '/Database.php';

class ActivityLogger {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Log user activity
     */
    public function log($userId, $action, $details = [], $ipAddress = null, $userAgent = null) {
        $detailsJson = json_encode($details);
        $ipAddress = $ipAddress ?? $this->getClientIp();
        $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            $this->db->execute(
                "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$userId, $action, $detailsJson, $ipAddress, $userAgent]
            );
            return true;
        } catch (Exception $e) {
            error_log("Activity Logging Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user registration
     */
    public function logRegistration($userId, $details = []) {
        return $this->log($userId, 'registration', $details);
    }
    
    /**
     * Log user login
     */
    public function logLogin($userId, $details = []) {
        return $this->log($userId, 'login', $details);
    }
    
    /**
     * Log user logout
     */
    public function logLogout($userId) {
        return $this->log($userId, 'logout');
    }
    
    /**
     * Log idea submission
     */
    public function logIdeaSubmission($userId, $ideaId, $categoryId, $title) {
        return $this->log($userId, 'idea_submission', [
            'idea_id' => $ideaId,
            'category_id' => $categoryId,
            'title' => $title
        ]);
    }
    
    /**
     * Log idea approval
     */
    public function logIdeaApproval($userId, $ideaId, $amount) {
        return $this->log($userId, 'idea_approved', [
            'idea_id' => $ideaId,
            'amount' => $amount
        ]);
    }
    
    /**
     * Log idea rejection
     */
    public function logIdeaRejection($userId, $ideaId, $reason) {
        return $this->log($userId, 'idea_rejected', [
            'idea_id' => $ideaId,
            'reason' => $reason
        ]);
    }
    
    /**
     * Log coin purchase
     */
    public function logCoinPurchase($userId, $coins, $amount, $orderId) {
        return $this->log($userId, 'coin_purchase', [
            'coins' => $coins,
            'amount' => $amount,
            'order_id' => $orderId
        ]);
    }
    
    /**
     * Log withdrawal request
     */
    public function logWithdrawalRequest($userId, $withdrawalId, $amount) {
        return $this->log($userId, 'withdrawal_request', [
            'withdrawal_id' => $withdrawalId,
            'amount' => $amount
        ]);
    }
    
    /**
     * Log withdrawal processed
     */
    public function logWithdrawalProcessed($userId, $withdrawalId, $status) {
        return $this->log($userId, 'withdrawal_processed', [
            'withdrawal_id' => $withdrawalId,
            'status' => $status
        ]);
    }
    
    /**
     * Log profile update
     */
    public function logProfileUpdate($userId, $changes) {
        return $this->log($userId, 'profile_update', $changes);
    }
    
    /**
     * Log password change
     */
    public function logPasswordChange($userId) {
        return $this->log($userId, 'password_change');
    }
    
    /**
     * Log email verification
     */
    public function logEmailVerification($userId) {
        return $this->log($userId, 'email_verified');
    }
    
    /**
     * Log SMS verification
     */
    public function logSmsVerification($userId) {
        return $this->log($userId, 'sms_verified');
    }
    
    /**
     * Log referral signup
     */
    public function logReferralSignup($userId, $referrerId) {
        return $this->log($userId, 'referral_signup', [
            'referrer_id' => $referrerId
        ]);
    }
    
    /**
     * Get user activities
     */
    public function getUserActivities($userId, $limit = 50) {
        return $this->db->fetchAll("
            SELECT * FROM activity_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$userId, $limit]);
    }
    
    /**
     * Get activities by action type
     */
    public function getActivitiesByAction($action, $limit = 100) {
        return $this->db->fetchAll("
            SELECT * FROM activity_logs 
            WHERE action = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$action, $limit]);
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats($startDate = null, $endDate = null) {
        $sql = "SELECT action, COUNT(*) as count FROM activity_logs WHERE 1=1";
        $params = [];
        
        if ($startDate) {
            $sql .= " AND created_at >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND created_at <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY action ORDER BY count DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get recent activities for admin dashboard
     */
    public function getRecentActivities($limit = 20) {
        return $this->db->fetchAll("
            SELECT al.*, u.name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ", [$limit]);
    }
    
    /**
     * Get failed login attempts
     */
    public function getFailedLoginAttempts($ipAddress, $timeWindowMinutes = 30) {
        return $this->db->fetchOne("
            SELECT COUNT(*) as count
            FROM activity_logs
            WHERE action = 'failed_login'
            AND ip_address = ?
            AND created_at > NOW() - INTERVAL '1 minute' * ?
        ", [$ipAddress, $timeWindowMinutes]);
    }
    
    /**
     * Check for suspicious activity
     */
    public function checkSuspiciousActivity($userId) {
        // Check multiple failed logins
        $failedLogins = $this->db->fetchOne("
            SELECT COUNT(*) as count
            FROM activity_logs
            WHERE user_id = ?
            AND action = 'failed_login'
            AND created_at > NOW() - INTERVAL '1 hour'
        ", [$userId]);
        
        if ($failedLogins['count'] >= 5) {
            return 'multiple_failed_logins';
        }
        
        // Check logins from multiple IPs
        $multipleIps = $this->db->fetchOne("
            SELECT COUNT(DISTINCT ip_address) as count
            FROM activity_logs
            WHERE user_id = ?
            AND action = 'login'
            AND created_at > NOW() - INTERVAL '24 hours'
        ", [$userId]);
        
        if ($multipleIps['count'] >= 3) {
            return 'multiple_ips';
        }
        
        return null;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
}