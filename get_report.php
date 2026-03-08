<?php
// File: api/get_report.php

include 'db_connect.php';

// We will store all our results in this one array
$reportData = [];

// Get Total Number of Users (Same as before)   
//  COUNT
$sql_users = "SELECT COUNT(*) AS total_users FROM users";
$result_users = $conn->query($sql_users);
$reportData['total_users'] = $result_users->fetch_assoc()['total_users'];

//Get Total Number of Tickets Booked (Same as before)   
//  COUNT
$sql_tickets = "SELECT COUNT(*) AS total_tickets FROM tickets";
$result_tickets = $conn->query($sql_tickets);
$reportData['total_tickets'] = $result_tickets->fetch_assoc()['total_tickets'];

// Get Total Revenue (Same as before) 
// SUM
$sql_revenue = "SELECT SUM(total_amount) AS total_revenue FROM tickets WHERE status = 'Confirmed'";
$result_revenue = $conn->query($sql_revenue);
$reportData['total_revenue'] = $result_revenue->fetch_assoc()['total_revenue'] ?? 0;

// Get Detailed Breakdown Per Train (NEW)
//  JOIN,COUNT,SUM,GROUP BY,ORDER BY,DESC
$sql_breakdown = "SELECT 
                        t.name, 
                        COUNT(tk.ticket_id) AS booking_count,
                        SUM(tk.total_amount) AS train_revenue
                    FROM tickets tk
                    JOIN trains t ON tk.train_no = t.train_no
                    GROUP BY t.name
                    ORDER BY booking_count DESC"; // Ordered by most popular

$result_breakdown = $conn->query($sql_breakdown);
$train_breakdown_array = [];

if ($result_breakdown->num_rows > 0) {
    while ($row = $result_breakdown->fetch_assoc()) {
        // We'll store the revenue as a number
        $row['train_revenue'] = $row['train_revenue'] ?? 0;
        $train_breakdown_array[] = $row;
    }
}
$reportData['train_breakdown'] = $train_breakdown_array;


// Finally, send all the data back as one big JSON object
echo json_encode(['status' => 'success', 'data' => $reportData]);

$conn->close();
?>