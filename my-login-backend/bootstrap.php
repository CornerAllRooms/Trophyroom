<?php
declare(strict_types=1);
use App\Auth\AuthHandler;
// Debug headers
error_log("=== BOOTSTRAP START ===");
echo "Bootstrap loading...\n";

// 1. First test - Core PHP
echo "1. Core PHP working\n";

// 2. Test autoloader
if (file_exists(__DIR__.'/../../vendor/autoload.php')) {
    require __DIR__.'/../../vendor/autoload.php';
    echo "2. Autoloader loaded\n";
} else {
    throw new Exception("Autoloader missing");
}

// 3. Test environment
$envPath = __DIR__.'/../../.env';  // Goes up two levels from public/my-login-backend/
if (!file_exists($envPath)) {
    throw new Exception(".env file missing at: ".realpath($envPath));
}
// 4. Test MongoDB extension
if (extension_loaded('mongodb')) {
    echo "4. MongoDB extension loaded\n";
} else {
    throw new Exception("MongoDB extension not loaded");
}

error_log("=== BOOTSTRAP COMPLETE ===");
if (!class_exists('Google_Client')) {
    throw new RuntimeException('Google API Client not installed. Run: composer require google/apiclient');
}