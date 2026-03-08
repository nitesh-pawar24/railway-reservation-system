<?php
// File: api/search_trains.php

// 1. Include the database connection
include 'db_connect.php';

// 2. Get the search parameters from the URL (we'll use a GET request)
// We use trim() to remove any extra spaces
$source = trim($_GET['source']);
$destination = trim($_GET['destination']);

// 3. Check if parameters are set
if (empty($source) || empty($destination)) {
    echo json_encode(['status' => 'error', 'message' => 'Source and destination are required.']);
    $conn->close();
    exit;
}

// 4. Prepare the SQL query to find matching trains
// We use placeholders (?) for security
$sql = "SELECT 
            train_no, 
            name, 
            source, 
            destination, 
            DATE_FORMAT(departure_time, '%h:%i %p') AS departure, 
            DATE_FORMAT(arrival_time, '%h:%i %p') AS arrival, 
            seats_available 
        FROM trains 
        WHERE source = ? AND destination = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $source, $destination);

// 5. Execute the query
$stmt->execute();
$result = $stmt->get_result();

// 6. Fetch all matching trains into an array
$trains = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $trains[] = $row; // Add this train to our array
    }
}

// 7. Send the results back to JavaScript as JSON
echo json_encode(['status' => 'success', 'trains' => $trains]);

// 8. Close everything
$stmt->close();
$conn->close();
?>