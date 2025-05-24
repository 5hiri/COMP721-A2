<!-- 
 REDACTED REDACTED REDACTED 
 
 Description:
-->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $jsonPayload = file_get_contents('php://input');

    // Decode the JSON payload
    $data = json_decode($jsonPayload, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        // JSON decoding failed
        http_response_code(400); // Bad Request
        echo "Error: Invalid JSON data received.";
        exit;
    }

    $bookingNumber = generate_booking_number();
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
    $date = date('d-m-Y', strtotime($date)); // Ensure date is in 'YYYY-MM-DD' format
    $time = $data['time'] ?? '';

    require_once 'sqlinfo.inc.php'; // Include database connection info
    $conn = new mysqli($sql_host, $sql_user, $sql_pass, $sql_db);
    if ($conn->connect_error) {
        http_response_code(500); // Internal Server Error
        echo "Error: Could not connect to the database.";
        exit;
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
        'sssssssss',
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
    )

    if ($stmt->execute()) {
        http_response_code(201); // Created
        echo json_encode(['bookingNumber' => $bookingNumber, 'pickupDate' => $date, 'pickupTime' => $time]);
    } else {
        http_response_code(500); // Internal Server Error
        echo "Error: Could not create booking.";
    }
    $stmt->close();
    $conn->close();

    echo "Booking request received for " . htmlspecialchars($data['customerName'] ?? 'Unknown');
} else {
    http_response_code(405); // Method Not Allowed
    echo "Error: Only POST requests are allowed.";
    exit;
}


function generate_booking_number() {
    $number_part = '';
    $booking_number = '';
    $count = 1; // Initialize count to enter the loop at least once

    // Loop until a unique booking number is found
    while ($count > 0) {
        // Generate a 5-digit number with leading zeros
        $number_part = sprintf("%05d", rand(0, 99999));
        $booking_number = 'BRN' . $number_part;

        $sql = "SELECT COUNT(*) FROM bookings WHERE booking_number = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // Handle prepare error, e.g., log it and exit or return an error indicator
            error_log("Failed to prepare statement in generate_booking_number: " . $conn->error);
            return false; // Or throw new Exception("Database error");
        }
        $stmt->bind_param('s', $booking_number);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close(); // Close the statement, not the connection
    }

    return $number;
}
?>