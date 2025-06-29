<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Sales Manager");

if (isset($_POST['update']) && isset($_POST['transportationId'])) {
    $transportationId = $_POST['transportationId'];
    $newStatus = $_POST['deliveryStatus'] ?? 'Delivered';

    // Update transportation
    mysqli_query($conn, "
        UPDATE transportation 
        SET deliveryStatus = '$newStatus', 
            deliveryDate = CURDATE() 
        WHERE transportationId = $transportationId
    ");

    // Mark orders completed if payment is done
    if ($newStatus === 'Delivered') {
        $orders = mysqli_query($conn, "
            SELECT orderId, paymentId 
            FROM orders 
            WHERE transportationId = $transportationId
        ");
        while ($order = mysqli_fetch_assoc($orders)) {
            $orderId = $order['orderId'];
            $paymentId = $order['paymentId'];

            if ($paymentId) {
                $paymentResult = mysqli_query($conn, "
                    SELECT paymentStatus 
                    FROM payments 
                    WHERE paymentId = $paymentId
                ");
                $pay = mysqli_fetch_assoc($paymentResult);
                if ($pay && $pay['paymentStatus'] === 'Completed') {
                    mysqli_query($conn, "
                        UPDATE orders 
                        SET orderStatus = 'Completed' 
                        WHERE orderId = $orderId
                    ");
                }
            }
        }
    }

    $msg = "✅ Delivery status updated and relevant orders checked.";
}

$data = mysqli_query($conn, "SELECT * FROM transportation");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transportation Details</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>🚚 Transportation Details</h2>

<?php if (isset($msg)): ?>
    <p style="color: green; font-weight: bold;"><?= $msg ?></p>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Delivery Date</th>
        <th>Delivery Partner ID</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($data)): ?>
        <tr>
            <td><?= $row['transportationId'] ?></td>
            <td><?= htmlspecialchars($row['deliveryStatus']) ?></td>
            <td><?= htmlspecialchars($row['deliveryDate']) ?></td>
            <td><?= $row['deliveryPartnerId'] ?></td>
            <td>
                <?php if ($row['deliveryStatus'] !== 'Delivered'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="transportationId" value="<?= $row['transportationId'] ?>">
                        <input type="hidden" name="deliveryStatus" value="Delivered">
                        <button type="submit" name="update">Mark Delivered</button>
                    </form>
                <?php else: ?>✔️<?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>
