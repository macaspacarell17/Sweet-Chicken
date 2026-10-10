<?php

session_start();

/* Prevent the dashboard from being restored from browser cache */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* User must be logged in */
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'kitchen') {
    $roleRedirects = [
        'admin' => 'admin_dashboard.php',
        'cashier' => 'cashier_dashboard.php',
        'kitchen' => 'kitchen_dashboard.php',
        'customer' => 'customer_dashboard.php'
    ];
    header('Location: ' . ($roleRedirects[$_SESSION['role']] ?? 'loginpage.php'));
    exit();
}

require 'config/database.php';
require_once 'auth.php';
requireActiveAccount($conn); // deactivated accounts are signed out immediately

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Chicken Restaurant - Kitchen Display System</title>
    <!-- GOOGLE FONT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Nunito:wght@700;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- STYLES -->
    <link rel="stylesheet" href="css/kitchen_dashboard.css">
</head>
<body>

<div class="kds-container">
    <header class="kds-top-bar">
        <div class="left-header">
            <div class="search-kds">
                <i class="fa fa-search"></i>
                <input type="text" placeholder="Search Order..." id="searchKDSOrder">
            </div>
        </div>

        <div id="clock"></div>

        <div class="right-header">
            <button id="historyBtn" title="Order History" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:16px;position:relative;margin-right:4px;">
                <i class="fas fa-clock-rotate-left"></i>
            </button>

            <div class="notification" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span>3</span>
            </div>

            <div class="profile" id="profileBtn">
                <img src="https://i.pravatar.cc/100" alt="Profile">
                <div>
                    <h4>Chef Marco</h4>
                    <small>Kitchen Staff</small>
                </div>
                <i class="fas fa-chevron-down text-light" style="font-size: 11px;"></i>
            </div>

            <button id="kdsFullscreen" title="Toggle Fullscreen"><i class="fa-solid fa-expand"></i></button>
        </div>
    </header>

    <!-- Profile Dropdown Menu -->
    <div class="dropdown-menu" id="profileMenu">
        <div class="profile-dropdown-header">
            <img src="https://i.pravatar.cc/100" alt="Profile">
            <div>
                <h4 style="font-size: 16px; margin: 0; font-weight: 700;">Chef Marco</h4>
                <small style="color: var(--text-light);">Kitchen Display Operator</small>
            </div>
        </div>
        <a href="#" id="menuProfileLink"><i class="fas fa-user"></i> My Profile</a>
        <a href="#" id="menuPasswordLink"><i class="fas fa-key"></i> Change Password</a>
        <div class="dropdown-divider"></div>
        <a href="#" id="darkModeToggle">
            <i class="fas fa-moon" id="darkModeIcon"></i> 
            <span id="darkModeText">Dark Mode</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="logout.php"
onclick="return confirm('Are you sure you want to logout?');"
style="color: #DC2626;"> <i class="fas fa-sign-out-alt"></i> Logout </a>

    </div>

    <!-- Order History Panel -->
    <div class="history-panel" id="historyPanel">
        <div class="panel-header">
            <h3>Order History</h3>
            <button id="closeHistory" type="button"><i class="fas fa-times"></i></button>
        </div>
        <div class="history-filter-bar">
            <button class="history-filter-btn active" data-history-filter="all">All</button>
            <button class="history-filter-btn" data-history-filter="Ready">Ready</button>
            <button class="history-filter-btn" data-history-filter="Completed">Completed</button>
            <button class="history-filter-btn" data-history-filter="Cancelled">Cancelled</button>
        </div>
        <div class="search-kds" style="margin:0 0 12px;">
            <i class="fa fa-search"></i>
            <input type="text" placeholder="Search order number..." id="searchHistoryOrder">
        </div>
        <div class="history-list" id="historyList">
            <p style="color:#6B7280;padding:20px;text-align:center;">Loading order history...</p>
        </div>
    </div>

    <!-- Notification Drawer Panel -->
    <div class="notification-panel" id="notificationPanel">
        <div class="panel-header">
            <h3>Notifications</h3>
            <button id="closeNotification" type="button"><i class="fas fa-times"></i></button>
        </div>
        <div class="notification-list">
            <div class="notify-card">
                <div class="notify-icon success"><i class="fas fa-receipt"></i></div>
                <div>
                    <h4 style="font-size: 13px;">New Order Received</h4>
                    <small style="font-size: 11px;">Just now</small>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <button class="filter-btn active" data-kds-filter="all">ALL</button>
        <button class="filter-btn" data-kds-filter="Dine In">DINE IN</button>
        <button class="filter-btn" data-kds-filter="Take Out">TAKE OUT</button>
        <button class="filter-btn" data-kds-filter="Online">ONLINE</button>
    </div>

    <section id="kdsOrdersGrid" class="kds-cards-grid">
        
    </section>
</div>

<script src="js/session_guard.js"></script>
<script src="js/kitchen_dashboard.js"></script>
</body>
</html>