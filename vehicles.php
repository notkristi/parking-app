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

// Get user's vehicles
try {
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE UserID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching vehicles: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-car"></i> My Vehicles</h1>
                <p class="text-muted">View your registered vehicles</p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if (!empty($vehicles)): ?>
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card vehicle-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <?php echo htmlspecialchars($vehicle['Make'] . ' ' . $vehicle['Model']); ?>
                                </h5>
                                <div class="vehicle-details">
                                    <p class="license-plate mb-3">
                                        <span class="plate-background">
                                            <?php echo htmlspecialchars($vehicle['LicensePlate']); ?>
                                        </span>
                                    </p>
                                    <div class="details-grid">
                                        <div class="detail-item">
                                            <i class="fas fa-palette"></i>
                                            <span><?php echo htmlspecialchars($vehicle['Color']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-car"></i>
                                            <span><?php echo htmlspecialchars($vehicle['Make']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-info-circle"></i>
                                            <span><?php echo htmlspecialchars($vehicle['Model']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You haven't registered any vehicles yet.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add vehicle call to action -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5><i class="fas fa-car-alt text-primary"></i> Need to register a new vehicle?</h5>
                            <p class="mb-0">Add your vehicle details to quickly book parking for your airport trips.</p>
                        </div>
                        <a href="addvehicle.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> Add Vehicle
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
