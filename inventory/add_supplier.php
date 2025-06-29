<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Inventory Manager");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $contact = $_POST["contact"];
    $location = $_POST["location"];

    mysqli_query($conn, "
        INSERT INTO suppliers (name, contactInfo, location) 
        VALUES ('$name', '$contact', '$location')
    ");

    $msg = "✅ Supplier added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Supplier</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
    <div class="container">
        <h2>Add New Supplier</h2>

        <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

        <form method="POST">
            <label>Name:</label>
            <input type="text" name="name" required>

            <label>Contact Info:</label>
            <input type="text" name="contact" required>

            <label>Location:</label>
            <input type="text" name="location" required>

            <button type="submit">Add Supplier</button>
        </form>

        <a href="../dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
