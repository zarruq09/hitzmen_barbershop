================================================================================
HITZMEN BARBERSHOP MANAGEMENT SYSTEM - PROJECT DOCUMENTATION
================================================================================

1. PROJECT OVERVIEW
-------------------
The Hitzmen Barbershop Management System is a comprehensive web-based application 
designed to streamline barbershop operations. It facilitates online appointment 
bookings for customers, schedule and queue management for staff, and detailed 
administrative control and reporting for business owners.

2. SYSTEM REQUIREMENTS
----------------------
- Server Environment: Apache + MySQL/MariaDB (Compatible with Shared Hosting)
- PHP Version: 8.0 or higher
- Browser: Google Chrome, Microsoft Edge, or Firefox (Latest Versions)

3. LOGIN CREDENTIALS & ROLES
----------------------------
The system supports three user roles with distinct dashboards. If specific credentials 
are not provided below, please check the 'users' table in PHPMyAdmin.

[ADMINISTRATOR]
username: admin
pass: admin123
- Access: Full control over barbers, services, haircuts, and financial reports.
- Dashboard Features:
  * Manage Barbers (Add/Edit/Delete profiles mechanisms).
  * Manage Services & Haircut Styles (Update pricing and trends).
  * Manage Users (Add/Edit/Delete accounts & Role Assignment).
  * PDF Reporting (Generate revenue and booking reports).

[STAFF / BARBER]
- Access: Schedule management and daily queue operations.
- Dashboard Features:
  * View Assigned Appointments.
  * Update Status (On Duty / Off Duty).
  * Manage "Next Client" queue (Booking completion/cancellation).

[CUSTOMER]
- Access: Booking interface and personal history.
- Dashboard Features:
  * User-friendly Booking Wizard.
  * Appointment History & Status Tracking.
  * Profile Management.

*NOTE*: New users can register via the main login page. Their role will default to 
'Customer'. To grant Admin or Staff privileges, update the 'role' column in the 
database manually via PHPMyAdmin.

4. KEY FEATURES & "WOW" FACTORS
-------------------------------
- Premium UI/UX: A modern "Dark & Gold" aesthetic offering a high-end user experience.
- Dynamic Dashboards: Role-specific interfaces that adapt to the user's needs.
- Automated PDF Reports: One-click generation of professional business reports.
- Real-Time Updates: Status changes (e.g., Appointment Completed) reflect immediately.
- Mobile Responsiveness: Fully optimized for usage on mobile devices for both staff and customers.
- Security: Implemented CSRF protection and secure password hashing.

================================================================================
End of Document
================================================================================
