<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Sales Manager");

// Handle form submission to mark payment as completed
if (isset($_POST['update']) && isset($_POST['paymentId'])) {
    $paymentId = $_POST['paymentId'];
    
    // Update payment status and set current date
    mysqli_query($conn, "
        UPDATE payments 
        SET paymentStatus = 'Completed', paymentDate = CURRENT_DATE 
        WHERE paymentId = $paymentId
    ");

    // Get associated order(s)
    $orders = mysqli_query($conn, "
        SELECT orderId, transportationId 
        FROM orders 
        WHERE paymentId = $paymentId
    ");
    while ($order = mysqli_fetch_assoc($orders)) {
        $orderId = $order['orderId'];
        $transId = $order['transportationId'];

        $complete = false;

        if (is_null($transId)) {
            $complete = true;
        } else {
            $transResult = mysqli_query($conn, "
                SELECT deliveryStatus 
                FROM transportation 
                WHERE transportationId = $transId
            ");
            $statusRow = mysqli_fetch_assoc($transResult);
            if ($statusRow && $statusRow['deliveryStatus'] === 'Delivered') {
                $complete = true;
            }
        }

        if ($complete) {
            mysqli_query($conn, "
                UPDATE orders 
                SET orderStatus = 'Completed' 
                WHERE orderId = $orderId
            ");
        }
    }

    $msg = "✅ Payment updated with today's date and relevant orders checked.";
}

// Fetch payment data
$data = mysqli_query($conn, "SELECT * FROM payments");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>💰 Payment Details</h2>

<?php if (isset($msg)): ?>
    <p style="color: green; font-weight: bold;"><?= $msg ?></p>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Date</th>
        <th>Amount</th>
        <th>Name</th>
        <th>Contact</th>
        <th>Action</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($data)): ?>
        <tr>
            <td><?= $row['paymentId'] ?></td>
            <td><?= htmlspecialchars($row['paymentStatus']) ?></td>
            <td><?= htmlspecialchars($row['paymentDate']) ?></td>
            <td>₹<?= number_format($row['amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['contactInfo']) ?></td>
            <td>
                <?php if ($row['paymentStatus'] !== 'Completed'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="paymentId" value="<?= $row['paymentId'] ?>">
                        <button type="submit" name="update">Mark Paid</button>
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
