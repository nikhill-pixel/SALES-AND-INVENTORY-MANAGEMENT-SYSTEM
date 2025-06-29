<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Sales Manager");

$partners = mysqli_query($conn, "SELECT * FROM deliverypartner");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Partner Details</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>🚚 Delivery Partner Details</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Contact</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($partners)) { ?>
        <tr>
            <td><?= $row['deliveryPartnerId'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['contactInfo']) ?></td>
        </tr>
    <?php } ?>
</table>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>
