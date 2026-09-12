<?php

/* INFO: Single shared MySQL connection (PDO).
 * Path correctly targets the .env file in the root directory.
 */

// 1. Target the .env file one level up from /config
$envPath = dirname(__DIR__) . '/.env';

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue; 
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, '"\''); // Clean up surrounding quotes
            
            $_ENV[$name] = $value;
        }
    }
}

// 2. Map environment variables with your default fallbacks
$db_host = $_ENV['DB_HOST']     ?? "";
$db_name = $_ENV['DB_NAME']     ?? "";
$db_user = $_ENV['DB_USER']     ?? "";
$db_pass = $_ENV['DB_PASSWORD'] ?? "";

// 3. Connection
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your system logs.");
}