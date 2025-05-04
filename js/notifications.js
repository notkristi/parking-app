/**
 * Rodai Parking Notifications System
 * Handles real-time notifications functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const bellButton = document.getElementById('notificationsBell');
    const notificationsMenu = document.getElementById('notificationsMenu');
    const notificationsBody = document.getElementById('notificationsBody');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    
    // Toggle notifications dropdown
    if (bellButton) {
        bellButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.notifications-dropdown');
            dropdown.classList.toggle('active');
            
            // If opening the dropdown, fetch fresh notifications
            if (dropdown.classList.contains('active')) {
                fetchNotifications();
            }
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
    
    // Stop propagation on menu clicks
    if (notificationsMenu) {
        notificationsMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Mark all notifications as read
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllAsRead();
        });
    }
    
    // Set up notification dismiss buttons
    setupDismissButtons();
    
    // Initial fetch of notifications
    fetchNotifications();
    
    // Set up auto-refresh for notifications (every 60 seconds)
    setInterval(fetchNotifications, 60000);
    
    /**
     * Fetch notifications from the server
     */
    function fetchNotifications() {
        fetch('api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.count);
                    
                    // Only update the UI if dropdown is open
                    const dropdown = document.querySelector('.notifications-dropdown');
                    if (dropdown && dropdown.classList.contains('active')) {
                        updateNotificationsUI(data.notifications);
                    }
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }
    
    /**
     * Update notifications UI with fetched data
     */
    function updateNotificationsUI(notifications) {
        if (!notificationsBody) return;
        
        if (notifications.length === 0) {
            notificationsBody.innerHTML = `
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>No new notifications</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        
        notifications.forEach(notification => {
            let iconClass = 'fa-info-circle';
            
            // Set icon based on notification type
            switch (notification.Type) {
                case 'reservation_start':
                    iconClass = 'fa-play-circle';
                    break;
                case 'reservation_end':
                    iconClass = 'fa-stop-circle';
                    break;
                case 'booking_confirmation':
                    iconClass = 'fa-check-circle';
                    break;
            }
            
            html += `
                <div class="notification-item" data-id="${notification.NotificationID}" data-type="${notification.Type}">
                    <div class="notification-icon">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-message">${notification.Message}</p>
                        <span class="notification-time">${timeAgo(notification.SentAt)}</span>
                    </div>
                    <button class="notification-dismiss" data-id="${notification.NotificationID}" aria-label="Dismiss">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        });
        
        notificationsBody.innerHTML = html;
        setupDismissButtons();
    }
    
    /**
     * Update notification badge count
     */
    function updateNotificationBadge(count) {
        const bellIcon = bellButton.querySelector('i');
        const existingBadge = bellButton.querySelector('.notifications-badge');
        
        // Update bell color
        if (count > 0) {
            bellIcon.classList.add('text-primary');
            bellIcon.classList.remove('text-muted');
        } else {
            bellIcon.classList.remove('text-primary');
            bellIcon.classList.add('text-muted');
        }
        
        // Update or remove badge
        if (count > 0) {
            if (existingBadge) {
                existingBadge.textContent = count;
            } else {
                const badge = document.createElement('span');
                badge.className = 'notifications-badge';
                badge.textContent = count;
                bellButton.appendChild(badge);
            }
        } else if (existingBadge) {
            existingBadge.remove();
        }
        
        // Update "Mark all as read" button visibility
        if (markAllReadBtn) {
            const markAllReadContainer = markAllReadBtn.closest('form');
            if (markAllReadContainer) {
                markAllReadContainer.style.display = count > 0 ? 'block' : 'none';
            }
        }
    }
    
    /**
     * Mark a single notification as read
     */
    function markAsRead(notificationId) {
        fetch('api/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the notification from UI
                const notification = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (notification) {
                    notification.style.opacity = '0';
                    notification.style.height = '0';
                    notification.style.padding = '0';
                    notification.style.margin = '0';
                    notification.style.overflow = 'hidden';
                    
                    setTimeout(() => {
                        notification.remove();
                        fetchNotifications(); // Refresh notifications after removal
                    }, 300);
                }
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }
    
    /**
     * Mark all notifications as read
     */
    function markAllAsRead() {
        fetch('api/mark_all_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear all notifications from UI
                if (notificationsBody) {
                    notificationsBody.innerHTML = `
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>No new notifications</p>
                        </div>
                    `;
                }
                
                // Update badge
                updateNotificationBadge(0);
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }
    
    /**
     * Set up dismiss buttons for notifications
     */
    function setupDismissButtons() {
        document.querySelectorAll('.notification-dismiss').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const notificationId = this.getAttribute('data-id');
                markAsRead(notificationId);
            });
        });
        
        // Make notification items clickable
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                markAsRead(notificationId);
                
                // If it's related to a booking, navigate to bookings page
                if (this.hasAttribute('data-booking-id')) {
                    const bookingId = this.getAttribute('data-booking-id');
                    window.location.href = `viewbookings.php?highlight=${bookingId}`;
                }
            });
        });
    }
    
    /**
     * Format time ago for notifications
     */
    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) {
            return "Just now";
        }
        
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) {
            return minutes + (minutes === 1 ? " minute ago" : " minutes ago");
        }
        
        const hours = Math.floor(minutes / 60);
        if (hours < 24) {
            return hours + (hours === 1 ? " hour ago" : " hours ago");
        }
        
        const days = Math.floor(hours / 24);
        if (days < 30) {
            return days + (days === 1 ? " day ago" : " days ago");
        }
        
        const months = Math.floor(days / 30);
        if (months < 12) {
            return months + (months === 1 ? " month ago" : " months ago");
        }
        
        const years = Math.floor(months / 12);
        return years + (years === 1 ? " year ago" : " years ago");
    }
}); 