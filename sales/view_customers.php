<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

$customers = mysqli_query($conn, "SELECT * FROM customers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Details</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>👥 Customer Details</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Contact</th>
        <th>Address</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($customers)) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['contactInfo']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
        </tr>
    <?php } ?>
</table>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>
