<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("admin");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['username']);
    $role = $_POST['role'];
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contactInfo = mysqli_real_escape_string($conn, $_POST['contactInfo']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $exists = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($exists) > 0) {
        $msg = "⚠️ Email already exists. Choose a different one.";
    } else {
        $insert = mysqli_query($conn, "
            INSERT INTO users (username, role, email, contactInfo, password)
            VALUES ('$name', '$role', '$email', '$contactInfo', '$password')
        ");
        $msg = $insert ? "✅ New user created successfully!" : "❌ Error creating user.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New User</title>
    <link rel="stylesheet" href="../assets/forms.css">
</head>
<body>
<div class="container">
    <h2>Add New User</h2>

    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

    <form method="POST">
        <label>Name:</label>
        <input type="text" name="username" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="">--Select Role--</option>
            <option value="admin">Admin</option>
            <option value="sales Manager">Sales Manager</option>
            <option value="inventory Manager">Inventory Manager</option>
        </select>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Contact Number:</label>
        <input type="text" name="contactInfo" pattern="\d{10}" title="10-digit number" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Create User</button>
    </form>

    <a href="../dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>
