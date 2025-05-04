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

// Check if notification ID is provided
if (!isset($_POST['notification_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Notification ID is required'
    ]);
    exit;
}

$notificationId = intval($_POST['notification_id']);

try {
    // Verify the notification belongs to the current user
    $checkStmt = $conn->prepare("
        SELECT UserID FROM notifications 
        WHERE NotificationID = :notificationId
    ");
    $checkStmt->bindParam(':notificationId', $notificationId, PDO::PARAM_INT);
    $checkStmt->execute();
    $notification = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$notification || $notification['UserID'] != $_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'error' => 'Notification not found or access denied'
        ]);
        exit;
    }
    
    // Mark notification as read
    $result = markNotificationAsRead($conn, $notificationId);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 