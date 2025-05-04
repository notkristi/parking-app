<?php
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
?>