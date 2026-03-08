<?php


//Include the database connection file
include 'db_connect.php';

// Get the JSON data that JavaScript will send
$jsonData = file_get_contents('php://input');

//  Decode the JSON into a PHP object
$data = json_decode($jsonData);

//  Check if we actually received data
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    $conn->close(); // Close connection
    exit; // Stop the script
}

//  Prepare the SQL query in a secure way (using placeholders '?')
$sql = "INSERT INTO users (first_name, last_name, dob, age, gender, mobile_no, address, city) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

//  Bind the user's data to the placeholders
// "sssissss" means: String, String, String, Integer, String, String, String, String
$stmt->bind_param(
    "sssissss",
    $data->firstName,
    $data->lastName,
    $data->dob,
    $data->age,
    $data->gender,
    $data->mobile,
    $data->address,
    $data->city
);

//  Execute the query and send a response back to JavaScript
if ($stmt->execute()) {
    // Success! Get the new ID that the database just created
    $newUserId = $conn->insert_id;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'User registered successfully!',
        'user_id' => $newUserId // Send the new ID back
    ]);
} else {
    // Failure
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $stmt->error
    ]);
}

// 8. Close everything
$stmt->close();
$conn->close();

?>