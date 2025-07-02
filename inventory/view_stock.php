<<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("inventory_manager");

// Fetch filter data for dropdowns
// Note: mysqli_query returns a result object or FALSE.
// We'll handle errors if any of these queries fail.
$inventories_res = mysqli_query($conn, "SELECT id, warehouseLocation FROM inventory");
$products_res = mysqli_query($conn, "SELECT id, name FROM products");
$suppliers_res = mysqli_query($conn, "SELECT id, name FROM suppliers");

// Basic error checking for dropdown data fetching
if (!$inventories_res || !$products_res || !$suppliers_res) {
    die("Error fetching filter options: " . mysqli_error($conn));
}

// Initialize filter variables for prepared statement
$filter_conditions = [];
$filter_types = '';
$filter_params = [];

// Handle filters - Use distinct GET parameters for each filter
if (!empty($_GET['inventory_id'])) {
    $filter_conditions[] = "c.inventory_id = ?";
    $filter_types .= "i"; // 'i' for integer
    $filter_params[] = (int)$_GET['inventory_id'];
}
if (!empty($_GET['product_id'])) {
    $filter_conditions[] = "c.product_id = ?";
    $filter_types .= "i"; // 'i' for integer
    $filter_params[] = (int)$_GET['product_id'];
}
if (!empty($_GET['supplier_id'])) {
    $filter_conditions[] = "c.supplier_id = ?";
    $filter_types .= "i"; // 'i' for integer
    $filter_params[] = (int)$_GET['supplier_id'];
}

// Build the WHERE clause dynamically
$where_clause = "WHERE 1=1"; // Start with a true condition to easily append AND clauses
if (!empty($filter_conditions)) {
    $where_clause .= " AND " . implode(" AND ", $filter_conditions);
}

// Fetch stock data using prepared statement
$sql = "
    SELECT
        c.inventory_id,         
        i.warehouseLocation,
        c.product_id,           
        p.name AS productName,
        c.supplier_id,          
        s.name AS supplierName,
        c.stockLevel,
        c.costPrice
    FROM contains c
    JOIN inventory i ON c.inventory_id = i.id  
    JOIN products p ON c.product_id = p.id      
    JOIN suppliers s ON c.supplier_id = s.id    
    $where_clause
    ORDER BY c.inventory_id, p.name
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

// Bind parameters if filters are present
if (!empty($filter_params)) {
    // call_user_func_array is used to bind an unknown number of parameters
    // The first argument to bind_param is the types string ($filter_types)
    // Subsequent arguments are the references to the parameters themselves ($filter_params)
    $bind_names = array_merge([$filter_types], $filter_params);
    $refs = [];
    foreach ($bind_names as $key => $value) {
        $refs[$key] = &$bind_names[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Execute the prepared statement
$execute_success = $stmt->execute();
if (!$execute_success) {
    die("SQL Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}

// Get the result set from the executed prepared statement
$stock_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Inventory Stock</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>📦 Inventory Stock Report</h2>

<form method="GET">
    <label for="inventory_id">Inventory:</label>
    <select name="inventory_id" id="inventory_id">
        <option value="">All</option>
        <?php
        // Rewind result set for dropdown display if it's already been used
        mysqli_data_seek($inventories_res, 0);
        while ($row = mysqli_fetch_assoc($inventories_res)) { ?>
            <option value="<?= $row['id'] ?>"
                <?= isset($_GET['inventory_id']) && (int)$_GET['inventory_id'] == $row['id'] ? 'selected' : '' ?>>
                <?= $row['id'] ?> - <?= htmlspecialchars($row['warehouseLocation']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="product_id">Product:</label>
    <select name="product_id" id="product_id">
        <option value="">All</option>
        <?php
        mysqli_data_seek($products_res, 0); // Rewind
        while ($row = mysqli_fetch_assoc($products_res)) { ?>
            <option value="<?= $row['id'] ?>"
                <?= isset($_GET['product_id']) && (int)$_GET['product_id'] == $row['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['name']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="supplier_id">Supplier:</label>
    <select name="supplier_id" id="supplier_id">
        <option value="">All</option>
        <?php
        mysqli_data_seek($suppliers_res, 0); // Rewind
        while ($row = mysqli_fetch_assoc($suppliers_res)) { ?>
            <option value="<?= $row['id'] ?>"
                <?= isset($_GET['supplier_id']) && (int)$_GET['supplier_id'] == $row['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['name']) ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">Filter</button>
</form>

<table>
    <thead>
        <tr>
            <th>Inventory ID</th>
            <th>Location</th>
            <th>Product</th>
            <th>Supplier</th>
            <th>Stock Level</th>
            <th>Cost Price (₹)</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($stock_result->num_rows > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($stock_result)) { ?>
            <tr>
                <td><?= htmlspecialchars($row['inventory_id']) ?></td>
                <td><?= htmlspecialchars($row['warehouseLocation']) ?></td>
                <td><?= htmlspecialchars($row['productName']) ?></td>
                <td><?= htmlspecialchars($row['supplierName']) ?></td>
                <td><?= htmlspecialchars($row['stockLevel']) ?></td>
                <td>₹<?= number_format($row['costPrice'], 2) ?></td>
            </tr>
            <?php } ?>
        <?php else: ?>
            <tr>
                <td colspan="6">✅ No matching records found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<a href="../dashboard.php">← Back to Dashboard</a>

<?php
// Close statement and free results
$stmt->close();
mysqli_free_result($inventories_res);
mysqli_free_result($products_res);
mysqli_free_result($suppliers_res);
mysqli_close($conn); // Close the database connection
?>

</body>
</html>