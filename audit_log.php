<?php
/**
 * Audit logging helper.
 *
 * Records who did what, to which resource, from which IP address, and when.
 * Logging must never break the action being logged, so failures are swallowed
 * and reported through the return value only.
 *
 * @param mysqli      $conn    Database connection
 * @param string      $action  Short action name, e.g. "User Login"
 * @param string|null $target  The resource acted on, e.g. "Order #12"
 * @param string|null $details Extra context, e.g. "Pending -> Preparing"
 * @param int|null    $userId  Acting user. Defaults to the logged-in user in the session.
 */
if (!function_exists('createAuditLog')) {
    function createAuditLog(
        mysqli $conn,
        string $action,
        ?string $target = null,
        ?string $details = null,
        ?int $userId = null
    ): bool {
        try {
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = (int) $_SESSION['user_id'];
            }
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

            $stmt = $conn->prepare("
                INSERT INTO audit_logs
                (user_id, action_performed, target_resource, ip_address, details)
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("issss", $userId, $action, $target, $ipAddress, $details);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Audit log failed: ' . $e->getMessage());
            return false;
        }
    }
}
