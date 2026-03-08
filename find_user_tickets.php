<?php
// File: api/find_user_tickets.php
include 'db_connect.php';

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);

if (!isset($data->firstName) || !isset($data->ticketId)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    $conn->close();
    exit;
}

$firstName = $data->firstName;
$ticketId = $data->ticketId;
$found_user_id = null;

try {
    // --- STEP 1: Find the ticket and get the user_id ---
    $sql_find_ticket = "SELECT user_id FROM tickets WHERE ticket_id = ?";
    $stmt1 = $conn->prepare($sql_find_ticket);
    $stmt1->bind_param("i", $ticketId);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    if ($result1->num_rows == 0) {
        throw new Exception("Ticket ID not found.");
    }
    
    $found_user_id = $result1->fetch_assoc()['user_id'];
    $stmt1->close();

    // --- STEP 2: Verify the user's first name ---
    $sql_verify_user = "SELECT first_name FROM users WHERE user_id = ?";
    $stmt2 = $conn->prepare($sql_verify_user);
    $stmt2->bind_param("i", $found_user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($result2->num_rows == 0) {
        throw new Exception("User associated with this ticket not found.");
    }
    
    $db_first_name = $result2->fetch_assoc()['first_name'];
    $stmt2->close();
    
    // --- STEP 3: Compare the names ---
    // strcasecmp returns 0 if they are equal (ignoring case)
    if (strcasecmp($db_first_name, $firstName) != 0) {
        throw new Exception("First name does not match the ticket record. Verification failed.");
    }

    // --- STEP 4: If verification passed, get all tickets for that user ---
    $sql_get_all_tickets = "SELECT 
                                tk.ticket_id, 
                                tk.total_amount, 
                                tk.status, 
                                tk.booking_date,
                                tr.name AS train_name,
                                tr.source,
                                tr.destination
                            FROM tickets tk
                            JOIN trains tr ON tk.train_no = tr.train_no
                            WHERE tk.user_id = ?
                            ORDER BY tk.booking_date DESC";
                            
    $stmt3 = $conn->prepare($sql_get_all_tickets);
    $stmt3->bind_param("i", $found_user_id);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    
    $tickets_array = [];
    while ($row = $result3->fetch_assoc()) {
        $tickets_array[] = $row;
    }
    $stmt3->close();

    // --- STEP 5: Send the list of tickets back ---
    echo json_encode([
        'status' => 'success',
        'tickets' => $tickets_array
    ]);

} catch (Exception $e) {
    // Catch any errors from Step 1, 2, or 3
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>