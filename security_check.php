<?php
/**
 * Security Check Script
 * Comprehensive security audit for IdeaOne application
 */

class SecurityAudit {
    private $results = [];
    private $warnings = [];
    private $errors = [];
    private $passed = [];
    
    public function __construct() {
        require_once __DIR__ . '/config/config.php';
    }
    
    /**
     * Run all security checks
     */
    public function runAllChecks() {
        echo "=== IdeaOne Security Audit ===\n\n";
        
        $this->checkEnvironmentVariables();
        $this->checkFilePermissions();
        $this->checkSensitiveFiles();
        $this->checkCodeSecurity();
        $this->checkAPIEndpoints();
        $this->checkDatabaseSecurity();
        $this->checkFrontendSecurity();
        
        $this->generateReport();
    }
    
    /**
     * Check environment variables
     */
    private function checkEnvironmentVariables() {
        echo "[+] Checking Environment Variables...\n";
        
        $required = [
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
            'SUPABASE_URL', 'SUPABASE_ANON_KEY', 'SUPABASE_SERVICE_ROLE_KEY',
            'RAZORPAY_KEY_ID', 'RAZORPAY_KEY_SECRET',
            'APP_URL', 'JWT_SECRET'
        ];
        
        $missing = [];
        foreach ($required as $var) {
            if (empty(Config::get($var))) {
                $missing[] = $var;
            }
        }
        
        if (empty($missing)) {
            $this->passed[] = 'All required environment variables are set';
        } else {
            $this->errors[] = 'Missing environment variables: ' . implode(', ', $missing);
        }
        
        // Check if using default values
        $defaults = [
            'your-database-password', 'your-anon-key', 'your-service-role-key',
            'your-razorpay-key-id', 'your-razorpay-key-secret', 'your-jwt-secret-key-change-this'
        ];
        
        foreach ($required as $var) {
            $value = Config::get($var);
            foreach ($defaults as $default) {
                if (strpos($value, $default) !== false) {
                    $this->errors[] = "Environment variable {$var} is using default value - must be changed";
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check file permissions
     */
    private function checkFilePermissions() {
        echo "[+] Checking File Permissions...\n";
        
        $sensitiveFiles = [
            '.env',
            'config/config.php',
            'classes/Database.php',
            'classes/Payment.php'
        ];
        
        foreach ($sensitiveFiles as $file) {
            $filepath = __DIR__ . '/' . $file;
            if (file_exists($filepath)) {
                $perms = substr(sprintf('%o', fileperms($filepath)), -4);
                if ($perms > '0644') {
                    $this->warnings[] = "File {$file} has permissive permissions: {$perms}";
                } else {
                    $this->passed[] = "File {$file} has secure permissions: {$perms}";
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check for exposed sensitive files
     */
    private function checkSensitiveFiles() {
        echo "[+] Checking for Exposed Sensitive Files...\n";
        
        $shouldNotExist = [
            '.env.local',
            '.env.production',
            'database.sqlite',
            'passwords.txt',
            'secrets.txt'
        ];
        
        foreach ($shouldNotExist as $file) {
            $filepath = __DIR__ . '/' . $file;
            if (file_exists($filepath)) {
                $this->errors[] = "Sensitive file found in repository: {$file}";
            }
        }
        
        // Check .gitignore
        $gitignore = file_get_contents(__DIR__ . '/.gitignore');
        if (strpos($gitignore, '.env') === false) {
            $this->errors[] = ".env file is not in .gitignore";
        } else {
            $this->passed[] = ".env file is in .gitignore";
        }
        
        echo "\n";
    }
    
    /**
     * Check code for security issues
     */
    private function checkCodeSecurity() {
        echo "[+] Checking Code Security...\n";
        
        // Check for hardcoded secrets in PHP files
        $phpFiles = $this->findFiles(__DIR__, '.php');
        $hardcodedSecrets = [];
        
        $patterns = [
            '/["\'](sk_test_[a-zA-Z0-9]{32})["\']/', // Stripe secret
            '/["\'](sk_live_[a-zA-Z0-9]{32})["\']/', // Stripe live
            '/["\'](AIza[A-Za-z0-9_\-]{35})["\']/', // Google API key
            '/["\'](AKIA[0-9A-Z]{16})["\']/', // AWS access key
        ];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $hardcodedSecrets[] = "{$file}: " . $matches[1];
                }
            }
        }
        
        if (empty($hardcodedSecrets)) {
            $this->passed[] = "No hardcoded API keys found in PHP files";
        } else {
            $this->errors[] = "Hardcoded API keys found: " . implode(', ', $hardcodedSecrets);
        }
        
        // Check for SQL injection vulnerabilities
        $unsafePatterns = [
            '/\$_GET\[.*\].*mysql_query/i',
            '/\$_POST\[.*\].*mysql_query/i',
            '/\$_GET\[.*\].*query\(.*\$_/i',
            '/\$_POST\[.*\].*query\(.*\$_/i',
        ];
        
        $sqlInjectionIssues = [];
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            foreach ($unsafePatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $sqlInjectionIssues[] = $file;
                }
            }
        }
        
        if (empty($sqlInjectionIssues)) {
            $this->passed[] = "No obvious SQL injection vulnerabilities found";
        } else {
            $this->warnings[] = "Potential SQL injection vulnerabilities in: " . implode(', ', array_unique($sqlInjectionIssues));
        }
        
        echo "\n";
    }
    
    /**
     * Check API endpoints security
     */
    private function checkAPIEndpoints() {
        echo "[+] Checking API Endpoints Security...\n";
        
        $apiDir = __DIR__ . '/api';
        $apiFiles = glob($apiDir . '/*.php');
        
        foreach ($apiFiles as $file) {
            $content = file_get_contents($file);
            $basename = basename($file);
            
            // Check if middleware is included
            if (strpos($content, 'middleware.php') === false && $basename !== 'middleware.php') {
                $this->warnings[] = "API endpoint {$basename} does not include security middleware";
            } else {
                $this->passed[] = "API endpoint {$basename} includes security middleware";
            }
            
            // Check for rate limiting
            if (strpos($content, 'checkRateLimit') === false && $basename !== 'middleware.php') {
                $this->warnings[] = "API endpoint {$basename} does not have rate limiting";
            } else {
                $this->passed[] = "API endpoint {$basename} has rate limiting";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check database security
     */
    private function checkDatabaseSecurity() {
        echo "[+] Checking Database Security...\n";
        
        // Check for prepared statements
        $dbFile = __DIR__ . '/classes/Database.php';
        if (file_exists($dbFile)) {
            $content = file_get_contents($dbFile);
            if (strpos($content, 'prepare') !== false) {
                $this->passed[] = "Database class uses prepared statements";
            } else {
                $this->errors[] = "Database class does not use prepared statements";
            }
        }
        
        // Check for Supabase RLS
        $schemaFile = __DIR__ . '/database/supabase-schema.sql';
        if (file_exists($schemaFile)) {
            $content = file_get_contents($schemaFile);
            if (strpos($content, 'ENABLE ROW LEVEL SECURITY') !== false) {
                $this->passed[] = "Supabase Row Level Security is configured";
            } else {
                $this->warnings[] = "Supabase Row Level Security not found in schema";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check frontend security
     */
    private function checkFrontendSecurity() {
        echo "[+] Checking Frontend Security...\n";
        
        $jsFiles = $this->findFiles(__DIR__ . '/assets', '.js');
        
        // Check for exposed secrets in JS
        $secretsFound = [];
        $secretPatterns = [
            '/["\']([a-zA-Z0-9_\-]{20,})["\'][\s]*=.*["\'](key|secret|password|token)/i',
            '/RAZORPAY_KEY_ID["\']?\s*[:=]\s*["\']([a-zA-Z0-9_\-]+)/i',
            '/SUPABASE.*KEY["\']?\s*[:=]\s*["\']([a-zA-Z0-9_\-]+)/i',
        ];
        
        foreach ($jsFiles as $file) {
            $content = file_get_contents($file);
            foreach ($secretPatterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $secretsFound[] = basename($file);
                }
            }
        }
        
        if (empty($secretsFound)) {
            $this->passed[] = "No exposed secrets in JavaScript files";
        } else {
            $this->errors[] = "Potential secrets exposed in JavaScript: " . implode(', ', array_unique($secretsFound));
        }
        
        // Check if sensitive data is exposed in public pages
        $publicPages = ['pages/categories.php', 'pages/pricing.php', 'index.php'];
        foreach ($publicPages as $page) {
            $filepath = __DIR__ . '/' . $page;
            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
                if (strpos($content, 'Database::') !== false) {
                    $this->warnings[] = "Public page {$page} has direct database queries";
                } else {
                    $this->passed[] = "Public page {$page} does not have direct database access";
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Helper function to find files
     */
    private function findFiles($dir, $extension) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === ltrim($extension, '.')) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Generate security report
     */
    private function generateReport() {
        echo "=== Security Audit Report ===\n\n";
        
        echo "✓ Passed: " . count($this->passed) . "\n";
        echo "⚠ Warnings: " . count($this->warnings) . "\n";
        echo "✗ Errors: " . count($this->errors) . "\n\n";
        
        if (!empty($this->passed)) {
            echo "--- Passed Checks ---\n";
            foreach ($this->passed as $check) {
                echo "  ✓ {$check}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "--- Warnings ---\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ {$warning}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "--- Critical Errors ---\n";
            foreach ($this->errors as $error) {
                echo "  ✗ {$error}\n";
            }
            echo "\n";
        }
        
        $totalIssues = count($this->warnings) + count($this->errors);
        if ($totalIssues === 0) {
            echo "🎉 All security checks passed!\n";
            return true;
        } else {
            echo "⚠️ Found {$totalIssues} issues that need attention.\n";
            return false;
        }
    }
}

// Run the audit
$audit = new SecurityAudit();
$audit->runAllChecks();