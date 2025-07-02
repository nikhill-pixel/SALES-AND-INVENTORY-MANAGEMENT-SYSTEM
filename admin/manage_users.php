<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("admin");

$result = mysqli_query($conn, "SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/tables.css">
</head>
<body>

<h2>👥 User Management</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>ContactInfo</th>
            <th style="width: 180px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= htmlspecialchars($row["id"]) ?></td>
                <td><?= htmlspecialchars($row["username"]) ?></td>
                <td><?= htmlspecialchars($row["email"]) ?></td>
                <td><?= htmlspecialchars($row["role"]) ?></td>
                <td><?= htmlspecialchars($row["contactInfo"]) ?></td>
                <td>
                    <a href="edit_user.php?id=<?= urlencode($row["id"]) ?>" 
                       style="color:#1e3c72; text-decoration:underline; display:block; margin-bottom:5px;">
                       Edit
                    </a>
                    <a href="delete_user.php?id=<?= urlencode($row["id"]) ?>" 
                       onclick="return confirm('Are you sure you want to delete this user?');"
                       style="color:#c0392b; text-decoration:underline; display:block;">
                       Delete
                    </a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<a href="../dashboard.php">← Back to Dashboard</a>

</body>
</html>
