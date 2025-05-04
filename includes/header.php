<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php'; // Ensure database connection is available
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title . ' - Rodai Parking' : 'Rodai Parking'; ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/styles.css">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="assets/images/logo.png">

  <style>
    :root {
      --primary: #7A1FA0;
      --primary-light: #9b59b6;
      --primary-dark: #5E1681;
      --secondary: #8e44ad;
      --accent: #7d3c98;
      --success: #27ae60;
      --warning: #f39c12;
      --danger: #e74c3c;
      --light: #f5f5f5;
      --light-purple: #F5EAFA;
      --dark: #333333;
      --gray: #95a5a6;
      --gray-light: #ecf0f1;
      --gray-dark: #7f8c8d;
      --black: #1a1a1a;
      --white: #ffffff;
      --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
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
    
    /* Header styles */
    .header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      transition: var(--transition);
      background-color: var(--white);
      box-shadow: var(--box-shadow);
    }
    
    .header-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 70px;
    }
    
    .header-scrolled {
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Logo styles */
    .logo {
      display: flex;
      align-items: center;
      text-decoration: none;
      gap: 10px;
    }
    
    .logo-icon {
      width: 36px;
      height: 36px;
      background-color: var(--primary);
      color: var(--white);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      font-weight: bold;
    }
    
    .logo-text {
      font-size: 18px;
      font-weight: 600;
      color: var(--primary);
      letter-spacing: 0.5px;
    }
    
    /* Navigation */
    .nav {
      display: flex;
      align-items: center;
    }
    
    .nav-list {
      display: flex;
      list-style-type: none;
    }
    
    .nav-item {
      margin-right: 5px;
    }
    
    .nav-link {
      padding: 10px 15px;
      color: var(--dark);
      text-decoration: none;
      font-weight: 500;
      font-size: 14px;
      position: relative;
      transition: var(--transition);
      border-radius: 6px;
    }
    
    /* Restore the underline animation */
    .nav-link::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 50%;
      background-color: var(--primary);
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }
    
    .nav-link:hover::after,
    .nav-link.active::after {
      width: 80%;
    }
    
    .nav-link:hover {
      color: var(--primary);
    }
    
    .nav-link.active {
      color: var(--primary);
    }
    
    .nav-link i {
      margin-right: 6px;
      font-size: 15px;
    }
    
    /* Header buttons */
    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 8px 18px;
      font-size: 14px;
      font-weight: 500;
      border-radius: 6px;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      border: none;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
      height: 40px;
    }
    
    .btn i {
      margin-right: 6px;
      font-size: 15px;
    }
    
    .btn-primary {
      background-color: var(--primary);
      color: var(--white);
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(122, 31, 160, 0.2);
    }
    
    .btn-outline {
      background-color: transparent;
      color: var(--primary);
      border: 1px solid var(--primary);
    }
    
    .btn-outline:hover {
      color: var(--white);
      background-color: var(--primary);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(122, 31, 160, 0.2);
    }
    
    .btn-danger {
      background-color: var(--danger);
      color: var(--white);
    }
    
    .btn-danger:hover {
      background-color: #c0392b;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(231, 76, 60, 0.2);
    }
    
    /* User menu */
    .user-button {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      padding: 8px 15px;
      border-radius: 20px;
      transition: var(--transition);
      background-color: var(--light-purple);
      border: 1px solid rgba(122, 31, 160, 0.1);
    }
    
    .user-button:hover {
      background-color: rgba(122, 31, 160, 0.1);
      transform: translateY(-2px);
    }
    
    .user-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: var(--primary);
      color: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 600;
    }
    
    .user-name {
      font-size: 14px;
      font-weight: 500;
      color: var(--primary);
    }
    
    /* Mobile navigation toggle */
    .nav-toggle {
      display: none;
      background: none;
      border: none;
      font-size: 24px;
      color: var(--primary);
      cursor: pointer;
      padding: 0;
    }
    
    /* Dropdown menu for mobile */
    .mobile-menu {
      display: none;
      position: fixed;
      top: 70px;
      left: 0;
      right: 0;
      background-color: var(--white);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
      padding: 20px;
      z-index: 999;
      flex-direction: column;
    }
    
    .mobile-menu .nav-list {
      flex-direction: column;
      width: 100%;
    }
    
    .mobile-menu .nav-item {
      margin: 0;
      width: 100%;
    }
    
    .mobile-menu .nav-link {
      padding: 12px 15px;
      display: flex;
      align-items: center;
      width: 100%;
    }
    
    .mobile-menu .header-actions {
      flex-direction: column;
      width: 100%;
      margin-top: 15px;
      gap: 10px;
    }
    
    .mobile-menu .btn {
      width: 100%;
      justify-content: center;
    }
    
    .mobile-menu.show {
      display: flex;
    }
    
    /* Responsive styles */
    @media (max-width: 992px) {
      .nav {
        display: none;
      }
      
      .nav-toggle {
        display: block;
      }
      
      .header-actions {
        display: none;
      }
    }
  </style>
</head>
<body>

<header class="header">
  <div class="header-container">
    <!-- Logo -->
    <a href="dashboard.php" class="logo">
      <img src="assets/images/logo.png" alt="Rodai Parking Logo" style="height:48px; width:auto; display:block;">
    </a>
    
    <!-- Navigation -->
    <nav class="nav">
      <ul class="nav-list">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa fa-home"></i> Home
          </a>
        </li>
        <li class="nav-item">
          <a href="reserve.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reserve.php' ? 'active' : ''; ?>">
            <i class="fa fa-calendar-plus"></i> Reserve
          </a>
        </li>
        <li class="nav-item">
          <a href="costs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'costs.php' ? 'active' : ''; ?>">
            <i class="fa fa-money-bill"></i> Costs
          </a>
        </li>
        <li class="nav-item">
          <a href="contact.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">
            <i class="fa fa-envelope"></i> Contact
          </a>
        </li>
      </ul>
    </nav>
    
    <!-- Header Actions -->
    <div class="header-actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <?php 
          // Get user's first name initial and last name initial for avatar
          $firstInitial = isset($_SESSION['first_name']) && !empty($_SESSION['first_name']) ? 
                          strtoupper(substr($_SESSION['first_name'], 0, 1)) : 'U';
          // Get unread notification count
          $unreadNotificationCount = 0;
          require_once __DIR__ . '/notification_system.php';
          $unreadNotificationCount = count(getUnreadNotifications($conn, $_SESSION['user_id']));
        ?>
        <a href="profile.php" class="user-button">
          <div class="user-avatar"><?php echo $firstInitial; ?></div>
          <div class="user-name"><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?></div>
        </a>
        <!-- Notification Bell -->
        <a href="notifications.php" class="btn btn-outline position-relative" title="Notifications">
          <i class="fa fa-bell"></i>
          <?php if ($unreadNotificationCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?php echo $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount; ?>
            </span>
          <?php endif; ?>
        </a>
        <a href="logout.php" class="btn btn-danger">
          <i class="fa fa-sign-out-alt"></i> Logout
        </a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline">
          <i class="fa fa-sign-in-alt"></i> Sign In
        </a>
        <a href="register.php" class="btn btn-primary">
          <i class="fa fa-user-plus"></i> Register
        </a>
      <?php endif; ?>
    </div>
    
    <!-- Mobile Menu Toggle -->
    <button class="nav-toggle" id="navToggle">
      <i class="fa fa-bars"></i>
    </button>
  </div>
  
  <!-- Mobile Menu -->
  <div class="mobile-menu" id="mobileMenu">
    <ul class="nav-list">
      <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
          <i class="fa fa-home"></i> Home
        </a>
      </li>
      <li class="nav-item">
        <a href="reserve.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reserve.php' ? 'active' : ''; ?>">
          <i class="fa fa-calendar-plus"></i> Reserve
        </a>
      </li>
      <li class="nav-item">
        <a href="costs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'costs.php' ? 'active' : ''; ?>">
          <i class="fa fa-money-bill"></i> Costs
        </a>
      </li>
      <li class="nav-item">
        <a href="contact.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">
          <i class="fa fa-envelope"></i> Contact
        </a>
      </li>
    </ul>
    
    <div class="header-actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="profile.php" class="user-button">
          <div class="user-avatar"><?php echo $firstInitial; ?></div>
          <div class="user-name"><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?></div>
        </a>
        <a href="notifications.php" class="btn btn-outline">
          <i class="fa fa-bell"></i> Notifications
          <?php if ($unreadNotificationCount > 0): ?>
            <span class="badge bg-danger"><?php echo $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount; ?></span>
          <?php endif; ?>
        </a>
        <a href="logout.php" class="btn btn-danger">
          <i class="fa fa-sign-out-alt"></i> Logout
        </a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline">
          <i class="fa fa-sign-in-alt"></i> Sign In
        </a>
        <a href="register.php" class="btn btn-primary">
          <i class="fa fa-user-plus"></i> Register
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<script>
  // Mobile menu toggle
  document.getElementById('navToggle').addEventListener('click', function() {
    document.getElementById('mobileMenu').classList.toggle('show');
  });
  
  // Close mobile menu when clicking outside
  document.addEventListener('click', function(event) {
    const mobileMenu = document.getElementById('mobileMenu');
    const navToggle = document.getElementById('navToggle');
    
    if (!mobileMenu.contains(event.target) && !navToggle.contains(event.target)) {
      mobileMenu.classList.remove('show');
    }
  });
</script>