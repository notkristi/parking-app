<?php
// Include the notification system if not already included
if (!function_exists('getUnreadNotifications')) {
    require_once __DIR__ . '/notification_system.php';
}

// Get unread notifications if user is logged in
$unreadNotifications = [];
$notificationCount = 0;

if (isset($_SESSION['user_id'])) {
    $unreadNotifications = getUnreadNotifications($conn, $_SESSION['user_id']);
    $notificationCount = count($unreadNotifications);
}

// Get notification icon color based on count
$notificationIconClass = $notificationCount > 0 ? 'text-primary' : 'text-muted';
?>

<!-- Notifications Dropdown -->
<div class="notifications-dropdown">
    <button class="notifications-bell" id="notificationsBell" aria-label="Notifications">
        <i class="fas fa-bell <?php echo $notificationIconClass; ?>"></i>
        <?php if ($notificationCount > 0): ?>
            <span class="notifications-badge"><?php echo $notificationCount; ?></span>
        <?php endif; ?>
    </button>
    
    <div class="notifications-menu" id="notificationsMenu">
        <div class="notifications-header">
            <h3>Notifications</h3>
            <?php if ($notificationCount > 0): ?>
                <form action="api/mark_notifications_read.php" method="POST" class="mark-all-read-form">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                    <button type="button" class="mark-all-read-btn" id="markAllReadBtn">Mark all as read</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="notifications-body" id="notificationsBody">
            <?php if (empty($unreadNotifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>No new notifications</p>
                </div>
            <?php else: ?>
                <?php foreach ($unreadNotifications as $notification): ?>
                    <div class="notification-item" data-id="<?php echo $notification['NotificationID']; ?>">
                        <div class="notification-icon">
                            <?php if ($notification['Type'] == 'reservation_start'): ?>
                                <i class="fas fa-play-circle"></i>
                            <?php elseif ($notification['Type'] == 'reservation_end'): ?>
                                <i class="fas fa-stop-circle"></i>
                            <?php elseif ($notification['Type'] == 'booking_confirmation'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-info-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <p class="notification-message"><?php echo htmlspecialchars($notification['Message']); ?></p>
                            <span class="notification-time"><?php echo timeAgo($notification['SentAt']); ?></span>
                        </div>
                        <button class="notification-dismiss" data-id="<?php echo $notification['NotificationID']; ?>" aria-label="Dismiss">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="notifications-footer">
            <a href="notifications.php" class="view-all-link">View All</a>
        </div>
    </div>
</div>

<?php
// Helper function to format time ago
function timeAgo($date) {
    $timestamp = strtotime($date);
    $strTime = array("second", "minute", "hour", "day", "month", "year");
    $length = array("60", "60", "24", "30", "12", "10");

    $currentTime = time();
    if ($currentTime >= $timestamp) {
        $diff = $currentTime - $timestamp;
        
        if ($diff < 60) {
            return "Just now";
        }
        
        for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
            $diff = $diff / $length[$i];
        }
        
        $diff = round($diff);
        return $diff . " " . $strTime[$i] . ($diff > 1 ? "s" : "") . " ago";
    }
    
    return date("M j, Y", $timestamp);
}
?> 