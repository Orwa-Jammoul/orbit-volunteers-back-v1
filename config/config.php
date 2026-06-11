<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_PORT', $_ENV['DB_PORT']);

define('JWT_SECRET', $_ENV['JWT_SECRET']);
define('JWT_EXPIRY', $_ENV['JWT_EXPIRY']);

define('API_VERSION', $_ENV['API_VERSION']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ALLOWED_ORIGINS', explode(',', $_ENV['ALLOWED_ORIGINS']));

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

date_default_timezone_set('UTC');
?>