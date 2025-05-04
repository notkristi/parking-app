<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Set page title
$page_title = 'Notifications';

require_once 'db.php';
require_once 'includes/notification_system.php';

$userId = $_SESSION['user_id'];
$success = [];
$errors = [];

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $result = markAllNotificationsAsRead($conn, $userId);
    if ($result) {
        $success[] = "All notifications marked as read.";
    } else {
        $errors[] = "Failed to mark notifications as read.";
    }
}

// Handle marking a single notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $notificationId = (int)$_POST['mark_read_id'];
    $result = markNotificationAsRead($conn, $notificationId);
    if ($result) {
        $success[] = "Notification marked as read.";
    } else {
        $errors[] = "Failed to mark notification as read.";
    }
}

// Get all notifications, including read ones
$allNotifications = getAllNotifications($conn, $userId, 100);

// Count unread notifications
$unreadCount = 0;
foreach ($allNotifications as $notification) {
    if (!$notification['IsRead']) {
        $unreadCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Rodai Parking</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/notifications.css">
    
    <style>
        .notifications-page {
            padding: 100px 0 50px;
            background-color: #f5f7fa;
        }
        
        .notifications-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .notifications-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--light-purple);
        }
        
        .notifications-header h1 {
            font-size: 24px;
            margin-bottom: 0;
            color: var(--primary);
        }
        
        .notifications-filter {
            display: flex;
            gap: 10px;
            padding: 15px 25px;
            border-bottom: 1px solid #e9ecef;
            background-color: #f8f9fa;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            background-color: #fff;
            color: var(--gray-dark);
            border: 1px solid #dee2e6;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .notifications-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 20px 25px;
            display: flex;
            align-items: flex-start;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s ease;
        }
        
        .notification-item:hover {
            background-color: var(--light-purple);
        }
        
        .notification-item.read {
            opacity: 0.7;
        }
        
        .notification-item.read .notification-icon {
            background-color: #f8f9fa;
            color: var(--gray);
        }
        
        .notification-icon {
            margin-right: 15px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--light-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            margin: 0 0 5px 0;
            font-size: 15px;
            color: var(--dark);
            line-height: 1.5;
        }
        
        .notification-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            color: var(--gray);
        }
        
        .notification-time {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notification-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .notification-status.unread {
            background-color: rgba(122, 31, 160, 0.1);
            color: var(--primary);
        }
        
        .notification-status.read {
            background-color: rgba(127, 140, 141, 0.1);
            color: var(--gray-dark);
        }
        
        .notifications-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: var(--gray);
            text-align: center;
        }
        
        .notifications-empty i {
            font-size: 50px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: var(--gray-light);
        }
        
        .notifications-empty h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--gray-dark);
        }
        
        .notifications-empty p {
            font-size: 14px;
            max-width: 300px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="notifications-page">
        <div class="container">
            <div class="dashboard-header mb-4">
                <div>
                    <h1 class="dashboard-title">Notifications</h1>
                    <p class="dashboard-subtitle">View and manage your notifications</p>
                </div>
                <div class="dashboard-date">
                    <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php foreach ($success as $message): ?>
                        <p class="mb-0"><?php echo $message; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-0"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="notifications-container">
                <div class="notifications-header">
                    <h2>Your Notifications</h2>
                    <?php if ($unreadCount > 0): ?>
                        <form method="post">
                            <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-check-double"></i> Mark All as Read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="notifications-filter">
                    <button class="filter-btn active" data-filter="all">All Notifications</button>
                    <button class="filter-btn" data-filter="unread">Unread</button>
                    <button class="filter-btn" data-filter="read">Read</button>
                </div>
                
                <div class="notifications-list">
                    <?php if (empty($allNotifications)): ?>
                        <div class="notifications-empty">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No Notifications</h3>
                            <p>You don't have any notifications yet. We'll notify you about important events related to your bookings.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allNotifications as $notification): ?>
                            <?php
                                $isRead = (bool)$notification['IsRead'];
                                $notificationClass = $isRead ? 'read' : 'unread';
                                $statusText = $isRead ? 'Read' : 'Unread';
                                
                                $iconClass = 'fa-info-circle';
                                switch ($notification['Type']) {
                                    case 'reservation_start':
                                        $iconClass = 'fa-play-circle';
                                        break;
                                    case 'reservation_end':
                                        $iconClass = 'fa-stop-circle';
                                        break;
                                    case 'booking_confirmation':
                                        $iconClass = 'fa-check-circle';
                                        break;
                                }
                            ?>
                            <div class="notification-item <?php echo $notificationClass; ?>" data-status="<?php echo $notificationClass; ?>">
                                <div class="notification-icon">
                                    <i class="fas <?php echo $iconClass; ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <p class="notification-message"><?php echo htmlspecialchars($notification['Message']); ?></p>
                                    <div class="notification-meta">
                                        <div class="notification-time">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($notification['SentAt'])); ?>
                                        </div>
                                        <span class="notification-status <?php echo $notificationClass; ?>"><?php echo $statusText; ?></span>
                                        <?php if (!$isRead): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="mark_read_id" value="<?php echo $notification['NotificationID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success ms-2">Mark as Read</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter buttons
            const filterButtons = document.querySelectorAll('.filter-btn');
            const notificationItems = document.querySelectorAll('.notification-item');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get filter value
                    const filter = this.getAttribute('data-filter');
                    
                    // Filter notifications
                    notificationItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-status') === filter) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 