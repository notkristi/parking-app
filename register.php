<?php
include 'db.php';
$errors = [];
$success = false;

// Form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($first_name) || strlen($first_name) < 3) {
        $errors[] = "First Name must be at least 3 characters long.";
    }
    
    if (empty($last_name) || strlen($last_name) < 3) {
        $errors[] = "Last Name must be at least 3 characters long.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Check if email already exists
    $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE Email = :email");
    $check->bindParam(':email', $email);
    $check->execute();
    
    if ($check->fetchColumn() > 0) {
        $errors[] = "Email already exists.";
    }
    
    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Current timestamp for RegistrationDate
        $registration_date = date('Y-m-d H:i:s');
        
        try {
            // Insert user into the users table
            $stmt = $conn->prepare("INSERT INTO users (FirstName, LastName, Email, Phone, Password, RegistrationDate, IsActive) 
                                   VALUES (:first_name, :last_name, :email, :phone, :password, :registration_date, 1)");
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':registration_date', $registration_date);
            
            if ($stmt->execute()) {
                $success = true;
            }
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
    <title>Register</title>
    <style>
        :root {
            --primary-color: #7A1FA0;
            --primary-hover: #5E1681;
            --light-purple: #F5EAFA;
            --text-color: #333;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --border-radius: 4px;
            --box-shadow: 0 4px 10px rgba(122, 31, 160, 0.2);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 450px;
            margin: 40px auto;
            padding: 25px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            background-color: #fff;
            transition: border 0.3s, box-shadow 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(122, 31, 160, 0.1);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: var(--primary-hover);
        }
        
        .error-message {
            color: var(--error-color);
            background-color: rgba(231, 76, 60, 0.1);
            padding: 10px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }
        
        .success-message {
            color: var(--success-color);
            background-color: rgba(46, 204, 113, 0.1);
            padding: 10px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background-color: #eee;
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo" style="text-align:center; margin-bottom:1.5rem;">
            <img src="assets/images/logo.png" alt="Rodai Parking Logo" style="height:48px; width:auto; display:inline-block;">
        </div>
        <h2>Create an Account</h2>

        <?php if ($success): ?>
            <div class="success-message">
                <p>Registration successful! Your account has been created.</p>
                <p>You can now <a href="login.php">login to your account</a>.</p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="register-form">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required
                        value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required
                        value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" required
                        value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-strength">
                        <div class="password-strength-meter" id="password-meter"></div>
                    </div>
                </div>

                <button type="submit">Register</button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.getElementById('password-meter');
            
            // Calculate strength
            let strength = 0;
            if (password.length > 0) strength += 20;
            if (password.length >= 8) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            // Update meter
            meter.style.width = strength + '%';
            
            // Change color based on strength
            if (strength < 40) {
                meter.style.backgroundColor = '#e74c3c'; // Red
            } else if (strength < 80) {
                meter.style.backgroundColor = '#f39c12'; // Orange
            } else {
                meter.style.backgroundColor = '#2ecc71'; // Green
            }
        });
    </script>
</body>
</html>