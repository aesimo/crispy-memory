<?php
/**
 * API: Public Pricing Information
 * Returns only public pricing data without internal margins or costs
 */
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../classes/Payment.php';

header('Content-Type: application/json');

// Apply rate limiting for public endpoint
checkRateLimit(getClientIdentifier(), 30, 60); // 30 requests per minute

try {
    // Get coin packages from Payment class (this is safe to expose)
    $packages = Payment::getCoinPackages();
    
    // Public usage pricing (safe to expose)
    $usagePricing = [
        'submission_cost' => 2,
        'signup_bonus' => 6,
        'referral_bonus' => 3,
        'minimum_withdrawal' => 500,
        'currency' => 'INR'
    ];
    
    // Public withdrawal fees (safe to expose)
    $withdrawalFees = [
        ['min' => 500, 'max' => 9999, 'fee' => 25, 'time' => '24-48 hours'],
        ['min' => 1000, 'max' => 4999, 'fee' => 50, 'time' => '24-48 hours'],
        ['min' => 5000, 'max' => 9999, 'fee' => 75, 'time' => '24-48 hours'],
        ['min' => 10000, 'max' => null, 'fee' => 100, 'time' => '24-48 hours']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'coin_packages' => $packages,
            'usage_pricing' => $usagePricing,
            'withdrawal_fees' => $withdrawalFees
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch pricing information'
    ]);
}