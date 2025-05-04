<?php
include 'db.php';
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
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Select dates and times
    if (isset($_POST['step1_submit'])) {
        $startDate = $_POST['start_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        
        if (empty($startDate) || empty($startTime) || empty($endDate) || empty($endTime)) {
            $errors[] = "All date and time fields are required.";
        } else {
            // Combine date and time
            $startDateTime = date('Y-m-d H:i:s', strtotime("$startDate $startTime"));
            $endDateTime = date('Y-m-d H:i:s', strtotime("$endDate $endTime"));
            
            // Validate dates
            if ($startDateTime < date('Y-m-d H:i:s')) {
                $errors[] = "Start time cannot be in the past.";
            }
            
            if ($endDateTime <= $startDateTime) {
                $errors[] = "End time must be after start time.";
            }
            
            if (empty($errors)) {
                // Store in session for next step
                $_SESSION['booking_start'] = $startDateTime;
                $_SESSION['booking_end'] = $endDateTime;
                
                // Move to step 2
                header('Location: reserve.php?step=2');
                exit();
            }
        }
    }
    
    // Step 2: Select a parking spot
    else if (isset($_POST['step2_submit'])) {
        $spotId = $_POST['spot_id'] ?? '';
        
        if (empty($spotId)) {
            $errors[] = "Please select a parking spot.";
        } else {
            // Store in session for next step
            $_SESSION['booking_spot_id'] = $spotId;
            
            // Move to step 3
            header('Location: reserve.php?step=3');
            exit();
        }
    }
    
    // Step 3: Select a vehicle
    else if (isset($_POST['step3_submit'])) {
        $vehicleId = $_POST['vehicle_id'] ?? '';
        
        if (empty($vehicleId)) {
            $errors[] = "Please select a vehicle.";
        } else {
            // Store in session for next step
            $_SESSION['booking_vehicle_id'] = $vehicleId;
            
            // Move to step 4
            header('Location: reserve.php?step=4');
            exit();
        }
    }
    
    // Step 4: Confirm and create booking
    else if (isset($_POST['step4_submit'])) {
        try {
            // Get session variables
            $startDateTime = $_SESSION['booking_start'];
            $endDateTime = $_SESSION['booking_end'];
            $spotId = $_SESSION['booking_spot_id'];
            $vehicleId = $_SESSION['booking_vehicle_id'];
            $userId = $_SESSION['user_id'];

            // Corrected: Check for conflicting booking for this spot (proper overlap logic)
            $conflictStmt = $conn->prepare("
                SELECT 1 FROM bookings
                WHERE SpotID = ?
                  AND BookingStatus NOT IN ('Cancelled', 'Completed')
                  AND (StartTime < ? AND EndTime > ?)
                LIMIT 1
            ");
            $conflictStmt->execute([
                $spotId,
                $endDateTime, $startDateTime
            ]);
            if ($conflictStmt->fetch()) {
                $errors[] = "Sorry, this spot has just been booked by someone else for the selected time. Please choose another spot.";
            } else {
                // Get rate information (using first active rate for simplicity)
                $rateStmt = $conn->query("SELECT * FROM pricingrates WHERE IsActive = 1 LIMIT 1");
                $rate = $rateStmt->fetch(PDO::FETCH_ASSOC);
                $rateId = $rate['RateID'];
                // Calculate total cost
                $interval = date_diff(date_create($startDateTime), date_create($endDateTime));
                $totalHours = $interval->h + ($interval->days * 24);
                // Simple pricing for example (can be enhanced with your logic)
                $totalCost = $totalHours * $rate['HourlyRate'];
                // Create booking
                $bookingStmt = $conn->prepare("INSERT INTO bookings 
                    (UserID, VehicleID, SpotID, RateID, StartTime, EndTime, TotalCost, BookingStatus, PaymentStatus, BookingTime) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Upcoming', 'Pending', NOW())");
                $bookingStmt->execute([
                    $userId, 
                    $vehicleId, 
                    $spotId, 
                    $rateId, 
                    $startDateTime, 
                    $endDateTime, 
                    $totalCost
                ]);
                $bookingId = $conn->lastInsertId();
                // Store booking ID for payment
                $_SESSION['booking_id'] = $bookingId;
                $_SESSION['booking_total'] = $totalCost;
                // Move to payment step
                header('Location: reserve.php?step=5');
                exit();
            }
        } catch (Exception $e) {
            $errors[] = "Error creating booking: " . $e->getMessage();
        }
    }
    
    // Step 5: Process payment
    else if (isset($_POST['step5_submit'])) {
        $paymentMethod = $_POST['payment_method'] ?? '';
        
        if (empty($paymentMethod)) {
            $errors[] = "Please select a payment method.";
        } else if (!isset($_SESSION['booking_id']) || !isset($_SESSION['booking_total'])) {
            $errors[] = "Booking information is missing. Please start the reservation process again.";
            // Clear session data
            unset($_SESSION['booking_start']);
            unset($_SESSION['booking_end']);
            unset($_SESSION['booking_spot_id']);
            unset($_SESSION['booking_vehicle_id']);
            unset($_SESSION['booking_total']);
            unset($_SESSION['booking_id']);
            // Redirect to step 1
            header('Location: reserve.php?step=1');
            exit();
        } else {
            try {
                // Generate fake transaction ID
                $transactionId = 'TXN' . time() . rand(1000, 9999);
                
                // Set payment status based on payment method
                $paymentStatus = ($paymentMethod == 'Cash') ? 'Pending' : 'Completed';
                $bookingStatus = ($paymentMethod == 'Cash') ? 'Pending' : 'Active';
                
                // Create payment record
                $paymentStmt = $conn->prepare("INSERT INTO payments 
                    (BookingID, Amount, PaymentMethod, TransactionID, PaymentTime, PaymentStatus) 
                    VALUES (?, ?, ?, ?, NOW(), ?)");
                
                $paymentStmt->execute([
                    $_SESSION['booking_id'],
                    $_SESSION['booking_total'],
                    $paymentMethod,
                    $transactionId,
                    $paymentStatus
                ]);
                
                // Update booking payment status
                $updateStmt = $conn->prepare("UPDATE bookings SET PaymentStatus = ?, BookingStatus = ? WHERE BookingID = ?");
                $updateStmt->execute([$paymentStatus, $bookingStatus, $_SESSION['booking_id']]);
                
                // Clear booking session data
                unset($_SESSION['booking_start']);
                unset($_SESSION['booking_end']);
                unset($_SESSION['booking_spot_id']);
                unset($_SESSION['booking_vehicle_id']);
                unset($_SESSION['booking_total']);
                
                // Store booking ID in session for confirmation page
                $_SESSION['booking_complete_id'] = $_SESSION['booking_id'];
                unset($_SESSION['booking_id']);
                
                // Set success message
                if ($paymentMethod == 'Cash') {
                    $success[] = "Booking completed successfully! Your booking is pending until cash payment is received on-site.";
                } else {
                    $success[] = "Booking completed successfully! Your payment has been processed.";
                }
                
                // Send booking confirmation notification
                $bookingId = $_SESSION['booking_complete_id'];
                $bookingStmt = $conn->prepare("
                    SELECT b.*, ps.SpotNumber, u.Phone 
                    FROM bookings b
                    JOIN parkingspots ps ON b.SpotID = ps.SpotID
                    JOIN users u ON b.UserID = u.UserID
                    WHERE b.BookingID = ?
                ");
                $bookingStmt->execute([$bookingId]);
                $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($booking) {
                    // Get user preferences for SMS notifications
                    $userPrefStmt = $conn->prepare("SELECT NotificationPreferences FROM users WHERE UserID = ?");
                    $userPrefStmt->execute([$booking['UserID']]);
                    $userPrefRow = $userPrefStmt->fetch(PDO::FETCH_ASSOC);
                    $sendSMS = false;
                    
                    if ($userPrefRow && !empty($userPrefRow['NotificationPreferences'])) {
                        $prefs = json_decode($userPrefRow['NotificationPreferences'], true);
                        $sendSMS = isset($prefs['sms']) && $prefs['sms'];
                    }
                    
                    // Send booking confirmation notification
                    sendBookingConfirmationNotification($conn, $booking, $sendSMS);
                }
                
                // Move to confirmation step
                header('Location: reserve.php?step=6&booking_id=' . $_SESSION['booking_complete_id']);
                exit();
            } catch (PDOException $e) {
                $errors[] = "Error processing payment: " . $e->getMessage();
            }
        }
    }
}

// Get available parking spots based on selected times
$spotsWithStatus = [];
if ($step == 2 && isset($_SESSION['booking_start']) && isset($_SESSION['booking_end'])) {
    try {
        // Fetch all active spots
        $spotStmt = $conn->prepare("SELECT * FROM parkingspots WHERE IsActive = 1");
        $spotStmt->execute();
        $allSpots = $spotStmt->fetchAll(PDO::FETCH_ASSOC);

        // For each spot, check if it is available for the selected time
        foreach ($allSpots as $spot) {
            $conflictStmt = $conn->prepare("
                SELECT 1 FROM bookings
                WHERE SpotID = ?
                  AND BookingStatus NOT IN ('Cancelled', 'Completed')
                  AND (StartTime < ? AND EndTime > ?)
                LIMIT 1
            ");
            $conflictStmt->execute([
                $spot['SpotID'],
                $_SESSION['booking_end'],
                $_SESSION['booking_start']
            ]);
            $isAvailable = !$conflictStmt->fetch();
            $spot['is_available'] = $isAvailable;
            $spotsWithStatus[] = $spot;
        }
    } catch (PDOException $e) {
        $errors[] = "Error finding available spots: " . $e->getMessage();
    }
}

// Get user's vehicles
$userVehicles = [];
if ($step == 3) {
    try {
        $vehicleStmt = $conn->prepare("SELECT * FROM vehicles WHERE UserID = ?");
        $vehicleStmt->execute([$_SESSION['user_id']]);
        $userVehicles = $vehicleStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Error fetching vehicles: " . $e->getMessage();
    }
}

// Get booking details for confirmation
$bookingDetails = [];
if ($step == 4 && isset($_SESSION['booking_spot_id']) && isset($_SESSION['booking_vehicle_id'])) {
    try {
        // Get spot info
        $spotStmt = $conn->prepare("SELECT * FROM parkingspots WHERE SpotID = ?");
        $spotStmt->execute([$_SESSION['booking_spot_id']]);
        $spot = $spotStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get vehicle info
        $vehicleStmt = $conn->prepare("SELECT * FROM vehicles WHERE VehicleID = ?");
        $vehicleStmt->execute([$_SESSION['booking_vehicle_id']]);
        $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get rate info
        $rateStmt = $conn->query("SELECT * FROM pricingrates WHERE IsActive = 1 LIMIT 1");
        $rate = $rateStmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate total cost
        $interval = date_diff(date_create($_SESSION['booking_start']), date_create($_SESSION['booking_end']));
        $totalHours = $interval->h + ($interval->days * 24);
        $totalCost = $totalHours * $rate['HourlyRate'];
        
        $bookingDetails = [
            'spot' => $spot,
            'vehicle' => $vehicle,
            'rate' => $rate,
            'start' => $_SESSION['booking_start'],
            'end' => $_SESSION['booking_end'],
            'total_hours' => $totalHours,
            'total_cost' => $totalCost
        ];
    } catch (PDOException $e) {
        $errors[] = "Error getting booking details: " . $e->getMessage();
    }
}

// Get booking info for confirmation page
$completedBooking = null;
if ($step == 6 && isset($_GET['booking_id'])) {
    try {
        $bookingStmt = $conn->prepare("
            SELECT b.*, ps.SpotNumber, v.Make, v.Model, v.LicensePlate
            FROM bookings b
            JOIN parkingspots ps ON b.SpotID = ps.SpotID
            JOIN vehicles v ON b.VehicleID = v.VehicleID
            WHERE b.BookingID = ?
        ");
        $bookingStmt->execute([$_GET['booking_id']]);
        $completedBooking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Error fetching booking details: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Parking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .booking-steps::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            background: white;
            text-align: center;
            width: 16.666%;
        }
        
        .step-icon {
            position: relative;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #6c757d;
            border: 2px solid #e9ecef;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .step-number {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 25px;
            height: 25px;
            background-color: #6c757d;
            color: white;
            border-radius: 50%;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }
        
        .step.active .step-number {
            background-color: #3498db;
        }
        
        .step.completed .step-number {
            background-color: #2ecc71;
        }
        
        .step.active .step-icon {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .step.completed .step-icon {
            background: #2ecc71;
            color: white;
            border-color: #2ecc71;
        }
        
        .step-title {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .step.active .step-title {
            color: #3498db;
            font-weight: 600;
        }
        
        .step.completed .step-title {
            color: #2ecc71;
        }
        
        .parking-spot {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1), border 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
            min-height: 170px;
            min-width: 220px;
            /* overflow: hidden; */
        }
        
        /* Remove the ::after overlay entirely */
        .parking-spot::after { display: none !important; }
        
        .parking-spot:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 24px rgba(52, 152, 219, 0.13);
            border: 1.5px solid #3498db;
        }
        
        .parking-spot.selected {
            border: 2px solid #3498db;
            background: #f4faff;
            box-shadow: 0 8px 24px rgba(52, 152, 219, 0.18);
        }
        
        .spot-number {
            font-size: 2rem;
            font-weight: 800;
            color: #2c3e50;
            display: block;
            margin-bottom: 1rem;
            position: relative;
            padding-left: 2.5rem;
            letter-spacing: -1px;
        }
        .spot-number::before {
            content: '\f5b9';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #3498db;
            font-size: 1.5rem;
            opacity: 0.9;
        }
        .spot-type {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: #f8f9fa;
            border-radius: 25px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            color: #6c757d;
            border: 1px solid #e9ecef;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .spot-notes {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            padding-left: 1rem;
            border-left: 3px solid #e9ecef;
            line-height: 1.6;
        }
        .parking-spot .form-check {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
        }
        .parking-spot .form-check-input {
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid #e9ecef;
            cursor: pointer;
        }
        .parking-spot .form-check-input:checked {
            background-color: #3498db;
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        .parking-spot.unavailable {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            opacity: 0.7;
            cursor: not-allowed;
        }
        .parking-spot.unavailable .spot-number {
            color: #adb5bd;
        }
        .parking-spot.unavailable .spot-type {
            background: #e9ecef;
            color: #adb5bd;
        }
        .parking-spot.unavailable .spot-notes {
            color: #adb5bd;
        }
        .parking-spot .availability-status {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .parking-spot .availability-status.available {
            background: #d4edda;
            color: #155724;
            box-shadow: 0 2px 4px rgba(21, 87, 36, 0.1);
        }
        .parking-spot .availability-status.unavailable {
            background: #f8d7da;
            color: #721c24;
            box-shadow: 0 2px 4px rgba(114, 28, 36, 0.1);
        }
        @media (max-width: 768px) {
            .parking-spot {
                padding: 1.5rem;
                min-width: 160px;
                min-height: 120px;
            }
            .spot-number {
                font-size: 1.5rem;
                padding-left: 2rem;
            }
            .spot-number::before {
                font-size: 1.2rem;
            }
            .spot-type {
                font-size: 0.85rem;
                padding: 0.4rem 1rem;
            }
            .parking-spot .form-check {
                top: 1rem;
                right: 1rem;
            }
            .parking-spot .availability-status {
                bottom: 1rem;
                right: 1rem;
            }
        }
        .vehicle-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .vehicle-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
        }
        
        .vehicle-card.selected {
            border-color: #3498db;
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .license-plate {
            background: #f0f0f0;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
            font-size: 1.2rem;
            display: inline-block;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
        }
        
        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .payment-option:hover {
            border-color: #3498db;
            transform: translateY(-2px);
        }
        
        .payment-option.selected {
            border-color: #3498db;
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .payment-option img {
            height: 40px;
            margin-right: 1rem;
        }
        
        .confirmation-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 4rem;
            color: #2ecc71;
            margin-bottom: 1rem;
        }
        
        .booking-detail-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .booking-detail-card .card-header {
            background-color: #3498db;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .booking-detail-card .list-group-item {
            display: flex;
            justify-content: space-between;
        }
        
        .booking-detail-card .list-group-item span:first-child {
            font-weight: 600;
            color: #7f8c8d;
        }
        
        .booking-detail-card .list-group-item span:last-child {
            color: #2c3e50;
        }
        
        .total-row {
            background-color: #f8f9fa;
            font-weight: 700;
        }
        
        .total-row span:last-child {
            color: #3498db !important;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-car"></i> Reserve Parking</h1>
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
        
        <!-- Booking Steps Indicator with numbers -->
        <div class="booking-steps">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="step-icon">
                    <span class="step-number">1</span>
                    <?php if ($step > 1): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <i class="fas fa-calendar-alt"></i>
                    <?php endif; ?>
                </div>
                <div class="step-title">Select Dates</div>
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="step-icon">
                    <span class="step-number">2</span>
                    <?php if ($step > 2): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <i class="fas fa-map-marker-alt"></i>
                    <?php endif; ?>
                </div>
                <div class="step-title">Select Spot</div>
            </div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                <div class="step-icon">
                    <span class="step-number">3</span>
                    <?php if ($step > 3): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <i class="fas fa-car-side"></i>
                    <?php endif; ?>
                </div>
                <div class="step-title">Select Vehicle</div>
            </div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?> <?php echo $step > 4 ? 'completed' : ''; ?>">
                <div class="step-icon">
                    <span class="step-number">4</span>
                    <?php if ($step > 4): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <i class="fas fa-clipboard-check"></i>
                    <?php endif; ?>
                </div>
                <div class="step-title">Confirm</div>
            </div>
            <div class="step <?php echo $step >= 5 ? 'active' : ''; ?> <?php echo $step > 5 ? 'completed' : ''; ?>">
                <div class="step-icon">
                    <span class="step-number">5</span>
                    <?php if ($step > 5): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <i class="fas fa-credit-card"></i>
                    <?php endif; ?>
                </div>
                <div class="step-title">Payment</div>
            </div>
            <div class="step <?php echo $step >= 6 ? 'active' : ''; ?>">
                <div class="step-icon">
                    <span class="step-number">6</span>
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="step-title">Complete</div>
            </div>
        </div>

        <!-- Step content -->
        <div class="card">
            <div class="card-body p-4">
                <?php if ($step == 1): ?>
                    <!-- Step 1: Select dates and times -->
                    <h2 class="mb-4">Select Parking Dates and Times</h2>
                    <form method="POST" action="reserve.php?step=1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" class="form-control" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" class="form-control" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" name="step1_submit" class="btn btn-primary btn-lg">
                                Find Available Spots <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                
                <?php elseif ($step == 2): ?>
                    <!-- Step 2: Select a parking spot -->
                    <h2 class="mb-4">Select a Parking Spot</h2>
                    <?php if (empty($spotsWithStatus)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No parking spots are available for the selected time period. 
                            <a href="reserve.php?step=1" class="alert-link">Try different dates</a>.
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <p>
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Select a parking spot for 
                                <strong><?php echo date('M d, Y g:i A', strtotime($_SESSION['booking_start'])); ?></strong> to 
                                <strong><?php echo date('M d, Y g:i A', strtotime($_SESSION['booking_end'])); ?></strong>
                            </p>
                        </div>
                        <form method="POST" action="reserve.php?step=2" id="spotSelectionForm">
                            <div class="row">
                                <?php foreach ($spotsWithStatus as $spot): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="parking-spot<?php echo $spot['is_available'] ? '' : ' unavailable'; ?>" data-spot-id="<?php echo $spot['SpotID']; ?>" <?php if($spot['is_available']): ?>onclick="selectSpot(this)"<?php endif; ?>>
                                            <span class="spot-number"><?php echo $spot['SpotNumber']; ?></span>
                                            <span class="spot-type"><?php echo $spot['SpotType']; ?></span>
                                            <?php if (!empty($spot['Notes'])): ?>
                                                <p class="spot-notes"><?php echo $spot['Notes']; ?></p>
                                            <?php endif; ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="spot_id" value="<?php echo $spot['SpotID']; ?>" id="spot<?php echo $spot['SpotID']; ?>" <?php if(!$spot['is_available']): ?>disabled<?php endif; ?>>
                                            </div>
                                            <span class="availability-status <?php echo $spot['is_available'] ? 'available' : 'unavailable'; ?>">
                                                <?php echo $spot['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <a href="reserve.php?step=1" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Back
                                </a>
                                <button type="submit" name="step2_submit" class="btn btn-primary btn-lg">
                                    Continue <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </form>
                        <script>
                            function selectSpot(el) {
                                // Remove selected class from all spots
                                document.querySelectorAll('.parking-spot').forEach(spot => {
                                    spot.classList.remove('selected');
                                });
                                // Add selected class to clicked spot
                                el.classList.add('selected');
                                // Check the radio button
                                const spotId = el.getAttribute('data-spot-id');
                                document.getElementById('spot' + spotId).checked = true;
                            }
                        </script>
                    <?php endif; ?>
                
                <?php elseif ($step == 3): ?>
                    <!-- Step 3: Select a vehicle -->
                    <h2 class="mb-4">Select a Vehicle</h2>
                    
                    <?php if (empty($userVehicles)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You don't have any registered vehicles. Please contact support to add a vehicle to your account.
                        </div>
                        <div class="text-center mt-4">
                            <a href="reserve.php?step=2" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="reserve.php?step=3" id="vehicleSelectionForm">
                            <div class="row">
                                <?php foreach ($userVehicles as $vehicle): ?>
                                    <div class="col-md-6">
                                        <div class="vehicle-card" data-vehicle-id="<?php echo $vehicle['VehicleID']; ?>" onclick="selectVehicle(this)">
                                            <h5><?php echo htmlspecialchars($vehicle['Make'] . ' ' . $vehicle['Model']); ?></h5>
                                            <div class="license-plate">
                                                <?php echo htmlspecialchars($vehicle['LicensePlate']); ?>
                                            </div>
                                            <p><strong>Color:</strong> <?php echo htmlspecialchars($vehicle['Color']); ?></p>
                                            
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="vehicle_id" value="<?php echo $vehicle['VehicleID']; ?>" id="vehicle<?php echo $vehicle['VehicleID']; ?>">
                                                <label class="form-check-label" for="vehicle<?php echo $vehicle['VehicleID']; ?>">Select</label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="reserve.php?step=2" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Back
                                </a>
                                <button type="submit" name="step3_submit" class="btn btn-primary btn-lg">
                                    Continue <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </form>
                        
                        <script>
                            function selectVehicle(el) {
                                // Remove selected class from all vehicles
                                document.querySelectorAll('.vehicle-card').forEach(vehicle => {
                                    vehicle.classList.remove('selected');
                                });
                                
                                // Add selected class to clicked vehicle
                                el.classList.add('selected');
                                
                                // Check the radio button
                                const vehicleId = el.getAttribute('data-vehicle-id');
                                document.getElementById('vehicle' + vehicleId).checked = true;
                            }
                        </script>
                    <?php endif; ?>
                
                <?php elseif ($step == 4): ?>
                    <!-- Step 4: Confirm and create booking -->
                    <h2 class="mb-4">Confirm Booking</h2>
                    
                    <div class="mb-4">
                        <p>
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Please review the booking details below:
                        </p>
                    </div>
                    
                    <div class="booking-detail-card">
                        <div class="card-header">
                            <h5>Booking Details</h5>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <span>Spot:</span>
                                <span><?php echo htmlspecialchars($bookingDetails['spot']['SpotNumber']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <span>Vehicle:</span>
                                <span><?php echo htmlspecialchars($bookingDetails['vehicle']['Make'] . ' ' . $bookingDetails['vehicle']['Model']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <span>Rate:</span>
                                <span><?php echo htmlspecialchars($bookingDetails['rate']['RateName']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <span>Start Time:</span>
                                <span><?php echo date('M d, Y g:i A', strtotime($bookingDetails['start'])); ?></span>
                            </li>
                            <li class="list-group-item">
                                <span>End Time:</span>
                                <span><?php echo date('M d, Y g:i A', strtotime($bookingDetails['end'])); ?></span>
                            </li>
                            <li class="list-group-item">
                                <span>Total Hours:</span>
                                <span><?php echo htmlspecialchars($bookingDetails['total_hours']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <span>Total Cost:</span>
                                <span><?php echo htmlspecialchars($bookingDetails['total_cost']); ?></span>
                            </li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="reserve.php?step=4" class="mt-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="termsCheck" required>
                            <label class="form-check-label" for="termsCheck">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a>
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="reserve.php?step=3" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back
                            </a>
                            <button type="submit" name="step4_submit" class="btn btn-primary btn-lg">
                                Proceed to Payment <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                
                <?php elseif ($step == 5): ?>
                    <!-- Step 5: Process payment -->
                    <h2 class="mb-4">Select Payment Method</h2>
                    
                    <form method="POST" action="reserve.php?step=5">
                        <div class="mb-4">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method">
                                <option value="">Select a payment method</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="PayPal">PayPal</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" name="step5_submit" class="btn btn-primary btn-lg">
                                Proceed to Payment <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                
                <?php elseif ($step == 6): ?>
                    <!-- Step 6: Confirmation -->
                    <div class="confirmation-box">
                        <i class="fas fa-check-circle confirmation-icon"></i>
                        <h2 class="mb-3">Booking Confirmed!</h2>
                        <p class="lead">Your parking spot has been successfully reserved.</p>
                        
                        <div class="card mt-4 mb-4 mx-auto" style="max-width: 400px;">
                            <div class="card-body text-center">
                                <h5 class="card-title">Booking ID</h5>
                                <?php if (isset($completedBooking['BookingID'])): ?>
                                    <p class="display-6 mb-3"><?php echo htmlspecialchars($completedBooking['BookingID']); ?></p>
                                <?php else: ?>
                                    <p class="display-6 mb-3">Booking Complete</p>
                                <?php endif; ?>
                                <p class="card-text">Please use this ID for all communications.</p>
                            </div>
                        </div>
                        
                        <div class="row mt-5">
                            <div class="col-md-6 mb-3">
                                <a href="viewbookings.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-list me-2"></i> View All Bookings
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="dashboard.php" class="btn btn-primary w-100 py-3">
                                    <i class="fas fa-home me-2"></i> Return to Home
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
