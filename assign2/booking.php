<!-- 
 REDACTED REDACTED REDACTED 
 
 Description:
-->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $jsonPayload = file_get_contents('php://input');

    require_once('../../files/sqlinfo.inc.php'); // Include database connection info. AUT Intranet Location
    $conn = new mysqli($sql_host, $sql_user, $sql_pass, $sql_db);
    if ($conn->connect_error) {
        http_response_code(500); // Internal Server Error
        echo "Error: Could not connect to the database.";
        exit;
    }

    // Decode the JSON payload
    $data = json_decode($jsonPayload, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        // JSON decoding failed
        http_response_code(400); // Bad Request
        echo "Error: Invalid JSON data received.";
        exit;
    }

    $bookingNumber = generate_booking_number($conn);
    if ($bookingNumber === false) {
        http_response_code(500); // Internal Server Error
        echo "Error: Could not generate booking number.";
        exit;
    }

    $customerName = $data['customerName'] ?? '';
    $phoneNumber = $data['phoneNumber'] ?? '';
    $unitNumber = $data['unitNumber'] ?? '';
    $streetNumber = $data['streetNumber'] ?? '';
    $streetName = $data['streetName'] ?? '';
    $suburb = $data['suburb'] ?? '';
    $destinationSuburb = $data['destinationSuburb'] ?? '';
    $date = $data['date'] ?? '';
    $date = date('d-m-Y', strtotime($date)); // Ensure date is in 'DD-MM-YYYY' format
    $time = $data['time'] ?? '';

    $tableName = 'bookings';
    // Check if the bookings table exists, if not create it
    $checkTableSql = "SHOW TABLES LIKE '$tableName'";
    $tableResult = $conn->query($checkTableSql);
    if (!$tableResult) {
        http_response_code(500); // Internal Server Error
        echo "Error: Could not check for bookings table.";
        exit;
    } elseif ($tableResult->num_rows == 0) {
        // Table does not exist, create it
        $createTableSql = """
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
        """;
        if (!$conn->query($createTableSql)) {
            http_response_code(500); // Internal Server Error
            echo "Error: Could not create bookings table.";
            exit;
        }
    }


    $sql = """
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
    """;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssssssssss',
        $bookingNumber,
        $customerName,
        $phoneNumber,
        $unitNumber,
        $streetNumber,
        $streetName,
        $suburb,
        $destinationSuburb,
        $date,
        $time
    );

    if ($stmt->execute()) {
        http_response_code(201); // Created
        echo json_encode(['bookingNumber' => $bookingNumber, 'pickupDate' => $date, 'pickupTime' => $time, 'success' => true]); // Send JSON response
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not create booking."]); // Send JSON error
    }
    $stmt->close();
    $conn->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo "Error: Only POST requests are allowed.";
    exit;
}


function generate_booking_number($conn) {
    $new_booking_number = '';

    // Step 1: Check the current count of bookings
    $sql_count = "SELECT COUNT(*) FROM bookings";
    $stmt_count = $conn->prepare($sql_count);

    if (!$stmt_count) {
        error_log("Failed to prepare count statement: " . $conn->error);
        return false; // Indicate critical error
    }
    $stmt_count->execute();
    $stmt_count->bind_result($current_booking_count);
    $stmt_count->fetch();
    $stmt_count->close();

    if ($current_booking_count == 0) {
        // No bookings exist, so this is the first one
        $new_booking_number = 'BRN00000';
    } else {
        // Bookings exist, find the last booking number to increment
        // Assuming booking_number is like 'BRN' followed by numbers and can be sorted alphabetically
        $sql_last_booking = "SELECT booking_number FROM bookings ORDER BY booking_number DESC LIMIT 1";
        $stmt_last_booking = $conn->prepare($sql_last_booking);

        if (!$stmt_last_booking) {
            error_log("Failed to prepare statement for last booking number: " . $conn->error);
            return false; // Indicate critical error
        }
        $stmt_last_booking->execute();
        $stmt_last_booking->store_result(); // Necessary for num_rows and bind_result

        if ($stmt_last_booking->num_rows > 0) {
            $stmt_last_booking->bind_result($last_booking_id_str);
            $stmt_last_booking->fetch();

            // Extract the numeric part (e.g., from "BRN00001" to "00001")
            // Assumes the prefix "BRN" is 3 characters long
            $numeric_part_str = substr($last_booking_id_str, 3);
            $current_id_numeric = intval($numeric_part_str); // Convert to integer (e.g., 1)
            
            $next_id_numeric = $current_id_numeric + 1; // Increment (e.g., 2)
            
            // Format the new numeric part back to a 5-digit string with leading zeros (e.g., "00002")
            // If $next_id_numeric becomes 100000, sprintf will produce "100000" (6 digits)
            // which is fine as your booking_number column is VARCHAR(10)
            $next_numeric_part_str = sprintf("%05d", $next_id_numeric); 
            
            $new_booking_number = 'BRN' . $next_numeric_part_str;
        } else {
            // This case (count > 0 but no last booking found) indicates an inconsistency.
            // It might happen if the table was cleared between the COUNT and this query.
            // Fallback to the first booking number or return an error.
            error_log("Inconsistent state: Booking count > 0, but no last booking_number found. Defaulting to BRN00000.");
            $new_booking_number = 'BRN00000'; // Or return false to indicate an error
        }
        $stmt_last_booking->close();
    }

    return $new_booking_number;
}
?>