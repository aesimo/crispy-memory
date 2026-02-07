<?php
/**
 * Payment Class
 * Handles Razorpay integration for coin purchases
 */

require_once __DIR__ . '/Database.php';

class Payment {
    private $db;
    private $keyId;
    private $keySecret;
    private $webhookSecret;
    private $enabled;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->keyId = Config::get('RAZORPAY_KEY_ID');
        $this->keySecret = Config::get('RAZORPAY_KEY_SECRET');
        $this->webhookSecret = Config::get('RAZORPAY_WEBHOOK_SECRET');
        $this->enabled = !empty($this->keyId) && !empty($this->keySecret);
    }
    
    /**
     * Create Razorpay order
     */
    public function createOrder($amount, $userId, $coinPackage) {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'Payment gateway is not configured. Please contact administrator.'
            ];
        }
        $amountInPaise = $amount * 100; // Convert to paise
        
        $data = [
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => 'order_' . $userId . '_' . time(),
            'notes' => [
                'user_id' => $userId,
                'coin_package' => $coinPackage
            ]
        ];
        
        $response = $this->makeRequest('/orders', 'POST', $data);
        
        if ($response) {
            // Save order to database
            $this->db->execute(
                "INSERT INTO razorpay_orders (order_id, user_id, amount, coins, status, created_at) 
                 VALUES (?, ?, ?, ?, 'created', NOW())",
                [$response['id'], $userId, $amount, $coinPackage]
            );
            
            return [
                'success' => true,
                'order_id' => $response['id'],
                'amount' => $amount,
                'currency' => 'INR',
                'key' => $this->keyId
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to create payment order'
        ];
    }
    
    /**
     * Verify payment signature
     */
    public function verifySignature($orderId, $paymentId, $signature) {
        if (!$this->enabled) {
            return false;
        }
        $generatedSignature = hash_hmac(
            'sha256',
            $orderId . '|' . $paymentId,
            $this->keySecret
        );
        
        return $generatedSignature === $signature;
    }
    
    /**
     * Process successful payment
     */
    public function processPayment($orderId, $paymentId, $signature) {
        // Verify signature
        if (!$this->verifySignature($orderId, $paymentId, $signature)) {
            return [
                'success' => false,
                'message' => 'Invalid payment signature'
            ];
        }
        
        // Get order details from database
        $order = $this->db->fetchOne(
            "SELECT * FROM razorpay_orders WHERE order_id = ?",
            [$orderId]
        );
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found'
            ];
        }
        
        // Check if already processed
        if ($order['status'] === 'completed') {
            return [
                'success' => false,
                'message' => 'Payment already processed'
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Update order status
            $this->db->execute(
                "UPDATE razorpay_orders SET status = 'completed', payment_id = ?, updated_at = NOW() 
                 WHERE order_id = ?",
                [$paymentId, $orderId]
            );
            
            // Add coins to user wallet
            $this->db->execute(
                "UPDATE users SET coins = coins + ? WHERE id = ?",
                [$order['coins'], $order['user_id']]
            );
            
            // Create wallet transaction
            $this->db->execute(
                "INSERT INTO wallet_transactions (user_id, type, amount, coins, status, payment_id, created_at) 
                 VALUES (?, 'purchase', ?, ?, 'completed', ?, NOW())",
                [$order['user_id'], $order['amount'], $order['coins'], $paymentId]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Payment successful! Coins added to your wallet.',
                'coins' => $order['coins']
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Payment Processing Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process payment'
            ];
        }
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature) {
        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            $this->webhookSecret
        );
        
        return $expectedSignature === $signature;
    }
    
    /**
     * Handle Razorpay webhook
     */
    public function handleWebhook($payload, $signature) {
        if (!$this->verifyWebhookSignature($payload, $signature)) {
            return [
                'success' => false,
                'message' => 'Invalid webhook signature'
            ];
        }
        
        $webhookData = json_decode($payload, true);
        
        if ($webhookData['event'] === 'payment.captured') {
            $orderEntity = $webhookData['payload']['payment']['entity'];
            $orderId = $orderEntity['order_id'];
            $paymentId = $orderEntity['id'];
            
            return $this->processPayment($orderId, $paymentId, $orderEntity['signature']);
        }
        
        return [
            'success' => true,
            'message' => 'Webhook received'
        ];
    }
    
    /**
     * Make API request to Razorpay
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = 'https://api.razorpay.com/v1' . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Get coin packages
     */
    public static function getCoinPackages() {
        return [
            [
                'coins' => 10,
                'amount' => 99,
                'bonus' => 0,
                'popular' => false
            ],
            [
                'coins' => 25,
                'amount' => 199,
                'bonus' => 5,
                'popular' => true
            ],
            [
                'coins' => 50,
                'amount' => 349,
                'bonus' => 15,
                'popular' => false
            ],
            [
                'coins' => 100,
                'amount' => 599,
                'bonus' => 40,
                'popular' => false
            ],
            [
                'coins' => 200,
                'amount' => 999,
                'bonus' => 100,
                'popular' => true
            ]
        ];
    }
}