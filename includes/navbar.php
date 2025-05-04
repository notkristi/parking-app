<?php
// Get unread notification count if user is logged in
$unreadNotificationCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'includes/notification_system.php';
        $unreadNotifications = getUnreadNotifications($conn, $_SESSION['user_id']);
        $unreadNotificationCount = count($unreadNotifications);
    } catch (Exception $e) {
        error_log("Error loading notifications: " . $e->getMessage());
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <span class="fw-bold text-primary">Parking Management</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reserve.php">
                        <i class="fas fa-calendar-plus me-1"></i> Reserve Spot
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="viewbookings.php">
                        <i class="fas fa-calendar-check me-1"></i> My Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="vehicles.php">
                        <i class="fas fa-car me-1"></i> My Vehicles
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Profile button (e.g., alex) -->
                    <li class="nav-item">
                        <!-- Example profile button, adjust as needed for your UI -->
                        <a class="nav-link" href="profile.php">
                            <span class="fw-bold text-primary"><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?></span>
                        </a>
                    </li>
                    <!-- Notification bell -->
                    <li class="nav-item position-relative me-2">
                        <a class="nav-link" href="notifications.php" id="notificationBell" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadNotificationCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <!-- Logout button -->
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger text-white px-4 ms-2" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Add spacing to prevent content from being hidden under the fixed navbar -->
<div style="padding-top: 70px;"></div> 