<?php
/**
 * SEO Class
 * Handles meta tags, OpenGraph, structured data, and sitemap generation
 */

class SEO {
    
    /**
     * Generate meta tags
     */
    public static function generateMetaTags($title, $description, $keywords = '', $image = '') {
        $meta = [];
        
        // Basic meta tags
        $meta[] = '<meta name="title" content="' . htmlspecialchars($title) . '">';
        $meta[] = '<meta name="description" content="' . htmlspecialchars($description) . '">';
        
        if ($keywords) {
            $meta[] = '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">';
        }
        
        // Author
        $meta[] = '<meta name="author" content="IdeaOne">';
        
        // Robots
        $meta[] = '<meta name="robots" content="index, follow">';
        
        // Viewport
        $meta[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        
        // Charset
        $meta[] = '<meta charset="UTF-8">';
        
        // OpenGraph tags
        $meta[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '">';
        $meta[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '">';
        $meta[] = '<meta property="og:type" content="website">';
        $meta[] = '<meta property="og:url" content="' . self::getCurrentUrl() . '">';
        
        if ($image) {
            $meta[] = '<meta property="og:image" content="' . htmlspecialchars($image) . '">';
            $meta[] = '<meta property="og:image:width" content="1200">';
            $meta[] = '<meta property="og:image:height" content="630">';
        }
        
        $meta[] = '<meta property="og:site_name" content="IdeaOne">';
        $meta[] = '<meta property="og:locale" content="en_US">';
        
        // Twitter Card tags
        $meta[] = '<meta name="twitter:card" content="summary_large_image">';
        $meta[] = '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">';
        $meta[] = '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">';
        
        if ($image) {
            $meta[] = '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">';
        }
        
        // Canonical URL
        $meta[] = '<link rel="canonical" href="' . self::getCurrentUrl() . '">';
        
        return implode("\n", $meta);
    }
    
    /**
     * Get current URL
     */
    private static function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = $_SERVER['REQUEST_URI'];
        return $protocol . '://' . $host . $path;
    }
    
    /**
     * Generate structured data (JSON-LD)
     */
    public static function generateStructuredData($type, $data) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type
        ];
        
        $schema = array_merge($schema, $data);
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    /**
     * Generate Organization schema
     */
    public static function generateOrganizationSchema() {
        $data = [
            'name' => 'IdeaOne',
            'url' => 'https://ideaone.com',
            'logo' => 'https://ideaone.com/assets/images/logo.png',
            'description' => 'IdeaOne - Turn Your Innovative Ideas Into Earnings. A platform for students to earn money by submitting innovative ideas.',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Mumbai',
                'addressRegion' => 'Maharashtra',
                'addressCountry' => 'IN'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+91-98765-43210',
                'contactType' => 'customer service',
                'email' => 'support@ideaone.com'
            ],
            'sameAs' => [
                'https://www.facebook.com/ideaone',
                'https://www.twitter.com/ideaone',
                'https://www.linkedin.com/company/ideaone'
            ]
        ];
        
        return self::generateStructuredData('Organization', $data);
    }
    
    /**
     * Generate WebSite schema
     */
    public static function generateWebSiteSchema() {
        $data = [
            'name' => 'IdeaOne',
            'url' => 'https://ideaone.com',
            'description' => 'Turn Your Innovative Ideas Into Earnings',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => 'https://ideaone.com/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        return self::generateStructuredData('WebSite', $data);
    }
    
    /**
     * Generate Breadcrumb schema
     */
    public static function generateBreadcrumbSchema($breadcrumbs) {
        $items = [];
        $position = 1;
        
        foreach ($breadcrumbs as $name => $url) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url
            ];
        }
        
        $data = [
            'itemListElement' => $items
        ];
        
        return self::generateStructuredData('BreadcrumbList', $data);
    }
    
    /**
     * Generate Article schema
     */
    public static function generateArticleSchema($data) {
        $defaultData = [
            'headline' => $data['title'] ?? '',
            'image' => $data['image'] ?? 'https://ideaone.com/assets/images/og-image.png',
            'author' => [
                '@type' => 'Organization',
                'name' => 'IdeaOne'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'IdeaOne',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => 'https://ideaone.com/assets/images/logo.png'
                ]
            ],
            'datePublished' => $data['date'] ?? date('Y-m-d'),
            'dateModified' => $data['modified'] ?? date('Y-m-d')
        ];
        
        return self::generateStructuredData('Article', array_merge($defaultData, $data));
    }
    
    /**
     * Generate sitemap
     */
    public static function generateSitemap($urls) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($url['loc']) . '</loc>';
            
            if (isset($url['lastmod'])) {
                $xml .= '<lastmod>' . $url['lastmod'] . '</lastmod>';
            }
            
            if (isset($url['changefreq'])) {
                $xml .= '<changefreq>' . $url['changefreq'] . '</changefreq>';
            }
            
            if (isset($url['priority'])) {
                $xml .= '<priority>' . $url['priority'] . '</priority>';
            }
            
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Generate robots.txt
     */
    public static function generateRobotsTxt() {
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /moderator/\n";
        $robots .= "Disallow: /user/\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /auth/\n";
        $robots .= "Disallow: /uploads/\n";
        $robots .= "Disallow: /classes/\n";
        $robots .= "Disallow: /config/\n";
        $robots .= "Disallow: /database/\n";
        $robots .= "Disallow: /includes/\n";
        $robots .= "\n";
        $robots .= "Sitemap: https://ideaone.com/sitemap.xml\n";
        
        return $robots;
    }
    
    /**
     * Slugify text for URLs
     */
    public static function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        return empty($text) ? 'n-a' : $text;
    }
}