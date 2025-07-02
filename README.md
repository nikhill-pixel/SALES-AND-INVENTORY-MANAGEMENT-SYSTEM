# 📦 Sales & Inventory Management System

## Project Overview

This is a web-based Sales and Inventory Management System designed to help businesses efficiently track sales, manage product inventory, handle customer payments, and oversee product transportation/delivery. It provides a comprehensive view of sales performance and profitability, with robust filtering and reporting capabilities.

The system is built using PHP for server-side logic and MySQL as the relational database, ensuring secure and dynamic data management.

## ✨ Features

* **Sales Report Generation:**
    * Filter sales data by date range, order status (Pending, Completed, Cancelled), delivery status (Processing, In Transit, Delivered, Failed), payment status (Pending, Completed, Failed, Refunded), and specific customers.
    * View detailed information for each order, including customer name, order date, total price, order status, profit, delivery details, and payment status.
    * Breakdown of products within each order (product name, quantity).
    * Calculate and display overall profit for filtered sales.
* **Payment Management:**
    * View a list of all payment records with details like ID, status, date, amount, customer name, and contact info.
    * Ability to mark payments as "Completed," automatically setting the payment date to the current date.
    * Intelligent order status updates: If a payment is completed, the system checks if the associated order's delivery is also "Delivered." If both conditions are met, the order status is automatically updated to "Completed."
* **Transportation/Delivery Management:**
    * View details of all transportation records, including ID, current status, delivery date, and associated delivery partner.
    * Ability to mark a delivery as "Delivered."
    * Intelligent order status updates: If a delivery is marked "Delivered," the system checks if the associated order's payment is also "Completed." If both conditions are met, the order status is automatically updated to "Completed."
* **Admin Profit Dashboard:**
    * Comprehensive view of profit, allowing filtering by product name, date range, and inventory warehouse location.
    * **Detailed Profits:** Shows individual line items for completed orders, including order ID, order date, **total order profit**, product name, quantity, cost price per unit, selling price per unit, **profit per item**, and inventory location.
    * **Filtered Summary:** Provides aggregate statistics for the filtered data, including total orders, total sales, and total profit.
* **User Authentication & Access Control:**
    * Basic authentication system ensuring only authorized users (e.g., "Sales Manager") can access specific functionalities.

## 🛠️ Technologies Used

* **Backend:** PHP
* **Database:** MySQL (using `mysqli` extension)
* **Frontend:** HTML, CSS (`tables.css` for styling)
* **Development Environment (Recommended):** XAMPP (Apache, MySQL, PHP) or similar WAMP/LAMP stack.

## 🚀 Getting Started

Follow these steps to get a local copy of the project up and running on your machine.

### Prerequisites

* A web server with PHP (e.g., Apache, Nginx).
* MySQL database server.
* PHP versions compatible with `mysqli` (PHP 7.x or 8.x recommended).
* Git (for cloning the repository).
* **XAMPP (Recommended):** Simplifies setting up Apache, MySQL, and PHP on your local machine.

### Installation

1.  **Clone the repository:**
    ```bash
    git clone [https://github.com/YOUR_GITHUB_USERNAME/SalesInventoryManagement.git](https://github.com/YOUR_GITHUB_USERNAME/SalesInventoryManagement.git)
    cd SalesInventoryManagement
    ```
    (Replace `YOUR_GITHUB_USERNAME` and `SalesInventoryManagement` with your actual repository details if you forked/renamed it.)

2.  **Set up your Web Server:**
    * If using **XAMPP**, copy the entire `SalesInventoryManagement` folder into your `htdocs` directory (e.g., `C:\xampp\htdocs\SalesInventoryManagement`).
    * If using another server, place the project folder in your server's web root.

3.  **Create the Database:**
    * Open your MySQL client (e.g., phpMyAdmin via XAMPP, MySQL Workbench, or command line).
    * Create a new database. Let's call it `sales_inventory_db` (you can choose any name).
    ```sql
    CREATE DATABASE sales_inventory_db;
    USE sales_inventory_db;
    ```

4.  **Import the Database Schema:**
    * You'll need an SQL file containing your table creation and initial data. 
    * **Via phpMyAdmin:** Select your `sales_inventory_db`, go to the "Import" tab, choose db.sql` file, and click "Go."
    * **Via MySQL Command Line:**
        ```bash
        mysql -u your_mysql_username -p sales_inventory_db < path/to/your/database.sql
        ```
        (Replace `your_mysql_username` and `path/to/your/database.sql` accordingly.)

5.  **Configure Database Connection:**
    * Open the `includes/db.php` file in your project.
    * Update the database connection details with your MySQL server credentials:
    ```php
    <?php
    $servername = "localhost"; // Usually 'localhost' for local development
    $username = "root";       // Your MySQL username (e.g., 'root' for XAMPP default)
    $password = "";           // Your MySQL password (e.g., empty '' for XAMPP default)
    $dbname = "sales_inventory_db"; // The name of the database you created

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // Optional: Set charset to utf8mb4 for better emoji and special char support
    $conn->set_charset("utf8mb4");
    ?>
    ```

### Usage

1.  **Start your Apache and MySQL servers** (if using XAMPP/WAMP).
2.  **Open your web browser** and navigate to your project:
    * If you placed it in `htdocs/SalesInventoryManagement`: `http://localhost/SalesInventoryManagement/`
    * You will likely land on an index page or a login page, possibly `dashboard.php` if `auth.php` redirects there after successful login.
3.  **Access Modules:**
    * Navigate to `sales/sales_report.php` to view sales reports.
    * Navigate to `sales/payment_details.php` to manage payments.
    * Navigate to `sales/transportation_details.php` to manage deliveries.
    * Navigate to `admin_profit_dashboard.php` for profit analysis.

## 🤝 Contributing

Contributions are welcome! If you find bugs or want to add new features, please:

1.  Fork the repository.
2.  Create a new branch (`git checkout -b feature/YourFeature`).
3.  Make your changes.
4.  Commit your changes (`git commit -m 'Add new feature'`).
5.  Push to the branch (`git push origin feature/YourFeature`).
6.  Open a Pull Request.
