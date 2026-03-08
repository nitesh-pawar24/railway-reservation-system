<?php
// File: api/cancel_ticket.php
include 'db_connect.php';

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);

if (!isset($data->ticket_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Ticket ID not provided.']);
    $conn->close();
    exit;
}

$ticketId = $data->ticket_id;
$train_no_to_update = null;

// Use a transaction
$conn->begin_transaction();

try {
    // --- Step 1: Get the ticket details ---
    $sql_get_ticket = "SELECT train_no, status FROM tickets WHERE ticket_id = ?";
    $stmt1 = $conn->prepare($sql_get_ticket);
    $stmt1->bind_param("i", $ticketId);
    $stmt1->execute();
    $result = $stmt1->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Ticket not found.");
    }
    
    $ticket = $result->fetch_assoc();
    $train_no_to_update = $ticket['train_no'];
    
    // Check if it's already cancelled
    if ($ticket['status'] == 'Cancelled') {
        throw new Exception("This ticket is already cancelled.");
    }
    $stmt1->close();

    // --- Step 2: Update the ticket status to 'Cancelled' ---
    $sql_update_ticket = "UPDATE tickets SET status = 'Cancelled' WHERE ticket_id = ?";
    $stmt2 = $conn->prepare($sql_update_ticket);
    $stmt2->bind_param("i", $ticketId);
    $stmt2->execute();
    $stmt2->close();
    
    // --- Step 3: Add 1 seat back to the train's availability ---
    $sql_update_train = "UPDATE trains SET seats_available = seats_available + 1 WHERE train_no = ?";
    $stmt3 = $conn->prepare($sql_update_train);
    $stmt3->bind_param("i", $train_no_to_update);
    $stmt3->execute();
    $stmt3->close();

    // --- If all good, commit the changes ---
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Ticket successfully cancelled.'
    ]);

} catch (Exception $e) {
    // If anything fails, roll back all changes
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>