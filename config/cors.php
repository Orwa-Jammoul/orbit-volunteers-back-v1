<?php
class CORS {
    private static $allowedOrigins = [];
    private static $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];
    private static $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'];
    private static $exposedHeaders = ['Authorization'];
    private static $maxAge = 86400; // 24 hours
    
    public static function init() {
        // Load allowed origins from environment
        if (isset($_ENV['ALLOWED_ORIGINS'])) {
            self::$allowedOrigins = explode(',', $_ENV['ALLOWED_ORIGINS']);
        } else {
            // Default allowed origins for development
            self::$allowedOrigins = [
                'http://localhost:3000',
                'http://localhost:8080',
                'http://localhost:5500',
                'https://yourdomain.com'
            ];
        }
        
        self::handleCORS();
    }
    
    private static function handleCORS() {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Check if origin is allowed
        if (self::isOriginAllowed($origin)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        }
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::handlePreflight();
        }
    }
    
    private static function handlePreflight() {
        header("Access-Control-Allow-Methods: " . implode(', ', self::$allowedMethods));
        header("Access-Control-Allow-Headers: " . implode(', ', self::$allowedHeaders));
        header("Access-Control-Max-Age: " . self::$maxAge);
        header("Content-Length: 0");
        header("Content-Type: text/plain");
        http_response_code(200);
        exit();
    }
    
    private static function isOriginAllowed($origin) {
        // Allow all in development mode
        if (getenv('APP_ENV') === 'development') {
            return true;
        }
        
        // Check against whitelist
        return in_array($origin, self::$allowedOrigins);
    }
    
    public static function addAllowedOrigin($origin) {
        self::$allowedOrigins[] = $origin;
    }
    
    public static function addAllowedMethod($method) {
        self::$allowedMethods[] = strtoupper($method);
    }
    
    public static function addAllowedHeader($header) {
        self::$allowedHeaders[] = $header;
    }
}

// Initialize CORS
CORS::init();
?>