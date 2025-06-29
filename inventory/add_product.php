<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Inventory Manager");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $category = $_POST["category"];
    $price = $_POST["price"]; // This is now selling price

    mysqli_query($conn, "
        INSERT INTO products (name, category, price)
        VALUES ('$name', '$category', $price)
    ");

    $msg = "✅ Product added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
    <div class="container">
        <h2>Add New Product</h2>

        <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

        <form method="POST">
            <label>Product Name:</label>
            <input type="text" name="name" required>

            <label>Category:</label>
            <input type="text" name="category" required>

            <label>Selling Price (₹):</label>
            <input type="number" step="0.01" name="price" required>

            <button type="submit">Add Product</button>
        </form>

        <a href="../dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
