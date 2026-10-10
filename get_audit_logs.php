<?php

session_start();
require 'config/database.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Only an active Administrator can view audit logs
$state = isset($_SESSION['user_id']) ? getAccountState($conn, (int) $_SESSION['user_id']) : null;
if (!$state || $state['status'] !== 'Active' || $state['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sql = "
    SELECT
        a.id,
        COALESCE(u.fullname, 'System / Guest') AS user_name,
        u.role AS user_role,
        a.action_performed,
        a.target_resource,
        a.details,
        a.ip_address,
        a.created_at
    FROM audit_logs a
    LEFT JOIN users u
        ON a.user_id = u.id
    ORDER BY a.id DESC
    LIMIT 200
";

$logs = [];
try {
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $logs[] = $row;
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Audit log table is not available. Run database/paper_alignment_update.sql.']);
    exit;
}

echo json_encode(['success' => true, 'logs' => $logs]);
