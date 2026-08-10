<?php
// config/db.php
$host = '127.0.0.1';
$dbname = 'findly';
$user = 'root';
$pass = ''; // Default XAMPP

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, $options);
} catch (PDOException $e) {
    // Log exception for debugging, don't expose to user in production
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection failed.");
}
