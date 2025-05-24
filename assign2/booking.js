/* 
 REDACTED REDACTED REDACTED 
 
 Description:
*/
document.addEventListener("DOMContentLoaded", function() {
    const submitButton = document.getElementById("submitBooking");
    const confirmationMessageElement = document.getElementById("confirmationMessage");
    
    if (submitButton) {
        submitButton.addEventListener("click", function() {
            console.log('Submit button clicked!'); 
            handleBookingSubmission();
        });
    } else {
        console.error("Submit button not found in the document.");
        if (confirmationMessageElement) {
            confirmationMessageElement.textContent = "Error: Submit button not found.";
            confirmationMessageElement.className = 'error'; // Optional: for styling
        }
    }
});

function handleBookingSubmission() {
    const confirmationMessageElement = document.getElementById("confirmationMessage");
    // Clear previous messages
    if (confirmationMessageElement) {
        confirmationMessageElement.textContent = "";
        confirmationMessageElement.className = ''; // Reset classes
    }
    // 1. Get all the form values
    const formData = {
        customerName: document.getElementById("cname").value,
        phoneNumber: document.getElementById("phone").value,
        unitNumber: document.getElementById("unumber").value,
        streetNumber: document.getElementById("snumber").value,
        streetName: document.getElementById("stname").value,
        suburb: document.getElementById("sbname").value,
        destinationSuburb: document.getElementById("dsbname").value,
        date: document.getElementById("date").value,
        time: document.getElementById("time").value,
    }

    // format date to DD/MM/YYYY and time to HH:MM
    const dateParts = formData.date.split('-');
    formData.date = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`; // Convert to DD/MM/YYYY format

    // 2. Validate the form data
    if (validateFormData(formData, confirmationMessageElement)) {
        // 3. Send the form data to booking.php using AJAX request.
        console.log("Form data is valid. Sending to booking.php:", formData);

        fetch('booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', // Let PHP know we're sending JSON
            },
            body: JSON.stringify(formData) // Convert form data to JSON string
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text || 'Server responsed with an error') });
            }
            return response.json(); // Parse JSON response
        })
        .then(data => {
            console.log("Success response from booking.php:", data);
            if (confirmationMessageElement) {
                confirmationMessageElement.textContent = "Thank you for your booking! Booking reference number: " + data.bookingNumber + ", Pickup Time: " + data.pickupTime + ", Pickup Date: " + data.pickupDate; // Display the server's response
                confirmationMessageElement.className = 'success'; 
            }
            document.querySelector(".booking-form").reset(); // Reset the form after successful submission
        })
        .catch(error => {
            console.error("Error during booking submission:", error);
            if (confirmationMessageElement) {
                confirmationMessageElement.textContent = "Booking failed: " + error.message; // Display error message
                confirmationMessageElement.className = 'error'; // Optional: for styling
            }
        });
    } else {
        console.error("Form data is invalid.");
    }
}

function validateFormData(data, messageElement) {
    // Basic validation checks
    if (!data.customerName || !data.phoneNumber || 
        !data.streetNumber || !data.streetName || 
        !data.date || !data.time) {
        if (messageElement) {
            messageElement.textContent = "Please fill in all required fields.";
            messageElement.className = 'error'; // Optional: for styling
        }
        return false;
    }

    const phoneRegex = /^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{3,4})(?: *x(\d+))?\s*$/;
    if (!phoneRegex.test(data.phoneNumber)) {
        if (messageElement) {
            messageElement.textContent = "Please enter a valid phone number. Examples: +64 123 456 7890, (123) 456-7890, 123-456-7890.";
            messageElement.className = 'error'; // Optional: for styling
        }
        return false;
    }

    const streetNumRegex = /^\d+[a-zA-Z]*$/; // Allows numbers possibly followed by letters e.g. 123 or 123a
    if (!streetNumRegex.test(data.streetNumber)) {
        if (messageElement) {
            messageElement.textContent = "Please enter a valid street number (e.g., 123 or 123a).";
            messageElement.className = 'error'; // Optional: for styling
        }
        return false;
    }

    // Date and Time Validation
    const now = new Date(); // Current date and time

    // Create a Date object for today at 00:00:00 local time
    const todayAtMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    // Parse the input date string "YYYY-MM-DD" to create a Date object at 00:00:00 local time
    const dateParts = data.date.split('-');
    const selectedYear = parseInt(dateParts[0]);
    const selectedMonth = parseInt(dateParts[1]) - 1; // JavaScript months are 0-indexed (0-11)
    const selectedDay = parseInt(dateParts[2]);
    const selectedDateAtMidnight = new Date(selectedYear, selectedMonth, selectedDay);

    // Check if the selected date is in the past
    if (selectedDateAtMidnight < todayAtMidnight) {
        if (messageElement) {
            messageElement.textContent = "Please select a date that is today or in the future.";
            messageElement.className = 'error';
        }
        return false;
    }

    // Time validation - only apply if the selected date IS today
    if (selectedDateAtMidnight.getTime() === todayAtMidnight.getTime()) {
        const currentTimeInMinutes = now.getHours() * 60 + now.getMinutes(); // Current time of day in minutes

        const timeParts = data.time.split(':');
        const selectedHours = parseInt(timeParts[0]);
        const selectedMinutes = parseInt(timeParts[1]);
        const selectedTimeInMinutes = selectedHours * 60 + selectedMinutes;

        if (selectedTimeInMinutes < currentTimeInMinutes) {
            if (messageElement) {
                messageElement.textContent = "Please select a time that is in the future for today's date.";
                messageElement.className = 'error';
            }
            return false;x
        }
    }

    if (messageElement) {
        messageElement.textContent = ""; // Clear message if valid so far
        messageElement.className = '';
    }
    return true; // If all checks pass, return true
}