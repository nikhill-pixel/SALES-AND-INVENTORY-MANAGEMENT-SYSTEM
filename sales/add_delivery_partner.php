<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $contact = $_POST['contactInfo'];

    mysqli_query($conn, "
        INSERT INTO deliverypartner (name, contactInfo)
        VALUES ('$name', '$contact')
    ");
    $msg = "Delivery partner added!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Delivery Partner</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
<div class="container">
    <h2>Add Delivery Partner</h2>

    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

    <form method="POST">
        <label>Name:</label>
        <input type="text" name="name" required>

        <label>Contact Info:</label>
        <input type="text" name="contactInfo" maxlength="10" required>

        <button type="submit">Add</button>
    </form>

    <a href="../dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>
