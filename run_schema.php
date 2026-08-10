<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; 

try {
    // Connect without dbname first to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating database if it doesn't exist...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS findly");
    $pdo->exec("USE findly");
    
    echo "Reading schema.sql...\n";
    $sql = file_get_contents('schema.sql');
    
    echo "Executing schema.sql...\n";
    $pdo->exec($sql);
    
    echo "Schema executed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
