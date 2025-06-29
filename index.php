<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION["user"] = $user;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Admin Panel</title>
    <link rel="stylesheet" href="assets/index.css">
</head>
<body>

<div class="login-container">
    <h2>🔐 Admin Login</h2>
    <form method="post">
        <label for="email">Email</label>
        <input type="text" name="email" id="email" required placeholder="Enter email">

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required placeholder="Enter password">

        <button type="submit">Login</button>
        
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
