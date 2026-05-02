<?php
/*
REDACTED REDACTED 

Description: Server-side PHP script for handling taxi booking submissions.
This file processes AJAX requests from the booking form and manages
database operations for the CabsOnline booking system. Key functions:
- Validates and processes JSON booking data from client
- Auto-creates MySQL booking table if it doesn't exist
- Generates unique incremental booking reference numbers (BRN00001 format)
- Inserts booking records with customer details and pickup information
- Returns JSON responses with booking confirmation data
- Handles error cases with appropriate HTTP status codes

Functions:
- generate_booking_number(): Creates unique BRN##### booking references
*/
session_start();
error_reporting(E_ALL); // Report all errors

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $jsonPayload = file_get_contents('php://input');

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
    $data = json_decode($jsonPayload, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        // JSON decoding failed
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Invalid JSON data received."]);
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
    } elseif (mysqli_num_rows($tableResult) == 0) { // Table does not exist, create it
        $createTableSql = "
        CREATE TABLE bookings (
            booking_number VARCHAR(10) PRIMARY KEY,
            customer_name VARCHAR(255) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            unit_number VARCHAR(50),
            street_number VARCHAR(20) NOT NULL,
            street_name VARCHAR(100) NOT NULL,
            suburb VARCHAR(100),
            destination_suburb VARCHAR(100),
            pickup_date DATE NOT NULL,
            pickup_time TIME NOT NULL,
            booking_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('unassigned', 'assigned', 'completed') DEFAULT 'unassigned'
        );
        ";
        if (!mysqli_query($conn, $createTableSql)) {
            http_response_code(500); // Internal Server Error
            echo json_encode(["error" => "Could not create bookings table: " . mysqli_error($conn)]); // Send JSON error
            exit;
        }
    }
    if ($tableResult) {
        mysqli_free_result($tableResult);
    }

    $bookingNumber = generate_booking_number($conn);
    if ($bookingNumber === false) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not generate booking number."]);
        exit;
    }

    $customerName = $data['customerName'] ?? '';
    $phoneNumber = $data['phoneNumber'] ?? '';
    $unitNumber = $data['unitNumber'] ?? '';
    $streetNumber = $data['streetNumber'] ?? '';
    $streetName = $data['streetName'] ?? '';
    $suburb = $data['suburb'] ?? '';
    $destinationSuburb = $data['destinationSuburb'] ?? '';    // Handle date formatting for DB and response
    $pickup_date_str = $data['date'] ?? '';
    
    // Parse DD/MM/YYYY format manually since strtotime can be unreliable with this format
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $pickup_date_str, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
          // Validate the date components
        if (checkdate($month, $day, $year)) {
            $date_for_db = "$year-$month-$day"; // Format for SQL (YYYY-MM-DD)
            $date_for_response = "$day/$month/$year"; // Format for client response (DD/MM/YYYY)
        } else {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "Invalid date provided."]);
            exit;
        }
    } else {
        // Handle invalid date format from client
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Invalid date format provided. Expected DD/MM/YYYY."]);
        exit;
    }
    
    $time = $data['time'] ?? '';
    // Basic time validation (e.g., HH:MM or HH:MM:SS)
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time)) {
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Invalid time format provided."]);
        exit;
    }    $sql = "
    INSERT INTO bookings (
        booking_number,
        customer_name,
        phone_number,
        unit_number,
        street_number,
        street_name,
        suburb,
        destination_suburb,
        pickup_date,
        pickup_time,
        booking_datetime,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'unassigned')
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not prepare statement: " . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param(
        $stmt,
        'ssssssssss',
        $bookingNumber,
        $customerName,
        $phoneNumber,
        $unitNumber,
        $streetNumber,
        $streetName,
        $suburb,
        $destinationSuburb,
        $date_for_db, // Use YYYY/MM/DD format for DB
        $time
    );

    if (mysqli_stmt_execute($stmt)) {
        http_response_code(201); // Created
        echo json_encode(['bookingNumber' => $bookingNumber, 'pickupDate' => $date_for_response, 'pickupTime' => $time, 'success' => true]); // Send JSON response
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not create booking: " . mysqli_stmt_error($stmt)]); // Send JSON error
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST requests are allowed."]); // Send JSON error
    exit;
}


function generate_booking_number($conn) {
    $new_booking_number = '';

    // Step 1: Check the current count of bookings
    $sql_count = "SELECT COUNT(*) FROM bookings";
    $stmt_count = mysqli_prepare($conn, $sql_count);

    if (!$stmt_count) {
        error_log("Failed to prepare count statement: " . mysqli_error($conn));
        return false; // Indicate critical error
    }
    mysqli_stmt_execute($stmt_count);
    mysqli_stmt_bind_result($stmt_count, $current_booking_count);
    mysqli_stmt_fetch($stmt_count);
    mysqli_stmt_close($stmt_count);
    if ($current_booking_count == 0) {
        // No bookings exist, so this is the first one
        $new_booking_number = 'BRN00001';
    } else {
        // Bookings exist, find the last booking number to increment
        // Assuming booking_number is like 'BRN' followed by numbers and can be sorted alphabetically
        $sql_last_booking = "SELECT booking_number FROM bookings ORDER BY booking_number DESC LIMIT 1";
        $stmt_last_booking = mysqli_prepare($conn, $sql_last_booking);

        if (!$stmt_last_booking) {
            error_log("Failed to prepare statement for last booking number: " . mysqli_error($conn));
            return false; // Indicate critical error
        }
        mysqli_stmt_execute($stmt_last_booking);
        mysqli_stmt_store_result($stmt_last_booking); // Necessary for num_rows and bind_result

        if (mysqli_stmt_num_rows($stmt_last_booking) > 0) {
            mysqli_stmt_bind_result($stmt_last_booking, $last_booking_id_str);
            mysqli_stmt_fetch($stmt_last_booking);

            // Extract the numeric part (e.g., from "BRN00001" to "00001")
            // Assumes the prefix "BRN" is 3 characters long
            $numeric_part_str = substr($last_booking_id_str, 3);
            $current_id_numeric = intval($numeric_part_str); // Convert to integer (e.g., 1)
            
            $next_id_numeric = $current_id_numeric + 1; // Increment (e.g., 2)
            
            $next_numeric_part_str = sprintf("%05d", $next_id_numeric); 
            
            $new_booking_number = 'BRN' . $next_numeric_part_str;
        } else {            
            // This case (count > 0 but no last booking found) indicates an inconsistency.
            // It might happen if the table was cleared between the COUNT and this query.
            // Fallback to the first booking number or return an error.
            error_log("Inconsistent state: Booking count > 0, but no last booking_number found. Defaulting to BRN00001.");
            $new_booking_number = 'BRN00001'; // Or return false to indicate an error
        }
        mysqli_stmt_close($stmt_last_booking);
    }

    return $new_booking_number;
}
?>