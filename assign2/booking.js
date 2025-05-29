/* 
 REDACTED REDACTED REDACTED 
 
 Description: Client-side JavaScript for the CabsOnline taxi booking system.
 This file handles form validation, AJAX communication with the server,
 and user interface interactions for the booking form. It includes:
 - Date/time field initialization with current values
 - Client-side form validation (phone numbers, dates, required fields)
 - AJAX requests to booking.php for form submission
 - Dynamic confirmation message display with booking details
 - Error handling and user feedback
 
*/
document.addEventListener("DOMContentLoaded", function() {
    const submitButton = document.getElementById("submitBooking");
    const confirmationMessageElement = document.getElementById("reference");
    
    // Initialize date and time fields with current values
    initializeDateTimeFields();
    
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

function initializeDateTimeFields() {
    const now = new Date();
    
    // Set current date in dd/mm/yyyy format for the date input
    const dateInput = document.getElementById("date");
    if (dateInput) {
        // For date input type, we need to set value in YYYY-MM-DD format
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}`;
    }
    
    // Set current time in HH:MM format for the time input
    const timeInput = document.getElementById("time");
    if (timeInput) {
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeInput.value = `${hours}:${minutes}`;
    }
}

function handleBookingSubmission() {
    const confirmationMessageElement = document.getElementById("reference");
    const confirmationHeadingElement = document.getElementById("confirmationHeading");
    const confirmationSection = document.getElementById("bookingConfirmation");
    
    // Clear previous messages and hide section
    if (confirmationMessageElement) {
        confirmationMessageElement.textContent = "";
        confirmationMessageElement.className = ''; // Reset classes
    }
    if (confirmationHeadingElement) {
        confirmationHeadingElement.textContent = "";
        confirmationHeadingElement.className = ''; // Reset classes
    }
    if (confirmationSection) {
        confirmationSection.style.display = 'none'; // Hide section initially
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
    }    // format date to DD/MM/YYYY and time to HH:MM
    const dateParts = formData.date.split('-');
    formData.date = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`; // Convert to DD/MM/YYYY format

    // 2. Validate the form data
    if (validateFormData(formData, confirmationMessageElement)) {
        // 3. Send the form data to booking.php using AJAX request.
        console.log("Form data is valid. Sending to booking.php:", formData);

        const confirmationHeadingElement = document.getElementById("confirmationHeading");

        fetch('booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', // Let PHP know we're sending JSON
            },
            body: JSON.stringify(formData) // Convert form data to JSON string
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text || 'Server responded with an error') });
            }
            return response.json(); // Parse JSON response
        })        .then(data => {
            console.log("Success response from booking.php:", data);
            
            // Show confirmation section with animation
            if (confirmationSection) {
                confirmationSection.style.display = 'block';
                // Trigger reflow to ensure the display change takes effect
                confirmationSection.offsetHeight;
            }
            
            if (confirmationHeadingElement) {
                confirmationHeadingElement.textContent = "Thank you for your booking!"; // Set the heading text
                confirmationHeadingElement.className = 'confirmation-heading'; // Optional: for styling
            }
            if (confirmationMessageElement) {
                const bookingDetails = `
                    <table class="confirmation-table">
                        <tr>
                            <td>Booking reference number:</td>
                            <td>${data.bookingNumber}</td>
                        </tr>
                        <tr>
                            <td>Pickup time:</td>
                            <td>${data.pickupTime}</td>
                        </tr>
                        <tr>
                            <td>Pickup date:</td>
                            <td>${data.pickupDate}</td>
                        </tr>
                    </table>
                `;
                confirmationMessageElement.innerHTML = bookingDetails; // Use innerHTML to render the table
                confirmationMessageElement.className = 'success'; 
            }
            
            // Scroll to confirmation section smoothly
            if (confirmationSection) {
                setTimeout(() => {
                    confirmationSection.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 100);
            }
            
            document.querySelector(".booking-form").reset(); // Reset the form after successful submission
            initializeDateTimeFields(); // Reinitialize date and time fields to current values
        })
        .catch(error => {
            console.error("Error during booking submission:", error);
            
            // Show confirmation section for error display
            if (confirmationSection) {
                confirmationSection.style.display = 'block';
                // Trigger reflow to ensure the display change takes effect
                confirmationSection.offsetHeight;
            }
            
            if (confirmationHeadingElement) {
                confirmationHeadingElement.textContent = "Booking Failed";
                confirmationHeadingElement.className = 'error-heading';
            }
            if (confirmationMessageElement) {
                confirmationMessageElement.textContent = "Booking failed: " + error.message; // Display error message
                confirmationMessageElement.className = 'error';
            }
            
            // Scroll to error message smoothly
            if (confirmationSection) {
                setTimeout(() => {
                    confirmationSection.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 100);
            }
        });
    } else {
        console.error("Form data is invalid.");
    }
}

function validateFormData(data, messageElement) {
    const confirmationSection = document.getElementById("bookingConfirmation");
    const confirmationHeadingElement = document.getElementById("confirmationHeading");
    
    // Function to show validation error
    function showValidationError(message) {
        if (confirmationSection) {
            confirmationSection.style.display = 'block';
            confirmationSection.offsetHeight; // Trigger reflow
        }
        if (confirmationHeadingElement) {
            confirmationHeadingElement.textContent = "Validation Error";
            confirmationHeadingElement.className = 'error-heading';
        }
        if (messageElement) {
            messageElement.textContent = message;
            messageElement.className = 'error';
        }
        // Scroll to error message
        if (confirmationSection) {
            setTimeout(() => {
                confirmationSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }, 100);
        }
        return false;
    }
      // Basic validation checks
    if (!data.customerName || !data.phoneNumber || 
        !data.streetNumber || !data.streetName || 
        !data.date || !data.time) {
        return showValidationError("Please fill in all required fields.");
    }    // Phone validation: must be all numbers with length between 10-12
    const phoneRegex = /^\d{10,12}$/;
    if (!phoneRegex.test(data.phoneNumber)) {
        return showValidationError("Phone number must be all numbers with length between 10-12 digits.");
    }

    const streetNumRegex = /^\d+[a-zA-Z]*$/; // Allows numbers possibly followed by letters e.g. 123 or 123a
    if (!streetNumRegex.test(data.streetNumber)) {
        return showValidationError("Please enter a valid street number (e.g., 123 or 123a).");
    }

    // Date and Time Validation
    const now = new Date(); // Current date and time

    // Create a Date object for today at 00:00:00 local time
    const todayAtMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());    // data.date is in DD/MM/YYYY format from handleBookingSubmission
    const dateParts = data.date.split('/'); // e.g., "29/05/2025" -> ["29", "05", "2025"]
    const selectedDay = parseInt(dateParts[0]);
    const selectedMonth = parseInt(dateParts[1]) - 1; // JavaScript months are 0-indexed (0-11)
    const selectedYear = parseInt(dateParts[2]);
    const selectedDateAtMidnight = new Date(selectedYear, selectedMonth, selectedDay); // Correctly new Date(2025, 4, 29)    // Check if the selected date is in the past
    if (selectedDateAtMidnight < todayAtMidnight) {
        return showValidationError("Please select a date that is today or in the future.");
    }

    // Time validation - only apply if the selected date IS today
    if (selectedDateAtMidnight.getTime() === todayAtMidnight.getTime()) {
        const currentTimeInMinutes = now.getHours() * 60 + now.getMinutes(); // Current time of day in minutes

        const timeParts = data.time.split(':');
        const selectedHours = parseInt(timeParts[0]);
        const selectedMinutes = parseInt(timeParts[1]);
        const selectedTimeInMinutes = selectedHours * 60 + selectedMinutes;

        if (selectedTimeInMinutes < currentTimeInMinutes) {
            return showValidationError("Please select a time that is in the future for today's date.");
        }
    }

    // Clear any previous error messages and hide confirmation section
    if (confirmationSection) {
        confirmationSection.style.display = 'none';
    }
    if (messageElement) {
        messageElement.textContent = ""; // Clear message if valid so far
        messageElement.className = '';
    }
    return true; // If all checks pass, return true
}