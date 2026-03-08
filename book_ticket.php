<?php
// File: api/book_ticket.php

// 1. Include the database connection
include 'db_connect.php';

// 2. Get the JSON data from JavaScript
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);

// 3. Check if we have all the data
if (
    !isset($data->userData) ||
    !isset($data->bookingData) ||
    !isset($data->paymentData)
) {
    echo json_encode(['status' => 'error', 'message' => 'Incomplete data received.']);
    $conn->close();
    exit;
}

// 4. Assign data to variables
$user = $data->userData;
$booking = $data->bookingData;
$payment = $data->paymentData;

// We need to use a "transaction"
// This makes sure that BOTH the ticket AND the payment are created.
// If one fails, the whole thing is cancelled (rolled back).
$conn->begin_transaction();

try {
    // --- STEP 1: Insert into 'tickets' table ---
    $sql_ticket = "INSERT INTO tickets (user_id, train_no, class_id, total_amount, status) 
                   VALUES (?, ?, ?, ?, ?)";
    
    $stmt_ticket = $conn->prepare($sql_ticket);
    $status = "Confirmed"; // We'll set it as confirmed since payment is "successful"
    
    $stmt_ticket->bind_param(
        "iiids",
        $user->userId,
        $booking->selectedTrain->train_no,
        $booking->selectedClassId,
        $booking->price,
        $status
    );
    
    $stmt_ticket->execute();

    // --- STEP 2: Get the new ticket_id that was just created ---
    $new_ticket_id = $conn->insert_id;
    $stmt_ticket->close();

    // --- STEP 3: Insert into 'payments' table ---
    $sql_payment = "INSERT INTO payments (ticket_id, bank_branch, card_no) 
                    VALUES (?, ?, ?)";
    
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param(
        "iss",
        $new_ticket_id,
        $payment->bankBranch,
        $payment->cardNo
    );
    
    $stmt_payment->execute();
    $stmt_payment->close();

    // --- (Optional but good) Step 4: Update train seats ---
    $sql_update_train = "UPDATE trains SET seats_available = seats_available - 1 
                         WHERE train_no = ?";
    $stmt_update = $conn->prepare($sql_update_train);
    $stmt_update->bind_param("i", $booking->selectedTrain->train_no);
    $stmt_update->execute();
    $stmt_update->close();


    // --- STEP 5: If all queries worked, commit the transaction ---
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Booking and payment successful! Your ticket is confirmed.',
        'ticket_id' => $new_ticket_id
    ]);

} catch (Exception $e) {
    // --- STEP 6: If any query failed, roll back all changes ---
    $conn->rollback();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Booking failed: ' . $e->getMessage()
    ]);
}

// 8. Close the connection
$conn->close();
?>