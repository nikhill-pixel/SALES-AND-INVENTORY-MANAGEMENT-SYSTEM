<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch customers for dropdown
$customers_result = mysqli_query($conn, "SELECT id, name FROM customers ORDER BY name ASC");
if (!$customers_result) {
    die("Error fetching customers: " . mysqli_error($conn));
}

// Build filters using prepared statements for security
$conditions = [];
$param_types = '';
$param_values = []; // This array will hold the actual variables by reference

if (!empty($_GET['from_date'])) {
    $from = $_GET['from_date'];
    $conditions[] = "o.orderDate >= ?";
    $param_types .= 's';
    $param_values[] = &$from; // Pass by reference
}
if (!empty($_GET['to_date'])) {
    $to = $_GET['to_date'];
    $conditions[] = "o.orderDate <= ?";
    $param_types .= 's';
    $param_values[] = &$to; // Pass by reference
}
if (!empty($_GET['order_status'])) {
    $status = $_GET['order_status'];
    $conditions[] = "o.orderStatus = ?";
    $param_types .= 's';
    $param_values[] = &$status; // Pass by reference
}
if (!empty($_GET['delivery_status'])) {
    $dstatus = $_GET['delivery_status'];
    $conditions[] = "t.deliveryStatus = ?";
    $param_types .= 's';
    $param_values[] = &$dstatus; // Pass by reference
}
if (!empty($_GET['payment_status'])) {
    $pstatus = $_GET['payment_status'];
    $conditions[] = "p.paymentStatus = ?";
    $param_types .= 's';
    $param_values[] = &$pstatus; // Pass by reference
}
if (!empty($_GET['customer_id'])) {
    $customer_id_filter = intval($_GET['customer_id']);
    $conditions[] = "c.id = ?"; // Corrected to c.id
    $param_types .= 'i';
    $param_values[] = &$customer_id_filter; // Pass by reference
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Fetch orders using prepared statement for filters
$sql_orders = "
    SELECT
        o.id AS orderId,
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
    JOIN customers c ON o.customer_id = c.id
    LEFT JOIN transportation t ON o.transportationId = t.id
    LEFT JOIN deliverypartner dp ON t.deliveryPartner_id = dp.id
    LEFT JOIN payments p ON o.paymentId = p.id
    $where
    ORDER BY o.orderDate DESC
";

$stmt_orders = $conn->prepare($sql_orders);
if ($stmt_orders === false) {
    die("Error preparing orders query: " . $conn->error);
}

// --- CORRECTED BINDING FOR ORDERS QUERY ---
if (!empty($param_values)) {
    // Prepend the types string to the array of references
    array_unshift($param_values, $param_types);
    call_user_func_array([$stmt_orders, 'bind_param'], $param_values);
}
// --- END CORRECTED BINDING ---

$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

$orderData = [];
$totalProfit = 0;

if ($orders_result) {
    while ($row = $orders_result->fetch_assoc()) {
        $orderId = $row['orderId'];
        $totalProfit += $row['profit'];
        $orderData[$orderId] = $row;
        $orderData[$orderId]['items'] = [];
    }
}
$stmt_orders->close();


// Fetch order items using prepared statement
if (!empty($orderData)) {
    $placeholders = implode(',', array_fill(0, count($orderData), '?'));
    $ids_array = array_keys($orderData); // Get the actual order IDs
    $param_types_items = str_repeat('i', count($ids_array)); // All IDs are integers

    $sql_items = "
        SELECT
            oi.order_id,
            p.name AS productName,
            oi.quantity
        FROM ordered oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($placeholders)
    ";

    $stmt_items = $conn->prepare($sql_items);
    if ($stmt_items === false) {
        die("Error preparing order items query: " . $conn->error);
    }

    // --- CORRECTED BINDING FOR ORDER ITEMS QUERY (LINE 132 APPROX) ---
    // Prepare the arguments array for bind_param
    $bind_args_items = [$param_types_items]; // Start with the type string
    foreach ($ids_array as $key => $value) {
        $bind_args_items[] = &$ids_array[$key]; // Add each ID by reference
    }
    call_user_func_array([$stmt_items, 'bind_param'], $bind_args_items);
    // --- END CORRECTED BINDING ---

    $stmt_items->execute();
    $items_result = $stmt_items->get_result();

    if ($items_result) {
        while ($item = $items_result->fetch_assoc()) {
            $orderData[$item['order_id']]['items'][] = $item;
        }
    }
    $stmt_items->close();
}

// The refValues helper function is no longer needed with this approach.
// You can remove it or keep it if it's used elsewhere.
/*
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}
*/
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
    <input type="date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">

    <label>To:</label>
    <input type="date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">

    <label>Order Status:</label>
    <select name="order_status">
        <option value="">All</option>
        <?php foreach (['Pending', 'Completed', 'Cancelled'] as $status): ?>
            <option value="<?= htmlspecialchars($status) ?>" <?= ($_GET['order_status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Delivery Status:</label>
    <select name="delivery_status">
        <option value="">All</option>
        <?php foreach (['Processing', 'In Transit', 'Delivered', 'Failed'] as $dstatus): // Added 'Failed' as a common status ?>
            <option value="<?= htmlspecialchars($dstatus) ?>" <?= ($_GET['delivery_status'] ?? '') === $dstatus ? 'selected' : '' ?>><?= htmlspecialchars($dstatus) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Payment Status:</label>
    <select name="payment_status">
        <option value="">All</option>
        <?php foreach (['Pending', 'Completed', 'Failed', 'Refunded'] as $pstatus): // Added more common statuses ?>
            <option value="<?= htmlspecialchars($pstatus) ?>" <?= ($_GET['payment_status'] ?? '') === $pstatus ? 'selected' : '' ?>><?= htmlspecialchars($pstatus) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Customer:</label>
    <select name="customer_id">
        <option value="">All</option>
        <?php
        if ($customers_result && mysqli_num_rows($customers_result) > 0) {
            mysqli_data_seek($customers_result, 0); // Reset pointer
            while ($cust = mysqli_fetch_assoc($customers_result)): ?>
                <option value="<?= htmlspecialchars($cust['id']) ?>" <?= ($_GET['customer_id'] ?? '') == $cust['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cust['name']) ?>
                </option>
            <?php endwhile;
        } else {
            echo "<option value=''>No customers available</option>";
        }
        ?>
    </select>

    <button type="submit">Filter</button>
</form>

<?php if (empty($orderData)): ?>
    <p><strong>No orders found for the selected filters.</strong></p>
<?php else: ?>
    <h3>Overall Profit: ₹<?= number_format($totalProfit, 2) ?></h3>
    <?php foreach ($orderData as $order): ?>
        <table class="order-details-table">
            <thead>
                <tr><th colspan="4" style="text-align: center; background-color: #f0f0f0;">Order Details</th></tr>
            </thead>
            <tbody>
                <tr>
                    <th>Order ID</th>
                    <td><?= htmlspecialchars($order["orderId"]) ?></td>
                    <th>Customer</th>
                    <td><?= htmlspecialchars($order["customerName"]) ?></td>
                </tr>
                <tr>
                    <th>Order Date</th>
                    <td><?= htmlspecialchars($order["orderDate"]) ?></td>
                    <th>Order Status</th>
                    <td><?= htmlspecialchars($order["orderStatus"]) ?></td>
                </tr>
                <tr>
                    <th>Total Price</th>
                    <td>₹<?= number_format($order["totalPrice"], 2) ?></td>
                    <th>Profit</th>
                    <td>₹<?= number_format($order["profit"], 2) ?></td>
                </tr>
                <tr>
                    <th>Payment Status</th>
                    <td><?= htmlspecialchars($order["paymentStatus"] ?? 'N/A') ?></td>
                    <th>Delivery Status</th>
                    <td><?= htmlspecialchars($order["deliveryStatus"] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Delivery Date</th>
                    <td><?= htmlspecialchars($order["deliveryDate"] ?? 'N/A') ?></td>
                    <th>Delivery Partner</th>
                    <td><?= htmlspecialchars($order["partnerName"] ?? 'N/A') ?></td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($order["items"])): ?>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order["items"] as $item): ?>
                        <tr class="product-row">
                            <td><?= htmlspecialchars($item["productName"]) ?></td>
                            <td><?= htmlspecialchars($item["quantity"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No items found for this order.</p>
        <?php endif; ?>
        <br><br>
    <?php endforeach; ?>

<?php endif; ?>

<br>
<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>