<?php
include 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's bookings with full details
try {
    $stmt = $conn->prepare("
        SELECT b.*, 
               ps.SpotNumber, 
               ps.SpotType,
               v.Make, 
               v.Model, 
               v.Color, 
               v.LicensePlate,
               pr.RateName,
               pr.HourlyRate
        FROM bookings b
        JOIN parkingspots ps ON b.SpotID = ps.SpotID
        JOIN vehicles v ON b.VehicleID = v.VehicleID
        LEFT JOIN pricingrates pr ON b.RateID = pr.RateID
        WHERE b.UserID = ?
        ORDER BY 
            CASE 
                WHEN b.BookingStatus = 'Active' THEN 1
                WHEN b.BookingStatus = 'Upcoming' THEN 2
                ELSE 3
            END, 
            b.StartTime DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all payment details for these bookings
    $bookingIds = array_column($bookings, 'BookingID');
    
    $payments = [];
    if (!empty($bookingIds)) {
        $placeholders = str_repeat('?,', count($bookingIds) - 1) . '?';
        $paymentStmt = $conn->prepare("
            SELECT * FROM payments 
            WHERE BookingID IN ($placeholders)
            ORDER BY PaymentTime DESC
        ");
        $paymentStmt->execute($bookingIds);
        $paymentResults = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Index payments by booking ID
        foreach ($paymentResults as $payment) {
            $payments[$payment['BookingID']][] = $payment;
        }
    }
    
    // Get access logs for these bookings
    $accessLogs = [];
    if (!empty($bookingIds)) {
        $logsStmt = $conn->prepare("
            SELECT * FROM accesslogs 
            WHERE BookingID IN ($placeholders)
            ORDER BY Timestamp DESC
        ");
        $logsStmt->execute($bookingIds);
        $logResults = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Index logs by booking ID
        foreach ($logResults as $log) {
            $accessLogs[$log['BookingID']][] = $log;
        }
    }
    
} catch (PDOException $e) {
    $error = "Error fetching bookings: " . $e->getMessage();
}

// Handle booking cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    try {
        $bookingId = $_POST['booking_id'];
        
        // First check if the booking belongs to the user
        $checkStmt = $conn->prepare("SELECT BookingID FROM bookings WHERE BookingID = ? AND UserID = ?");
        $checkStmt->execute([$bookingId, $_SESSION['user_id']]);
        
        if ($checkStmt->rowCount() > 0) {
            // Now cancel the booking
            $cancelStmt = $conn->prepare("UPDATE bookings SET BookingStatus = 'Cancelled' WHERE BookingID = ?");
            $cancelStmt->execute([$bookingId]);
            
            $success = "Booking #{$bookingId} has been cancelled successfully.";
            
            // Redirect to refresh the page data
            header("Location: bookings.php?success=cancelled");
            exit();
        } else {
            $error = "You don't have permission to cancel this booking.";
        }
    } catch (PDOException $e) {
        $error = "Error cancelling booking: " . $e->getMessage();
    }
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Active':
            return 'bg-success';
        case 'Upcoming':
            return 'bg-primary';
        case 'Completed':
            return 'bg-info';
        case 'Cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Function to get payment status badge class
function getPaymentBadgeClass($status) {
    switch ($status) {
        case 'Paid':
            return 'bg-success';
        case 'Pending':
            return 'bg-warning';
        case 'Failed':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 24px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        /* 
        * Example of how Cash payment / Not Paid bookings would appear:
        *
        * Booking #35
        * VEHICLE: Mercedes A180 (White)
        * LICENSE: ABC1234
        * PARKING SPOT: Spot A4 (Standard)
        * BOOKING TIME: May 04, 2025 - 9:15 AM
        * RESERVATION PERIOD: May 10, 2025 - 10:00 AM to May 11, 2025 - 10:00 AM
        * PAYMENT STATUS: [Pending] (shown in yellow/orange)
        * PAYMENT METHOD: Cash
        * AMOUNT: $60.00
        * BOOKING STATUS: [Pending] (shown in yellow/orange)
        *
        * For cash payments, both the payment status and booking status would be "Pending"
        * The user needs to pay in person before the booking becomes active.
        */
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .booking-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .booking-id {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .booking-body {
            padding: 20px;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .booking-details h6 {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .booking-details p {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .license-plate {
            display: inline-block;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 4px;
            padding: 5px 15px;
            font-family: monospace;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        
        .time-badge {
            display: inline-flex;
            align-items: center;
            background-color: #f8f9fa;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .time-badge i {
            margin-right: 8px;
            color: #3498db;
        }
        
        .booking-footer {
            padding: 15px 20px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .total-cost {
            font-weight: 700;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        
        .booking-actions {
            display: flex;
            gap: 10px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 11px;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-badge {
            position: absolute;
            left: -30px;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            z-index: 1;
        }
        
        .timeline-content {
            padding-bottom: 10px;
        }
        
        .timeline-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .timeline-timestamp {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .no-bookings {
            text-align: center;
            padding: 50px 0;
        }
        
        .no-bookings-icon {
            font-size: 4rem;
            color: #e9ecef;
            margin-bottom: 20px;
        }
        
        .filter-bar {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .booking-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .booking-actions {
                margin-top: 10px;
                width: 100%;
            }
            
            .booking-actions .btn {
                flex: 1;
            }
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
            color: #27ae60;
        }
        
        .status-badge.upcoming, .status-badge.pending {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }
        
        .status-badge.completed {
            background: rgba(127, 140, 141, 0.1);
            color: #7f8c8d;
        }
        
        .status-badge.cancelled {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-ticket-alt"></i> My Bookings</h1>
                <p class="text-muted">View and manage all your parking bookings</p>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 'cancelled'): ?>
            <div class="alert alert-success">
                Booking has been cancelled successfully.
            </div>
        <?php endif; ?>
        
        <!-- Filter options -->
        <div class="filter-bar">
            <div class="row">
                <div class="col-md-8">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-success" data-filter="active">Active</button>
                        <button type="button" class="btn btn-outline-info" data-filter="completed">Completed</button>
                        <button type="button" class="btn btn-outline-danger" data-filter="cancelled">Cancelled</button>
                        <button type="button" class="btn btn-outline-warning" data-filter="pending">Pending</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search bookings..." id="searchInput" onkeyup="searchBookings()">
                        <button class="btn btn-outline-secondary" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($bookings)): ?>
            <div class="no-bookings">
                <i class="fas fa-calendar-times no-bookings-icon"></i>
                <h3>No Bookings Found</h3>
                <p class="text-muted">You haven't made any bookings yet.</p>
                <a href="reserve.php" class="btn btn-primary mt-3">Make a Booking</a>
            </div>
        <?php else: ?>
            <!-- Bookings List -->
            <div class="bookings-container">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card" data-status="<?php echo strtolower($booking['BookingStatus']); ?>">
                        <div class="booking-header">
                            <span class="booking-id">Booking #<?php echo $booking['BookingID']; ?></span>
                            <span class="status-badge <?php echo strtolower($booking['BookingStatus']); ?>"><?php echo $booking['BookingStatus']; ?></span>
                        </div>
                        <div class="booking-body">
                            <div class="booking-grid">
                                <div class="booking-details">
                                    <h6>Vehicle</h6>
                                    <p><?php echo htmlspecialchars($booking['Make'] . ' ' . $booking['Model'] . ' (' . $booking['Color'] . ')'); ?></p>
                                    
                                    <div class="license-plate">
                                        <?php echo htmlspecialchars($booking['LicensePlate']); ?>
                                    </div>
                                    
                                    <h6>Parking Spot</h6>
                                    <p>Spot <?php echo htmlspecialchars($booking['SpotNumber']); ?> (<?php echo htmlspecialchars($booking['SpotType']); ?>)</p>
                                    
                                    <h6>Rate Plan</h6>
                                    <p><?php echo htmlspecialchars($booking['RateName'] ?? 'Standard Rate'); ?> 
                                       ($<?php echo number_format($booking['HourlyRate'], 2); ?>/hour)</p>
                                </div>
                                
                                <div class="booking-details">
                                    <h6>Booking Time</h6>
                                    <div class="time-badge">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y - g:i A', strtotime($booking['BookingTime'])); ?>
                                    </div>
                                    
                                    <h6>Reservation Period</h6>
                                    <div class="time-badge">
                                        <i class="fas fa-hourglass-start"></i>
                                        <?php echo date('M d, Y - g:i A', strtotime($booking['StartTime'])); ?>
                                    </div>
                                    <div class="time-badge">
                                        <i class="fas fa-hourglass-end"></i>
                                        <?php echo date('M d, Y - g:i A', strtotime($booking['EndTime'])); ?>
                                    </div>
                                    
                                    <h6>Payment Status</h6>
                                    <span class="status-badge <?php echo strtolower($booking['PaymentStatus']); ?>">
                                        <?php echo $booking['PaymentStatus']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Access Logs Timeline -->
                            <?php if (!empty($accessLogs[$booking['BookingID']])): ?>
                                <h5 class="mt-4 mb-3">Access Logs</h5>
                                <div class="timeline">
                                    <?php foreach ($accessLogs[$booking['BookingID']] as $log): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-badge">
                                                <i class="fas <?php echo $log['EventType'] == 'Entry' ? 'fa-sign-in-alt' : 'fa-sign-out-alt'; ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-title">
                                                    <?php echo $log['EventType'] == 'Entry' ? 'Entered Parking' : 'Exited Parking'; ?>
                                                </div>
                                                <div class="timeline-timestamp">
                                                    <?php echo date('M d, Y - g:i A', strtotime($log['Timestamp'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Payment History -->
                            <?php if (!empty($payments[$booking['BookingID']])): ?>
                                <h5 class="mt-4 mb-3">Payment History</h5>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments[$booking['BookingID']] as $payment): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($payment['PaymentTime'])); ?></td>
                                                <td>$<?php echo number_format($payment['Amount'], 2); ?></td>
                                                <td><?php echo $payment['PaymentMethod']; ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo strtolower($payment['PaymentStatus']); ?>">
                                                        <?php echo $payment['PaymentStatus']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <!-- Notes Section -->
                            <?php if (!empty($booking['Notes'])): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?php echo htmlspecialchars($booking['Notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="booking-footer">
                            <div class="total-cost">
                                Total: $<?php echo number_format($booking['TotalCost'], 2); ?>
                            </div>
                            
                            <div class="booking-actions">
                                <?php if ($booking['BookingStatus'] == 'Upcoming' || $booking['BookingStatus'] == 'Active'): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['BookingID']; ?>">
                                        <button type="submit" name="cancel_booking" class="btn btn-outline-danger">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($booking['BookingStatus'] == 'Upcoming' || $booking['BookingStatus'] == 'Active'): ?>
                                    <a href="extend.php?id=<?php echo $booking['BookingID']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-clock"></i> Extend
                                    </a>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-secondary" onclick="printBookingDetails(<?php echo $booking['BookingID']; ?>)">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter bookings based on status
        function filterBookings(status) {
            const bookings = document.querySelectorAll('.booking-card');
            
            bookings.forEach(booking => {
                const bookingStatus = booking.dataset.status.toLowerCase();
                
                if (status === 'all') {
                    booking.style.display = 'block';
                } else if (status === bookingStatus) {
                    booking.style.display = 'block';
                } else {
                    booking.style.display = 'none';
                }
            });
            
            // Update active filter button
            document.querySelectorAll('.filter-bar .btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to the clicked button
            document.querySelector(`.btn-group .btn[data-filter="${status}"]`).classList.add('active');
        }
        
        // Search bookings
        function searchBookings() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const bookings = document.querySelectorAll('.booking-card');
            
            bookings.forEach(booking => {
                const bookingText = booking.textContent.toLowerCase();
                if (bookingText.includes(searchInput)) {
                    booking.style.display = 'block';
                } else {
                    booking.style.display = 'none';
                }
            });
        }
        
        // Make sure status filters are properly initialized
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial active state on the "All" button
            document.querySelector('.btn-group .btn[data-filter="all"]').classList.add('active');
            
            // Add click event listeners to all filter buttons
            document.querySelectorAll('.btn-group .btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const statusFilter = this.getAttribute('data-filter');
                    filterBookings(statusFilter);
                });
            });
        });
        
        // Print booking details
        function printBookingDetails(bookingId) {
            // Find the booking card matching the ID
            const bookingCards = document.querySelectorAll('.booking-card');
            let bookingCard = null;
            
            bookingCards.forEach(card => {
                if (card.querySelector('.booking-id').textContent.includes(bookingId)) {
                    bookingCard = card;
                }
            });
            
            if (!bookingCard) return;
            
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Booking #${bookingId} Details</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
                        h1 { text-align: center; color: #3498db; }
                        .booking-details { margin-bottom: 20px; }
                        .booking-details h3 { border-bottom: 1px solid #eee; padding-bottom: 5px; }
                        .booking-details p { margin: 5px 0; }
                        .booking-details strong { width: 150px; display: inline-block; }
                        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #7f8c8d; }
                    </style>
                </head>
                <body>
                    <h1>Booking #${bookingId} Details</h1>
                    <div class="booking-details">
                        ${bookingCard.querySelector('.booking-body').innerHTML}
                    </div>
                    <div class="footer">
                        <p>Printed on ${new Date().toLocaleString()}</p>
                        <p>SmartPark Booking System</p>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
    </script>
</body>
</html>
