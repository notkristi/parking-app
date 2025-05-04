<?php
// Include the database connection file
require_once 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add this at the top of your dashboard.php file
$showLoginAnimation = false;
if (isset($_SESSION['fresh_login']) && $_SESSION['fresh_login'] === true) {
    $showLoginAnimation = true;
    $_SESSION['fresh_login'] = false; // Reset so it only shows once
}

// Fetch parking spots data
try {
    // Get total parking spots
    $spotQuery = $conn->query("SELECT COUNT(*) AS TotalSpots FROM parkingspots WHERE IsActive = 1");
    $spotData = $spotQuery->fetch(PDO::FETCH_ASSOC);
    $totalSpots = $spotData['TotalSpots'];
    
    // Get currently occupied spots (spots with bookings ongoing right now)
    $occupiedQuery = $conn->prepare("
        SELECT COUNT(*) AS OccupiedSpots
        FROM bookings b
        WHERE NOW() BETWEEN b.StartTime AND b.EndTime
    ");
    $occupiedQuery->execute();
    $occupiedData = $occupiedQuery->fetch(PDO::FETCH_ASSOC);
    $occupiedSpots = $occupiedData['OccupiedSpots'];
    
    // Calculate available spots and occupancy rate
    $availableSpots = $totalSpots - $occupiedSpots;
    $occupancyRate = ($totalSpots > 0) ? ($occupiedSpots / $totalSpots) * 100 : 0;
    
    // Fetch recent bookings - MODIFIED to only show current user's bookings
    $bookingsQuery = $conn->prepare("
        SELECT b.BookingID, ps.SpotNumber, b.StartTime, b.EndTime, b.BookingStatus
        FROM bookings b
        JOIN parkingspots ps ON b.SpotID = ps.SpotID
        WHERE b.UserID = ?  /* Added this condition to filter by current user */
        ORDER BY b.BookingTime DESC
        LIMIT 5
    ");
    $bookingsQuery->execute([$_SESSION['user_id']]); // Pass the logged-in user's ID
    $recentBookings = $bookingsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch special events
    $eventsQuery = $conn->prepare("
        SELECT EventName, StartTime, EndTime
        FROM specialevents
        WHERE EndTime > NOW()
        ORDER BY StartTime
        LIMIT 3
    ");
    $eventsQuery->execute();
    $specialEvents = $eventsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Get parking spot status for the map
    $spotStatusQuery = $conn->prepare("
        SELECT 
            ps.SpotID, 
            ps.SpotNumber, 
            ps.SpotType,
            SUBSTRING(ps.SpotNumber, 1, 1) AS Section,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM bookings b 
                    WHERE b.SpotID = ps.SpotID 
                    AND b.BookingStatus IN ('Active', 'Pending')
                    AND NOW() BETWEEN b.StartTime AND b.EndTime
                ) THEN 0 
                ELSE 1 
            END AS IsAvailable,
            CASE 
                WHEN ps.SpotType = 'Handicap' THEN 1
                WHEN ps.SpotType = 'Electric' THEN 2
                WHEN ps.SpotType = 'Premium' THEN 3
                ELSE 0
            END AS SpotTypeCode
        FROM parkingspots ps
        WHERE ps.IsActive = 1
        ORDER BY Section, SpotNumber
        LIMIT 64
    ");
    $spotStatusQuery->execute();
    $parkingSpots = $spotStatusQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize spots by section
    $spotsBySection = [];
    foreach ($parkingSpots as $spot) {
        $section = $spot['Section'];
        if (!isset($spotsBySection[$section])) {
            $spotsBySection[$section] = [];
        }
        $spotsBySection[$section][] = $spot;
    }
    
} catch (PDOException $e) {
    // Log the error and display a user-friendly message
    error_log("Database error: " . $e->getMessage());
    $errorMessage = "We're experiencing technical difficulties. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Management System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-light: #34495e;
            --secondary: #3498db;
            --accent: #2980b9;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --gray-light: #ecf0f1;
            --gray-dark: #7f8c8d;
            --black: #1a1a1a;
            --white: #ffffff;
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --border-radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            background-color: #f5f7fa;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Dashboard layout */
        .dashboard {
            padding: 100px 0 50px;
        }
        
        .dashboard-header {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .dashboard-subtitle {
            color: var(--gray-dark);
            font-weight: 400;
            font-size: 16px;
        }
        
        .dashboard-date {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            padding: 8px 16px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--box-shadow);
        }
        
        /* Dashboard grid layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 25px;
        }
        
        .grid-col-3 {
            grid-column: span 3;
        }
        
        .grid-col-4 {
            grid-column: span 4;
        }
        
        .grid-col-5 {
            grid-column: span 5;
        }
        
        .grid-col-6 {
            grid-column: span 6;
        }
        
        .grid-col-7 {
            grid-column: span 7;
        }
        
        .grid-col-8 {
            grid-column: span 8;
        }
        
        .grid-col-9 {
            grid-column: span 9;
        }
        
        .grid-col-12 {
            grid-column: span 12;
        }
        
        /* Cards */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }
        
        .card-body {
            padding: 25px;
            flex: 1;
        }
        
        .card-footer {
            padding: 15px 25px;
            border-top: 1px solid var(--gray-light);
            background-color: var(--white);
        }
        
        /* Stat cards */
        .stat-card {
            display: flex;
            align-items: center;
            padding: 25px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
            margin-right: 20px;
        }
        
        .available .stat-icon {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .occupied .stat-icon {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .percentage .stat-icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .available .stat-value {
            color: var(--success);
        }
        
        .occupied .stat-value {
            color: var(--warning);
        }
        
        .percentage .stat-value {
            color: var(--secondary);
        }
        
        .stat-label {
            color: var(--gray-dark);
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Action buttons */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            padding: 20px;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            text-decoration: none;
            color: var(--primary);
            border: 1px solid var(--gray-light);
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .action-icon {
            width: 45px;
            height: 45px;
            background-color: var(--gray-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 18px;
            margin-right: 15px;
            transition: var(--transition);
        }
        
        .action-btn:hover .action-icon {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .action-text {
            flex: 1;
        }
        
        .action-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 3px;
            color: var(--dark);
        }
        
        .action-desc {
            font-size: 12px;
            color: var(--gray);
        }
        
        /* Bookings table */
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bookings-table th {
            text-align: left;
            padding: 15px;
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .bookings-table td {
            padding: 15px;
            font-size: 14px;
            color: var(--gray-dark);
            border-bottom: 1px solid var(--gray-light);
        }
        
        .bookings-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            min-width: 90px;
        }
        
        .status-active {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .status-upcoming {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
        }
        
        .status-completed {
            background-color: rgba(127, 140, 141, 0.1);
            color: var(--gray-dark);
        }
        
        /* Events */
        .event-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .event-card {
            padding: 15px;
            background-color: var(--white);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        
        .event-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .event-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .event-time {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: var(--gray);
        }
        
        .event-time i {
            margin-right: 8px;
            color: var(--primary);
        }
        
        /* Parking map */
        .parking-map {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 25px;
            position: relative;
            border: 1px solid var(--gray-light);
        }
        
        .map-header {
            margin-bottom: 25px;
        }
        
        .map-title {
            font-weight: 600;
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .map-subtitle {
            font-size: 14px;
            color: var(--gray);
        }
        
        .parking-layout {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
        }
        
        .parking-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            background-color: var(--primary-light);
            color: var(--white);
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .parking-row {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
            position: relative;
        }
        
        .parking-row:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: var(--gray-light);
            z-index: 0;
        }
        
        .parking-spot {
            width: 50px;
            height: 70px;
            margin: 0 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border-radius: 6px;
            position: relative;
            transition: var(--transition);
            z-index: 1;
            text-align: center;
            font-size: 14px;
        }
        
        .parking-spot:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }
        
        .spot-available {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .spot-occupied {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .spot-handicap {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .spot-electric {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .spot-premium {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .spot-type-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 10px;
        }
        
        .road {
            background-color: #34495e;
            color: white;
            text-align: center;
            padding: 5px 0;
            margin: 20px 0;
            border-radius: 4px;
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .map-features {
            position: absolute;
        }
        
        .map-feature {
            position: absolute;
            background-color: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            font-size: 16px;
        }
        
        .feature-entrance {
            top: 30px;
            right: 30px;
            color: var(--success);
        }
        
        .feature-exit {
            bottom: 30px;
            right: 30px;
            color: var(--danger);
        }
        
        .feature-elevator {
            top: 50%;
            right: 80px;
            color: var(--primary);
        }
        
        .feature-info {
            top: 100px;
            left: 40px;
            color: var(--secondary);
        }
        
        .feature-payment {
            bottom: 100px;
            left: 40px;
            color: var(--warning);
        }
        
        .map-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-light);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: var(--gray-dark);
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            margin-right: 8px;
        }
        
        .legend-available {
            background-color: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .legend-occupied {
            background-color: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .legend-handicap {
            background-color: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .legend-electric {
            background-color: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .legend-premium {
            background-color: rgba(243, 156, 18, 0.1);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-outline-primary {
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn i {
            margin-right: 6px;
        }
        
        .text-end {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .empty-state {
            padding: 30px;
            text-align: center;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--gray-light);
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .grid-col-3, .grid-col-4, .grid-col-5 {
                grid-column: span 6;
            }
            
            .grid-col-7, .grid-col-8, .grid-col-9 {
                grid-column: span 12;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .grid-col-3, .grid-col-4, .grid-col-5, .grid-col-6 {
                grid-column: span 12;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .action-btn {
                padding: 15px;
            }
            
            .parking-spot {
                width: 40px;
                height: 60px;
                font-size: 12px;
                margin: 0 3px;
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>
    
    <main class="dashboard">
        <div class="container">
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Parking Management</h1>
                    <p class="dashboard-subtitle">Welcome, <?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?></p>
                </div>
                <div class="dashboard-date">
                    <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <!-- Stats Section -->
                <div class="grid-col-4">
                    <div class="card stat-card available">
                        <div class="stat-icon">
                            <i class="fas fa-parking"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $availableSpots; ?></div>
                            <div class="stat-label">Available Parking Spots</div>
                        </div>
                    </div>
                </div>
                
                <div class="grid-col-4">
                    <div class="card stat-card occupied">
                        <div class="stat-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $occupiedSpots; ?></div>
                            <div class="stat-label">Occupied Parking Spots</div>
                        </div>
                    </div>
                </div>
                
                <div class="grid-col-4">
                    <div class="card stat-card percentage">
                        <div class="stat-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($occupancyRate, 1); ?>%</div>
                            <div class="stat-label">Current Occupancy Rate</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions Section -->
                <div class="grid-col-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Quick Actions</h2>
                        </div>
                        <div class="card-body">
                            <div class="action-grid">
                                <a href="reserve.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div class="action-text">
                                        <h3 class="action-title">New Booking</h3>
                                        <p class="action-desc">Reserve a parking spot</p>
                                    </div>
                                </a>
                                
                                <a href="vehicles.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-car"></i>
                                    </div>
                                    <div class="action-text">
                                        <h3 class="action-title">Vehicles</h3>
                                        <p class="action-desc">Manage your vehicles</p>
                                    </div>
                                </a>
                                
                                <a href="payments.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="action-text">
                                        <h3 class="action-title">Payments</h3>
                                        <p class="action-desc">View payment history</p>
                                    </div>
                                </a>
                                
                                <a href="accesslogs.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <div class="action-text">
                                        <h3 class="action-title">Access Logs</h3>
                                        <p class="action-desc">Track access history</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Special Events Section -->
                <div class="grid-col-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Special Events</h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($specialEvents) > 0): ?>
                                <div class="event-list">
                                    <?php foreach ($specialEvents as $event): ?>
                                        <div class="event-card">
                                            <h3 class="event-title"><?php echo htmlspecialchars($event['EventName']); ?></h3>
                                            <div class="event-time">
                                                <i class="far fa-clock"></i>
                                                <?php 
                                                    echo date('M d, h:i A', strtotime($event['StartTime'])); 
                                                    echo ' - ';
                                                    echo date('M d, h:i A', strtotime($event['EndTime']));
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="far fa-calendar"></i>
                                    <p>No upcoming events scheduled</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <a href="events.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-calendar-alt"></i> View All Events
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings Section -->
                <div class="grid-col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Recent Bookings</h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentBookings) > 0): ?>
                                <div class="table-responsive">
                                    <table class="bookings-table">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Spot</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentBookings as $booking): ?>
                                                <tr>
                                                    <td>#<?php echo $booking['BookingID']; ?></td>
                                                    <td><?php echo htmlspecialchars($booking['SpotNumber']); ?></td>
                                                    <td><?php echo date('M d, h:i A', strtotime($booking['StartTime'])); ?></td>
                                                    <td><?php echo date('M d, h:i A', strtotime($booking['EndTime'])); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php 
                                                            echo $booking['BookingStatus'] == 'Active' ? 'status-active' : 
                                                                ($booking['BookingStatus'] == 'Upcoming' ? 'status-upcoming' : 'status-completed'); 
                                                        ?>">
                                                            <?php echo $booking['BookingStatus']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="far fa-calendar-check"></i>
                                    <p>No recent bookings found</p>
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
                
                <!-- Parking Map Section -->
                <div class="grid-col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Parking Map</h2>
                        </div>
                        <div class="card-body">
                            <div class="parking-map">
                                <!-- Map features -->
                                <div class="map-features">
                                    <div class="map-feature feature-entrance" title="Entrance">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <div class="map-feature feature-exit" title="Exit">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </div>
                                    <div class="map-feature feature-elevator" title="Elevator">
                                        <i class="fas fa-elevator"></i>
                                    </div>
                                    <div class="map-feature feature-info" title="Information Kiosk">
                                        <i class="fas fa-info"></i>
                                    </div>
                                    <div class="map-feature feature-payment" title="Payment Machine">
                                        <i class="fas fa-money-bill"></i>
                                    </div>
                                </div>
                                
                                <div class="parking-layout">
                                    <!-- Main road at top -->
                                    <div class="road">Main Entry Road</div>
                                    
                                    <!-- Display parking by sections -->
                                    <?php foreach($spotsBySection as $section => $spots): ?>
                                        <div class="parking-section">
                                            <div class="section-title">
                                                Section <?php echo $section; ?>
                                            </div>
                                            
                                            <!-- Split spots into rows of 8 -->
                                            <?php 
                                            $spotRows = array_chunk($spots, 8);
                                            foreach($spotRows as $rowSpots): 
                                            ?>
                                                <div class="parking-row">
                                                    <?php foreach($rowSpots as $spot): 
                                                        // Determine spot classes and icons
                                                        $spotClass = $spot['IsAvailable'] ? 'spot-available' : 'spot-occupied';
                                                        $spotIcon = '';
                                                        $spotClass = $spot['IsAvailable'] ? 'spot-available' : 'spot-occupied';
                                                    if ($spot['SpotTypeCode'] == 1) {
                                                        $spotClass .= ' spot-handicap';
                                                        $spotIcon = '<span class="spot-type-icon">♿</span>';
                                                    } elseif ($spot['SpotTypeCode'] == 2) {
                                                        $spotClass .= ' spot-electric';
                                                        $spotIcon = '<span class="spot-type-icon">⚡</span>';
                                                    } elseif ($spot['SpotTypeCode'] == 3) {
                                                        $spotClass .= ' spot-premium';
                                                        $spotIcon = '<span class="spot-type-icon">⭐</span>';
                                                    }
                                                ?>
                                                    <div class="parking-spot <?php echo $spotClass; ?>" 
                                                         title="<?php echo $spot['IsAvailable'] ? 'Available' : 'Occupied'; ?>">
                                                        <?php echo htmlspecialchars($spot['SpotNumber']); ?>
                                                        <?php echo $spotIcon; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Add pedestrian path if not the last section -->
                                        <?php if ($section !== array_key_last($spotsBySection)): ?>
                                            <div class="pedestrian-path"></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Bottom road -->
                                <div class="road">Exit Road</div>
                                
                                <!-- Map legend -->
                                <div class="map-legend">
                                    <div class="legend-item">
                                        <span class="legend-color legend-available"></span> Available
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color legend-occupied"></span> Occupied
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color legend-handicap"></span> Handicap
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color legend-electric"></span> Electric Vehicle
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color legend-premium"></span> Premium
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="map.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-map-marked-alt"></i> View Full Map
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include footer -->
    <?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>

<!-- Login animation -->
<?php if ($showLoginAnimation): ?>
<div class="login-animation-overlay" id="loginAnimation">
    <div class="login-animation-content">
        <div class="welcome-message">Welcome, <span class="user-name"><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?></span>!</div>
        <div class="animation-icon">
            <i class="fas fa-check-circle"></i>
        </div>
    </div>
</div>

<style>
    .login-animation-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #2c3e50, #34495e);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: fadeOut 0.5s ease 2.5s forwards;
    }
    
    .login-animation-content {
        text-align: center;
        color: white;
        animation: fadeInUp 0.8s ease forwards;
    }
    
    .welcome-message {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 2rem;
        font-family: 'Playfair Display', serif;
    }
    
    .user-name {
        color: #3498db;
    }
    
    .animation-icon {
        font-size: 5rem;
        color: #3498db;
        animation: pulse 2s infinite;
    }
    
    @keyframes fadeInUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeOut {
        0% {
            opacity: 1;
        }
        100% {
            opacity: 0;
            visibility: hidden;
        }
    }
    
    @keyframes pulse {
        0% {
            transform: scale(0.9);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(0.9);
        }
    }
</style>

<script>
    // Remove the animation overlay after it fades out
    setTimeout(function() {
        const element = document.getElementById('loginAnimation');
        if (element) element.remove();
    }, 3000);
</script>
<?php endif; ?>
</body>
</html>