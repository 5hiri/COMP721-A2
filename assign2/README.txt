REDACTED REDACTED REDACTED

CabsOnline - Taxi Booking System

LIST OF FILES:
- booking.html: Client-side booking form page
- booking.js: JavaScript for booking form validation and AJAX requests
- booking.php: Server-side script to handle booking submissions and database operations
- admin.html: Administrator interface for viewing and managing bookings
- admin.js: JavaScript for admin search functionality and booking assignments
- admin.php: Server-side script for admin search and booking assignment operations
- mysqlcommand.txt: MySQL table creation commands
- README.txt: This file containing system documentation
- style/style.css: CSS styles for the booking and admin pages

INSTRUCTIONS FOR USE:

1. BOOKING SYSTEM (booking.html):
   - Navigate to booking.html to access the taxi booking form
   - Fill in all required fields (marked with *)
   - Customer Name: Enter your full name
   - Phone Number: Enter 10-12 digit phone number (numbers only)
   - Unit Number: Optional address field
   - Street Number: Enter street number (e.g., 123 or 123a)
   - Street Name: Enter street name
   - Suburb: Optional pickup suburb
   - Destination Suburb: Optional destination suburb
   - Pick-Up Date: Select date (must be today or future)
   - Pick-Up Time: Select time (must be in future if today's date)
   - Click "Book Taxi" to submit booking
   - Confirmation message will display with booking reference number

2. ADMIN SYSTEM (admin.html):
   - Navigate to admin.html to access the admin interface
   - Search Options:
     a) Leave search field empty and click "Search Bookings" to view all unassigned bookings within next 2 hours
     b) Enter a booking reference number (format: BRN00001) to search for specific booking
   - Click "Assign" button to assign a taxi to an unassigned booking
   - Confirmation message will appear when booking is successfully assigned

3. DATABASE SETUP:
   - The system automatically creates the required 'bookings' table if it doesn't exist
   - Alternatively, use the commands in mysqlcommand.txt to manually create the table

SYSTEM FEATURES:
- Client-side validation for all form inputs
- AJAX communication between client and server
- Automatic booking reference number generation (BRN00001, BRN00002, etc.)
- Date and time validation to prevent past bookings
- Admin interface for booking management
- Real-time status updates when bookings are assigned

TECHNICAL REQUIREMENTS:
- PHP with MySQL support
- Modern web browser with JavaScript enabled
- MySQL database access