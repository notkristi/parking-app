<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

require_once '../db.php';
require_once '../includes/notification_system.php';

$userId = $_SESSION['user_id'];

try {
    // Mark all notifications as read
    $result = markAllNotificationsAsRead($conn, $userId);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'All notifications marked as read' : 'Failed to mark notifications as read'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 