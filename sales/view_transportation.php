<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = ''; // Initialize message variable

if (isset($_POST['update']) && isset($_POST['transportationId'])) {
    $transportation_id_to_update = intval($_POST['transportationId']); // Sanitize input
    $newStatus = $_POST['deliveryStatus'] ?? 'Delivered'; // Default to Delivered if not set, though form forces it

    // Start a transaction for atomicity
    mysqli_begin_transaction($conn);

    try {
        // --- 1. Update transportation status and set delivery date using Prepared Statement ---
        $stmt_update_transportation = $conn->prepare("
            UPDATE transportation
            SET deliveryStatus = ?,
                deliveryDate = CURDATE()
            WHERE id = ? -- Corrected column name to 'id'
        ");
        if ($stmt_update_transportation === false) {
            throw new Exception("Error preparing transportation update: " . $conn->error);
        }
        $stmt_update_transportation->bind_param("si", $newStatus, $transportation_id_to_update); // 's' for string, 'i' for integer
        if (!$stmt_update_transportation->execute()) {
            throw new Exception("Error updating transportation: " . $stmt_update_transportation->error);
        }
        $stmt_update_transportation->close();

        // --- 2. Mark orders completed if payment is done and newStatus is 'Delivered' ---
        if ($newStatus === 'Delivered') {
            // Fetch associated orders using Prepared Statement
            $stmt_fetch_orders = $conn->prepare("
                SELECT id AS orderId, paymentId
                FROM orders
                WHERE transportationId = ?
            ");
            if ($stmt_fetch_orders === false) {
                throw new Exception("Error preparing order fetch: " . $conn->error);
            }
            $stmt_fetch_orders->bind_param("i", $transportation_id_to_update);
            $stmt_fetch_orders->execute();
            $orders_result = $stmt_fetch_orders->get_result();

            while ($order = $orders_result->fetch_assoc()) {
                $orderId = $order['orderId'];
                $paymentId = $order['paymentId'];

                if ($paymentId) { // Only check payment if there's an associated payment
                    // Check payment status using Prepared Statement
                    $stmt_payment_status = $conn->prepare("
                        SELECT paymentStatus
                        FROM payments
                        WHERE id = ? -- Corrected column name to 'id'
                    ");
                    if ($stmt_payment_status === false) {
                        throw new Exception("Error preparing payment status fetch: " . $conn->error);
                    }
                    $stmt_payment_status->bind_param("i", $paymentId);
                    $stmt_payment_status->execute();
                    $paymentResult = $stmt_payment_status->get_result();
                    $pay = $paymentResult->fetch_assoc();
                    $stmt_payment_status->close();

                    if ($pay && $pay['paymentStatus'] === 'Completed') {
                        // Update order status to 'Completed' using Prepared Statement
                        $stmt_update_order_status = $conn->prepare("
                            UPDATE orders
                            SET orderStatus = 'Completed'
                            WHERE id = ? -- Corrected column name to 'id'
                        ");
                        if ($stmt_update_order_status === false) {
                            throw new Exception("Error preparing order status update: " . $conn->error);
                        }
                        $stmt_update_order_status->bind_param("i", $orderId);
                        if (!$stmt_update_order_status->execute()) {
                            throw new Exception("Error updating order status: " . $stmt_update_order_status->error);
                        }
                        $stmt_update_order_status->close();
                    }
                }
            }
            $stmt_fetch_orders->close(); // Close after the loop
        }

        mysqli_commit($conn); // Commit the transaction if all operations succeed
        $msg = "✅ Delivery status updated and relevant orders checked.";

    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback on any error
        $msg = "<p style='color:red;'>❌ Transaction failed: " . $e->getMessage() . "</p>";
    }
}

// Fetch transportation data
// Use correct column names for transportation table
$data_result = mysqli_query($conn, "
    SELECT t.id, t.deliveryStatus, t.deliveryDate, dp.name AS deliveryPartnerName
    FROM transportation t
    LEFT JOIN deliverypartner dp ON t.deliveryPartner_id = dp.id -- Corrected column names
    ORDER BY t.id DESC
");
if (!$data_result) {
    die("Error fetching transportation data: " . mysqli_error($conn));
}
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
    <thead>
        <tr>
            <th>ID</th>
            <th>Status</th>
            <th>Delivery Date</th>
            <th>Delivery Partner</th> <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($data_result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($data_result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td> <td><?= htmlspecialchars($row['deliveryStatus']) ?></td>
                    <td><?= htmlspecialchars($row['deliveryDate'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['deliveryPartnerName'] ?? 'N/A') ?></td> <td>
                        <?php if ($row['deliveryStatus'] !== 'Delivered'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="transportationId" value="<?= htmlspecialchars($row['id']) ?>"> <input type="hidden" name="deliveryStatus" value="Delivered">
                                <button type="submit" name="update">Mark Delivered</button>
                            </form>
                        <?php else: ?>✔️<?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No transportation records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>