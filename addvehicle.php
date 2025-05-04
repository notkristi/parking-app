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

// Initialize variables
$errors = [];
$success = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $licensePlate = trim($_POST['license_plate'] ?? '');
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $userId = $_SESSION['user_id'];
    
    // Validate input
    if (empty($licensePlate)) {
        $errors[] = "License plate is required";
    }
    
    if (empty($make)) {
        $errors[] = "Vehicle make is required";
    }
    
    if (empty($model)) {
        $errors[] = "Vehicle model is required";
    }
    
    if (empty($color)) {
        $errors[] = "Vehicle color is required";
    }
    
    // If validation passes, insert into database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO vehicles (UserID, LicensePlate, Make, Model, Color) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $licensePlate, $make, $model, $color]);
            
            $success = "Vehicle added successfully!";
            
            // Clear form data after successful submission
            $licensePlate = $make = $model = $color = '';
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Vehicle</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .form-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .license-plate-preview {
            background: linear-gradient(145deg, #f0f0f0, #e6e6e6);
            border: 2px solid #ccc;
            border-radius: 4px;
            padding: 10px 20px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.5rem;
            display: inline-block;
            margin: 20px 0;
            min-width: 200px;
            text-align: center;
            color: #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
        }
        
        .vehicle-type-preview {
            margin-top: 20px;
            text-align: center;
        }
        
        .vehicle-icon {
            font-size: 4rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .vehicle-preview-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 10px;
            border: 2px solid #dee2e6;
            vertical-align: middle;
        }
        
        .airport-info {
            background-color: #e8f4fc;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-top: 20px;
            border-radius: 0 5px 5px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="vehicles.php">My Vehicles</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add New Vehicle</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-car-alt text-primary me-2"></i> Add New Vehicle</h1>
                <p class="text-muted">Register a new vehicle for Vlorë Airport Parking</p>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                <a href="vehicles.php" class="alert-link ms-2">Return to My Vehicles</a>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h3 class="mb-0">Vehicle Information</h3>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="addvehicle.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="license_plate" class="form-label">License Plate</label>
                                    <input type="text" class="form-control form-control-lg" id="license_plate" name="license_plate" value="<?php echo htmlspecialchars($licensePlate ?? ''); ?>" placeholder="Enter license plate number" required>
                                    <div class="form-text">Enter your vehicle's license plate exactly as it appears.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="make" class="form-label">Vehicle Make</label>
                                    <input type="text" class="form-control form-control-lg" id="make" name="make" value="<?php echo htmlspecialchars($make ?? ''); ?>" placeholder="e.g. Toyota, BMW, Mercedes" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="model" class="form-label">Vehicle Model</label>
                                    <input type="text" class="form-control form-control-lg" id="model" name="model" value="<?php echo htmlspecialchars($model ?? ''); ?>" placeholder="e.g. Corolla, X5, C-Class" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="color" class="form-label">Vehicle Color</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-lg" id="color" name="color" value="<?php echo htmlspecialchars($color ?? ''); ?>" placeholder="e.g. Black, White, Silver" required>
                                        <span class="input-group-text p-0">
                                            <input type="color" class="form-control form-control-color h-100" id="colorPicker" value="#6c757d" title="Choose your vehicle color">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="vehicle-type-preview">
                                    <i class="fas fa-car vehicle-icon"></i>
                                    <div class="license-plate-preview" id="platePreview">ABC 123</div>
                                    <p class="vehicle-preview-text" id="vehiclePreview">Vehicle Preview</p>
                                    <p class="text-muted" id="colorPreviewText">Color: <span>Select a color</span> <span class="color-preview" id="colorPreviewBox"></span></p>
                                </div>
                                
                                <div class="airport-info mt-4">
                                    <h5><i class="fas fa-info-circle me-2"></i> Important Information</h5>
                                    <p>Adding your vehicle to our system will allow for faster check-in at Vlorë Airport Parking. Make sure the license plate information is accurate to avoid any issues upon arrival.</p>
                                    <p class="mb-0">Vehicle details are used to identify your car in our parking facility.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="vehicles.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i> Back to Vehicles
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i> Save Vehicle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // License plate preview
        document.getElementById('license_plate').addEventListener('input', function() {
            document.getElementById('platePreview').textContent = this.value || 'ABC 123';
        });
        
        // Vehicle make/model preview
        function updateVehiclePreview() {
            const make = document.getElementById('make').value;
            const model = document.getElementById('model').value;
            let previewText = 'Vehicle Preview';
            
            if (make && model) {
                previewText = make + ' ' + model;
            } else if (make) {
                previewText = make;
            } else if (model) {
                previewText = model;
            }
            
            document.getElementById('vehiclePreview').textContent = previewText;
        }
        
        document.getElementById('make').addEventListener('input', updateVehiclePreview);
        document.getElementById('model').addEventListener('input', updateVehiclePreview);
        
        // Color preview
        document.getElementById('color').addEventListener('input', function() {
            document.getElementById('colorPreviewText').querySelector('span').textContent = this.value || 'Select a color';
            
            // Try to match common color names to hex values
            const colorNameToHex = {
                'black': '#000000',
                'white': '#ffffff',
                'red': '#ff0000',
                'blue': '#0000ff',
                'green': '#008000',
                'yellow': '#ffff00',
                'silver': '#c0c0c0',
                'gray': '#808080',
                'orange': '#ffa500',
                'purple': '#800080'
            };
            
            const colorLower = this.value.toLowerCase();
            if (colorNameToHex[colorLower]) {
                document.getElementById('colorPreviewBox').style.backgroundColor = colorNameToHex[colorLower];
                document.getElementById('colorPicker').value = colorNameToHex[colorLower];
            }
        });
        
        // Color picker
        document.getElementById('colorPicker').addEventListener('input', function() {
            document.getElementById('colorPreviewBox').style.backgroundColor = this.value;
            document.getElementById('color').value = this.value;
        });
        
        // Initialize preview on page load
        window.addEventListener('DOMContentLoaded', function() {
            const licensePlate = document.getElementById('license_plate').value;
            if (licensePlate) {
                document.getElementById('platePreview').textContent = licensePlate;
            }
            
            updateVehiclePreview();
            
            const color = document.getElementById('color').value;
            if (color) {
                document.getElementById('colorPreviewText').querySelector('span').textContent = color;
                document.getElementById('colorPreviewBox').style.backgroundColor = color;
            }
        });
    </script>
</body>
</html>
