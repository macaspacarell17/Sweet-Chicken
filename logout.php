<?php

session_start();

/* Record the logout before the session (and the user id) is destroyed */
if (isset($_SESSION['user_id'])) {
    require 'config/database.php';
    require_once 'audit_log.php';
    createAuditLog($conn, 'User Logout', $_SESSION['email'] ?? null, 'Role: ' . ($_SESSION['role'] ?? 'unknown'));
}

/* Destroy all session data */
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_unset();
session_destroy();

/* Prevent browser from caching the logout response */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* Return to login */
header("Location: loginpage.php");
exit();
?>
