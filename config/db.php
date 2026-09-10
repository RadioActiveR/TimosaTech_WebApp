<?php

/* INFO: Single shared MySQL connection (PDO).
 * Adjust these four values if your XAMPP MySQL setup differs
 * from the defaults (root user, no password, localhost).
 */

$db_host = "localhost";
$db_name = "timosatech_db";
$db_user = "root";
$db_pass = "ProjSol_@59287!";

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
    // In production, log this instead of printing it.
    die("Database connection failed: " . $e->getMessage());
}