<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Sales Manager");

// Fetch customers
$customers = mysqli_query($conn, "SELECT customerId, name FROM customers");

// Fetch all available stock entries with product & warehouse info
$stockQuery = mysqli_query($conn, "
    SELECT 
        c.inventoryId, 
        c.productId, 
        c.costPrice, 
        c.stockLevel, 
        p.name AS productName, 
        i.warehouseLocation
    FROM contains c
    JOIN products p ON c.productId = p.productId
    JOIN inventory i ON c.inventoryId = i.inventoryId
    WHERE c.stockLevel > 0
    ORDER BY p.name ASC, c.costPrice ASC
");

// Fetch delivery partners
$partners = mysqli_query($conn, "SELECT deliveryPartnerId, name FROM deliverypartner");

if (isset($_POST['submit'])) {
    $customerId = intval($_POST['customerId']);
    $quantity = intval($_POST['quantity']);
    $deliveryRequired = $_POST['deliveryRequired'];
    $deliveryPartnerId = $_POST['deliveryPartnerId'] ?? null;

    list($inventoryId, $productId, $costPrice) = explode('_', $_POST['stockKey']);
    $costPrice = floatval($costPrice);
    $inventoryId = intval($inventoryId);
    $productId = intval($productId);

    $stockCheck = mysqli_query($conn, "
        SELECT stockLevel 
        FROM contains 
        WHERE inventoryId = $inventoryId 
          AND productId = $productId 
          AND costPrice = $costPrice
    ");
    $stockRow = mysqli_fetch_assoc($stockCheck);
    $stockLevel = $stockRow['stockLevel'];

    if ($stockLevel < $quantity) {
        $msg = "<p style='color:red;'>Error: Not enough stock available.</p>";
    } else {
        mysqli_query($conn, "
            UPDATE contains 
            SET stockLevel = stockLevel - $quantity 
            WHERE inventoryId = $inventoryId 
              AND productId = $productId 
              AND costPrice = $costPrice
        ");

        $priceRes = mysqli_query($conn, "SELECT price FROM products WHERE productId = $productId");
        $price = mysqli_fetch_assoc($priceRes)['price'];
        $total = $price * $quantity;
        $profit = ($price - $costPrice) * $quantity;

        mysqli_query($conn, "
            INSERT INTO orders (customerId, orderDate, totalPrice, profit, orderStatus) 
            VALUES ($customerId, CURDATE(), $total, $profit, 'Pending')
        ");
        $orderId = mysqli_insert_id($conn);

        mysqli_query($conn, "
            INSERT INTO ordered (orderId, productId, quantity) 
            VALUES ($orderId, $productId, $quantity)
        ");

        mysqli_query($conn, "
            INSERT INTO payments (paymentDate, paymentStatus, amount, name, contactInfo)
            VALUES (NULL, 'Pending', $total,
                (SELECT name FROM customers WHERE customerId = $customerId),
                (SELECT contactInfo FROM customers WHERE customerId = $customerId)
            )
        ");
        $paymentId = mysqli_insert_id($conn);

        mysqli_query($conn, "
            UPDATE orders SET paymentId = $paymentId WHERE orderId = $orderId
        ");

        if ($deliveryRequired === "Yes" && $deliveryPartnerId) {
            mysqli_query($conn, "
                INSERT INTO transportation (deliveryStatus, deliveryDate, deliveryPartnerId)
                VALUES ('Processing', NULL, $deliveryPartnerId)
            ");
            $transportationId = mysqli_insert_id($conn);

            mysqli_query($conn, "
                UPDATE orders SET transportationId = $transportationId WHERE orderId = $orderId
            ");
        }

        $msg = "<p style='color:green;'>Sale successfully created! Profit earned: ₹" . number_format($profit, 2) . "</p>";
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
        <select name="customerId" required>
            <?php while ($row = mysqli_fetch_assoc($customers)) { ?>
                <option value="<?= $row['customerId'] ?>"><?= $row['name'] ?></option>
            <?php } ?>
        </select>

        <label>Product (with Cost & Warehouse):</label>
        <select name="stockKey" required>
            <?php while ($row = mysqli_fetch_assoc($stockQuery)) {
                $value = "{$row['inventoryId']}_{$row['productId']}_{$row['costPrice']}";
                $label = "{$row['productName']} | ₹{$row['costPrice']} | Stock: {$row['stockLevel']} | {$row['warehouseLocation']}";
            ?>
                <option value="<?= $value ?>"><?= $label ?></option>
            <?php } ?>
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
            <select name="deliveryPartnerId">
                <?php while ($row = mysqli_fetch_assoc($partners)) { ?>
                    <option value="<?= $row['deliveryPartnerId'] ?>"><?= $row['name'] ?></option>
                <?php } ?>
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
