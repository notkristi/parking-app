<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in, otherwise redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's first name if available
$userName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : "User";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Vlorë Airport Parking</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Lottie Player for animations -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        :root {
            --primary-color: #8e44ad;
            --primary-light: #9b59b6;
            --primary-dark: #7d3c98;
        }
        
        body {
            height: 100vh;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-success-container {
            text-align: center;
            color: white;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease forwards, fadeOut 0.5s ease 3.5s forwards;
        }
        
        .welcome-message {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .user-name {
            color: #f8e9a1;
            font-weight: 800;
        }
        
        .success-message {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .animation-container {
            width: 250px;
            margin: 0 auto;
            filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));
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
            }
        }
        
        .progress-bar-container {
            width: 300px;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            margin: 0 auto;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            width: 0;
            background: white;
            animation: fillProgress 3s linear forwards;
        }
        
        @keyframes fillProgress {
            0% {
                width: 0;
            }
            100% {
                width: 100%;
            }
        }
        
        /* Background animation elements */
        .background-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .bg-element {
            position: absolute;
            opacity: 0.1;
            animation: floatAnimation 10s ease-in-out infinite;
        }
        
        .element-1 {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .element-2 {
            top: 70%;
            left: 80%;
            animation-delay: 0.5s;
        }
        
        .element-3 {
            top: 40%;
            left: 60%;
            animation-delay: 1s;
        }
        
        .element-4 {
            top: 80%;
            left: 30%;
            animation-delay: 1.5s;
        }
        
        @keyframes floatAnimation {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }
    </style>
</head>
<body>
    <!-- Background animated elements -->
    <div class="background-elements">
        <div class="bg-element element-1">
            <i class="fas fa-plane fa-4x"></i>
        </div>
        <div class="bg-element element-2">
            <i class="fas fa-car fa-3x"></i>
        </div>
        <div class="bg-element element-3">
            <i class="fas fa-ticket-alt fa-3x"></i>
        </div>
        <div class="bg-element element-4">
            <i class="fas fa-parking fa-4x"></i>
        </div>
    </div>

    <div class="login-success-container">
        <div class="welcome-message">Welcome, <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>!</div>
        <p class="success-message">You've successfully logged in. Preparing your dashboard...</p>
        
        <div class="animation-container">
            <!-- Lottie animation player -->
            <lottie-player
                src="https://assets10.lottiefiles.com/packages/lf20_q7hiluze.json"
                background="transparent"
                speed="1"
                style="width: 100%; height: 100%;"
                autoplay>
            </lottie-player>
        </div>
        
        <div class="progress-bar-container mt-4">
            <div class="progress-bar"></div>
        </div>
    </div>
    
    <script>
        // Redirect to dashboard after animation completes
        setTimeout(function() {
            window.location.href = "dashboard.php";
        }, 4000); // 4 seconds
    </script>
</body>
</html>
