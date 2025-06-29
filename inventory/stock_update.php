<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Inventory Manager");

// Fetch options for dropdowns
$inventories = mysqli_query($conn, "SELECT inventoryId FROM inventory");
$products = mysqli_query($conn, "SELECT productId, name FROM products");
$suppliers = mysqli_query($conn, "SELECT supplierId, name FROM suppliers");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productId = $_POST['productId'];
    $supplierId = $_POST['supplierId'];
    $inventoryId = $_POST['inventoryId'];
    $quantity = (int) $_POST['quantity'];
    $costPrice = (float) $_POST['costPrice'];

    $check = mysqli_query($conn, "
        SELECT * FROM contains 
        WHERE productId = $productId 
          AND supplierId = $supplierId 
          AND inventoryId = $inventoryId 
          AND costPrice = $costPrice
    ");

    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "
            UPDATE contains 
            SET stockLevel = stockLevel + $quantity 
            WHERE productId = $productId 
              AND supplierId = $supplierId 
              AND inventoryId = $inventoryId 
              AND costPrice = $costPrice
        ");
        $msg = "Stock level updated!";
    } else {
        mysqli_query($conn, "
            INSERT INTO contains (inventoryId, productId, supplierId, stockLevel, costPrice)
            VALUES ($inventoryId, $productId, $supplierId, $quantity, $costPrice)
        ");
        $msg = "New stock entry created!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Stock</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>

<div class="container">
    <h2>📦 Update Stock</h2>

    <?php if (isset($msg)) echo "<p class='success-msg'>$msg</p>"; ?>

    <form method="POST">
        <label for="inventoryId">Inventory:</label>
        <select name="inventoryId" id="inventoryId" required>
            <option value="">Select Inventory</option>
            <?php while ($inv = mysqli_fetch_assoc($inventories)) { ?>
                <option value="<?= $inv['inventoryId'] ?>"><?= $inv['inventoryId'] ?></option>
            <?php } ?>
        </select>

        <label for="productId">Product:</label>
        <select name="productId" id="productId" required>
            <option value="">Select Product</option>
            <?php while ($prod = mysqli_fetch_assoc($products)) { ?>
                <option value="<?= $prod['productId'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
            <?php } ?>
        </select>

        <label for="supplierId">Supplier:</label>
        <select name="supplierId" id="supplierId" required>
            <option value="">Select Supplier</option>
            <?php while ($sup = mysqli_fetch_assoc($suppliers)) { ?>
                <option value="<?= $sup['supplierId'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
            <?php } ?>
        </select>

        <label for="quantity">Quantity to Add:</label>
        <input type="number" name="quantity" id="quantity" required>

        <label for="costPrice">Cost Price (₹):</label>
        <input type="number" step="0.01" name="costPrice" id="costPrice" required>

        <button type="submit">Update Stock</button>
    </form>

    <a href="../dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
