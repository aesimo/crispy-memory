<?php
session_start();
require_once __DIR__ . '/../classes/Payment.php';

header('Content-Type: application/json');

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