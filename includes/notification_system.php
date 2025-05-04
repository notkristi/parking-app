<?php
/**
 * Notification System
 * Handles all types of notifications including in-app, SMS via Twilio, etc.
 */

// Include PHPMailer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Twilio API Configuration
define('TWILIO_ENABLED', false); // Set to true to enable actual SMS sending
define('TWILIO_SID', 'YOUR_TWILIO_SID_HERE');
define('TWILIO_TOKEN', 'YOUR_TWILIO_TOKEN_HERE');
define('TWILIO_PHONE', '+15550123456'); // Your Twilio phone number

/**
 * Send an email notification
 * 
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $message Email message content
 * @return array Result with success status and debug information
 */
function sendEmailNotification($toEmail, $toName, $subject, $message) {
    $mail = new PHPMailer(true);
    $debugInfo = [
        'success' => false,
        'to_email' => $toEmail,
        'to_name' => $toName,
        'subject' => $subject,
        'error' => null,
        'debug_output' => null
    ];

    try {
        // Enable debug output
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) use (&$debugInfo) {
            $debugInfo['debug_output'] .= $str . "\n";
        };

        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dulerodha@gmail.com'; // Your email
        $mail->Password   = 'lvgg abya iorv jpku'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('dulerodha@gmail.com', 'Rodai Parking');
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2c3e50;">Rodai Parking Notification</h2>
                <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                    ' . $message . '
                </div>
                <p style="color: #7f8c8d; font-size: 12px;">This is an automated message from Rodai Parking. Please do not reply to this email.</p>
            </div>
        ';

        $mail->send();
        $debugInfo['success'] = true;
        
        // Log success
        error_log("Email sent successfully to {$toEmail} with subject: {$subject}");
        
    } catch (Exception $e) {
        $debugInfo['error'] = $mail->ErrorInfo;
        error_log("Email sending failed: " . $mail->ErrorInfo);
    }

    return $debugInfo;
}

/**
 * Create an in-app notification and send email if enabled
 * 
 * @param PDO $conn Database connection
 * @param int $userId User ID
 * @param int $bookingId Booking ID
 * @param string $type Notification type
 * @param string $message Notification message
 * @return array Result with success status and debug information
 */
function createNotification($conn, $userId, $bookingId, $type, $message) {
    $result = [
        'in_app_success' => false,
        'email_success' => false,
        'email_debug' => null,
        'error' => null
    ];

    try {
        // Get user's email preferences
        $prefQuery = $conn->prepare("
            SELECT u.Email, u.FirstName, u.NotificationPreferences
            FROM users u
            WHERE u.UserID = :userId
        ");
        $prefQuery->bindParam(':userId', $userId, PDO::PARAM_INT);
        $prefQuery->execute();
        $userData = $prefQuery->fetch(PDO::FETCH_ASSOC);

        // Create in-app notification
        $stmt = $conn->prepare("
            INSERT INTO notifications (UserID, BookingID, Type, Message)
            VALUES (:userId, :bookingId, :type, :message)
        ");
        
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        
        $result['in_app_success'] = $stmt->execute();

        // Send email if enabled in preferences
        if ($result['in_app_success'] && $userData && !empty($userData['Email'])) {
            $preferences = json_decode($userData['NotificationPreferences'] ?? '{}', true);
            
            if (isset($preferences['email']) && $preferences['email']) {
                $subject = "Rodai Parking - " . ucwords(str_replace('_', ' ', $type));
                $emailResult = sendEmailNotification(
                    $userData['Email'],
                    $userData['FirstName'] ?? 'Customer',
                    $subject,
                    $message
                );
                
                $result['email_success'] = $emailResult['success'];
                $result['email_debug'] = $emailResult;
            }
        }
        
    } catch (PDOException $e) {
        $result['error'] = $e->getMessage();
        error_log("Error creating notification: " . $e->getMessage());
    }

    return $result;
}

/**
 * Get unread notifications for a user
 * 
 * @param PDO $conn Database connection
 * @param int $userId User ID
 * @return array Unread notifications
 */
function getUnreadNotifications($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT n.*, b.SpotID, ps.SpotNumber 
            FROM notifications n
            LEFT JOIN bookings b ON n.BookingID = b.BookingID
            LEFT JOIN parkingspots ps ON b.SpotID = ps.SpotID
            WHERE n.UserID = :userId AND n.IsRead = 0
            ORDER BY n.SentAt DESC
        ");
        
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting unread notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all notifications for a user
 * 
 * @param PDO $conn Database connection
 * @param int $userId User ID
 * @param int $limit Maximum number of notifications to retrieve
 * @return array Notifications
 */
function getAllNotifications($conn, $userId, $limit = 50) {
    try {
        $stmt = $conn->prepare("
            SELECT n.*, b.SpotID, ps.SpotNumber, b.StartTime, b.EndTime
            FROM notifications n
            LEFT JOIN bookings b ON n.BookingID = b.BookingID
            LEFT JOIN parkingspots ps ON b.SpotID = ps.SpotID
            WHERE n.UserID = :userId
            ORDER BY n.SentAt DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting all notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark a notification as read
 * 
 * @param PDO $conn Database connection
 * @param int $notificationId Notification ID
 * @return bool Success status
 */
function markNotificationAsRead($conn, $notificationId) {
    try {
        $stmt = $conn->prepare("
            UPDATE notifications
            SET IsRead = 1, ReadAt = NOW()
            WHERE NotificationID = :notificationId
        ");
        
        $stmt->bindParam(':notificationId', $notificationId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 * 
 * @param PDO $conn Database connection
 * @param int $userId User ID
 * @return bool Success status
 */
function markAllNotificationsAsRead($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            UPDATE notifications
            SET IsRead = 1, ReadAt = NOW()
            WHERE UserID = :userId AND IsRead = 0
        ");
        
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Send an SMS notification via Twilio API
 * 
 * @param string $phoneNumber Recipient phone number
 * @param string $message SMS message content
 * @return array Result with success status and details
 */
function sendSMS($phoneNumber, $message) {
    // If Twilio is disabled, simulate success for testing
    if (!TWILIO_ENABLED) {
        return [
            'success' => true,
            'simulated' => true,
            'phone' => $phoneNumber,
            'message' => $message
        ];
    }
    
    // Validate phone number format
    if (!preg_match('/^\+\d{10,15}$/', $phoneNumber)) {
        return [
            'success' => false,
            'error' => 'Invalid phone number format'
        ];
    }
    
    // Set up Twilio API request
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';
    
    $data = [
        'From' => TWILIO_PHONE,
        'To' => $phoneNumber,
        'Body' => $message
    ];
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ':' . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute the request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Process response
    if ($statusCode >= 200 && $statusCode < 300 && !$error) {
        $responseData = json_decode($response, true);
        return [
            'success' => true,
            'sid' => $responseData['sid'],
            'status' => $responseData['status']
        ];
    } else {
        return [
            'success' => false,
            'status_code' => $statusCode,
            'error' => $error ?: 'Failed to send SMS'
        ];
    }
}

/**
 * Format phone number to E.164 international format
 * 
 * @param string $phoneNumber Input phone number
 * @return string|false Formatted phone number or false if invalid
 */
function formatPhoneNumber($phoneNumber) {
    // Remove all non-digit characters
    $digits = preg_replace('/\D/', '', $phoneNumber);
    
    // Handle different formats
    if (strlen($digits) == 10) {
        // US 10-digit number
        return '+1' . $digits;
    } elseif (strlen($digits) == 11 && substr($digits, 0, 1) == '1') {
        // US 11-digit number with country code
        return '+' . $digits;
    } elseif (substr($phoneNumber, 0, 1) == '+' && strlen($digits) >= 11) {
        // International format
        return '+' . $digits;
    }
    
    return false;
}

/**
 * Send reservation start notification (in-app and SMS)
 * 
 * @param PDO $conn Database connection
 * @param array $booking Booking details
 * @param bool $sendSMS Whether to send SMS notification
 * @return array Result with success status
 */
function sendReservationStartNotification($conn, $booking, $sendSMS = false) {
    $userId = $booking['UserID'];
    $bookingId = $booking['BookingID'];
    $spotNumber = $booking['SpotNumber'];
    $message = "Your parking reservation for spot {$spotNumber} has started. Your spot is now available for use.";
    
    // Create in-app notification
    $notificationResult = createNotification($conn, $userId, $bookingId, 'reservation_start', $message);
    
    $result = [
        'in_app_success' => $notificationResult['in_app_success'],
        'sms_success' => false
    ];
    
    // Send SMS if enabled and phone number exists
    if ($sendSMS && !empty($booking['Phone'])) {
        $phoneNumber = formatPhoneNumber($booking['Phone']);
        
        if ($phoneNumber) {
            $smsResult = sendSMS($phoneNumber, $message);
            $result['sms_success'] = $smsResult['success'];
            $result['sms_details'] = $smsResult;
        } else {
            $result['sms_error'] = 'Invalid phone number format';
        }
    }
    
    return $result;
}

/**
 * Send reservation ending notification (in-app and SMS)
 * 
 * @param PDO $conn Database connection
 * @param array $booking Booking details
 * @param int $minutesLeft Minutes until reservation ends
 * @param bool $sendSMS Whether to send SMS notification
 * @return array Result with success status
 */
function sendReservationEndingNotification($conn, $booking, $minutesLeft = 15, $sendSMS = false) {
    $userId = $booking['UserID'];
    $bookingId = $booking['BookingID'];
    $spotNumber = $booking['SpotNumber'];
    $message = "Your parking reservation for spot {$spotNumber} will end in {$minutesLeft} minutes. Please ensure you vacate the spot to avoid additional charges.";
    
    // Create in-app notification
    $notificationResult = createNotification($conn, $userId, $bookingId, 'reservation_end', $message);
    
    $result = [
        'in_app_success' => $notificationResult['in_app_success'],
        'sms_success' => false
    ];
    
    // Send SMS if enabled and phone number exists
    if ($sendSMS && !empty($booking['Phone'])) {
        $phoneNumber = formatPhoneNumber($booking['Phone']);
        
        if ($phoneNumber) {
            $smsResult = sendSMS($phoneNumber, $message);
            $result['sms_success'] = $smsResult['success'];
            $result['sms_details'] = $smsResult;
        } else {
            $result['sms_error'] = 'Invalid phone number format';
        }
    }
    
    return $result;
}

/**
 * Send booking confirmation notification (in-app and SMS)
 * 
 * @param PDO $conn Database connection
 * @param array $booking Booking details
 * @param bool $sendSMS Whether to send SMS notification
 * @return array Result with success status
 */
function sendBookingConfirmationNotification($conn, $booking, $sendSMS = false) {
    $userId = $booking['UserID'];
    $bookingId = $booking['BookingID'];
    $spotNumber = $booking['SpotNumber'];
    $startDate = date('D, M j', strtotime($booking['StartTime']));
    $startTime = date('g:i A', strtotime($booking['StartTime']));
    $endTime = date('g:i A', strtotime($booking['EndTime']));
    
    $message = "Your parking reservation is confirmed! Spot: {$spotNumber}, Date: {$startDate}, Time: {$startTime}-{$endTime}. Booking ID: {$bookingId}.";
    
    // Create in-app notification
    $notificationResult = createNotification($conn, $userId, $bookingId, 'booking_confirmation', $message);
    
    $result = [
        'in_app_success' => $notificationResult['in_app_success'],
        'sms_success' => false
    ];
    
    // Send SMS if enabled and phone number exists
    if ($sendSMS && !empty($booking['Phone'])) {
        $phoneNumber = formatPhoneNumber($booking['Phone']);
        
        if ($phoneNumber) {
            $smsResult = sendSMS($phoneNumber, $message);
            $result['sms_success'] = $smsResult['success'];
            $result['sms_details'] = $smsResult;
        } else {
            $result['sms_error'] = 'Invalid phone number format';
        }
    }
    
    return $result;
}

/**
 * Check for upcoming reservations and send notifications
 * 
 * @param PDO $conn Database connection
 * @return array Results of notification sending
 */
function checkAndSendReservationNotifications($conn) {
    try {
        // Get user notification preferences
        $prefQuery = $conn->query("
            SELECT UserID, NotificationPreferences 
            FROM users 
            WHERE NotificationPreferences IS NOT NULL
        ");
        $userPrefs = [];
        while ($row = $prefQuery->fetch(PDO::FETCH_ASSOC)) {
            $userPrefs[$row['UserID']] = json_decode($row['NotificationPreferences'], true);
        }
        
        // Get newly created bookings that haven't received confirmation notifications
        $newBookingsQuery = $conn->prepare("
            SELECT b.*, ps.SpotNumber, u.Phone
            FROM bookings b
            JOIN parkingspots ps ON b.SpotID = ps.SpotID
            JOIN users u ON b.UserID = u.UserID
            WHERE b.BookingTime >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.BookingID = b.BookingID AND n.Type = 'booking_confirmation'
            )
        ");
        $newBookingsQuery->execute();
        $newBookings = $newBookingsQuery->fetchAll(PDO::FETCH_ASSOC);
        
        // Get reservations starting soon (within 15 minutes)
        $startingSoonQuery = $conn->prepare("
            SELECT b.*, ps.SpotNumber, u.Phone
            FROM bookings b
            JOIN parkingspots ps ON b.SpotID = ps.SpotID
            JOIN users u ON b.UserID = u.UserID
            WHERE b.BookingStatus = 'Upcoming' 
            AND b.StartTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 MINUTE)
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.BookingID = b.BookingID AND n.Type = 'reservation_start'
            )
        ");
        $startingSoonQuery->execute();
        $startingSoon = $startingSoonQuery->fetchAll(PDO::FETCH_ASSOC);
        
        // Get reservations ending soon (within 15 minutes)
        $endingSoonQuery = $conn->prepare("
            SELECT b.*, ps.SpotNumber, u.Phone
            FROM bookings b
            JOIN parkingspots ps ON b.SpotID = ps.SpotID
            JOIN users u ON b.UserID = u.UserID
            WHERE b.BookingStatus = 'Active' 
            AND b.EndTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 MINUTE)
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.BookingID = b.BookingID AND n.Type = 'reservation_end'
            )
        ");
        $endingSoonQuery->execute();
        $endingSoon = $endingSoonQuery->fetchAll(PDO::FETCH_ASSOC);
        
        $confirmationsSent = 0;
        $startNotificationsSent = 0;
        $endNotificationsSent = 0;
        $smsSent = 0;
        $errors = [];
        
        // Process new bookings for confirmation notifications
        foreach ($newBookings as $booking) {
            $userId = $booking['UserID'];
            $sendSMS = isset($userPrefs[$userId]['sms']) && $userPrefs[$userId]['sms'];
            
            $result = sendBookingConfirmationNotification($conn, $booking, $sendSMS);
            
            if ($result['in_app_success']) {
                $confirmationsSent++;
            }
            
            if ($sendSMS && $result['sms_success']) {
                $smsSent++;
            } elseif ($sendSMS && !$result['sms_success']) {
                $errors[] = "Failed to send confirmation SMS for booking {$booking['BookingID']}";
            }
        }
        
        // Process starting reservations
        foreach ($startingSoon as $booking) {
            $userId = $booking['UserID'];
            $sendSMS = isset($userPrefs[$userId]['sms']) && $userPrefs[$userId]['sms'];
            
            $result = sendReservationStartNotification($conn, $booking, $sendSMS);
            
            if ($result['in_app_success']) {
                $startNotificationsSent++;
                
                // Update booking status if needed
                if (strtotime($booking['StartTime']) <= time()) {
                    $updateStmt = $conn->prepare("
                        UPDATE bookings 
                        SET BookingStatus = 'Active' 
                        WHERE BookingID = :bookingId
                    ");
                    $updateStmt->bindParam(':bookingId', $booking['BookingID'], PDO::PARAM_INT);
                    $updateStmt->execute();
                }
            }
            
            if ($sendSMS && $result['sms_success']) {
                $smsSent++;
            } elseif ($sendSMS && !$result['sms_success']) {
                $errors[] = "Failed to send start SMS for booking {$booking['BookingID']}";
            }
        }
        
        // Process ending reservations
        foreach ($endingSoon as $booking) {
            $userId = $booking['UserID'];
            $sendSMS = isset($userPrefs[$userId]['sms']) && $userPrefs[$userId]['sms'];
            
            $result = sendReservationEndingNotification($conn, $booking, 15, $sendSMS);
            
            if ($result['in_app_success']) {
                $endNotificationsSent++;
                
                // Update booking status if needed
                if (strtotime($booking['EndTime']) <= time()) {
                    $updateStmt = $conn->prepare("
                        UPDATE bookings 
                        SET BookingStatus = 'Completed' 
                        WHERE BookingID = :bookingId
                    ");
                    $updateStmt->bindParam(':bookingId', $booking['BookingID'], PDO::PARAM_INT);
                    $updateStmt->execute();
                }
            }
            
            if ($sendSMS && $result['sms_success']) {
                $smsSent++;
            } elseif ($sendSMS && !$result['sms_success']) {
                $errors[] = "Failed to send end SMS for booking {$booking['BookingID']}";
            }
        }
        
        return [
            'success' => true,
            'confirmation_notifications' => $confirmationsSent,
            'start_notifications' => $startNotificationsSent,
            'end_notifications' => $endNotificationsSent,
            'sms_sent' => $smsSent,
            'errors' => $errors
        ];
        
    } catch (PDOException $e) {
        error_log("Error checking reservations: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function sendBookingConfirmationEmail($toEmail, $toName, $booking) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dulerodha@gmail.com'; // Your email
        $mail->Password   = 'lvgg abya iorv gpku'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('dulerodha@gmail.com', 'Rodai Parking');
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Parking Booking is Confirmed!';
        $mail->Body    = "
            <h2>Booking Confirmed!</h2>
            <p>Dear {$toName},</p>
            <p>Your parking reservation is confirmed.</p>
            <ul>
                <li><strong>Spot:</strong> {$booking['SpotNumber']}</li>
                <li><strong>Date:</strong> " . date('D, M j', strtotime($booking['StartTime'])) . "</li>
                <li><strong>Time:</strong> " . date('g:i A', strtotime($booking['StartTime'])) . " - " . date('g:i A', strtotime($booking['EndTime'])) . "</li>
                <li><strong>Booking ID:</strong> {$booking['BookingID']}</li>
            </ul>
            <p>Thank you for choosing Rodai Parking!</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}
?> 