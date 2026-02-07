<?php
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Payment.php';

header('Content-Type: application/json');

// Apply rate limiting
checkRateLimit(getClientIdentifier(), 10, 60); // 10 requests per minute

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$amount = $input['amount'] ?? 0;
$coins = $input['coins'] ?? 0;

if (empty($amount) || empty($coins)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid amount or coins'
    ]);
    exit;
}

$payment = new Payment();
$result = $payment->createOrder($amount, $user['id'], $coins);

echo json_encode($result);