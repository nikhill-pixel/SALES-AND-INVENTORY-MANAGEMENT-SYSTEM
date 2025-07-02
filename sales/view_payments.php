<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = ''; // Initialize message variable

// Handle form submission to mark payment as completed
if (isset($_POST['update']) && isset($_POST['paymentId'])) {
    $paymentId_to_update = intval($_POST['paymentId']); // Sanitize input immediately

    // Start a transaction for atomicity
    mysqli_begin_transaction($conn);

    try {
        // --- 1. Update payment status and set current date using Prepared Statement ---
        $stmt_update_payment = $conn->prepare("
            UPDATE payments
            SET paymentStatus = 'Completed', paymentDate = CURRENT_DATE
            WHERE id = ? -- Corrected column name to 'id'
        ");
        if ($stmt_update_payment === false) {
            throw new Exception("Error preparing payment update: " . $conn->error);
        }
        $stmt_update_payment->bind_param("i", $paymentId_to_update);
        if (!$stmt_update_payment->execute()) {
            throw new Exception("Error updating payment: " . $stmt_update_payment->error);
        }
        $stmt_update_payment->close();

        // --- 2. Get associated order(s) using Prepared Statement ---
        $stmt_fetch_orders = $conn->prepare("
            SELECT id AS orderId, transportationId
            FROM orders
            WHERE paymentId = ?
        ");
        if ($stmt_fetch_orders === false) {
            throw new Exception("Error preparing order fetch: " . $conn->error);
        }
        $stmt_fetch_orders->bind_param("i", $paymentId_to_update);
        $stmt_fetch_orders->execute();
        $orders_result = $stmt_fetch_orders->get_result();

        while ($order = $orders_result->fetch_assoc()) {
            $orderId = $order['orderId'];
            $transportation_id = $order['transportationId']; // Corrected variable name

            $complete_order = false;

            if (is_null($transportation_id)) {
                // If no transportation is linked, order is complete upon payment
                $complete_order = true;
            } else {
                // Check transportation status using Prepared Statement
                $stmt_trans_status = $conn->prepare("
                    SELECT deliveryStatus
                    FROM transportation
                    WHERE id = ? -- Corrected column name to 'id'
                ");
                if ($stmt_trans_status === false) {
                    throw new Exception("Error preparing transportation status fetch: " . $conn->error);
                }
                $stmt_trans_status->bind_param("i", $transportation_id);
                $stmt_trans_status->execute();
                $transResult = $stmt_trans_status->get_result();
                $statusRow = $transResult->fetch_assoc();
                $stmt_trans_status->close();

                if ($statusRow && $statusRow['deliveryStatus'] === 'Delivered') {
                    $complete_order = true;
                }
            }

            // --- 3. Update order status if complete using Prepared Statement ---
            if ($complete_order) {
                $stmt_update_order = $conn->prepare("
                    UPDATE orders
                    SET orderStatus = 'Completed'
                    WHERE id = ? -- Corrected column name to 'id'
                ");
                if ($stmt_update_order === false) {
                    throw new Exception("Error preparing order status update: " . $conn->error);
                }
                $stmt_update_order->bind_param("i", $orderId);
                if (!$stmt_update_order->execute()) {
                    throw new Exception("Error updating order status: " . $stmt_update_order->error);
                }
                $stmt_update_order->close();
            }
        }
        $stmt_fetch_orders->close(); // Close after the loop

        mysqli_commit($conn); // Commit the transaction if all operations succeed
        $msg = "✅ Payment updated with today's date and relevant orders checked.";

    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback on any error
        $msg = "<p style='color:red;'>❌ Transaction failed: " . $e->getMessage() . "</p>";
    }
}

// Fetch payment data
// Use correct column names for payments table
$data_result = mysqli_query($conn, "SELECT id, paymentStatus, paymentDate, amount, customer_name, customer_contactInfo FROM payments ORDER BY id DESC");
if (!$data_result) {
    die("Error fetching payments: " . mysqli_error($conn));
}
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
    <thead>
        <tr>
            <th>ID</th>
            <th>Status</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Customer Name</th> <th>Customer Contact</th> <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($data_result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($data_result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td> <td><?= htmlspecialchars($row['paymentStatus']) ?></td>
                    <td><?= htmlspecialchars($row['paymentDate'] ?? 'N/A') ?></td>
                    <td>₹<?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td> <td><?= htmlspecialchars($row['customer_contactInfo']) ?></td> <td>
                        <?php if ($row['paymentStatus'] !== 'Completed'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="paymentId" value="<?= htmlspecialchars($row['id']) ?>"> <button type="submit" name="update">Mark Paid</button>
                            </form>
                        <?php else: ?>✔️<?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No payment records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>