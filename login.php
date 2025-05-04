<?php
session_start();
include 'db.php'; // Ensure this file contains the proper PDO database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['guest_login'])) {
        // Set guest session
        $_SESSION['guest'] = true;
        $_SESSION['user_id'] = null;
        $_SESSION['first_name'] = 'Guest';
        $_SESSION['username'] = 'Guest';
        header("Location: dashboard.php");
        exit();
    }
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Sanitize input
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        try {
            // Prepare SQL query to fetch user based on email
            $stmt = $conn->prepare("SELECT * FROM users WHERE Email = :email AND IsActive = 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Check if the password matches using password_verify for hashed passwords
                if (password_verify($password, $user['Password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['first_name'] = $user['FirstName'];
                    $_SESSION['username'] = $user['FirstName'] . ' ' . $user['LastName']; // Combine first and last name
                    $_SESSION['fresh_login'] = true;  // Set this flag
                    header("Location: dashboard.php"); // Redirect to animation page
                    exit();
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No active account found with that email.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* CSS styling as before */
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
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-purple);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-color);
            padding: 20px;
        }

        .login-container {
            display: flex;
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 900px;
            overflow: hidden;
        }

        .image-section {
            background-color: var(--primary-color);
            flex: 1;
            display: none;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 768px) {
            .image-section {
                display: block;
            }
        }

        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.8;
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(122, 31, 160, 0.9) 0%, rgba(94, 22, 129, 0.7) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: white;
            text-align: center;
        }

        .image-overlay h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .image-overlay p {
            font-size: 1rem;
            max-width: 80%;
        }

        .form-section {
            flex: 1;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.8rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        input {
            padding: 0.75rem;
            padding-left: 2.5rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(122, 31, 160, 0.2);
        }

        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .error-message {
            color: var(--error-color);
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            background-color: rgba(231, 76, 60, 0.1);
            padding: 8px;
            border-radius: var(--border-radius);
        }

        .success-message {
            color: var(--success-color);
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            background-color: rgba(46, 204, 113, 0.1);
            padding: 8px;
            border-radius: var(--border-radius);
        }

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo img {
            height: 50px;
            width: auto;
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="image-section">
            <!-- Placeholder image for branding -->
            <div class="image-overlay">
                <h1>Welcome Back</h1>
                <p>Log in to access your account and continue your journey with us.</p>
            </div>
        </div>
        <div class="form-section">
            <div class="logo" style="text-align:center; margin-bottom:1.5rem;">
                <img src="assets/images/logo.png" alt="Rodai Parking Logo" style="height:48px; width:auto; display:inline-block;">
            </div>
            
            <?php if(isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Registration successful! You can now log in.
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Log In</button>
            </form>
            <form action="login.php" method="POST" style="margin-top: 1rem; text-align: center;">
                <button type="submit" name="guest_login" class="btn btn-outline" style="background: #F5EAFA; color: #7A1FA0; border: 1px solid #7A1FA0; padding: 0.75rem 1.5rem; border-radius: 4px; font-size: 1rem; cursor: pointer;">
                    <i class="fas fa-user-secret"></i> Continue as Guest
                </button>
            </form>
            <div class="form-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>