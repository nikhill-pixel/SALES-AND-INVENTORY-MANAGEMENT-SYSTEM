<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Inventory Manager");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $location = $_POST["location"];

    $stmt = $conn->prepare("INSERT INTO inventory (warehouseLocation) VALUES (?)");
    $stmt->bind_param("s", $location);
    $stmt->execute();

    $msg = "✅ New inventory location added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Inventory</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
    <div class="container">
        <h2>Add New Inventory Location</h2>

        <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

        <form method="POST">
            <label>Warehouse Location:</label>
            <input type="text" name="location" required>

            <button type="submit">Add Inventory</button>
        </form>

        <a href="../dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
