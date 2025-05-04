<?php
// Include the database connection file
require_once 'db.php';
require_once 'includes/notification_system.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$success = [];
$errors = [];

// Get user information
try {
    $userStmt = $conn->prepare("
        SELECT * FROM users 
        WHERE UserID = ?
    ");
    
    $userStmt->execute([$_SESSION['user_id']]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        $errors[] = "User information not found.";
    }
    
    // Get user's vehicles
    $vehicleStmt = $conn->prepare("
        SELECT * FROM vehicles
        WHERE UserID = ?
        ORDER BY Make, Model
    ");
    
    $vehicleStmt->execute([$_SESSION['user_id']]);
    $vehicles = $vehicleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's booking history
    $bookingStmt = $conn->prepare("
        SELECT b.*, ps.SpotNumber, v.Make, v.Model, v.Color, v.LicensePlate
        FROM bookings b
        JOIN parkingspots ps ON b.SpotID = ps.SpotID
        JOIN vehicles v ON b.VehicleID = v.VehicleID
        WHERE b.UserID = ?
        ORDER BY b.BookingTime DESC
        LIMIT 5
    ");
    
    $bookingStmt->execute([$_SESSION['user_id']]);
    $recentBookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history
    $paymentStmt = $conn->prepare("
        SELECT p.*, b.StartTime, b.EndTime
        FROM payments p
        JOIN bookings b ON p.BookingID = b.BookingID
        WHERE b.UserID = ?
        ORDER BY p.PaymentTime DESC
        LIMIT 5
    ");
    
    $paymentStmt->execute([$_SESSION['user_id']]);
    $recentPayments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Process profile update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Basic validation
    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Check if email is already in use by another user
    if (!empty($email) && $email !== $userData['Email']) {
        $emailCheckStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE Email = ? AND UserID != ?");
        $emailCheckStmt->execute([$email, $_SESSION['user_id']]);
        if ($emailCheckStmt->fetchColumn() > 0) {
            $errors[] = "Email is already in use by another account.";
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        try {
            $updateStmt = $conn->prepare("
                UPDATE users 
                SET FirstName = ?, LastName = ?, Email = ?, Phone = ? 
                WHERE UserID = ?
            ");
            
            $updateStmt->execute([$firstName, $lastName, $email, $phone, $_SESSION['user_id']]);
            
            // Update session data
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;
            
            $success[] = "Profile updated successfully!";
            
            // Refresh user data after update
            $userStmt->execute([$_SESSION['user_id']]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Process password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($currentPassword)) {
        $errors[] = "Current password is required.";
    }
    
    if (empty($newPassword)) {
        $errors[] = "New password is required.";
    } elseif (strlen($newPassword) < 8) {
        $errors[] = "New password must be at least 8 characters long.";
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = "New passwords do not match.";
    }
    
    // Verify current password
    if (!empty($currentPassword) && !empty($userData['Password'])) {
        if (!password_verify($currentPassword, $userData['Password'])) {
            $errors[] = "Current password is incorrect.";
        }
    }
    
    // If no errors, update password
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updatePasswordStmt = $conn->prepare("
                UPDATE users 
                SET Password = ? 
                WHERE UserID = ?
            ");
            
            $updatePasswordStmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            $success[] = "Password changed successfully!";
            
        } catch (PDOException $e) {
            $errors[] = "Error changing password: " . $e->getMessage();
        }
    }
}

// Process notification preferences form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $inAppNotifications = isset($_POST['in_app_notifications']) ? true : false;
    $smsNotifications = isset($_POST['sms_notifications']) ? true : false;
    $emailNotifications = isset($_POST['email_notifications']) ? true : false;
    
    // Prepare preferences JSON
    $notificationPreferences = json_encode([
        'in_app' => $inAppNotifications,
        'sms' => $smsNotifications,
        'email' => $emailNotifications
    ]);
    
    try {
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET NotificationPreferences = ? 
            WHERE UserID = ?
        ");
        
        $updateStmt->execute([$notificationPreferences, $_SESSION['user_id']]);
        $success[] = "Notification preferences updated successfully!";
        
        // Refresh user data after update
        $userStmt->execute([$_SESSION['user_id']]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $errors[] = "Error updating notification preferences: " . $e->getMessage();
    }
}

// Helper function for status badge classes
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Active':
            return 'active';
        case 'Upcoming':
            return 'upcoming';
        case 'Completed':
            return 'completed';
        case 'Cancelled':
            return 'cancelled';
        case 'Paid':
            return 'paid';
        case 'Pending':
            return 'pending';
        default:
            return 'completed';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Rodai Parking</title>
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
    <style>
        /* Enhanced styling for profile page */
        :root {
            --primary: #7A1FA0;
            --primary-light: #9b59b6;
            --primary-dark: #5E1681;
            --secondary: #8e44ad;
            --accent: #7d3c98;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f5f5f5;
            --light-purple: #F5EAFA;
            --dark: #333333;
            --gray: #95a5a6;
            --gray-light: #ecf0f1;
            --gray-dark: #7f8c8d;
            --black: #1a1a1a;
            --white: #ffffff;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .profile-page {
            padding: 100px 0 50px;
            background-color: #f5f7fa;
        }
        
        .profile-avatar {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .profile-avatar i {
            font-size: 80px;
            color: var(--primary);
            background: var(--light-purple);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 3px solid rgba(122, 31, 160, 0.2);
        }
        
        .profile-name {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .profile-info {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .profile-info p {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .profile-info i {
            color: var(--primary);
            width: 25px;
            margin-right: 10px;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-top: 20px;
        }
        
        .stat-item {
            padding: 15px;
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            min-width: 90px;
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .security-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .security-item {
            display: flex;
            align-items: center;
            background: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .security-item:hover {
            transform: translateY(-3px);
        }
        
        .security-icon {
            width: 50px;
            height: 50px;
            background: var(--light-purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 20px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .security-info {
            flex: 1;
        }
        
        .security-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .security-info p {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 0;
        }
        
        .security-action {
            margin-left: 20px;
        }
        
        .vehicle-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .vehicle-item {
            display: flex;
            align-items: center;
            background: var(--white);
            border-radius: 8px;
            padding: 15px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .vehicle-item:hover {
            transform: translateY(-3px);
        }
        
        .vehicle-icon {
            width: 40px;
            height: 40px;
            background: var(--light-purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 16px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .vehicle-info {
            flex: 1;
        }
        
        .vehicle-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .license-plate {
            display: inline-block;
            padding: 5px 10px;
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .vehicle-actions {
            display: flex;
            gap: 5px;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .bookings-table th {
            background: var(--light);
            color: var(--primary);
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid var(--gray-light);
            font-size: 14px;
        }
        
        .bookings-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-light);
            font-size: 14px;
            color: var(--dark);
        }
        
        .bookings-table tr:last-child td {
            border-bottom: none;
        }
        
        .bookings-table tr:hover td {
            background: var(--light-purple);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
        }
        
        .status-badge.active, .status-badge.paid {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .status-badge.upcoming, .status-badge.pending {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .status-badge.completed {
            background: rgba(127, 140, 141, 0.1);
            color: var(--gray-dark);
        }
        
        .status-badge.cancelled {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--gray-light);
            margin-bottom: 15px;
            display: block;
        }
        
        /* Card styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--gray-light);
            padding: 15px 20px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .card-title {
            margin-bottom: 0;
            color: var(--primary);
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-footer {
            background-color: var(--white);
            border-top: 1px solid var(--gray-light);
            padding: 15px 20px;
        }
        
        /* Modal styles */
        .modal-content {
            border-radius: 10px;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background-color: var(--primary);
            color: white;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            border-top: 1px solid var(--gray-light);
            padding: 15px 25px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .form-control {
            height: 48px;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid var(--gray-light);
            background-color: var(--light);
            color: var(--dark);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background-color: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(122, 31, 160, 0.25);
        }
        
        .form-text {
            color: var(--gray);
            font-size: 13px;
            margin-top: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }
        
        /* Form switch */
        .form-check-input {
            width: 2.5em;
            height: 1.25em;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* Alert styling */
        .alert {
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.1);
            border-color: rgba(39, 174, 96, 0.2);
            color: var(--success);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            border-color: rgba(231, 76, 60, 0.2);
            color: var(--danger);
        }
        
        /* Dashboard styling */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .dashboard-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .dashboard-subtitle {
            color: var(--gray);
            margin-bottom: 0;
        }
        
        .dashboard-date {
            color: var(--gray);
            font-size: 14px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }
        
        .grid-col-4 {
            grid-column: span 4;
        }
        
        .grid-col-6 {
            grid-column: span 6;
        }
        
        .grid-col-8 {
            grid-column: span 8;
        }
        
        .grid-col-12 {
            grid-column: span 12;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .security-item, .vehicle-item {
                flex-direction: column;
                text-align: center;
            }
            
            .security-icon, .vehicle-icon {
                margin: 0 auto 15px;
            }
            
            .security-action, .vehicle-actions {
                margin-top: 15px;
                margin-left: 0;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .stat-item {
                max-width: none;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .grid-col-4, .grid-col-6, .grid-col-8, .grid-col-12 {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>
    
    <main class="profile-page">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">My Profile</h1>
                    <p class="dashboard-subtitle">Manage your account information</p>
                </div>
                <div class="dashboard-date">
                    <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <?php foreach($success as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <!-- Profile Information Section -->
                <div class="grid-col-4">
                    <div class="card profile-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="card-title">Profile Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                                <h3 class="profile-name"><?php echo htmlspecialchars($userData['FirstName'] . ' ' . $userData['LastName']); ?></h3>
                            </div>
                            
                            <div class="profile-info">
                                <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($userData['Email']); ?></p>
                                <p><strong><i class="fas fa-phone"></i> Phone:</strong> <?php echo htmlspecialchars($userData['Phone'] ?? 'Not provided'); ?></p>
                                <p><strong><i class="fas fa-calendar-alt"></i> Member Since:</strong> <?php echo date('F j, Y', strtotime($userData['RegistrationDate'])); ?></p>
                            </div>
                            
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo count($vehicles); ?></span>
                                    <span class="stat-label">Vehicles</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo count($recentBookings); ?></span>
                                    <span class="stat-label">Bookings</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button class="btn btn-outline-primary btn-sm" 
                                   data-bs-toggle="modal" 
                                   data-bs-target="#editProfileModal">
                                <i class="fas fa-pencil-alt"></i> Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Account Security Section -->
                <div class="grid-col-8">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Account Security</h2>
                        </div>
                        <div class="card-body">
                            <div class="security-section">
                                <div class="security-item">
                                    <div class="security-icon">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div class="security-info">
                                        <h4>Password</h4>
                                        <p>Last changed: <?php echo isset($userData['PasswordLastChanged']) ? date('F j, Y', strtotime($userData['PasswordLastChanged'])) : 'Never'; ?></p>
                                    </div>
                                    <div class="security-action">
                                        <button class="btn btn-outline-primary btn-sm" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#changePasswordModal">
                                            Change Password
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="security-item">
                                    <div class="security-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="security-info">
                                        <h4>Email Notifications</h4>
                                        <p>Receive booking confirmations and updates</p>
                                    </div>
                                    <div class="security-action">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="emailNotifications" <?php echo isset($userData['EmailNotifications']) && $userData['EmailNotifications'] ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vehicles Section -->
                <div class="grid-col-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="card-title">My Vehicles</h2>
                            <a href="vehicles.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Vehicle
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($vehicles)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-car"></i>
                                    <p>You haven't added any vehicles yet</p>
                                </div>
                            <?php else: ?>
                                <div class="vehicle-list">
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <div class="vehicle-item">
                                            <div class="vehicle-icon">
                                                <i class="fas fa-car"></i>
                                            </div>
                                            <div class="vehicle-info">
                                                <h4><?php echo htmlspecialchars($vehicle['Make'] . ' ' . $vehicle['Model']); ?></h4>
                                                <div class="license-plate"><?php echo htmlspecialchars($vehicle['LicensePlate']); ?></div>
                                                <p>Color: <?php echo htmlspecialchars($vehicle['Color']); ?></p>
                                            </div>
                                            <div class="vehicle-actions">
                                                <a href="vehicles.php?edit=<?php echo $vehicle['VehicleID']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <a href="vehicles.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-car"></i> Manage Vehicles
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings Section -->
                <div class="grid-col-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Recent Bookings</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentBookings)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>No recent bookings found</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="bookings-table">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Spot</th>
                                                <th>Start Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentBookings as $booking): ?>
                                                <tr>
                                                    <td>#<?php echo $booking['BookingID']; ?></td>
                                                    <td><?php echo htmlspecialchars($booking['SpotNumber']); ?></td>
                                                    <td><?php echo date('M d, h:i A', strtotime($booking['StartTime'])); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo strtolower($booking['BookingStatus'] ?: 'completed'); ?>">
                                                            <?php echo $booking['BookingStatus'] ?: 'Completed'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <a href="viewbookings.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list"></i> View All Bookings
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Payment History Section -->
                <div class="grid-col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Payment History</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentPayments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-credit-card"></i>
                                    <p>No payment history found</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="bookings-table">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Booking ID</th>
                                                <th>Amount</th>
                                                <th>Payment Method</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentPayments as $payment): ?>
                                                <tr>
                                                    <td>#<?php echo $payment['PaymentID']; ?></td>
                                                    <td>#<?php echo $payment['BookingID']; ?></td>
                                                    <td>$<?php echo number_format($payment['Amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($payment['PaymentMethod']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($payment['PaymentTime'])); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo getStatusBadgeClass($payment['PaymentStatus'] ?: 'Completed'); ?>">
                                                            <?php echo $payment['PaymentStatus'] ?: 'Completed'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <a href="payments.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-credit-card"></i> View All Payments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="profile.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userData['FirstName']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userData['LastName']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['Email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['Phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="profile.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Notifications Tab -->
    <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0"><i class="fas fa-bell me-2 text-primary"></i>Notification Preferences</h4>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Control how you receive notifications about your parking reservations.</p>
                
                <?php
                // Parse notification preferences
                $notifPrefs = [];
                if (!empty($userData['NotificationPreferences'])) {
                    $notifPrefs = json_decode($userData['NotificationPreferences'], true) ?? [];
                }
                
                $inAppEnabled = isset($notifPrefs['in_app']) ? $notifPrefs['in_app'] : true;
                $smsEnabled = isset($notifPrefs['sms']) ? $notifPrefs['sms'] : false;
                $emailEnabled = isset($notifPrefs['email']) ? $notifPrefs['email'] : false;
                ?>
                
                <form method="POST" action="profile.php?tab=notifications">
                    <div class="list-group mb-4">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">In-App Notifications</h5>
                                <p class="mb-0 text-muted small">Receive notifications in the app when you're online.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="in_app_notifications" name="in_app_notifications" <?php echo $inAppEnabled ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">SMS Notifications</h5>
                                <p class="mb-0 text-muted small">Receive text messages for important updates about your reservations.</p>
                                <?php if (empty($userData['Phone'])): ?>
                                    <div class="alert alert-warning mt-2 mb-0 py-2 small">
                                        <i class="fas fa-exclamation-triangle me-1"></i> You need to add a phone number to receive SMS notifications.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="sms_notifications" name="sms_notifications" <?php echo $smsEnabled ? 'checked' : ''; ?> <?php echo empty($userData['Phone']) ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Email Notifications</h5>
                                <p class="mb-0 text-muted small">Receive email notifications for bookings and receipts.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="email_notifications" name="email_notifications" <?php echo $emailEnabled ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>What notifications will I receive?</h5>
                        <ul class="mb-0 ps-3">
                            <li><strong>Booking confirmations</strong> - When you make a new reservation</li>
                            <li><strong>Reservation start</strong> - When your parking time begins</li>
                            <li><strong>Reservation end</strong> - 15 minutes before your parking time ends</li>
                            <li><strong>Payment confirmations</strong> - When your payment is processed</li>
                        </ul>
                    </div>
                    
                    <button type="submit" name="update_notifications" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Notification Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Include footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
