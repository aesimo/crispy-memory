<?php
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Payment.php';

header('Content-Type: application/json');

// Apply rate limiting
checkRateLimit(getClientIdentifier(), 20, 60); // 20 requests per minute for payment verification

$auth = new Auth();
$auth->requireAuth();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$orderId = $input['order_id'] ?? '';
$paymentId = $input['payment_id'] ?? '';
$signature = $input['signature'] ?? '';

if (empty($orderId) || empty($paymentId) || empty($signature)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid payment details'
    ]);
    exit;
}

$payment = new Payment();
$result = $payment->processPayment($orderId, $paymentId, $signature);

echo json_encode($result);