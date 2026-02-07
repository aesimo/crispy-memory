<?php
/**
 * API: Get Categories
 * Returns public category information without sensitive data
 */
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

// Apply rate limiting for public endpoint
checkRateLimit(getClientIdentifier(), 60, 60); // 60 requests per minute

$db = Database::getInstance();

try {
    // Only return public-safe category information
    $categories = $db->fetchAll("
        SELECT id, name, description, estimated_earning 
        FROM categories 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch categories'
    ]);
}