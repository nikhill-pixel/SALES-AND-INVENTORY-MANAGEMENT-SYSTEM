<?php
function checkAccess($role_required) {
    session_start();
    if (!isset($_SESSION["user"])) {
        header("Location: ../index.php");
        exit();
    }

    $user = $_SESSION["user"];
    if ($user["role"] !== $role_required) {
        echo "Access Denied: You do not have permission to access this page.";
        exit();
    }
}
?>
