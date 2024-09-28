<?php
$host = 'localhost';
$username = 'root';  // Update this based on your MySQL setup
$password = '';      // Update if your MySQL setup has a password

// Function to connect to the MySQL server
function connectDB() {
    global $host, $username, $password;
    $conn = new mysqli($host, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>
