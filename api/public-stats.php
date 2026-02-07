<?php
/**
 * API: Public Statistics
 * Returns only safe, non-sensitive platform statistics
 * Excludes financial data, user counts, and internal metrics
 */
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

// Apply rate limiting for public endpoint
checkRateLimit(getClientIdentifier(), 30, 60); // 30 requests per minute

$db = Database::getInstance();

try {
    // Only return public-safe statistics
    $stats = $db->fetchOne("
        SELECT 
            (SELECT COUNT(*) FROM ideas WHERE status = 'approved') as approved_ideas_count,
            (SELECT COUNT(DISTINCT category_id) FROM categories WHERE is_active = 1) as total_categories,
            '10K+' as ideas_submitted_display,
            '5K+' as active_students_display,
            '₹10L+' as paid_out_display
    ");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'ideas_submitted' => $stats['ideas_submitted_display'],
            'active_students' => $stats['active_students_display'],
            'paid_out' => $stats['paid_out_display'],
            'total_categories' => $stats['total_categories'],
            'approved_ideas' => $stats['approved_ideas_count']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch statistics'
    ]);
}