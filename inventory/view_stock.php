<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Inventory Manager");

// Fetch filter data
$inventories = mysqli_query($conn, "SELECT inventoryId, warehouseLocation FROM inventory");
$products = mysqli_query($conn, "SELECT productId, name FROM products");
$suppliers = mysqli_query($conn, "SELECT supplierId, name FROM suppliers");

// Handle filters
$filter = "1"; // default = no filter
if (!empty($_GET['inventoryId'])) {
    $inventoryId = $_GET['inventoryId'];
    $filter .= " AND c.inventoryId = $inventoryId";
}
if (!empty($_GET['productId'])) {
    $productId = $_GET['productId'];
    $filter .= " AND c.productId = $productId";
}
if (!empty($_GET['supplierId'])) {
    $supplierId = $_GET['supplierId'];
    $filter .= " AND c.supplierId = $supplierId";
}

// Fetch stock data
$stock = mysqli_query($conn, "
    SELECT 
        c.inventoryId, i.warehouseLocation,
        c.productId, p.name AS productName,
        c.supplierId, s.name AS supplierName,
        c.stockLevel, c.costPrice
    FROM contains c
    JOIN inventory i ON c.inventoryId = i.inventoryId
    JOIN products p ON c.productId = p.productId
    JOIN suppliers s ON c.supplierId = s.supplierId
    WHERE $filter
    ORDER BY c.inventoryId, p.name
");
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
    <label for="inventoryId">Inventory:</label>
    <select name="inventoryId" id="inventoryId">
        <option value="">All</option>
        <?php while ($row = mysqli_fetch_assoc($inventories)) { ?>
            <option value="<?= $row['inventoryId'] ?>" 
                <?= isset($_GET['inventoryId']) && $_GET['inventoryId'] == $row['inventoryId'] ? 'selected' : '' ?>>
                <?= $row['inventoryId'] ?> - <?= htmlspecialchars($row['warehouseLocation']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="productId">Product:</label>
    <select name="productId" id="productId">
        <option value="">All</option>
        <?php while ($row = mysqli_fetch_assoc($products)) { ?>
            <option value="<?= $row['productId'] ?>" 
                <?= isset($_GET['productId']) && $_GET['productId'] == $row['productId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['name']) ?>
            </option>
        <?php } ?>
    </select>

    <label for="supplierId">Supplier:</label>
    <select name="supplierId" id="supplierId">
        <option value="">All</option>
        <?php while ($row = mysqli_fetch_assoc($suppliers)) { ?>
            <option value="<?= $row['supplierId'] ?>" 
                <?= isset($_GET['supplierId']) && $_GET['supplierId'] == $row['supplierId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['name']) ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">Filter</button>
</form>

<table>
    <tr>
        <th>Inventory ID</th>
        <th>Location</th>
        <th>Product</th>
        <th>Supplier</th>
        <th>Stock Level</th>
        <th>Cost Price (₹)</th>
    </tr>
    <?php if (mysqli_num_rows($stock) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($stock)) { ?>
        <tr>
            <td><?= $row['inventoryId'] ?></td>
            <td><?= htmlspecialchars($row['warehouseLocation']) ?></td>
            <td><?= htmlspecialchars($row['productName']) ?></td>
            <td><?= htmlspecialchars($row['supplierName']) ?></td>
            <td><?= $row['stockLevel'] ?></td>
            <td>₹<?= number_format($row['costPrice'], 2) ?></td>
        </tr>
        <?php } ?>
    <?php else: ?>
        <tr>
            <td colspan="6">✅ No matching records found.</td>
        </tr>
    <?php endif; ?>
</table>

<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>
