<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Inventory Manager");

// Fetch filter values
$filterProduct = $_GET['product'] ?? '';
$filterInventory = $_GET['inventory'] ?? '';
$filterSupplier = $_GET['supplier'] ?? '';

// Fetch filter dropdown options
$productOptions = mysqli_query($conn, "SELECT productId, name FROM products");
$inventoryOptions = mysqli_query($conn, "SELECT inventoryId, warehouseLocation FROM inventory");
$supplierOptions = mysqli_query($conn, "SELECT supplierId, name FROM suppliers");

// Base query
$query = "
    SELECT c.supplierId, c.costPrice, i.warehouseLocation, p.name AS productName, c.stockLevel
    FROM contains c
    JOIN products p ON c.productId = p.productId
    JOIN inventory i ON c.inventoryId = i.inventoryId
    WHERE c.stockLevel < 10
";

// Append filters if set
if ($filterProduct !== '') {
    $query .= " AND c.productId = " . (int)$filterProduct;
}
if ($filterInventory !== '') {
    $query .= " AND c.inventoryId = " . (int)$filterInventory;
}
if ($filterSupplier !== '') {
    $query .= " AND c.supplierId = " . (int)$filterSupplier;
}

$result = mysqli_query($conn, $query);
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

<!-- Filter Form -->
<form method="GET">
    <label for="product">Product:</label>
    <select name="product" id="product">
        <option value="">All</option>
        <?php while ($p = mysqli_fetch_assoc($productOptions)) { ?>
            <option value="<?= $p['productId'] ?>" <?= $filterProduct == $p['productId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="inventory">Inventory:</label>
    <select name="inventory" id="inventory">
        <option value="">All</option>
        <?php while ($i = mysqli_fetch_assoc($inventoryOptions)) { ?>
            <option value="<?= $i['inventoryId'] ?>" <?= $filterInventory == $i['inventoryId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($i['warehouseLocation']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="supplier">Supplier:</label>
    <select name="supplier" id="supplier">
        <option value="">All</option>
        <?php while ($s = mysqli_fetch_assoc($supplierOptions)) { ?>
            <option value="<?= $s['supplierId'] ?>" <?= $filterSupplier == $s['supplierId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['name']) ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">Filter</button>
</form>

<!-- Table Display -->
<table>
    <tr>
        <th>Inventory Location</th>
        <th>Product Name</th>
        <th>Supplier ID</th>
        <th>Cost Price (₹)</th>
        <th>Stock Level</th>
    </tr>
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?= htmlspecialchars($row["warehouseLocation"]) ?></td>
            <td><?= htmlspecialchars($row["productName"]) ?></td>
            <td><?= $row["supplierId"] ?></td>
            <td>₹<?= number_format($row["costPrice"], 2) ?></td>
            <td><?= $row["stockLevel"] ?></td>
        </tr>
        <?php } ?>
    <?php else: ?>
        <tr><td colspan="5">✅ All stocks are sufficient!</td></tr>
    <?php endif; ?>
</table>

<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>
