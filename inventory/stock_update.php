<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("inventory_manager");

// Fetch options for dropdowns (using existing result sets is fine)
$inventories_res = mysqli_query($conn, "SELECT id, warehouseLocation FROM inventory"); // Fetch location for display
$products_res = mysqli_query($conn, "SELECT id, name FROM products");
$suppliers_res = mysqli_query($conn, "SELECT id, name FROM suppliers");

// Check if queries for dropdowns were successful
if (!$inventories_res || !$products_res || !$suppliers_res) {
    die("Error fetching dropdown data: " . mysqli_error($conn));
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Correctly get IDs from distinct form fields
    $inventory_id = (int) $_POST['inventory_id'];
    $product_id = (int) $_POST['product_id'];
    $supplier_id = (int) $_POST['supplier_id'];
    $quantity = (int) $_POST['quantity'];
    $costPrice = (float) $_POST['costPrice'];

    // Use prepared statements for security and to prevent SQL injection
    // CHECK if the combination already exists
    $check_sql = "SELECT stockLevel FROM contains 
                  WHERE inventory_id = ? 
                    AND product_id = ? 
                    AND supplier_id = ? 
                    AND costPrice = ?"; // Use all primary key components and costPrice for specific match
    $stmt_check = $conn->prepare($check_sql);
    if (!$stmt_check) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_check->bind_param("iiid", $inventory_id, $product_id, $supplier_id, $costPrice);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result(); // Get the result set

    if ($check_result->num_rows > 0) {
        // If combination exists, update stock level
        $update_sql = "UPDATE contains 
                       SET stockLevel = stockLevel + ? 
                       WHERE inventory_id = ? 
                         AND product_id = ? 
                         AND supplier_id = ? 
                         AND costPrice = ?";
        $stmt_update = $conn->prepare($update_sql);
        if (!$stmt_update) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt_update->bind_param("iiiid", $quantity, $inventory_id, $product_id, $supplier_id, $costPrice);
        if ($stmt_update->execute()) {
            $msg = "✅ Stock level updated!";
        } else {
            $msg = "❌ Error updating stock: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        // If combination does not exist, insert new entry
        $insert_sql = "INSERT INTO contains (inventory_id, product_id, supplier_id, stockLevel, costPrice)
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        if (!$stmt_insert) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt_insert->bind_param("iiiid", $inventory_id, $product_id, $supplier_id, $quantity, $costPrice);
        if ($stmt_insert->execute()) {
            $msg = "✅ New stock entry created!";
        } else {
            $msg = "❌ Error creating new stock entry: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close(); // Close the check statement
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
        <label for="inventory_id">Inventory:</label>
        <select name="inventory_id" id="inventory_id" required>
            <option value="">Select Inventory</option>
            <?php
            // Rewind result set if it was already fetched to populate options
            mysqli_data_seek($inventories_res, 0);
            while ($inv = mysqli_fetch_assoc($inventories_res)) { ?>
                <option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['warehouseLocation']) ?> (ID: <?= $inv['id'] ?>)</option>
            <?php } ?>
        </select>

        <label for="product_id">Product:</label>
        <select name="product_id" id="product_id" required>
            <option value="">Select Product</option>
            <?php
            mysqli_data_seek($products_res, 0); // Rewind
            while ($prod = mysqli_fetch_assoc($products_res)) { ?>
                <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
            <?php } ?>
        </select>

        <label for="supplier_id">Supplier:</label>
        <select name="supplier_id" id="supplier_id" required>
            <option value="">Select Supplier</option>
            <?php
            mysqli_data_seek($suppliers_res, 0); // Rewind
            while ($sup = mysqli_fetch_assoc($suppliers_res)) { ?>
                <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
            <?php } ?>
        </select>

        <label for="quantity">Quantity to Add:</label>
        <input type="number" name="quantity" id="quantity" required min="1">

        <label for="costPrice">Cost Price (₹):</label>
        <input type="number" step="0.01" name="costPrice" id="costPrice" required min="0">

        <button type="submit">Update Stock</button>
    </form>

    <a href="../dashboard.php">← Back to Dashboard</a>
</div>

<?php
mysqli_close($conn); // Close connection at the end
?>
</body>
</html>