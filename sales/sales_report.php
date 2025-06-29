<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Sales Manager");

// Fetch customers for dropdown
$customers = mysqli_query($conn, "SELECT customerId, name FROM customers");

// Build filters
$conditions = [];
if (!empty($_GET['from_date'])) {
    $from = $_GET['from_date'];
    $conditions[] = "o.orderDate >= '$from'";
}
if (!empty($_GET['to_date'])) {
    $to = $_GET['to_date'];
    $conditions[] = "o.orderDate <= '$to'";
}
if (!empty($_GET['order_status'])) {
    $conditions[] = "o.orderStatus = '" . $_GET['order_status'] . "'";
}
if (!empty($_GET['delivery_status'])) {
    $conditions[] = "t.deliveryStatus = '" . $_GET['delivery_status'] . "'";
}
if (!empty($_GET['payment_status'])) {
    $conditions[] = "p.paymentStatus = '" . $_GET['payment_status'] . "'";
}
if (!empty($_GET['customer_id'])) {
    $conditions[] = "c.customerId = " . intval($_GET['customer_id']);
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Fetch orders
$orders = mysqli_query($conn, "
    SELECT 
        o.orderId, 
        c.name AS customerName, 
        o.orderDate, 
        o.totalPrice, 
        o.orderStatus,
        o.profit,
        t.deliveryStatus, 
        t.deliveryDate,
        dp.name AS partnerName,
        p.paymentStatus
    FROM orders o
    JOIN customers c ON o.customerId = c.customerId
    LEFT JOIN transportation t ON o.transportationId = t.transportationId
    LEFT JOIN deliverypartner dp ON t.deliveryPartnerId = dp.deliveryPartnerId
    LEFT JOIN payments p ON o.paymentId = p.paymentId
    $where
    ORDER BY o.orderDate DESC
");

$orderData = [];
$totalProfit = 0;

while ($row = mysqli_fetch_assoc($orders)) {
    $orderId = $row['orderId'];
    $totalProfit += $row['profit'];
    $orderData[$orderId] = $row;
    $orderData[$orderId]['items'] = [];
}

// Fetch order items
if (!empty($orderData)) {
    $ids = implode(",", array_keys($orderData));
    $items = mysqli_query($conn, "
        SELECT oi.orderId, p.name AS productName, oi.quantity
        FROM ordered oi
        JOIN products p ON oi.productId = p.productId
        WHERE oi.orderId IN ($ids)
    ");
    while ($item = mysqli_fetch_assoc($items)) {
        $orderData[$item['orderId']]['items'][] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>📊 Sales Report</h2>

<form method="GET">
    <label>From:</label>
    <input type="date" name="from_date" value="<?= $_GET['from_date'] ?? '' ?>">

    <label>To:</label>
    <input type="date" name="to_date" value="<?= $_GET['to_date'] ?? '' ?>">

    <label>Order Status:</label>
    <select name="order_status">
        <option value="">All</option>
        <?php foreach (['Pending', 'Completed', 'Cancelled'] as $status): ?>
            <option value="<?= $status ?>" <?= ($_GET['order_status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
    </select>

    <label>Delivery Status:</label>
    <select name="delivery_status">
        <option value="">All</option>
        <?php foreach (['Processing', 'In Transit', 'Delivered'] as $dstatus): ?>
            <option value="<?= $dstatus ?>" <?= ($_GET['delivery_status'] ?? '') === $dstatus ? 'selected' : '' ?>><?= $dstatus ?></option>
        <?php endforeach; ?>
    </select>

    <label>Payment Status:</label>
    <select name="payment_status">
        <option value="">All</option>
        <?php foreach (['Pending', 'Completed'] as $pstatus): ?>
            <option value="<?= $pstatus ?>" <?= ($_GET['payment_status'] ?? '') === $pstatus ? 'selected' : '' ?>><?= $pstatus ?></option>
        <?php endforeach; ?>
    </select>

    <label>Customer:</label>
    <select name="customer_id">
        <option value="">All</option>
        <?php while ($cust = mysqli_fetch_assoc($customers)): ?>
            <option value="<?= $cust['customerId'] ?>" <?= ($_GET['customer_id'] ?? '') == $cust['customerId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cust['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Filter</button>
</form>

<?php if (empty($orderData)): ?>
    <p><strong>No orders found for the selected filters.</strong></p>
<?php else: ?>
    <?php foreach ($orderData as $order): ?>
        <table>
            <tr>
                <th>Order ID</th>
                <td><?= $order["orderId"] ?></td>
                <th>Customer</th>
                <td><?= htmlspecialchars($order["customerName"]) ?></td>
            </tr>
            <tr>
                <th>Order Date</th>
                <td><?= $order["orderDate"] ?></td>
                <th>Order Status</th>
                <td><?= $order["orderStatus"] ?></td>
            </tr>
            <tr>
                <th>Total Price</th>
                <td>₹<?= number_format($order["totalPrice"], 2) ?></td>
                <th>Profit</th>
                <td>₹<?= number_format($order["profit"], 2) ?></td>
            </tr>
            <tr>
                <th>Payment Status</th>
                <td><?= $order["paymentStatus"] ?? 'N/A' ?></td>
                <th>Delivery Status</th>
                <td><?= $order["deliveryStatus"] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <th>Delivery Date</th>
                <td><?= $order["deliveryDate"] ?? 'N/A' ?></td>
                <th>Delivery Partner</th>
                <td><?= htmlspecialchars($order["partnerName"] ?? 'N/A') ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
            </tr>
            <?php foreach ($order["items"] as $item): ?>
                <tr class="product-row">
                    <td><?= htmlspecialchars($item["productName"]) ?></td>
                    <td><?= $item["quantity"] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>

<?php endif; ?>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>
