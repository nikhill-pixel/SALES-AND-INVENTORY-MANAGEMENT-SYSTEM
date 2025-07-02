<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $contact = $_POST["contact"];
    $address = $_POST["address"];

    mysqli_query($conn, "
        INSERT INTO customers (name, contactInfo, address)
        VALUES ('$name', '$contact', '$address')
    ");

    $msg = "✅ Customer added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
    <div class="container">
        <h2>Add New Customer</h2>

        <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

        <form method="POST">
            <label>Name:</label>
            <input type="text" name="name" required>

            <label>Contact Info:</label>
            <input type="text" name="contact" required>

            <label>Address:</label>
            <textarea name="address" required></textarea>

            <button type="submit">Add Customer</button>
        </form>

        <a href="../dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
