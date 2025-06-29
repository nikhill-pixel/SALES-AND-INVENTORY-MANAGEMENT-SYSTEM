<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Admin");

$userId = intval($_GET['id']);

// Optional: prevent deletion of self
if ($userId == $_SESSION['userId']) {
    die("You can't delete your own account.");
}

mysqli_query($conn, "DELETE FROM users WHERE userId = $userId");

header("Location: manage_users.php?msg=deleted");
exit();
