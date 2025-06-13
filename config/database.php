<?php
// config/database.php
$host = 'localhost';
$dbname = 'project_tracker';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $pdo = new PDO( "mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ] );

    // Create database if it doesn't exist
    $pdo->exec( "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" );

    // Select the database
    $pdo->exec( "USE `$dbname`" );

} catch ( PDOException $e ) {
    die( 'Database connection failed: ' . $e->getMessage() );
}

// Function to get database connection

function get_db_connection() {
    global $pdo;
    return $pdo;
}