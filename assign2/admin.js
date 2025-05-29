/* 
 REDACTED REDACTED REDACTED 
 
 Description: Client-side JavaScript for the CabsOnline admin interface.
 This file handles the administrative functions for managing taxi bookings,
 including search functionality and booking assignments. Key features:
 - Search for specific bookings by reference number (BRN##### format)
 - Display unassigned bookings within 2-hour time window when search is empty
 - Validate booking reference number format before sending requests
 - Handle booking assignment operations with real-time status updates
 - Dynamic table generation and management for search results
 - Error handling and user feedback for admin operations
 
 Functions:
 - setupAssignButtons(): Configures event listeners for assign buttons
 - handleSearch(): Processes search requests and displays results
*/
document.addEventListener("DOMContentLoaded", function() {
    const searchButton = document.getElementById("sbutton");
    const adminMessageElement = document.getElementById("adminMessage");

    if (searchButton) {
        searchButton.addEventListener("click", function() {
            console.log('Search button clicked!');
            handleSearch(adminMessageElement);
        });
    } else {
        console.error("Submit button not found in the document.");
    }
});

// Function to setup assign button event listeners (called after search results are loaded)
function setupAssignButtons() {
    const assignButtons = document.querySelectorAll(".assign-button:not(.disabled)"); // Only select non-disabled assign buttons

    if (assignButtons.length > 0) {
        assignButtons.forEach(button => {
            button.addEventListener("click", function() {
                // Double-check that the button is not disabled before proceeding
                if (this.disabled || this.classList.contains('disabled')) {
                    console.log("Button is disabled, ignoring click");
                    return;
                }
                
                const bookingId = this.getAttribute("data-booking-id");
                console.log("Assign button clicked for booking ID:", bookingId);
                
                // Dispatch a custom event to handle the assignment
                const assignEvent = new CustomEvent("assignBooking", { detail: { bookingId: bookingId } });
                document.dispatchEvent(assignEvent);
            });
        });
        console.log(`Set up event listeners for ${assignButtons.length} active assign buttons`);
    } else {
        console.log("No active assign buttons found to set up event listeners for.");
    }
}

document.addEventListener("assignBooking", function(event) {
    const bookingId = event.detail.bookingId;
    console.log("Assigning booking with ID:", bookingId);
    
    // Fetch the booking details from the server
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ assignBooking: bookingId })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error(text || 'Server responded with an error') });
        }
        return response.json();
    })    .then(data => {
        console.log("Booking assigned successfully:", data);
        
        // Display success message in admin messages div
        const adminMessageElement = document.getElementById("adminMessage");
        if (adminMessageElement) {
            adminMessageElement.textContent = `Booking ${bookingId} assigned successfully!`;
            adminMessageElement.className = 'success';
        }

        const resultsElement = document.getElementById("bookingBody");
        if (resultsElement) { // Update the assigned booking from the results
                const rows = resultsElement.querySelectorAll("tr");
                rows.forEach(row => {
                    const assignButton = row.querySelector(".assign-button, .disabled.assign-button");
                    if (assignButton && assignButton.getAttribute("data-booking-id") === bookingId) {
                        // Update the status cell (column 6 - status column)
                        const statusCell = row.cells[6]; // Status is in the 7th column (index 6)
                        if (statusCell) {
                            statusCell.textContent = "assigned";
                        }
                        
                        // Update the button cell (column 7 - button column)
                        const buttonCell = row.cells[7]; // Button is in the 8th column (index 7)
                        if (buttonCell) {
                            assignButton.classList.remove("assign-button");
                            assignButton.classList.add("disabled", "assign-button");
                            assignButton.disabled = true;
                            assignButton.textContent = "Assigned";
                        }
                    }
                });
        } else {
            console.error("Results element not found for assigned booking.");
        }
    })    .catch(error => {
        console.error("Error during booking assignment:", error);
        
        // Display error message in admin messages div
        const adminMessageElement = document.getElementById("adminMessage");
        if (adminMessageElement) {
            adminMessageElement.textContent = `Error assigning booking: ${error.message}`;
            adminMessageElement.className = 'error';
        }
    });
});

function handleSearch(adminMessageElement) {
    const searchInputElement = document.getElementById("bsearch");
    
    // Check if the element exists
    if (!searchInputElement) {
        console.error("Search input element with ID 'bsearch' not found");
        if (adminMessageElement) {
            adminMessageElement.textContent = "Error: Search input element not found";
            adminMessageElement.className = 'error';
        }
        return;
    }
    
    const searchInput = searchInputElement.value;
    const searchPattern = /^BRN\d{5}$/;

    // Validate the search input
    if (!searchPattern.test(searchInput) && searchInput !== "") {
        console.error("Invalid search format. Expected format: BRN00001");
        if (adminMessageElement) {
            adminMessageElement.textContent = "Invalid search format. Expected format: BRN00001";
            adminMessageElement.className = 'error';
        }
        return;
    } // Clear previous results
    const resultsElement = document.getElementById("bookingBody");
    if (resultsElement) {
        resultsElement.innerHTML = ""; // Clear previous results
    }

    console.log("Searching for:", searchInput);
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json', // Let PHP know we're sending JSON
        },
        body: JSON.stringify({ search: searchInput }) // Send the search input as JSON
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error(text || 'Server responsed with an error') });
        }
        return response.json(); // Parse JSON response
    })
    .then(data => {
        console.log("Success response from admin.php:", data);
        if (adminMessageElement) {
            adminMessageElement.textContent = "Successfully retrieved bookings!"; // Set the heading text
            adminMessageElement.className = 'success';
        }
        if (data.length === 0) {
            console.warn("No bookings found for the given search.");
            if (adminMessageElement) {
                adminMessageElement.textContent = "No bookings found for the given search.";
                adminMessageElement.className = 'warning';
            }
            return; // Exit if no bookings found
        }// Display the booking details
        if (resultsElement) {
            resultsElement.innerHTML = ""; // Clear previous results            
            data.forEach(booking => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${booking.booking_id}</td>
                    <td>${booking.customer_name}</td>
                    <td>${booking.phone_number}</td>
                    <td>${booking.suburb}</td>
                    <td>${booking.destination_suburb}</td>
                    <td>${booking.date} ${booking.time}</td>
                    <td>${booking.status}</td>
                    <td><button class="${booking.status == "unassigned" ? "assign-button" : "disabled assign-button"}" data-booking-id="${booking.booking_id}">${booking.status == "unassigned" ? "Assign" : "Assigned"}</button></td>
                `;
                resultsElement.appendChild(row);
            });
            
            // Set up event listeners for the newly created assign buttons
            setupAssignButtons();
        }
    })
    .catch(error => {
        console.error("Error during booking submission:", error);
        if (adminMessageElement) {
            adminMessageElement.textContent = "Booking Search failed: " + error.message; // Display error message
            adminMessageElement.className = 'error';
        }
    });
    
}