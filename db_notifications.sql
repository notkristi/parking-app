-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `Type` varchar(50) NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `SentAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ReadAt` datetime DEFAULT NULL,
  PRIMARY KEY (`NotificationID`),
  KEY `notifications_user_idx` (`UserID`),
  KEY `notifications_booking_idx` (`BookingID`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add NotificationPreferences field to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `NotificationPreferences` JSON DEFAULT NULL COMMENT 'JSON object with notification preferences';

-- Sample notification preferences: {"email": true, "sms": false, "in_app": true}
-- Example update:
-- UPDATE users SET NotificationPreferences = '{"email": true, "sms": true, "in_app": true}' WHERE UserID = 1; 