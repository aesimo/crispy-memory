<?php
/**
 * Sitemap Generator
 * Run this script to generate sitemap.xml
 */

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/SEO.php';

$db = Database::getInstance();

// Define static URLs
$urls = [
    [
        'loc' => 'https://ideaone.com/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '1.0'
    ],
    [
        'loc' => 'https://ideaone.com/pages/features.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => 'https://ideaone.com/pages/benefits.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => 'https://ideaone.com/pages/categories.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => 'https://ideaone.com/pages/earning.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => 'https://ideaone.com/pages/pricing.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => 'https://ideaone.com/pages/contact.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'monthly',
        'priority' => '0.7'
    ],
    [
        'loc' => 'https://ideaone.com/auth/login.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'monthly',
        'priority' => '0.5'
    ],
    [
        'loc' => 'https://ideaone.com/auth/register.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'monthly',
        'priority' => '0.5'
    ]
];

// Add category pages (if you want individual category pages)
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name");
foreach ($categories as $category) {
    $urls[] = [
        'loc' => 'https://ideaone.com/pages/categories.php?id=' . $category['id'],
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.6'
    ];
}

// Generate sitemap
$sitemap = SEO::generateSitemap($urls);

// Save to file
file_put_contents(__DIR__ . '/sitemap.xml', $sitemap);

echo "Sitemap generated successfully!\n";
echo "Location: " . __DIR__ . '/sitemap.xml' . "\n';