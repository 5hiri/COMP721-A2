/* 
 REDACTED REDACTED REDACTED 
 
 Description:
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

function handleSearch(adminMessageElement) {
    const searchInput = document.getElementById("sbsearch").value;
    const searchPattern = /^BRN\d{5}$/;
    const emptyInput = false;

    // Validate the search input
    if (!searchPattern.test(searchInput)) {
        console.error("Invalid search format. Expected format: BRN00001");
        if (adminMessageElement) {
            adminMessageElement.textContent = "Invalid search format. Expected format: BRN00001";
            adminMessageElement.className = 'error'; // Optional: for styling
        }
        return;
    }

    // Clear previous results
    // Implement when results display is added
    const resultsElement = document.getElementById("bookingTable");
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
            adminMessageElement.className = 'success'; // Optional: for styling
        }
        if (data.length === 0) {
            console.warn("No bookings found for the given search.");
            if (adminMessageElement) {
                adminMessageElement.textContent = "No bookings found for the given search.";
                adminMessageElement.className = 'warning'; // Optional: for styling
            }
            return; // Exit if no bookings found
        }
        // Display the booking details
        if (resultsElement) {
            resultsElement.innerHTML = ""; // Clear previous results
            data.forEach(booking => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${booking.booking_id}</td>
                    <td>${booking.customer_name}</td>
                    <td>${booking.phone_number}</td>
                    <td>${booking.unit_number}</td>
                    <td>${booking.street_number}</td>
                    <td>${booking.street_name}</td>
                    <td>${booking.suburb}</td>
                    <td>${booking.destination_suburb}</td>
                    <td>${booking.date}</td>
                    <td>${booking.time}</td>
                    <td>${booking.status}</td>
                `;
                resultsElement.appendChild(row);
            });
        }
    })
    .catch(error => {
        console.error("Error during booking submission:", error);
        if (adminMessageElementt) {
            adminMessageElement.textContent = "Booking failed: " + error.message; // Display error message
            adminMessageElement.className = 'error'; // Optional: for styling
        }
    });
    
}