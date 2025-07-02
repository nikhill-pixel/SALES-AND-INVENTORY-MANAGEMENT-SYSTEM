<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch dropdown options for products (using correct column 'name')
$productList_result = mysqli_query($conn, "SELECT DISTINCT name FROM products ORDER BY name");
if (!$productList_result) {
    die("Error fetching product list: " . mysqli_error($conn));
}

// Fetch dropdown options for warehouse locations (using correct column 'warehouseLocation')
$locationList_result = mysqli_query($conn, "SELECT DISTINCT warehouseLocation FROM inventory ORDER BY warehouseLocation");
if (!$locationList_result) {
    die("Error fetching location list: " . mysqli_error($conn));
}

// Get filter values from GET request
$product = $_GET['product'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$location = $_GET['location'] ?? '';

// Build WHERE conditions and parameters for prepared statements
$conditions = ["o.orderStatus = 'Completed'"];
$param_types = '';
$param_values = [];

// Apply filters if provided
if (!empty($product)) {
    $conditions[] = "p.name = ?";
    $param_types .= 's';
    $param_values[] = &$product; // Pass by reference
}
if (!empty($from)) {
    $conditions[] = "o.orderDate >= ?";
    $param_types .= 's';
    $param_values[] = &$from; // Pass by reference
}
if (!empty($to)) {
    $conditions[] = "o.orderDate <= ?";
    $param_types .= 's';
    $param_values[] = &$to; // Pass by reference
}
if (!empty($location)) {
    $conditions[] = "i.warehouseLocation = ?";
    $param_types .= 's';
    $param_values[] = &$location; // Pass by reference
}
$whereClause = implode(" AND ", $conditions);

// --- Detailed profit information query using Prepared Statement ---
$sql_details = "
    SELECT
        o.id AS orderId,                     -- Corrected: o.id
        o.orderDate,
        o.profit AS totalOrderProfit,        -- Order's total profit
        p.name AS productName,
        od.quantity,
        c.costPrice,
        p.price AS sellingPrice,             -- Corrected: use p.price
        (p.price - c.costPrice) * od.quantity AS itemProfit, -- Calculate profit per item
        i.warehouseLocation
    FROM orders o
    JOIN ordered od ON o.id = od.order_id     -- Corrected: o.id = od.order_id
    JOIN products p ON od.product_id = p.id   -- Corrected: od.product_id = p.id
    JOIN contains c ON od.product_id = c.product_id -- Corrected: od.product_id = c.product_id
    JOIN inventory i ON c.inventory_id = i.id -- Corrected: c.inventory_id = i.id
    WHERE $whereClause
    ORDER BY o.orderDate DESC
";

$stmt_details = $conn->prepare($sql_details);
if ($stmt_details === false) {
    die("Error preparing detailed profit query: " . $conn->error);
}

// Bind parameters for detailed query
if (!empty($param_values)) {
    $bind_args = array_merge([$param_types], $param_values);
    // Ensure all values are passed by reference for call_user_func_array
    $refs = [];
    foreach ($bind_args as $key => $value) {
        $refs[$key] = &$bind_args[$key];
    }
    call_user_func_array([$stmt_details, 'bind_param'], $refs);
}

$stmt_details->execute();
$details_result = $stmt_details->get_result();

// --- Filtered Summary calculation using Prepared Statement ---
// This query also needs the joins to filter by product name and location
$sql_summary = "
    SELECT
        COUNT(DISTINCT o.id) AS totalOrders,   -- Count distinct orders
        SUM(o.totalPrice) AS totalSales,
        SUM(o.profit) AS totalProfit
    FROM orders o
    JOIN ordered od ON o.id = od.order_id
    JOIN products p ON od.product_id = p.id
    JOIN contains c ON od.product_id = c.product_id
    JOIN inventory i ON c.inventory_id = i.id
    WHERE $whereClause
";

$stmt_summary = $conn->prepare($sql_summary);
if ($stmt_summary === false) {
    die("Error preparing summary profit query: " . $conn->error);
}

// Bind parameters for summary query (using the same prepared parameters)
if (!empty($param_values)) {
    // Note: $param_values had its type string prepended for $stmt_details.
    // We need to re-create the array for $stmt_summary or ensure a fresh set of references.
    // For simplicity, let's create a new set of references for the summary statement.
    $summary_param_values = []; // Create a new array to hold references for summary
    // Re-assign references from the original GET values
    if (!empty($_GET['product'])) $summary_param_values[] = &$_GET['product'];
    if (!empty($_GET['from'])) $summary_param_values[] = &$_GET['from'];
    if (!empty($_GET['to'])) $summary_param_values[] = &$_GET['to'];
    if (!empty($_GET['location'])) $summary_param_values[] = &$_GET['location'];

    $summary_bind_args = array_merge([$param_types], $summary_param_values);
    $summary_refs = [];
    foreach ($summary_bind_args as $key => $value) {
        $summary_refs[$key] = &$summary_bind_args[$key];
    }
    call_user_func_array([$stmt_summary, 'bind_param'], $summary_refs);
}

$stmt_summary->execute();
$filteredSummaryData = $stmt_summary->get_result()->fetch_assoc();
$stmt_summary->close();

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
            <?php
            // Reset pointer for productList_result as it might have been consumed
            if ($productList_result && mysqli_num_rows($productList_result) > 0) {
                mysqli_data_seek($productList_result, 0);
                while ($p = mysqli_fetch_assoc($productList_result)) {
                    $selected = ($p['name'] == $product) ? 'selected' : '';
            ?>
                    <option value="<?= htmlspecialchars($p['name']) ?>" <?= $selected ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
            <?php
                }
            }
            ?>
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
            <?php
            // Reset pointer for locationList_result as it might have been consumed
            if ($locationList_result && mysqli_num_rows($locationList_result) > 0) {
                mysqli_data_seek($locationList_result, 0);
                while ($loc = mysqli_fetch_assoc($locationList_result)) {
                    $selected = ($loc['warehouseLocation'] == $location) ? 'selected' : '';
            ?>
                    <option value="<?= htmlspecialchars($loc['warehouseLocation']) ?>" <?= $selected ?>>
                        <?= htmlspecialchars($loc['warehouseLocation']) ?>
                    </option>
            <?php
                }
            }
            ?>
        </select>
    </label>

    <button type="submit">Apply Filter</button>
</form>

<h3>Detailed Profits</h3>
<?php if ($details_result && mysqli_num_rows($details_result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Total Order Profit</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Cost Price (Per Unit)</th>
                <th>Selling Price (Per Unit)</th>
                <th>Item Profit</th>
                <th>Inventory Location</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($details_result)) { ?>
            <tr>
                <td><?= htmlspecialchars($row['orderId']) ?></td>
                <td><?= htmlspecialchars($row['orderDate']) ?></td>
                <td style="color:green;">₹<?= number_format($row['totalOrderProfit'], 2) ?></td>
                <td><?= htmlspecialchars($row['productName']) ?></td>
                <td><?= htmlspecialchars($row['quantity']) ?></td>
                <td>₹<?= number_format($row['costPrice'], 2) ?></td>
                <td>₹<?= number_format($row['sellingPrice'], 2) ?></td>
                <td style="color:green;">₹<?= number_format($row['itemProfit'], 2) ?></td>
                <td><?= htmlspecialchars($row['warehouseLocation']) ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No detailed profit records found for the selected filters.</p>
<?php endif; ?>
<?php $stmt_details->close(); // Close statement after fetching all results ?>


<h4>Filtered Summary</h4>
<ul>
    <li><strong>Total Orders:</strong> <?= htmlspecialchars($filteredSummaryData['totalOrders'] ?? 0) ?></li>
    <li><strong>Total Sales:</strong> ₹<?= number_format($filteredSummaryData['totalSales'] ?? 0, 2) ?></li>
    <li><strong>Total Profit:</strong> ₹<?= number_format($filteredSummaryData['totalProfit'] ?? 0, 2) ?></li>
</ul>

<a href="../dashboard.php">← Back to Dashboard</a>
</body>
</html>