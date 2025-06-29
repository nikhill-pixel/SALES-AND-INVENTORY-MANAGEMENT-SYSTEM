<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Sales Manager");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productId = $_POST['productId'];
    $newPrice = $_POST['newPrice'];

    mysqli_query($conn, "
        UPDATE products 
        SET price = $newPrice 
        WHERE productId = $productId
    ");

    $msg = "Price updated successfully!";
}

// Fetch product list
$products = mysqli_query($conn, "SELECT productId, name, price FROM products");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Product Price</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
<div class="container">
    <h2>Update Product Price</h2>

    <?php if (isset($msg)) echo "<p style='color: green;'>$msg</p>"; ?>

    <form method="POST">
        <label for="productId">Select Product:</label>
        <select name="productId" required>
            <option value="">-- Choose a Product --</option>
            <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                <option value="<?= $row['productId'] ?>">
                    <?= $row['name'] ?> (Current Price: ₹<?= $row['price'] ?>)
                </option>
            <?php } ?>
        </select>

        <label for="newPrice">New Price (₹):</label>
        <input type="number" name="newPrice" step="0.01" required>

        <button type="submit">Update Price</button>
    </form>

    <a href="../dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>
