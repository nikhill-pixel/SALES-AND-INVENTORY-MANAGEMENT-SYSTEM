<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Sales Manager");

// Fetch dropdown options
$productList = mysqli_query($conn, "SELECT DISTINCT name FROM products ORDER BY name");
$locationList = mysqli_query($conn, "SELECT DISTINCT warehouseLocation FROM inventory ORDER BY warehouseLocation");

// Get filter values
$product = $_GET['product'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$location = $_GET['location'] ?? '';

// Build WHERE conditions for detailed data
$conditions = ["o.orderStatus = 'Completed'"];
if (!empty($product)) $conditions[] = "p.name = '" . mysqli_real_escape_string($conn, $product) . "'";
if (!empty($from)) $conditions[] = "o.orderDate >= '" . mysqli_real_escape_string($conn, $from) . "'";
if (!empty($to)) $conditions[] = "o.orderDate <= '" . mysqli_real_escape_string($conn, $to) . "'";
if (!empty($location)) $conditions[] = "i.warehouseLocation = '" . mysqli_real_escape_string($conn, $location) . "'";
$whereClause = implode(" AND ", $conditions);

// Detailed profit info
$details = mysqli_query($conn, "
    SELECT o.orderId, o.orderDate, o.profit, p.name AS productName,
           od.quantity, c.costPrice, pr.price AS sellingPrice, i.warehouseLocation
    FROM orders o
    JOIN ordered od ON o.orderId = od.orderId
    JOIN products p ON od.productId = p.productId
    JOIN contains c ON od.productId = c.productId
    JOIN inventory i ON c.inventoryId = i.inventoryId
    JOIN products pr ON od.productId = pr.productId
    WHERE $whereClause
    GROUP BY o.orderId, od.productId
    ORDER BY o.orderDate DESC
");

// Filtered profit and summary calculation
$profitFilterConditions = ["orderStatus = 'Completed'"];
if (!empty($product)) {
    $profitFilterConditions[] = "orderId IN (
        SELECT o.orderId FROM orders o
        JOIN ordered od ON o.orderId = od.orderId
        JOIN products p ON od.productId = p.productId
        WHERE p.name = '" . mysqli_real_escape_string($conn, $product) . "'
    )";
}
if (!empty($from)) $profitFilterConditions[] = "orderDate >= '" . mysqli_real_escape_string($conn, $from) . "'";
if (!empty($to)) $profitFilterConditions[] = "orderDate <= '" . mysqli_real_escape_string($conn, $to) . "'";
if (!empty($location)) {
    $profitFilterConditions[] = "orderId IN (
        SELECT o.orderId FROM orders o
        JOIN ordered od ON o.orderId = od.orderId
        JOIN contains c ON od.productId = c.productId
        JOIN inventory i ON c.inventoryId = i.inventoryId
        WHERE i.warehouseLocation = '" . mysqli_real_escape_string($conn, $location) . "'
    )";
}
$profitWhere = implode(" AND ", $profitFilterConditions);

// Filtered Summary
$filteredSummary = mysqli_query($conn, "
    SELECT COUNT(*) AS totalOrders, SUM(totalPrice) AS totalSales, SUM(profit) AS totalProfit
    FROM orders WHERE $profitWhere
");
$filteredSummaryData = mysqli_fetch_assoc($filteredSummary);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profit Dashboard</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>🧾 Admin Profit Dashboard</h2>

<h3>Filter Detailed Profits</h3>
<form method="GET">
    <label>Product Name:
        <select name="product">
            <option value="">-- All Products --</option>
            <?php while ($p = mysqli_fetch_assoc($productList)) {
                $selected = ($p['name'] == $product) ? 'selected' : '';
            ?>
                <option value="<?= htmlspecialchars($p['name']) ?>" <?= $selected ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php } ?>
        </select>
    </label>

    <label>From Date:
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
    </label>

    <label>To Date:
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
    </label>

    <label>Inventory Location:
        <select name="location">
            <option value="">-- All Locations --</option>
            <?php while ($loc = mysqli_fetch_assoc($locationList)) {
                $selected = ($loc['warehouseLocation'] == $location) ? 'selected' : '';
            ?>
                <option value="<?= htmlspecialchars($loc['warehouseLocation']) ?>" <?= $selected ?>>
                    <?= htmlspecialchars($loc['warehouseLocation']) ?>
                </option>
            <?php } ?>
        </select>
    </label>

    <button type="submit">Apply Filter</button>
</form>

<h3>Detailed Profits</h3>
<table>
    <tr>
        <th>Order ID</th>
        <th>Date</th>
        <th>Product</th>
        <th>Quantity</th>
        <th>Cost Price</th>
        <th>Selling Price</th>
        <th>Profit</th>
        <th>Inventory Location</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($details)) { ?>
    <tr>
        <td><?= $row['orderId'] ?></td>
        <td><?= $row['orderDate'] ?></td>
        <td><?= $row['productName'] ?></td>
        <td><?= $row['quantity'] ?></td>
        <td>₹<?= number_format($row['costPrice'], 2) ?></td>
        <td>₹<?= number_format($row['sellingPrice'], 2) ?></td>
        <td style="color:green;">₹<?= number_format($row['profit'], 2) ?></td>
        <td><?= $row['warehouseLocation'] ?></td>
    </tr>
    <?php } ?>
</table>

<h4>Filtered Summary</h4>
<ul>
    <li><strong>Total Orders:</strong> <?= $filteredSummaryData['totalOrders'] ?></li>
    <li><strong>Total Sales:</strong> ₹<?= number_format($filteredSummaryData['totalSales'], 2) ?></li>
    <li><strong>Total Profit:</strong> ₹<?= number_format($filteredSummaryData['totalProfit'], 2) ?></li>
</ul>

<a href="../dashboard.php">Back to Dashboard</a>
</body>
</html>
