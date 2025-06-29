<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}
$user = $_SESSION["user"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body class="dashboard-page">

<div class="dashboard-container">
    <h2 class="welcome-msg">
        Welcome, <?= htmlspecialchars($user["name"]) ?> 
        <span class="role-tag">(<?= htmlspecialchars($user["role"]) ?>)</span>
    </h2>

    <div class="card">
        <h3>Your Dashboard</h3>
        <ul class="link-list">
            <?php if ($user["role"] === "Admin") { ?>
                <li><a href="admin/manage_users.php">👤 Manage Users</a></li>
                <li><a href="admin/add_user.php">➕ Add Manager</a></li>
                <li><a href="admin/query_tool.php">🔍 Query Database</a></li>
            <?php } elseif ($user["role"] === "Sales Manager") { ?>
                <li><a href="sales/new_sale.php">📝 Create New Sale</a></li>
                <li><a href="sales/sales_report.php">📊 Sales Reports</a></li>
                <li><a href="sales/add_customer.php">➕ Add New Customer</a></li>
                <li><a href="sales/add_delivery_partner.php">➕ Add New Delivery Partner</a></li>
                <li><a href="sales/view_payments.php">💵 Manage Payments</a></li>
                <li><a href="sales/view_transportation.php">🚚 Manage Deliveries</a></li>
                <li><a href="sales/view_customers.php">👥 View Customers</a></li>
                <li><a href="sales/view_delivery_partners.php">🚛 View Delivery Partners</a></li>
                <li><a href="sales/change_price.php">💲 Change Product Price</a></li>
                <li><a href="sales/profit_dashboard.php">💰 Profit Dashboard</a></li>
            <?php } elseif ($user["role"] === "Inventory Manager") { ?>
                <li><a href="inventory/stock_update.php">📦 Update Stock</a></li>
                <li><a href="inventory/low_stock.php">⚠️ Low Stock Alerts</a></li>
                <li><a href="inventory/add_product.php">➕ Add New Product</a></li>
                <li><a href="inventory/add_supplier.php">➕ Add New Supplier</a></li>
                <li><a href="inventory/view_stock.php">📋 View Stock Details</a></li>
                <li><a href="inventory/add_inventory.php">🏢 Add New Inventory</a></li>
            <?php } ?>
        </ul>
    </div>

    <div class="logout">
        <a href="logout.php" class="btn">Logout</a>
    </div>
</div>

</body>
</html>
