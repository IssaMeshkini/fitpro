<?php
// conn.php - Database Connection File ONLY

// Database configuration
$host = "localhost";
$username = "root";  // Change if needed
$password = "";      // Change if needed
$database = "fitness_tracker_db";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>