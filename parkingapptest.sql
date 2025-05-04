-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2025 at 09:54 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parkingapptest`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateBooking` (IN `p_UserID` INT, IN `p_VehicleID` INT, IN `p_SpotID` INT, IN `p_StartTime` DATETIME, IN `p_EndTime` DATETIME, OUT `p_BookingID` INT)   BEGIN
    DECLARE spot_available BOOLEAN;
    DECLARE rate_id INT;
    DECLARE total_hours DECIMAL(10, 2);
    DECLARE calc_cost DECIMAL(10, 2);
    
    -- Check if spot is available
    SET spot_available = IsSpotAvailable(p_SpotID, p_StartTime, p_EndTime);
    
    IF spot_available THEN
        -- Get the active rate
        SELECT RateID INTO rate_id
        FROM PricingRates
        WHERE IsActive = TRUE
        ORDER BY EffectiveFrom DESC
        LIMIT 1;
        
        -- Calculate hours and cost
        SET total_hours = TIMESTAMPDIFF(HOUR, p_StartTime, p_EndTime);
        
        -- For simplicity, just using hourly rate
        SELECT HourlyRate * total_hours INTO calc_cost
        FROM PricingRates
        WHERE RateID = rate_id;
        
        -- Create the booking
        INSERT INTO Bookings (UserID, VehicleID, SpotID, RateID, StartTime, EndTime, TotalCost, BookingStatus)
        VALUES (p_UserID, p_VehicleID, p_SpotID, rate_id, p_StartTime, p_EndTime, calc_cost, 'Pending');
        
        SET p_BookingID = LAST_INSERT_ID();
    ELSE
        SET p_BookingID = 0; -- Indicates failure
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `FindAvailableSpots` (IN `start_datetime` DATETIME, IN `end_datetime` DATETIME)   BEGIN
    SELECT ps.SpotID, ps.SpotNumber, ps.SpotType
    FROM ParkingSpots ps
    WHERE ps.IsActive = TRUE
    AND NOT EXISTS (
        SELECT 1
        FROM Bookings b
        WHERE b.SpotID = ps.SpotID
        AND b.BookingStatus IN ('Pending', 'Confirmed', 'Checked-in')
        AND (
            (start_datetime BETWEEN b.StartTime AND b.EndTime) OR
            (end_datetime BETWEEN b.StartTime AND b.EndTime) OR
            (b.StartTime BETWEEN start_datetime AND end_datetime)
        )
    );
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `IsSpotAvailable` (`spot_id` INT, `start_datetime` DATETIME, `end_datetime` DATETIME) RETURNS TINYINT(1)  BEGIN
    DECLARE is_available BOOLEAN;
    
    SELECT COUNT(*) = 0 INTO is_available
    FROM Bookings
    WHERE SpotID = spot_id
    AND BookingStatus IN ('Pending', 'Confirmed', 'Checked-in')
    AND (
        (start_datetime BETWEEN StartTime AND EndTime) OR
        (end_datetime BETWEEN StartTime AND EndTime) OR
        (StartTime BETWEEN start_datetime AND end_datetime)
    );
    
    RETURN is_available;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accesslogs`
--

CREATE TABLE `accesslogs` (
  `LogID` int(11) NOT NULL,
  `BookingID` int(11) NOT NULL,
  `EventType` enum('Entry','Exit') NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accesslogs`
--

INSERT INTO `accesslogs` (`LogID`, `BookingID`, `EventType`, `Timestamp`) VALUES
(1, 1, 'Entry', '2025-03-10 08:05:00'),
(2, 1, 'Exit', '2025-03-10 12:10:00'),
(3, 2, 'Entry', '2025-03-11 09:10:00'),
(4, 2, 'Exit', '2025-03-11 15:05:00'),
(5, 3, 'Entry', '2025-03-12 10:03:00'),
(6, 3, 'Exit', '2025-03-12 14:12:00'),
(7, 4, 'Entry', '2025-03-13 08:07:00'),
(8, 4, 'Exit', '2025-03-13 17:03:00'),
(9, 5, 'Entry', '2025-03-14 07:08:00'),
(10, 5, 'Exit', '2025-03-14 19:02:00'),
(11, 6, 'Entry', '2025-03-21 07:04:00'),
(12, 7, 'Entry', '2025-03-21 08:33:00'),
(13, 8, 'Entry', '2025-03-21 09:05:00');

--
-- Triggers `accesslogs`
--
DELIMITER $$
CREATE TRIGGER `after_access_log` AFTER INSERT ON `accesslogs` FOR EACH ROW BEGIN
    IF NEW.EventType = 'Entry' THEN
        UPDATE Bookings
        SET BookingStatus = 'Checked-in'
        WHERE BookingID = NEW.BookingID
        AND BookingStatus = 'Confirmed';
    ELSEIF NEW.EventType = 'Exit' THEN
        UPDATE Bookings
        SET BookingStatus = 'Completed'
        WHERE BookingID = NEW.BookingID
        AND BookingStatus = 'Checked-in';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `availablespots`
-- (See below for the actual view)
--
CREATE TABLE `availablespots` (
`SpotID` int(11)
,`SpotNumber` varchar(10)
,`SpotType` enum('Standard','Handicap','Electric','Compact')
);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `SpotID` int(11) NOT NULL,
  `RateID` int(11) NOT NULL,
  `StartTime` datetime NOT NULL,
  `EndTime` datetime NOT NULL,
  `TotalCost` decimal(10,2) NOT NULL,
  `BookingStatus` enum('Pending','Confirmed','Checked-in','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `PaymentStatus` enum('Unpaid','Paid','Refunded') NOT NULL DEFAULT 'Unpaid',
  `BookingTime` datetime NOT NULL DEFAULT current_timestamp(),
  `Notes` text DEFAULT NULL
) ;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`BookingID`, `UserID`, `VehicleID`, `SpotID`, `RateID`, `StartTime`, `EndTime`, `TotalCost`, `BookingStatus`, `PaymentStatus`, `BookingTime`, `Notes`) VALUES
(1, 1, 1, 1, 1, '2025-03-10 08:00:00', '2025-03-10 12:00:00', 10.00, 'Completed', 'Paid', '2025-03-09 15:30:00', 'Regular customer'),
(2, 2, 3, 5, 1, '2025-03-11 09:00:00', '2025-03-11 15:00:00', 15.00, 'Completed', 'Paid', '2025-03-10 18:45:00', NULL),
(3, 3, 4, 8, 1, '2025-03-12 10:00:00', '2025-03-12 14:00:00', 10.00, 'Completed', 'Paid', '2025-03-11 20:15:00', NULL),
(4, 4, 5, 12, 2, '2025-03-13 08:00:00', '2025-03-13 17:00:00', 31.50, 'Completed', 'Paid', '2025-03-12 09:30:00', 'Weekend rate applied'),
(5, 5, 6, 15, 1, '2025-03-14 07:00:00', '2025-03-14 19:00:00', 30.00, 'Completed', 'Paid', '2025-03-13 14:20:00', NULL),
(6, 6, 7, 2, 3, '2025-03-21 07:00:00', '2025-03-21 19:00:00', 24.00, 'Checked-in', 'Paid', '2025-03-20 10:00:00', 'Electric vehicle discount'),
(7, 7, 8, 7, 1, '2025-03-21 08:30:00', '2025-03-21 16:30:00', 20.00, 'Checked-in', 'Paid', '2025-03-20 16:45:00', NULL),
(8, 8, 9, 10, 1, '2025-03-21 09:00:00', '2025-03-22 09:00:00', 20.00, 'Checked-in', 'Paid', '2025-03-20 19:30:00', 'Daily rate applied'),
(9, 9, 10, 20, 1, '2025-03-21 10:30:00', '2025-03-21 15:30:00', 12.50, 'Confirmed', 'Paid', '2025-03-21 07:15:00', 'Arriving soon'),
(10, 10, 11, 25, 1, '2025-03-21 12:00:00', '2025-03-21 18:00:00', 15.00, 'Confirmed', 'Paid', '2025-03-21 08:30:00', NULL),
(11, 1, 2, 3, 1, '2025-03-22 09:00:00', '2025-03-22 17:00:00', 20.00, 'Confirmed', 'Paid', '2025-03-20 12:00:00', NULL),
(12, 2, 12, 6, 2, '2025-03-23 10:00:00', '2025-03-23 16:00:00', 21.00, 'Confirmed', 'Paid', '2025-03-21 09:45:00', 'Weekend rate'),
(13, 3, 4, 9, 1, '2025-03-24 08:30:00', '2025-03-24 14:30:00', 15.00, 'Confirmed', 'Paid', '2025-03-21 11:20:00', NULL),
(14, 4, 5, 13, 1, '2025-03-25 07:45:00', '2025-03-25 19:45:00', 30.00, 'Confirmed', 'Paid', '2025-03-21 13:00:00', NULL),
(15, 5, 6, 16, 4, '2025-04-01 00:00:00', '2025-04-30 23:59:59', 250.00, 'Confirmed', 'Paid', '2025-03-21 14:30:00', 'Monthly subscription'),
(16, 11, 13, 1, 1, '2025-03-25 18:45:00', '2025-03-26 19:45:00', 62.50, '', 'Paid', '2025-03-24 03:49:44', NULL),
(17, 11, 14, 1, 1, '2025-03-24 04:01:00', '2025-03-24 04:05:00', 0.00, '', 'Paid', '2025-03-24 04:00:09', NULL),
(18, 12, 15, 14, 1, '2025-03-25 20:30:00', '2025-03-26 10:30:00', 35.00, '', 'Paid', '2025-03-24 04:31:55', NULL),
(19, 12, 16, 9, 1, '2025-04-02 10:15:00', '2025-04-03 11:15:00', 62.50, '', 'Paid', '2025-03-24 05:15:05', NULL),
(20, 12, 17, 14, 1, '2025-03-28 09:30:00', '2025-03-31 10:30:00', 182.50, '', 'Paid', '2025-03-24 06:27:10', NULL),
(21, 12, 17, 5, 1, '2025-03-25 14:28:00', '2025-03-26 14:29:00', 60.00, '', 'Paid', '2025-03-24 14:28:05', NULL),
(22, 14, 18, 2, 1, '2025-03-27 20:50:00', '2025-03-30 11:50:00', 155.00, '', 'Paid', '2025-03-25 18:49:30', NULL),
(23, 15, 19, 3, 1, '2025-03-27 17:30:00', '2025-03-28 17:30:00', 60.00, '', 'Paid', '2025-03-26 15:34:17', NULL),
(24, 11, 13, 1, 1, '2025-03-28 20:30:00', '2025-03-29 21:30:00', 62.50, '', 'Paid', '2025-03-27 17:28:07', NULL),
(25, 11, 13, 40, 1, '2025-04-11 16:50:00', '2025-04-12 17:50:00', 62.50, '', 'Paid', '2025-04-09 14:52:14', NULL),
(26, 11, 13, 6, 1, '2025-05-01 14:20:00', '2025-05-02 15:20:00', 62.50, '', 'Paid', '2025-04-30 23:20:47', NULL),
(27, 11, 14, 1, 1, '2025-05-06 23:23:00', '2025-05-08 23:25:00', 120.00, '', 'Paid', '2025-04-30 23:21:40', NULL),
(28, 12, 16, 6, 1, '2025-05-01 14:20:00', '2025-05-02 15:20:00', 62.50, '', 'Paid', '2025-04-30 23:23:13', NULL),
(29, 12, 15, 1, 1, '2025-05-07 20:30:00', '2025-05-08 20:30:00', 60.00, '', 'Paid', '2025-05-03 19:31:57', NULL),
(30, 11, 13, 1, 1, '2025-05-07 20:30:00', '2025-05-08 20:30:00', 60.00, '', 'Paid', '2025-05-03 19:34:33', NULL),
(31, 11, 13, 1, 1, '2025-05-07 20:30:00', '2025-05-08 20:30:00', 60.00, '', 'Paid', '2025-05-03 19:38:03', NULL),
(32, 11, 14, 1, 1, '2025-05-07 20:30:00', '2025-05-08 20:30:00', 60.00, '', 'Paid', '2025-05-03 19:40:32', NULL),
(33, 11, 14, 2, 1, '2025-05-30 22:52:00', '2025-05-31 22:54:00', 60.00, 'Pending', '', '2025-05-03 20:51:33', NULL),
(34, 11, 13, 1, 1, '2025-05-03 21:10:00', '2025-05-03 21:15:00', 0.00, 'Pending', '', '2025-05-03 21:06:41', NULL),
(35, 11, 13, 1, 1, '2025-05-03 22:24:00', '2025-05-03 22:26:00', 0.00, '', '', '2025-05-03 22:22:46', NULL),
(36, 11, 14, 29, 1, '2025-05-03 22:31:00', '2025-05-03 22:32:00', 0.00, '', '', '2025-05-03 22:29:30', NULL),
(37, 1, 2, 18, 1, '2025-05-04 00:30:00', '2025-05-04 00:40:00', 0.00, 'Pending', '', '2025-05-04 00:26:24', NULL),
(38, 12, 16, 1, 1, '2025-05-04 00:43:00', '2025-05-05 01:00:00', 60.00, '', '', '2025-05-04 00:42:10', NULL),
(39, 11, 13, 2, 1, '2025-05-08 16:30:00', '2025-05-09 16:35:00', 60.00, '', '', '2025-05-04 15:23:36', NULL),
(40, 12, 15, 3, 1, '2025-05-07 18:31:00', '2025-05-09 19:30:00', 120.00, '', '', '2025-05-04 16:25:16', NULL),
(41, 12, 15, 4, 1, '2025-05-07 17:35:00', '2025-05-08 18:35:00', 62.50, '', '', '2025-05-04 16:31:38', NULL),
(42, 12, 15, 5, 1, '2025-05-07 18:50:00', '2025-05-08 19:50:00', 62.50, '', '', '2025-05-04 16:48:56', NULL),
(43, 11, 13, 1, 1, '2025-05-12 19:30:00', '2025-05-13 19:35:00', 60.00, 'Pending', '', '2025-05-04 17:27:49', NULL),
(44, 11, 13, 2, 1, '2025-05-12 19:30:00', '2025-05-13 20:30:00', 62.50, '', '', '2025-05-04 17:32:40', NULL),
(45, 11, 13, 3, 1, '2025-05-13 14:22:00', '2025-05-14 19:38:00', 72.50, '', '', '2025-05-04 17:36:34', NULL),
(46, 11, 14, 1, 1, '2025-05-05 18:39:00', '2025-05-06 19:40:00', 62.50, '', '', '2025-05-04 17:38:53', NULL),
(47, 11, 13, 5, 1, '2025-05-13 17:43:00', '2025-05-13 19:44:00', 5.00, '', '', '2025-05-04 17:42:49', NULL),
(48, 12, 16, 2, 1, '2025-05-06 19:58:00', '2025-05-07 17:02:00', 52.50, '', '', '2025-05-04 17:59:00', NULL),
(49, 16, 20, 1, 1, '2025-05-09 19:45:00', '2025-05-10 20:45:00', 62.50, '', '', '2025-05-04 18:43:13', NULL),
(50, 17, 21, 2, 1, '2025-05-05 21:00:00', '2025-05-05 23:00:00', 5.00, '', '', '2025-05-04 19:54:52', NULL),
(51, 17, 21, 6, 1, '2025-05-07 22:00:00', '2025-05-08 22:00:00', 60.00, '', '', '2025-05-04 19:55:46', NULL),
(52, 18, 22, 2, 1, '2025-05-09 22:55:00', '2025-05-10 23:55:00', 62.50, '', '', '2025-05-04 21:52:40', NULL),
(53, 18, 22, 2, 1, '2025-05-04 21:58:00', '2025-05-04 22:00:00', 0.00, '', '', '2025-05-04 21:57:27', NULL),
(54, 12, 15, 3, 1, '2025-05-04 22:04:00', '2025-05-06 22:05:00', 120.00, '', '', '2025-05-04 22:02:29', NULL),
(55, 12, 15, 2, 1, '2025-05-04 22:08:00', '2025-05-04 23:08:00', 2.50, '', '', '2025-05-04 22:07:05', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `currentoccupancy`
-- (See below for the actual view)
--
CREATE TABLE `currentoccupancy` (
`OccupiedSpots` bigint(21)
,`TotalSpots` bigint(21)
,`OccupancyPercentage` decimal(28,5)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `BookingID` int(11) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `SentAt` datetime DEFAULT current_timestamp(),
  `ReadAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`NotificationID`, `UserID`, `BookingID`, `Type`, `Message`, `IsRead`, `SentAt`, `ReadAt`) VALUES
(6, 12, 18, 'reservation_start', 'Your parking reservation for spot A12 has started. Your spot is now available for use.', 1, '2025-05-03 21:25:19', '2025-05-04 16:29:22'),
(7, 12, 19, 'reservation_end', 'Your parking reservation for spot A12 is ending in 15 minutes. Please ensure you vacate the spot to avoid additional charges.', 1, '2025-05-03 19:25:19', '2025-05-04 16:29:22'),
(8, 12, 20, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: B05, Date: Mon, Jan 15, Time: 9:00 AM-11:00 AM. Booking ID: 46.', 1, '2025-05-02 21:25:19', NULL),
(9, 12, 21, 'payment_received', 'Payment of $15.00 has been successfully processed for your parking reservation (Booking ID: 47).', 1, '2025-05-03 17:25:19', '2025-05-04 16:29:22'),
(10, 11, 33, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A2, Date: Fri, May 30, Time: 10:52 PM-10:54 PM. Booking ID: 33.', 1, '2025-05-03 21:31:14', '2025-05-03 21:34:05'),
(11, 11, 33, 'reservation_start', 'Your parking reservation for spot A2 has started. Your spot is now available for use.', 1, '2025-05-03 21:33:49', '2025-05-03 21:34:05'),
(12, 11, 33, 'reservation_start', 'Your parking reservation for spot A2 has started. Your spot is now available for use.', 1, '2025-05-03 21:34:22', '2025-05-03 22:04:15'),
(13, 11, 33, 'reservation_start', 'Your parking reservation for spot A2 has started. Your spot is now available for use.', 1, '2025-05-03 22:03:29', '2025-05-03 22:04:15'),
(14, 11, 35, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A1, Date: Sat, May 3, Time: 10:24 PM-10:26 PM. Booking ID: 35.', 1, '2025-05-03 22:27:25', '2025-05-03 22:27:52'),
(15, 11, 36, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: C9, Date: Sat, May 3, Time: 10:31 PM-10:32 PM. Booking ID: 36.', 1, '2025-05-03 22:29:33', '2025-05-03 22:31:35'),
(16, 1, 37, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: B8, Date: Sun, May 4, Time: 12:30 AM-12:40 AM. Booking ID: 37.', 1, '2025-05-04 00:26:29', '2025-05-04 00:40:31'),
(17, 12, 38, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A1, Date: Sun, May 4, Time: 12:43 AM-1:00 AM. Booking ID: 38.', 1, '2025-05-04 00:42:13', '2025-05-04 16:29:22'),
(18, 11, 39, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A2, Date: Thu, May 8, Time: 4:30 PM-4:35 PM. Booking ID: 39.', 1, '2025-05-04 15:23:39', '2025-05-04 17:39:35'),
(19, 11, 33, 'reservation_start', 'Your parking reservation has started. Your spot is now available for use.', 1, '2025-05-04 16:21:41', '2025-05-04 17:39:35'),
(20, 11, 33, 'reservation_start', 'Your parking reservation has started. Your spot is now available for use.', 1, '2025-05-04 16:22:16', '2025-05-04 17:39:35'),
(21, 12, 38, 'reservation_start', 'Your parking reservation has started. Your spot is now available for use.', 1, '2025-05-04 16:23:49', '2025-05-04 16:29:22'),
(22, 12, 40, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A3, Date: Wed, May 7, Time: 6:31 PM-7:30 PM. Booking ID: 40.', 1, '2025-05-04 16:25:19', '2025-05-04 16:29:22'),
(23, 12, 40, 'payment_received', 'Your payment has been received. Thank you for your business.', 1, '2025-05-04 16:30:00', '2025-05-04 17:14:23'),
(24, 12, 41, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A4, Date: Wed, May 7, Time: 5:35 PM-6:35 PM. Booking ID: 41.', 1, '2025-05-04 16:31:40', '2025-05-04 17:14:23'),
(25, 12, 42, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A5, Date: Wed, May 7, Time: 6:50 PM-7:50 PM. Booking ID: 42.', 1, '2025-05-04 16:48:58', '2025-05-04 17:14:23'),
(26, 11, 43, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A1, Date: Mon, May 12, Time: 7:30 PM-7:35 PM. Booking ID: 43.', 1, '2025-05-04 17:27:52', '2025-05-04 17:39:35'),
(27, 11, 44, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A2, Date: Mon, May 12, Time: 7:30 PM-8:30 PM. Booking ID: 44.', 1, '2025-05-04 17:32:43', '2025-05-04 17:39:35'),
(28, 11, 45, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A3, Date: Tue, May 13, Time: 2:22 PM-7:38 PM. Booking ID: 45.', 1, '2025-05-04 17:36:39', '2025-05-04 17:39:35'),
(29, 11, 46, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A1, Date: Mon, May 5, Time: 6:39 PM-7:40 PM. Booking ID: 46.', 1, '2025-05-04 17:38:55', '2025-05-04 17:39:35'),
(30, 11, 47, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A5, Date: Tue, May 13, Time: 5:43 PM-7:44 PM. Booking ID: 47.', 1, '2025-05-04 17:42:51', '2025-05-04 17:43:04'),
(31, 12, 48, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A2, Date: Tue, May 6, Time: 7:58 PM-5:02 PM. Booking ID: 48.', 1, '2025-05-04 17:59:02', '2025-05-04 17:59:09'),
(32, 16, 49, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A1, Date: Fri, May 9, Time: 7:45 PM-8:45 PM. Booking ID: 49.', 1, '2025-05-04 18:43:15', '2025-05-04 19:41:08'),
(33, 17, 51, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A6, Date: Wed, May 7, Time: 10:00 PM-10:00 PM. Booking ID: 51.', 1, '2025-05-04 19:55:48', '2025-05-04 19:57:22'),
(34, 18, 52, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A2, Date: Fri, May 9, Time: 10:55 PM-11:55 PM. Booking ID: 52.', 1, '2025-05-04 21:52:48', '2025-05-04 21:55:36'),
(35, 18, 53, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A2, Date: Sun, May 4, Time: 9:58 PM-10:00 PM. Booking ID: 53.', 1, '2025-05-04 21:57:29', '2025-05-04 21:57:34'),
(36, 12, 54, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A3, Date: Sun, May 4, Time: 10:04 PM-10:05 PM. Booking ID: 54.', 1, '2025-05-04 22:02:31', '2025-05-04 22:03:02'),
(37, 12, 55, 'booking_confirmation', 'Your parking reservation is confirmed! Spot: A2, Date: Sun, May 4, Time: 10:08 PM-11:08 PM. Booking ID: 55.', 1, '2025-05-04 22:07:07', '2025-05-04 22:12:39');

-- --------------------------------------------------------

--
-- Table structure for table `parkingspots`
--

CREATE TABLE `parkingspots` (
  `SpotID` int(11) NOT NULL,
  `SpotNumber` varchar(10) NOT NULL,
  `SpotType` enum('Standard','Handicap','Electric','Compact') NOT NULL DEFAULT 'Standard',
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parkingspots`
--

INSERT INTO `parkingspots` (`SpotID`, `SpotNumber`, `SpotType`, `IsActive`, `Notes`) VALUES
(1, 'A1', 'Standard', 1, NULL),
(2, 'A2', 'Standard', 1, NULL),
(3, 'A3', 'Standard', 1, NULL),
(4, 'A4', 'Standard', 1, NULL),
(5, 'A5', 'Handicap', 1, NULL),
(6, 'A6', 'Standard', 1, NULL),
(7, 'A7', 'Standard', 1, NULL),
(8, 'A8', 'Standard', 1, NULL),
(9, 'A9', 'Standard', 1, NULL),
(10, 'A10', 'Standard', 1, NULL),
(11, 'B1', 'Standard', 1, NULL),
(12, 'B2', 'Electric', 1, NULL),
(13, 'B3', 'Standard', 1, NULL),
(14, 'B4', 'Standard', 1, NULL),
(15, 'B5', 'Standard', 1, NULL),
(16, 'B6', 'Standard', 1, NULL),
(17, 'B7', 'Standard', 1, NULL),
(18, 'B8', 'Standard', 1, NULL),
(19, 'B9', 'Handicap', 1, NULL),
(20, 'B10', 'Standard', 1, NULL),
(21, 'C1', 'Standard', 1, NULL),
(22, 'C2', 'Standard', 1, NULL),
(23, 'C3', 'Standard', 1, NULL),
(24, 'C4', 'Electric', 1, NULL),
(25, 'C5', 'Standard', 1, NULL),
(26, 'C6', 'Standard', 1, NULL),
(27, 'C7', 'Standard', 1, NULL),
(28, 'C8', 'Standard', 1, NULL),
(29, 'C9', 'Standard', 1, NULL),
(30, 'C10', 'Standard', 1, NULL),
(31, 'D1', 'Compact', 1, NULL),
(32, 'D2', 'Compact', 1, NULL),
(33, 'D3', 'Compact', 1, NULL),
(34, 'D4', 'Standard', 1, NULL),
(35, 'D5', 'Standard', 1, NULL),
(36, 'D6', 'Standard', 1, NULL),
(37, 'D7', 'Standard', 1, NULL),
(38, 'D8', 'Standard', 1, NULL),
(39, 'D9', 'Standard', 1, NULL),
(40, 'D10', 'Standard', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PaymentID` int(11) NOT NULL,
  `BookingID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `PaymentMethod` enum('Credit Card','Debit Card','Cash','Online Transfer','Mobile Payment') NOT NULL,
  `TransactionID` varchar(100) DEFAULT NULL,
  `PaymentTime` datetime NOT NULL DEFAULT current_timestamp(),
  `PaymentStatus` enum('Pending','Completed','Failed','Refunded') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`PaymentID`, `BookingID`, `Amount`, `PaymentMethod`, `TransactionID`, `PaymentTime`, `PaymentStatus`) VALUES
(2, 2, 15.00, 'Debit Card', 'TXN234567890', '2025-03-10 18:50:00', 'Completed'),
(3, 3, 10.00, 'Mobile Payment', 'TXN345678901', '2025-03-11 20:20:00', 'Completed'),
(4, 4, 31.50, 'Credit Card', 'TXN456789012', '2025-03-12 09:35:00', 'Completed'),
(5, 5, 30.00, 'Online Transfer', 'TXN567890123', '2025-03-13 14:25:00', 'Completed'),
(6, 6, 24.00, 'Mobile Payment', 'TXN678901234', '2025-03-20 10:05:00', 'Completed'),
(7, 7, 20.00, 'Credit Card', 'TXN789012345', '2025-03-20 16:50:00', 'Completed'),
(8, 8, 20.00, 'Debit Card', 'TXN890123456', '2025-03-20 19:35:00', 'Completed'),
(9, 9, 12.50, 'Mobile Payment', 'TXN901234567', '2025-03-21 07:20:00', 'Completed'),
(10, 10, 15.00, 'Credit Card', 'TXN012345678', '2025-03-21 08:35:00', 'Completed'),
(11, 11, 20.00, 'Online Transfer', 'TXN123456780', '2025-03-20 12:05:00', 'Completed'),
(12, 12, 21.00, 'Mobile Payment', 'TXN234567801', '2025-03-21 09:50:00', 'Completed'),
(13, 13, 15.00, 'Credit Card', 'TXN345678012', '2025-03-21 11:25:00', 'Completed'),
(14, 14, 30.00, 'Debit Card', 'TXN456780123', '2025-03-21 13:05:00', 'Completed'),
(15, 15, 250.00, 'Credit Card', 'TXN567801234', '2025-03-21 14:35:00', 'Completed'),
(16, 16, 62.50, 'Credit Card', 'TXN17427809874047', '2025-03-24 03:49:47', 'Completed'),
(17, 17, 0.00, '', 'TXN17427816131896', '2025-03-24 04:00:13', 'Completed'),
(18, 18, 35.00, 'Credit Card', 'TXN17427835189573', '2025-03-24 04:31:58', 'Completed'),
(19, 19, 62.50, 'Cash', 'TXN17427861107818', '2025-03-24 05:15:10', 'Completed'),
(20, 20, 182.50, 'Credit Card', 'TXN17427904338586', '2025-03-24 06:27:13', 'Completed'),
(21, 21, 60.00, 'Credit Card', 'TXN17428192881784', '2025-03-24 14:28:08', 'Completed'),
(22, 22, 155.00, '', 'TXN17429213805534', '2025-03-25 18:49:40', 'Completed'),
(23, 23, 60.00, 'Cash', 'TXN17429960623089', '2025-03-26 15:34:22', 'Completed'),
(24, 24, 62.50, 'Credit Card', 'TXN17430892933779', '2025-03-27 17:28:13', 'Completed'),
(25, 25, 62.50, 'Cash', 'TXN17441995372819', '2025-04-09 14:52:17', 'Completed'),
(26, 26, 62.50, 'Credit Card', 'TXN17460444523905', '2025-04-30 23:20:52', 'Completed'),
(27, 27, 120.00, 'Credit Card', 'TXN17460445023666', '2025-04-30 23:21:42', 'Completed'),
(28, 28, 62.50, 'Credit Card', 'TXN17460445969935', '2025-04-30 23:23:16', 'Completed'),
(29, 29, 60.00, 'Credit Card', 'TXN17462899218588', '2025-05-03 19:32:01', 'Completed'),
(30, 30, 60.00, 'Credit Card', 'TXN17462900757423', '2025-05-03 19:34:35', 'Completed'),
(31, 31, 60.00, 'Credit Card', 'TXN17462902861707', '2025-05-03 19:38:06', 'Completed'),
(32, 32, 60.00, 'Credit Card', 'TXN17462904344469', '2025-05-03 19:40:34', 'Completed'),
(33, 33, 60.00, 'Cash', 'TXN17462946962808', '2025-05-03 20:51:36', 'Pending'),
(34, 34, 0.00, 'Cash', 'TXN17462956044873', '2025-05-03 21:06:44', 'Pending'),
(35, 35, 0.00, 'Credit Card', 'TXN17463001702897', '2025-05-03 22:22:50', 'Completed'),
(36, 36, 0.00, 'Credit Card', 'TXN17463005737927', '2025-05-03 22:29:33', 'Completed'),
(37, 37, 0.00, 'Cash', 'TXN17463075896628', '2025-05-04 00:26:29', 'Pending'),
(38, 38, 60.00, 'Credit Card', 'TXN17463085333219', '2025-05-04 00:42:13', 'Completed'),
(39, 39, 60.00, 'Credit Card', 'TXN17463614191504', '2025-05-04 15:23:39', 'Completed'),
(40, 40, 120.00, 'Credit Card', 'TXN17463651194943', '2025-05-04 16:25:19', 'Completed'),
(41, 41, 62.50, 'Credit Card', 'TXN17463655008238', '2025-05-04 16:31:40', 'Completed'),
(42, 42, 62.50, 'Credit Card', 'TXN17463665372252', '2025-05-04 16:48:57', 'Completed'),
(43, 43, 60.00, 'Cash', 'TXN17463688718862', '2025-05-04 17:27:51', 'Pending'),
(44, 44, 62.50, 'Credit Card', 'TXN17463691626277', '2025-05-04 17:32:42', 'Completed'),
(45, 45, 72.50, 'Credit Card', 'TXN17463693999424', '2025-05-04 17:36:39', 'Completed'),
(46, 46, 62.50, 'Credit Card', 'TXN17463695357436', '2025-05-04 17:38:55', 'Completed'),
(47, 47, 5.00, 'Credit Card', 'TXN17463697715630', '2025-05-04 17:42:51', 'Completed'),
(48, 48, 52.50, 'Credit Card', 'TXN17463707416603', '2025-05-04 17:59:01', 'Completed'),
(49, 49, 62.50, 'Credit Card', 'TXN17463733952765', '2025-05-04 18:43:15', 'Completed'),
(50, 51, 60.00, 'Credit Card', 'TXN17463777481151', '2025-05-04 19:55:48', 'Completed'),
(51, 52, 62.50, '', 'TXN17463847686968', '2025-05-04 21:52:48', 'Completed'),
(52, 53, 0.00, 'Credit Card', 'TXN17463850486439', '2025-05-04 21:57:28', 'Completed'),
(53, 54, 120.00, 'Credit Card', 'TXN17463853512002', '2025-05-04 22:02:31', 'Completed'),
(54, 55, 2.50, 'Credit Card', 'TXN17463856277643', '2025-05-04 22:07:07', 'Completed');

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `after_payment_insert` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
    IF NEW.PaymentStatus = 'Completed' THEN
        UPDATE Bookings
        SET PaymentStatus = 'Paid',
            BookingStatus = IF(BookingStatus = 'Pending', 'Confirmed', BookingStatus)
        WHERE BookingID = NEW.BookingID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pricingrates`
--

CREATE TABLE `pricingrates` (
  `RateID` int(11) NOT NULL,
  `RateName` varchar(50) NOT NULL,
  `HourlyRate` decimal(10,2) NOT NULL,
  `DailyRate` decimal(10,2) NOT NULL,
  `WeeklyRate` decimal(10,2) DEFAULT NULL,
  `MonthlyRate` decimal(10,2) DEFAULT NULL,
  `SpecialRate` tinyint(1) DEFAULT 0,
  `EffectiveFrom` datetime NOT NULL,
  `EffectiveTo` datetime DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pricingrates`
--

INSERT INTO `pricingrates` (`RateID`, `RateName`, `HourlyRate`, `DailyRate`, `WeeklyRate`, `MonthlyRate`, `SpecialRate`, `EffectiveFrom`, `EffectiveTo`, `IsActive`) VALUES
(1, 'Standard', 2.50, 20.00, 100.00, 300.00, 0, '2025-03-21 13:24:31', NULL, 1),
(2, 'Weekend', 3.50, 25.00, 120.00, NULL, 1, '2025-03-01 00:00:00', NULL, 1),
(3, 'Holiday', 4.00, 30.00, NULL, NULL, 1, '2025-03-01 00:00:00', NULL, 1),
(4, 'Electric Vehicle', 2.00, 18.00, 90.00, 270.00, 1, '2025-03-01 00:00:00', NULL, 1),
(5, 'Monthly Pass', 0.00, 0.00, NULL, 250.00, 1, '2025-03-01 00:00:00', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `specialevents`
--

CREATE TABLE `specialevents` (
  `EventID` int(11) NOT NULL,
  `EventName` varchar(100) NOT NULL,
  `StartTime` datetime NOT NULL,
  `EndTime` datetime NOT NULL,
  `AffectedSpots` text DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ;

--
-- Dumping data for table `specialevents`
--

INSERT INTO `specialevents` (`EventID`, `EventName`, `StartTime`, `EndTime`, `AffectedSpots`, `Notes`) VALUES
(1, 'Maintenance - Section A', '2025-03-27 00:00:00', '2025-03-28 23:59:59', 'A1,A2,A3,A4,A5', 'Repaving and line repainting'),
(2, 'Local Festival Parking', '2025-04-15 06:00:00', '2025-04-15 23:00:00', 'ALL', 'City festival - expect high occupancy'),
(3, 'EV Charging Station Installation', '2025-04-05 08:00:00', '2025-04-07 18:00:00', 'B2,C4', 'Upgrading electric vehicle charging stations');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `RegistrationDate` datetime NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `NotificationPreferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{"email":true,"push":true,"sms":false}' CHECK (json_valid(`NotificationPreferences`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FirstName`, `LastName`, `Email`, `Phone`, `Password`, `RegistrationDate`, `IsActive`, `NotificationPreferences`) VALUES
(1, 'John', 'Smith', 'john.smith@example.com', '555-123-4567', 'hashed_password_1', '2025-02-01 08:30:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(2, 'Emily', 'Johnson', 'emily.j@example.com', '555-234-5678', 'hashed_password_2', '2025-02-03 10:15:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(3, 'Michael', 'Williams', 'michael.w@example.com', '555-345-6789', 'hashed_password_3', '2025-02-05 14:45:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(4, 'Sarah', 'Brown', 'sarah.b@example.com', '555-456-7890', 'hashed_password_4', '2025-02-07 16:20:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(5, 'Robert', 'Jones', 'robert.j@example.com', '555-567-8901', 'hashed_password_5', '2025-02-09 09:00:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(6, 'Jennifer', 'Garcia', 'jennifer.g@example.com', '555-678-9012', 'hashed_password_6', '2025-02-11 11:30:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(7, 'David', 'Miller', 'david.m@example.com', '555-789-0123', 'hashed_password_7', '2025-02-13 13:45:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(8, 'Lisa', 'Davis', 'lisa.d@example.com', '555-890-1234', 'hashed_password_8', '2025-02-15 17:10:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(9, 'James', 'Rodriguez', 'james.r@example.com', '555-901-2345', 'hashed_password_9', '2025-02-17 08:50:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(10, 'Jessica', 'Martinez', 'jessica.m@example.com', '555-012-3456', 'hashed_password_10', '2025-02-19 12:25:00', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(11, 'diana', 'rodha', 'djanarodha@gmail.com', '698-575-5814', '$2y$10$t2v38m.PFqMQE4hcyz53xexvqrlKO3PjjFJZSMH2wobNkh92kzLRa', '2025-03-23 19:08:14', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(12, 'alex', 'rodai', 'rodakristi@hotmail.com', '698-575-5814', '$2y$10$d5.IQsIJwQ7uUqz49hOB7Oy4pub3mNm7rCAq4WZyrHL9cjsvNFQ2W', '2025-03-24 03:20:53', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(13, 'dule', 'roa', 'duro@gmail.com', '6976543213', '$2y$10$DsXZftz0KCNn8QXdABiCP.Umv3xW9D34thU2kE20dUCmjodFuYNEG', '2025-03-24 13:24:39', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(14, 'bety', 'trisa', 'trisa@gmail.com', '4205358221', '$2y$10$TY0C2jCD7CbbHNMz2ii8kOM8zuxPKccIsyW.GPRX5dWjNUJz8NrUa', '2025-03-25 17:46:07', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(15, 'klaidi', 'lik', 'klaidi@gmail.com', '6984234321', '$2y$10$DF5otVhQAi6Nm3JPp2NfJOxPRxC2s8NsXexFi1M5RvzVRWR01RW0i', '2025-03-26 14:31:57', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(16, 'eri', 'sho', 'eri_shore@yahoo.com', '698-575-5814', '$2y$10$0GwuEzLgieAKRwz8II7eFejKUPA33FvFeGbj.PlBZDFmfvPjiYWna', '2025-05-04 17:42:06', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(17, 'Dritan', 'Rod', 'rodhadritan@gmail.com', '6979733080', '$2y$10$uE12x3awm0k0uOz.3P4PY.8GV.dD0rVmCo9VZrH6p6xVNtk4G8lBm', '2025-05-04 18:53:06', 1, '{\"email\":true,\"push\":true,\"sms\":false}'),
(18, 'Bety', 'Mocanu', 'mbeatrisa62@gmail.com', '698-575-5814', '$2y$10$ZX9BSkyN8gUl9c0tn.ju3uWtibvOZV4XUc7coT0fVYU1gLM6pQkRG', '2025-05-04 20:49:40', 1, '{\"email\":true,\"push\":true,\"sms\":false}');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `VehicleID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `LicensePlate` varchar(20) NOT NULL,
  `Make` varchar(50) DEFAULT NULL,
  `Model` varchar(50) DEFAULT NULL,
  `Color` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`VehicleID`, `UserID`, `LicensePlate`, `Make`, `Model`, `Color`) VALUES
(1, 1, 'ABC123', 'Toyota', 'Camry', 'Blue'),
(2, 1, 'XYZ789', 'Honda', 'Civic', 'Black'),
(3, 2, 'DEF456', 'Ford', 'Focus', 'Red'),
(4, 3, 'GHI789', 'Chevrolet', 'Malibu', 'Silver'),
(5, 4, 'JKL012', 'Nissan', 'Altima', 'White'),
(6, 5, 'MNO345', 'BMW', '3 Series', 'Black'),
(7, 6, 'PQR678', 'Tesla', 'Model 3', 'Blue'),
(8, 7, 'STU901', 'Volkswagen', 'Jetta', 'Gray'),
(9, 8, 'VWX234', 'Hyundai', 'Sonata', 'Red'),
(10, 9, 'YZA567', 'Audi', 'A4', 'White'),
(11, 10, 'BCD890', 'Subaru', 'Outback', 'Green'),
(12, 2, 'EFG123', 'Toyota', 'RAV4', 'Silver'),
(13, 11, 'ASX3256', 'Peugeot', '206cc', 'Black'),
(14, 11, 'SDA2161', 'Opel', 'Zafira', 'Grey'),
(15, 12, 'DAW2371', 'BMW', '535D', 'Grey'),
(16, 12, 'SAD1234', 'VOLVO', 'G30', 'Silver'),
(17, 12, 'KRISTI1312', 'Mazda', 'RX8', 'Black'),
(18, 14, 'BETY254', 'Mitsubishi', 'BLA', 'Black'),
(19, 15, 'Klaidi123', 'Peuegot', '207', 'Black'),
(20, 16, 'ERIBMW', 'BMW', '535D', 'BLUE'),
(21, 17, 'IZY3258', 'Open', 'Zafira', 'Grey'),
(22, 18, 'DSA3215', 'Mitsubishi', 'colt', 'Black');

-- --------------------------------------------------------

--
-- Structure for view `availablespots`
--
DROP TABLE IF EXISTS `availablespots`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `availablespots`  AS SELECT `ps`.`SpotID` AS `SpotID`, `ps`.`SpotNumber` AS `SpotNumber`, `ps`.`SpotType` AS `SpotType` FROM `parkingspots` AS `ps` WHERE `ps`.`IsActive` = 1 AND !(`ps`.`SpotID` in (select distinct `b`.`SpotID` from `bookings` `b` where `b`.`BookingStatus` in ('Confirmed','Checked-in') AND current_timestamp() between `b`.`StartTime` and `b`.`EndTime`)) ;

-- --------------------------------------------------------

--
-- Structure for view `currentoccupancy`
--
DROP TABLE IF EXISTS `currentoccupancy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `currentoccupancy`  AS SELECT count(0) AS `OccupiedSpots`, (select count(0) from `parkingspots` where `parkingspots`.`IsActive` = 1) AS `TotalSpots`, count(0) * 100.0 / (select count(0) from `parkingspots` where `parkingspots`.`IsActive` = 1) AS `OccupancyPercentage` FROM `bookings` WHERE `bookings`.`BookingStatus` in ('Confirmed','Checked-in') AND current_timestamp() between `bookings`.`StartTime` and `bookings`.`EndTime` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accesslogs`
--
ALTER TABLE `accesslogs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `BookingID` (`BookingID`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `VehicleID` (`VehicleID`),
  ADD KEY `RateID` (`RateID`),
  ADD KEY `idx_booking_dates` (`StartTime`,`EndTime`),
  ADD KEY `idx_user_bookings` (`UserID`),
  ADD KEY `idx_spot_bookings` (`SpotID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `BookingID` (`BookingID`);

--
-- Indexes for table `parkingspots`
--
ALTER TABLE `parkingspots`
  ADD PRIMARY KEY (`SpotID`),
  ADD UNIQUE KEY `SpotNumber` (`SpotNumber`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `BookingID` (`BookingID`);

--
-- Indexes for table `pricingrates`
--
ALTER TABLE `pricingrates`
  ADD PRIMARY KEY (`RateID`);

--
-- Indexes for table `specialevents`
--
ALTER TABLE `specialevents`
  ADD PRIMARY KEY (`EventID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`VehicleID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `idx_vehicle_license` (`LicensePlate`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accesslogs`
--
ALTER TABLE `accesslogs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `parkingspots`
--
ALTER TABLE `parkingspots`
  MODIFY `SpotID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `pricingrates`
--
ALTER TABLE `pricingrates`
  MODIFY `RateID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `specialevents`
--
ALTER TABLE `specialevents`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `VehicleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accesslogs`
--
ALTER TABLE `accesslogs`
  ADD CONSTRAINT `accesslogs_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`SpotID`) REFERENCES `parkingspots` (`SpotID`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`RateID`) REFERENCES `pricingrates` (`RateID`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`);

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
