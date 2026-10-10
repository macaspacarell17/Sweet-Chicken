<?php
/**
 * Account-status checks.
 *
 * A session alone is not enough: the Administrator can deactivate an employee
 * account at any time, and the change must take effect immediately, even for a
 * user who is already logged in. The role is also re-read from the database so
 * a role change applies without waiting for the user to log in again.
 */

/** Returns ['role' => ..., 'status' => ...] for the user, or null if the account no longer exists. */
function getAccountState(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare("SELECT role, status FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Ends the session but leaves a one-time message for the login page. */
function endSessionWithMessage(string $message): void
{
    $_SESSION = [];
    $_SESSION['flash_error'] = $message;
}

/**
 * For page requests: if the logged-in account is deactivated or deleted,
 * end the session and send the user back to the login page.
 */
function requireActiveAccount(mysqli $conn): void
{
    if (!isset($_SESSION['user_id'])) {
        return; // each page already handles "not logged in"
    }
    $state = getAccountState($conn, (int) $_SESSION['user_id']);
    if ($state === null || $state['status'] !== 'Active') {
        endSessionWithMessage('This account has been deactivated. Please contact the administrator.');
        header('Location: loginpage.php');
        exit();
    }
    $_SESSION['role'] = $state['role'];
}
