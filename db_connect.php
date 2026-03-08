<?php
// File: api/db_connect.php

// Database connection details
$servername = "localhost"; // This is the default for XAMPP
$username = "root";        // This is the default for XAMPP
$password = "";            // This is the default for XAMPP
$dbname = "railway_db";    // The database we created

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If connection fails, stop and show an error
    die("Connection failed: " . $conn->connect_error);
}

// Set the character set to utf8 (good practice)
$conn->set_charset("utf8");

// Set the header to tell the browser we're sending JSON
// This is important for our JavaScript to understand the response
header('Content-Type: application/json');

?>