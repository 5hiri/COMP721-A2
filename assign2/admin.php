<?php
/*
REDACTED REDACTED 

Description: Server-side PHP script for the CabsOnline admin interface.
This file handles AJAX requests from the admin panel for booking management
and search operations. Provides two main administrative functions:
- Search functionality: Find specific bookings or unassigned bookings within 2 hours
- Assignment functionality: Update booking status from 'unassigned' to 'assigned'
- Database validation and error handling for all operations
- JSON response formatting for client-side consumption

Functions:
- search(): Processes booking search requests with validation
- assignBooking(): Handles taxi assignment to specific booking requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('../../files/sqlinfo.inc.php'); // Path as specified for server environment
    $conn = mysqli_connect(
        $sql_host, 
        $sql_user, 
        $sql_pass, 
        $sql_db
    );

    if (!$conn) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Database connection failed: " . mysqli_connect_error()]);
        exit;
    }

    // Decode the JSON payload
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        // JSON decoding failed
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Invalid JSON data received." . mysqli_error($conn)]);
        exit;
    }

    if (isset($data['search'])) {
        search($conn, $data);
    }

    if (isset($data['assignBooking'])) {
        assignBooking($conn, $data);
    }


    mysqli_close($conn);

    exit;
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST requests are allowed." . mysqli_error($conn)]); // Send JSON error
    exit;
}

function assignBooking($conn, $data) {
    $bookingId = $data['assignBooking'] ?? '';

    // Validate format (BRN followed by 5 digits)
    if (!preg_match('/^BRN\d{5}$/', $bookingId)) {
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Invalid format. Expected format: BRN00001" . mysqli_error($conn)]);
        exit;
    }

    $tableName = 'bookings';
    $checkSql = "SELECT booking_number FROM $tableName WHERE booking_number = ?";
    $stmt = mysqli_prepare($conn, $checkSql);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not prepare statement: " . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 's', $bookingId);
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not execute statement: " . mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 0) {
        http_response_code(404); // Not Found
        echo json_encode(["error" => "Booking not found."]);
        mysqli_stmt_close($stmt);
        exit;
    }
    mysqli_stmt_close($stmt);
    mysqli_free_result($result);
    // Booking exists, proceed to assign
    $assignSql = "UPDATE $tableName SET status = 'assigned' WHERE booking_number = ?";
    $stmt = mysqli_prepare($conn, $assignSql);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not prepare assignment statement: " . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 's', $bookingId);
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not execute assignment: " . mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Return success response
    http_response_code(200); // OK
    echo json_encode(["message" => "Congratulations! Booking request $bookingId has been successfully assigned!"]);
}

function search($conn, $data) {
    $searchInput = $data['search'] ?? '';

    // Validate search format (BRN followed by 5 digits)
    if (!preg_match('/^BRN\d{5}$/', $searchInput) && $searchInput !== '') {
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Invalid search format. Expected format: BRN00001" . mysqli_error($conn)]);
        exit;
    }

    $tableName = 'bookings';
    // Check if the bookings table exists, if not create it
    $checkTableSql = "SHOW TABLES LIKE '$tableName'";
    $tableResult = mysqli_query($conn, $checkTableSql);
    if (!$tableResult) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not check for bookings table: " . mysqli_error($conn)]);
        exit;    
    } elseif (mysqli_num_rows($tableResult) == 0) {        
        // Table does not exist
        http_response_code(404); // Not Found
        echo json_encode(["error" => "No bookings table found." . mysqli_error($conn)]); 
        exit;
    }
    if ($tableResult) {
        mysqli_free_result($tableResult);
    }    // Search for the booking    
    if ($searchInput !== '') {
        // Search for specific booking number
        $searchSql = "SELECT booking_number, customer_name, phone_number, unit_number, street_number, street_name, suburb, destination_suburb, pickup_date, TIME_FORMAT(pickup_time, '%H:%i') as pickup_time, status FROM bookings WHERE booking_number = ?";
        $stmt = mysqli_prepare($conn, $searchSql);
        
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            echo json_encode(["error" => "Could not prepare search statement: " . mysqli_error($conn)]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, 's', $searchInput);    
    } else {
        // Get all unassigned bookings for the next 2 hours
        $searchSql = "SELECT booking_number, customer_name, phone_number, unit_number, street_number, street_name, suburb, destination_suburb, pickup_date, TIME_FORMAT(pickup_time, '%H:%i') as pickup_time, status FROM bookings WHERE 
            CONCAT(pickup_date, ' ', pickup_time) >= NOW() 
            AND CONCAT(pickup_date, ' ', pickup_time) <= DATE_ADD(NOW(), INTERVAL 2 HOUR)
            ORDER BY pickup_date, pickup_time";
        
        $stmt = mysqli_prepare($conn, $searchSql);
        
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            echo json_encode(["error" => "Could not prepare search statement: " . mysqli_error($conn)]);
            exit;
        }
        
        // No parameters to bind for this query
    }
      if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not execute search: " . mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }
    $result = mysqli_stmt_get_result($stmt);
    $bookings = [];
      while ($row = mysqli_fetch_assoc($result)) {
        // Format date for display (convert from YYYY-MM-DD to DD/MM/YYYY)
        $dateParts = explode('-', $row['pickup_date']);
        $formattedDate = $dateParts[2] . '/' . $dateParts[1] . '/' . $dateParts[0];$bookings[] = [
            'booking_id' => $row['booking_number'],
            'customer_name' => $row['customer_name'],
            'phone_number' => $row['phone_number'],
            'unit_number' => $row['unit_number'] ?? '',
            'street_number' => $row['street_number'],
            'street_name' => $row['street_name'],
            'suburb' => $row['suburb'] ?? '',
            'destination_suburb' => $row['destination_suburb'] ?? '',
            'date' => $formattedDate,
            'time' => $row['pickup_time'],
            'status' => $row['status']
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    // Return the results
    http_response_code(200); // OK
    echo json_encode($bookings);
}
?>