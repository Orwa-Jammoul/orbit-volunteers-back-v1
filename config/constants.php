<?php
// config/constants.php
// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env');
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'orbit_volunteers');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-secret-key-change-this');
define('JWT_EXPIRY', getenv('JWT_EXPIRY') ?: 86400);

define('API_VERSION', getenv('API_VERSION') ?: 'v1');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

define('ALLOWED_ORIGINS', explode(',', getenv('ALLOWED_ORIGINS') ?: 'http://localhost:3000,http://localhost:8080'));

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

date_default_timezone_set('UTC');
?>