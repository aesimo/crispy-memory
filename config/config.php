<?php
/**
 * Configuration Loader
 * Loads environment variables and sets up application configuration
 */

class Config {
    private static $config = [];
    
    public static function load() {
        if (!empty(self::$config)) {
            return self::$config;
        }
        
        // Load .env file
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                self::$config[trim($name)] = trim($value);
                $_ENV[trim($name)] = trim($value);
            }
        }
        
        return self::$config;
    }
    
    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? $default;
    }
    
    public static function isProduction() {
        return self::get('APP_ENV') === 'production';
    }
}

// Auto-load configuration
Config::load();