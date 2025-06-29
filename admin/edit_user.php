<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Admin");

$userId = $_GET['id'] ?? null;
if (!$userId) {
    die("Invalid user ID.");
}

// Fetch user details
$userQuery = mysqli_query($conn, "SELECT * FROM users WHERE userId = $userId");
$user = mysqli_fetch_assoc($userQuery);

if (!$user) {
    die("User not found.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $role = $_POST['role'];
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contactInfo = mysqli_real_escape_string($conn, $_POST['contactInfo']);

    // Update password only if provided
    $passwordUpdate = '';
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $passwordUpdate = ", password = '$password'";
    }

    $update = mysqli_query($conn, "
        UPDATE users 
        SET name = '$name', role = '$role', email = '$email', contactInfo = '$contactInfo' $passwordUpdate
        WHERE userId = $userId
    ");

    $msg = $update ? "✅ User updated successfully!" : "❌ Error updating user.";
    // Refresh user info
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE userId = $userId"));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
<div class="container">
    <h2>Edit User</h2>

    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

    <form method="POST">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
            <option value="Sales Manager" <?= $user['role'] === 'Sales Manager' ? 'selected' : '' ?>>Sales Manager</option>
            <option value="Inventory Manager" <?= $user['role'] === 'Inventory Manager' ? 'selected' : '' ?>>Inventory Manager</option>
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
