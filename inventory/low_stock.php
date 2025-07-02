<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("inventory_manager");

$productOptions_res = mysqli_query($conn, "SELECT id, name FROM products");
$inventoryOptions_res = mysqli_query($conn, "SELECT id, warehouseLocation FROM inventory");
$supplierOptions_res = mysqli_query($conn, "SELECT id, name FROM suppliers");

if (!$productOptions_res || !$inventoryOptions_res || !$supplierOptions_res) {
    die("Error fetching filter options: " . mysqli_error($conn));
}

$filterProduct = $_GET['product'] ?? '';
$filterInventory = $_GET['inventory'] ?? '';
$filterSupplier = $_GET['supplier'] ?? '';

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
    JOIN products p ON c.product_id = p.id      
    JOIN inventory i ON c.inventory_id = i.id  
    JOIN suppliers s ON c.supplier_id = s.id    
    WHERE c.stockLevel < 10
";

$filter_conditions = [];
$filter_types = '';
$filter_params = [];

if ($filterProduct !== '') {
    $filter_conditions[] = "c.product_id = ?";
    $filter_types .= "i";
    $filter_params[] = (int)$filterProduct;
}
if ($filterInventory !== '') {
    $filter_conditions[] = "c.inventory_id = ?";
    $filter_types .= "i";
    $filter_params[] = (int)$filterInventory;
}
if ($filterSupplier !== '') {
    $filter_conditions[] = "c.supplier_id = ?";
    $filter_types .= "i";
    $filter_params[] = (int)$filterSupplier;
}

if (!empty($filter_conditions)) {
    $sql .= " AND " . implode(" AND ", $filter_conditions);
}

$sql .= " ORDER BY c.inventory_id, p.name";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

if (!empty($filter_params)) {
    $bind_names = array_merge([$filter_types], $filter_params);
    $refs = [];
    foreach ($bind_names as $key => $value) {
        $refs[$key] = &$bind_names[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$execute_success = $stmt->execute();
if (!$execute_success) {
    die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Low Stock Alerts</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>🚨 Low Stock Products</h2>

<form method="GET">
    <label for="product">Product:</label>
    <select name="product" id="product">
        <option value="">All</option>
        <?php
        mysqli_data_seek($productOptions_res, 0);
        while ($p = mysqli_fetch_assoc($productOptions_res)) { ?>
            <option value="<?= $p['id'] ?>" <?= $filterProduct == $p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="inventory">Inventory:</label>
    <select name="inventory" id="inventory">
        <option value="">All</option>
        <?php
        mysqli_data_seek($inventoryOptions_res, 0);
        while ($i = mysqli_fetch_assoc($inventoryOptions_res)) { ?>
            <option value="<?= $i['id'] ?>" <?= $filterInventory == $i['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($i['warehouseLocation']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="supplier">Supplier:</label>
    <select name="supplier" id="supplier">
        <option value="">All</option>
        <?php
        mysqli_data_seek($supplierOptions_res, 0);
        while ($s = mysqli_fetch_assoc($supplierOptions_res)) { ?>
            <option value="<?= $s['id'] ?>" <?= $filterSupplier == $s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['name']) ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">Filter</button>
</form>

<table>
    <thead>
        <tr>
            <th>Inventory Location</th>
            <th>Product Name</th>
            <th>Supplier Name</th>
            <th>Cost Price (₹)</th>
            <th>Stock Level</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= htmlspecialchars($row["warehouseLocation"]) ?></td>
                <td><?= htmlspecialchars($row["productName"]) ?></td>
                <td><?= htmlspecialchars($row["supplierName"]) ?></td>
                <td>₹<?= number_format($row["costPrice"], 2) ?></td>
                <td><?= $row["stockLevel"] ?></td>
            </tr>
            <?php } ?>
        <?php else: ?>
            <tr><td colspan="5">✅ All stocks are sufficient!</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<a href="../dashboard.php">← Back to Dashboard</a>

<?php
$stmt->close();
mysqli_free_result($productOptions_res);
mysqli_free_result($inventoryOptions_res);
mysqli_free_result($supplierOptions_res);
mysqli_close($conn);
?>

</body>
</html>