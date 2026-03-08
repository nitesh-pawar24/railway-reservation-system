<?php
// File: api/get_classes.php

// 1. Include the database connection
include 'db_connect.php';

// 2. Prepare the SQL query to get all classes
$sql = "SELECT class_id, class_name, price FROM classes ORDER BY price DESC";

$stmt = $conn->prepare($sql);

// 3. Execute the query
$stmt->execute();
$result = $stmt->get_result();

// 4. Fetch all classes into an array
$classes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row; // Add this class to our array
    }
}

// 7. Send the results back to JavaScript as JSON
echo json_encode(['status' => 'success', 'classes' => $classes]);

// 8. Close everything
$stmt->close();
$conn->close();
?>