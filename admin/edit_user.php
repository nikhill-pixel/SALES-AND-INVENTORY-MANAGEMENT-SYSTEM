<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("admin"); // Assuming this correctly authenticates and authorizes

$userId = (int)($_GET['id'] ?? 0); // Cast to int for safety, default to 0
if (!$userId) {
    die("Invalid user ID provided. Please go back to user list.");
}

// Fetch user details - ensure 'contactInfo' is selected if it's in your DB
$userQuery = mysqli_query($conn, "SELECT id, username, email, role, contactInfo FROM users WHERE id = $userId"); 
$user = mysqli_fetch_assoc($userQuery);

if (!$user) {
    die("User not found.");
}

$msg = ''; // Initialize message variable

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Variable for username (consistent name with column)
    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $role = mysqli_real_escape_string($conn, $_POST['role']); 
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Ensure contactInfo is retrieved, even if empty or not set (though 'required' implies it should be)
    $contactInfo = mysqli_real_escape_string($conn, $_POST['contactInfo'] ?? ''); 

    // Update password only if provided
    $passwordUpdate = '';
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $passwordUpdate = ", password = '$password'";
    }

    // Construct the UPDATE query
    // Ensure column names EXACTLY match your database (username, role, email, contactInfo, password)
    $updateSql = "
        UPDATE users 
        SET 
            username = '$username', 
            role = '$role', 
            email = '$email', 
            contactInfo = '$contactInfo'
            $passwordUpdate
        WHERE id = $userId
    ";

    $update = mysqli_query($conn, $updateSql);

    if ($update) {
        $msg = "✅ User updated successfully!";
        // Re-fetch user data to display the most current information in the form fields
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, username, email, role, contactInfo FROM users WHERE id = $userId"));
    } else {
        // THIS IS THE CRITICAL LINE FOR DEBUGGING
        $msg = "❌ Error updating user: " . mysqli_error($conn); 
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
<div class="container">
    <h2>Edit User</h2>

    <?php 
    // Display messages, adding 'error' class if it's a failure message
    if (isset($msg)) {
        echo "<p class='". (strpos($msg, '❌') !== false ? 'error' : '') ."'>$msg</p>"; 
    }
    ?>

    <form method="POST">
        <label>Username:</label> 
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="sales_manager" <?= $user['role'] === 'sales_manager' ? 'selected' : '' ?>>Sales Manager</option>
            <option value="inventory_manager" <?= $user['role'] === 'inventory_manager' ? 'selected' : '' ?>>Inventory Manager</option>
        </select>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Contact Number:</label>
        <input type="text" name="contactInfo" pattern="\d{10}" title="10-digit number" value="<?= htmlspecialchars($user['contactInfo']) ?>" required>

        <label>New Password (leave blank to keep current):</label>
        <input type="password" name="password">

        <button type="submit">Update User</button>
    </form>

    <a href="manage_users.php">← Back to User List</a>
</div>
</body>
</html>