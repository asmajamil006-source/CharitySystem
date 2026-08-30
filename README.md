CharityHub
CharityHub is a web-based donation coordination platform that connects Donors, Receivers, Delivery Agents, and an Admin (Handler) around a single, trackable donation-request lifecycle from a donor listing an item, to a receiver requesting it, to an admin approving it, to an agent picking it up and delivering it.
Features
Role-based accounts: Donor, Receiver, Agent, and Handler (Admin), each with their own dashboard
Donor dashboard: list a donation (item, category, quantity, and expiry date for Food/Other categories), delete a donation, and see a notification bell with live status of requests placed against your items
Receiver flow: request specific donated items
Admin (Handler) panel: search and filter all requests by item name, category, and status; approve or reject pending requests
Agent panel: view approved requests filterable by pickup/delivery area, confirm pickup, and mark items as delivered
Request status pipeline: Pending → Approved / Rejected → Picked Up → Delivered, fully auditable at every step
Session-based authentication with role-based redirect after login
Tech Stack
Backend: PHP (PDO)
Database: Microsoft SQL Server (via the sqlsrv PDO driver)
Frontend: Plain HTML, CSS, and vanilla JavaScript (no framework)
Icons: Font Awesome (via CDN)
Project Structure
CharityHub/
├── login.php              # Login + role-based redirect
├── register.php           # Donor/Receiver registration
├── db.php                 # Shared PDO/SQL Server connection
├── test.php                # Basic PHP environment check
├── donor/
│   └── dashboard.php      # Add/view/delete donations, notifications
├── reciever/
│   └── dashboard.php      # Browse & request donated items
├── agent/
│   └── dashboard.php      # Confirm pickup, mark delivered
├── admin/
│   └── dashboard.php      # Review, filter, approve/reject requests
└── assets/
    └── images/            # Logo (image2.jpg) and login background (image.jpg)
Database Schema
The app expects a SQL Server database (CharityDB) with the following core tables:
Table	Key Columns
Users	UserID, Name, Email, Password, Role (Donor/Receiver/Agent/Handler), Type (Individual/Organization), CNIC, Address
Donations	DonationID, DonorID, ItemName, Category, Quantity, ExpiryDate
Requests	RequestID, DonationID, ReceiverID, RequestedQty, Status, CreatedDate
Pickup	DonationID, RequestID, AgentID, PickupTime, DeliveryTime, Status
Setup
Install PHP with the sqlsrv and pdo_sqlsrv extensions enabled.
Set up a Microsoft SQL Server instance and create the CharityDB database with the tables above.
Update the connection details in db.php:
php
   $server = "localhost\\SQLEXPRESS";
   $dbname = "CharityDB";
Place your logo/background images in assets/images/ as image.jpg and image2.jpg.
Serve the project through a local PHP server (e.g. XAMPP, or php -S localhost:8000) and open login.php.
Known Limitations
Passwords are stored and compared in plain text — not production-safe; should be hashed (e.g. password_hash / password_verify) before any real deployment.
Password length is restricted to 3–6 characters at registration/login — intentionally loose for coursework, should be revisited.
Single-server setup with no environment-based config (DB credentials are hardcoded in db.php).
License
This project was built for academic/coursework purposes.
