<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

// Assuming your table name is 'delivery_partners' or 'deliverypartner'
// Replace 'deliverypartner' with the actual correct table name in your database
$tableName = "deliverypartner"; // <--- **VERIFY THIS TABLE NAME IN YOUR DATABASE**

$partners = mysqli_query($conn, "SELECT id, name, contactInfo FROM {$tableName}"); // It's good to select specific columns

// Add error checking for the query
if (!$partners) {
    die("Error fetching delivery partners: " . mysqli_error($conn) . " Query: SELECT id, name, contactInfo FROM {$tableName}");
}

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
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Contact</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($partners) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($partners)) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['contactInfo']) ?></td>
                </tr>
            <?php } ?>
        <?php else: ?>
            <tr>
                <td colspan="3">No delivery partners found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

<?php
// Free result set and close connection
mysqli_free_result($partners);
mysqli_close($conn);
?>

</body>
</html>