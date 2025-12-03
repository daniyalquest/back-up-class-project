<?php
function get_db_connection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "admin_db";
    
    // Attempt to establish connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        // Log the error securely instead of exposing it to the user
        error_log("Database Connection failed: " . $conn->connect_error);
        // Display a generic error message
        die("Error: Database connection could not be established.");
    }   
    
    // Set character set for security and proper handling
    $conn->set_charset("utf8mb4");
    
    return $conn;
}