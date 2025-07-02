<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("sales Manager");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch customers
$customers_result = mysqli_query($conn, "SELECT id, name FROM customers ORDER BY name ASC");
// Check for errors
if (!$customers_result) {
    die("Error fetching customers: " . mysqli_error($conn));
}

// Fetch all available stock entries with product, inventory, and supplier info
// IMPORTANT: The `contains` table's primary key is (inventory_id, product_id, supplier_id, costPrice).
// You need to select all these components to correctly identify a stock item.
$stockQuery_result = mysqli_query($conn, "
    SELECT
        c.inventory_id,
        c.product_id,
        c.supplier_id,
        c.costPrice,
        c.stockLevel,
        p.name AS productName,
        i.warehouseLocation,
        s.name AS supplierName -- Added supplier name for clarity
    FROM contains c
    JOIN products p ON c.product_id = p.id
    JOIN inventory i ON c.inventory_id = i.id
    JOIN suppliers s ON c.supplier_id = s.id -- Join with suppliers table
    WHERE c.stockLevel > 0
    ORDER BY p.name ASC, i.warehouseLocation ASC, c.costPrice ASC
");

// Check for errors
if (!$stockQuery_result) {
    die("Error fetching stock data: " . mysqli_error($conn));
}

// Fetch delivery partners
$partners_result = mysqli_query($conn, "SELECT id, name FROM deliverypartner ORDER BY name ASC");
// Check for errors
if (!$partners_result) {
    die("Error fetching delivery partners: " . mysqli_error($conn));
}

$msg = ''; // Initialize message variable

if (isset($_POST['submit'])) {
    // --- 1. Sanitize and validate input ---
    $customer_id = intval($_POST['customer_id']); // Renamed from $_POST['id'] to avoid confusion
    $stockKey = $_POST['stockKey']; // Format: inventory_id_product_id_supplier_id_costPrice
    $quantity = intval($_POST['quantity']);
    $deliveryRequired = $_POST['deliveryRequired'];
    $deliveryPartner_id = ($deliveryRequired === "Yes" && isset($_POST['deliveryPartner_id'])) ? intval($_POST['deliveryPartner_id']) : null; // Get delivery partner if delivery is required

    // Validate if quantities are positive
    if ($quantity <= 0) {
        $msg = "<p style='color:red;'>Error: Quantity must be a positive number.</p>";
        // Exit early if validation fails
    } else {
        // --- 2. Extract components from stockKey ---
        $stockKeyParts = explode('_', $stockKey);
        if (count($stockKeyParts) !== 4) {
            $msg = "<p style='color:red;'>Error: Invalid stock selection.</p>";
        } else {
            list($inventory_id, $product_id, $supplier_id, $costPrice_str) = $stockKeyParts;

            $inventory_id = intval($inventory_id);
            $product_id = intval($product_id);
            $supplier_id = intval($supplier_id);
            $costPrice = floatval($costPrice_str); // Use floatval for decimal numbers

            // --- 3. Check Stock Level using Prepared Statement ---
            $stmt_stock_check = $conn->prepare("SELECT stockLevel FROM contains WHERE inventory_id = ? AND product_id = ? AND supplier_id = ? AND costPrice = ?");
            if ($stmt_stock_check === false) {
                $msg = "<p style='color:red;'>Error preparing stock check: " . $conn->error . "</p>";
            } else {
                $stmt_stock_check->bind_param("iiid", $inventory_id, $product_id, $supplier_id, $costPrice); // 'i' for int, 'd' for double
                $stmt_stock_check->execute();
                $stockCheckResult = $stmt_stock_check->get_result();
                $stockRow = $stockCheckResult->fetch_assoc();
                $stockLevel = $stockRow['stockLevel'] ?? 0; // Use null coalescing to prevent errors if no stock found
                $stmt_stock_check->close();

                if ($stockLevel < $quantity) {
                    $msg = "<p style='color:red;'>Error: Not enough stock available. Available: " . $stockLevel . "</p>";
                } else {
                    // --- 4. Begin Transaction ---
                    mysqli_begin_transaction($conn); // Start a transaction for atomicity

                    try {
                        // --- 5. Update Contains Stock Level using Prepared Statement ---
                        $stmt_update_contains = $conn->prepare("UPDATE contains SET stockLevel = stockLevel - ? WHERE inventory_id = ? AND product_id = ? AND supplier_id = ? AND costPrice = ?");
                        if ($stmt_update_contains === false) { throw new Exception("Error preparing update contains: " . $conn->error); }
                        $stmt_update_contains->bind_param("iiiid", $quantity, $inventory_id, $product_id, $supplier_id, $costPrice);
                        if (!$stmt_update_contains->execute()) { throw new Exception("Error updating stock: " . $stmt_update_contains->error); }
                        $stmt_update_contains->close();

                        // --- 6. Get Product Selling Price using Prepared Statement ---
                        $stmt_price_res = $conn->prepare("SELECT price FROM products WHERE id = ?");
                        if ($stmt_price_res === false) { throw new Exception("Error preparing price fetch: " . $conn->error); }
                        $stmt_price_res->bind_param("i", $product_id);
                        $stmt_price_res->execute();
                        $priceResult = $stmt_price_res->get_result();
                        $product_price_row = $priceResult->fetch_assoc();
                        $product_selling_price = $product_price_row['price'];
                        $stmt_price_res->close();

                        $total = $product_selling_price * $quantity;
                        $profit = ($product_selling_price - $costPrice) * $quantity;

                        // --- 7. Insert into Orders using Prepared Statement ---
                        // orders table needs customer_id, totalPrice, profit, orderStatus, paymentId, transportationId
                        // paymentId and transportationId are nullable and updated later
                        $stmt_insert_order = $conn->prepare("INSERT INTO orders (customer_id, orderDate, totalPrice, profit, orderStatus) VALUES (?, CURDATE(), ?, ?, 'Pending')");
                        if ($stmt_insert_order === false) { throw new Exception("Error preparing insert order: " . $conn->error); }
                        $stmt_insert_order->bind_param("idd", $customer_id, $total, $profit); // i for customer_id, d for total, d for profit
                        if (!$stmt_insert_order->execute()) { throw new Exception("Error inserting order: " . $stmt_insert_order->error); }
                        $order_id = mysqli_insert_id($conn);
                        $stmt_insert_order->close();

                        // --- 8. Insert into Ordered (order_items) using Prepared Statement ---
                        // ordered table requires order_id, inventory_id, product_id, supplier_id, costPriceAtSale, quantity
                        $stmt_insert_ordered = $conn->prepare("INSERT INTO ordered (order_id, inventory_id, product_id, supplier_id, costPriceAtSale, quantity) VALUES (?, ?, ?, ?, ?, ?)");
                        if ($stmt_insert_ordered === false) { throw new Exception("Error preparing insert ordered: " . $conn->error); }
                        $stmt_insert_ordered->bind_param("iiiiid", $order_id, $inventory_id, $product_id, $supplier_id, $costPrice, $quantity);
                        if (!$stmt_insert_ordered->execute()) { throw new Exception("Error inserting ordered item: " . $stmt_insert_ordered->error); }
                        $stmt_insert_ordered->close();

                        // --- 9. Insert into Payments using Prepared Statement ---
                        // payments table needs order_id (nullable), paymentDate, paymentStatus, amount, customer_name, customer_contactInfo
                        // Fetch customer details for payment record
                        $stmt_customer_info = $conn->prepare("SELECT name, contactInfo FROM customers WHERE id = ?");
                        if ($stmt_customer_info === false) { throw new Exception("Error preparing customer info fetch: " . $conn->error); }
                        $stmt_customer_info->bind_param("i", $customer_id);
                        $stmt_customer_info->execute();
                        $customerInfoResult = $stmt_customer_info->get_result();
                        $customerRow = $customerInfoResult->fetch_assoc();
                        $customer_name_for_payment = $customerRow['name'];
                        $customer_contactInfo_for_payment = $customerRow['contactInfo'];
                        $stmt_customer_info->close();

                        $stmt_insert_payment = $conn->prepare("INSERT INTO payments (order_id, paymentDate, paymentStatus, amount, customer_name, customer_contactInfo) VALUES (?, CURDATE(), 'Pending', ?, ?, ?)");
                        if ($stmt_insert_payment === false) { throw new Exception("Error preparing insert payment: " . $conn->error); }
                        $stmt_insert_payment->bind_param("idss", $order_id, $total, $customer_name_for_payment, $customer_contactInfo_for_payment);
                        if (!$stmt_insert_payment->execute()) { throw new Exception("Error inserting payment: " . $stmt_insert_payment->error); }
                        $paymentId = mysqli_insert_id($conn);
                        $stmt_insert_payment->close();

                        // --- 10. Update Orders with paymentId using Prepared Statement ---
                        $stmt_update_order_payment = $conn->prepare("UPDATE orders SET paymentId = ? WHERE id = ?");
                        if ($stmt_update_order_payment === false) { throw new Exception("Error preparing update order payment: " . $conn->error); }
                        $stmt_update_order_payment->bind_param("ii", $paymentId, $order_id);
                        if (!$stmt_update_order_payment->execute()) { throw new Exception("Error updating order with paymentId: " . $stmt_update_order_payment->error); }
                        $stmt_update_order_payment->close();

                        // --- 11. Handle Transportation if Required ---
                        if ($deliveryRequired === "Yes") {
                            $stmt_insert_transportation = $conn->prepare("INSERT INTO transportation (order_id, deliveryPartner_id, deliveryStatus, deliveryDate) VALUES (?, ?, 'Processing', NULL)"); // Assume deliveryDate is set later
                            if ($stmt_insert_transportation === false) { throw new Exception("Error preparing insert transportation: " . $conn->error); }
                            $stmt_insert_transportation->bind_param("ii", $order_id, $deliveryPartner_id);
                            if (!$stmt_insert_transportation->execute()) { throw new Exception("Error inserting transportation: " . $stmt_insert_transportation->error); }
                            $transportationId = mysqli_insert_id($conn);
                            $stmt_insert_transportation->close();

                            // Update Orders with transportationId
                            $stmt_update_order_transportation = $conn->prepare("UPDATE orders SET transportationId = ? WHERE id = ?");
                            if ($stmt_update_order_transportation === false) { throw new Exception("Error preparing update order transportation: " . $conn->error); }
                            $stmt_update_order_transportation->bind_param("ii", $transportationId, $order_id);
                            if (!$stmt_update_order_transportation->execute()) { throw new Exception("Error updating order with transportationId: " . $stmt_update_order_transportation->error); }
                            $stmt_update_order_transportation->close();
                        }

                        // --- 12. Commit Transaction ---
                        mysqli_commit($conn);
                        $msg = "<p style='color:green;'>Sale successfully created! Order ID: " . $order_id . ". Profit earned: ₹" . number_format($profit, 2) . "</p>";

                    } catch (Exception $e) {
                        // --- 13. Rollback Transaction on Error ---
                        mysqli_rollback($conn);
                        $msg = "<p style='color:red;'>Sale creation failed: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create New Sale</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
<div class="container">
    <h2>Create New Sale</h2>

    <?php if (isset($msg)) echo $msg; ?>

    <form method="post">
        <label>Customer:</label>
        <select name="customer_id" required>
            <option value="">Select Customer</option> <?php
            // Rewind customer result set in case it was already iterated
            if ($customers_result && mysqli_num_rows($customers_result) > 0) {
                 mysqli_data_seek($customers_result, 0); // Reset pointer
                 while ($row = mysqli_fetch_assoc($customers_result)) {
            ?>
                    <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php
                }
            } else {
                echo "<option value=''>No customers available</option>";
            }
            ?>
        </select>

        <label>Product (with Cost & Warehouse):</label>
        <select name="stockKey" required>
            <option value="">Select Product from Stock</option> <?php
            // Rewind stockQuery result set in case it was already iterated
            if ($stockQuery_result && mysqli_num_rows($stockQuery_result) > 0) {
                mysqli_data_seek($stockQuery_result, 0); // Reset pointer
                while ($row = mysqli_fetch_assoc($stockQuery_result)) {
                    // Corrected value to include all composite key parts
                    $value = "{$row['inventory_id']}_{$row['product_id']}_{$row['supplier_id']}_{$row['costPrice']}";
                    $label = "{$row['productName']} | ₹{$row['costPrice']} | Stock: {$row['stockLevel']} | W: {$row['warehouseLocation']} | S: {$row['supplierName']}";
            ?>
                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php
                }
            } else {
                echo "<option value=''>No stock available</option>";
            }
            ?>
        </select>

        <label>Quantity:</label>
        <input type="number" name="quantity" min="1" required>

        <label>Delivery Required?</label>
        <select name="deliveryRequired" onchange="togglePartner(this.value)" required>
            <option value="No">No</option>
            <option value="Yes">Yes</option>
        </select>

        <div id="deliveryPartnerField" style="display: none;">
            <label>Delivery Partner:</label>
            <select name="deliveryPartner_id"> <option value="">Select Delivery Partner</option> <?php
                // Rewind partners result set
                if ($partners_result && mysqli_num_rows($partners_result) > 0) {
                     mysqli_data_seek($partners_result, 0); // Reset pointer
                    while ($row = mysqli_fetch_assoc($partners_result)) {
                ?>
                        <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php
                    }
                } else {
                    echo "<option value=''>No delivery partners available</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" name="submit">Submit Sale</button>
    </form>

    <a href="../dashboard.php">Back to Dashboard</a>
</div>

<script>
function togglePartner(value) {
    document.getElementById('deliveryPartnerField').style.display = (value === "Yes") ? "block" : "none";
}
</script>
</body>
</html>