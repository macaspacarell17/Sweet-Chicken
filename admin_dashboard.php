<?php
session_start();

require 'config/database.php';
require_once 'auth.php';
requireActiveAccount($conn); // deactivated accounts are signed out immediately

/* Prevent browser caching */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

/* Require login */
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: loginpage.php'); exit(); }

if (($_SESSION['role'] ?? '') !== 'admin') {
    $roleRedirects = [
        'admin' => 'admin_dashboard.php',
        'cashier' => 'cashier_dashboard.php',
        'kitchen' => 'kitchen_dashboard.php',
        'customer' => 'customer_dashboard.php'
    ];
    header('Location: ' . ($roleRedirects[$_SESSION['role']] ?? 'loginpage.php'));
    exit();
}

/* Create employee account (Cashier or Kitchen Staff only) */
$createUserMessage = null;
if (isset($_POST['create_user'])) {
    require_once 'audit_log.php';

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $rawPass  = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['cashier', 'kitchen'], true)) {
        $createUserMessage = 'Please enter a name, a valid email, and choose Cashier or Kitchen.';
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{9,}$/', $rawPass)) {
        $createUserMessage = 'Password must be at least 9 characters and include a letter and a number.';
    } else {
        $password = password_hash($rawPass, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $password, $role);
            $created = $stmt->execute();
        } catch (Throwable $e) {
            $created = false; // e.g. duplicate email on PHP 8.1+, where mysqli throws
        }

        if ($created) {
            createAuditLog($conn, 'Employee Account Created', $fullname . ' (' . $role . ')', 'User #' . $conn->insert_id . ' | ' . $email);
            $createUserMessage = 'Employee account created successfully!';
        } else {
            $createUserMessage = 'Failed to create account. The email may already be registered.';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management & Kitchen Monitoring | Sweet Chicken Restaurant</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    
    <!-- CONSOLIDATED CSS STYLING -->
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>

<body>
<?php if ($createUserMessage !== null): ?>
<script>alert(<?php echo json_encode($createUserMessage); ?>);</script>
<?php endif; ?>
<div class="layout">

    <!-- OVERLAYS -->
    <div class="profile-overlay" id="profileOverlay"></div>
    <div class="notification-overlay" id="notificationOverlay"></div>
    <div class="overlay" id="overlay"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar collapsed" id="sidebar">
        <div class="logo">
            <img src="images/logo.png" class="logo-img" alt="Logo" onerror="this.src='https://via.placeholder.com/48'">
            <div class="logo-text">
                <h2>Sweet Chicken</h2>
            </div>
        </div>

        <div class="nav-section">
            <ul class="nav-menu">
                <li data-target="dashboard-view" class="active">
                    <a href="#">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li data-target="orders-view">
                    <a href="#">
                        <i class="fas fa-receipt"></i>
                        <span>Orders</span>
                        <span class="menu-badge" id="orderBadgeCount">12</span>
                    </a>
                </li>
                <li data-target="kitchen-view">
                    <a href="#">
                        <i class="fas fa-fire-burner"></i>
                        <span>Kitchen</span>
                        <span class="menu-badge danger" id="kitchenBadge">7</span>
                    </a>
                </li>
                <li data-target="sales-view">
                    <a href="#">
                        <i class="fas fa-chart-line"></i>
                        <span>Sales</span>
                    </a>
                </li>
                <li data-target="menu-view">
                    <a href="#">
                        <i class="fas fa-burger"></i>
                        <span>Menu Management</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <ul class="nav-menu">
                <li data-target="users-view" id="navUsers">
                    <a href="#">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                
                <li data-target="audit-view" id="navAudit">
                    <a href="#">
                        <i class="fas fa-file-lines"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main sidebar-collapsed" id="mainContent">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="left-header">
                <button id="toggleSidebar" type="button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="right-header">
                <div id="clock"></div>
                
                <div class="notification" id="notificationBtn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span>8</span>
                </div>

                <div class="profile" id="profileBtn">
                    <img src="https://i.pravatar.cc/100" alt="Profile">
                    <div>
                        <h4>Lucky T. Tico</h4>
                        <small>Admin</small>
                    </div>
                    <i class="fas fa-chevron-down text-light" style="font-size: 11px;"></i>
                </div>

                <div class="notification" id="fullscreenBtn" title="Toggle Fullscreen">
                    <i class="fas fa-expand" id="fullscreenIcon"></i>
                </div>
            </div>
        </header>

        <!-- Profile Dropdown Menu -->
        <div class="dropdown-menu" id="profileMenu">
            <div class="profile-dropdown-header">
                <img src="https://i.pravatar.cc/100" alt="Profile">
                <div>
                    <h4 style="font-size: 16px; margin: 0; font-weight: 700;">Lucky T. Tico</h4>
                    <small style="color: var(--text-light);">Administrator</small>
                </div>
            </div>
            <a href="#" id="openProfileModalLink"><i class="fas fa-user"></i> My Profile</a>
            <a href="#"><i class="fas fa-key"></i> Change Password</a>
            <a href="#"><i class="fas fa-history"></i> Activity Logs</a>
            <div class="dropdown-divider"></div>
            <a href="#" id="darkModeToggle"><i class="fas fa-moon" id="darkModeIcon"></i> <span id="darkModeText">Dark Mode</span></a>
            <div class="dropdown-divider"></div>
            <a href="logout.php"
onclick="return confirm('Are you sure you want to logout?');"
style="color: #DC2626;"> <i class="fas fa-sign-out-alt"></i> Logout </a>
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
            
                        
                </div>
                <div class="notify-card">
                    <div class="notify-icon warning"><i class="fas fa-utensils"></i></div>
                    <div>
                        <h4>Kitchen Finished Order #1048</h4>
                        <small>2 mins ago</small>
                    </div>
                </div>
                <div class="notify-card">
                    <div class="notify-icon danger"><i class="fas fa-triangle-exclamation"></i></div>
                    <div>
                        <h4>Low Stock Alert</h4>
                        <small>Chicken Wings Remaining: 8</small>
                    </div>
                </div>
                <div class="notify-card">
                    <div class="notify-icon info"><i class="fas fa-user"></i></div>
                    <div>
                        <h4>Staff Login</h4>
                        <small>John Dela Cruz logged in</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAGE CONTAINER WITH SWITCHABLE VIEWS -->
        <div class="page-container">

            <!-- 0. DASHBOARD HOME VIEW -->
            <div class="app-view active" id="dashboard-view">
                <div class="welcome">
                    <div>
                        <h1>Welcome Back 👋</h1>
                        <p>Here's what's happening today.</p>
                    </div>
        
                </div>

                <!-- KPI CARDS -->
                <section class="cards">
                    <div class="card revenue">
                        <div class="card-top">
                            <div class="icon"><i class="fas fa-peso-sign"></i></div>
                            <span class="trend up">+18%</span>
                        </div>
                        <h2 class="counter" data-target="42580">₱42,580</h2>
                        <p>Today's Revenue</p>
                    </div>
                    <div class="card orders">
                        <div class="card-top">
                            <div class="icon"><i class="fas fa-receipt"></i></div>
                            <span class="trend up">+6%</span>
                        </div>
                        <h2 class="counter" data-target="156">156</h2>
                        <p>Orders Today</p>
                    </div>
                    <div class="card customers">
                        <div class="card-top">
                            <div class="icon"><i class="fas fa-users"></i></div>
                            <span class="trend up">+10%</span>
                        </div>
                        <h2 class="counter" data-target="85">85</h2>
                        <p>Customers Today</p>
                    </div>
                    <div class="card kitchen">
                        <div class="card-top">
                            <div class="icon"><i class="fas fa-fire"></i></div>
                            <span class="trend warning">7 Active</span>
                        </div>
                        <h2 class="counter" data-target="7">7</h2>
                        <p>Kitchen Queue</p>
                    </div>
                    <div class="card pending">
                        <div class="card-top">
                            <div class="icon"><i class="fas fa-clock"></i></div>
                            <span class="trend danger">12 Pending</span>
                        </div>
                        <h2 class="counter" data-target="12">12</h2>
                        <p>Pending Orders</p>
                    </div>
                    <div class="card monthly">
                        <div class="card-top">
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            <span class="trend up">+23%</span>
                        </div>
                        <h2 class="counter" data-target="1200000">₱1,200,000</h2>
                        <p>Monthly Revenue</p>
                    </div>
                </section>

                <!-- ANALYTICS SECTION -->
                <section class="analytics">
                    <div class="chart-card">
                        <div class="card-header">
                            <div>
                                <h3>Revenue Analytics</h3>
                                <p>Monthly Sales Performance</p>
                            </div>
                            <select>
                                <option>Last 7 Days</option>
                                <option selected>Last 30 Days</option>
                                <option>This Year</option>
                            </select>
                        </div>
                        <canvas id="salesChart"></canvas>
                    </div>
                    <div class="chart-card donut">
                        <div class="card-header">
                            <h3>Best Selling Menu</h3>
                        </div>
                        <canvas id="menuChart"></canvas>
                    </div>
                </section>

                <!-- RECENT ORDERS SECTION -->
                <section class="orders-section">
                    <div class="section-header">
                        <div>
                            <h2>Recent Orders</h2>
                            <p>Latest customer transactions</p>
                        </div>
                        <button class="view-all" data-target="orders-view" onclick="switchView('orders-view')">View All</button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#1001</td>
                                    <td>Maria Santos</td>
                                    <td>2x Chicken Combo</td>
                                    <td>₱560</td>
                                    <td><span class="status preparing">Preparing</span></td>
                                    <td><button class="action-btn" onclick="viewOrderDetails(1001)"><i class="fas fa-eye"></i></button></td>
                                </tr>
                                <tr>
                                    <td>#1002</td>
                                    <td>John Cruz</td>
                                    <td>Family Bucket</td>
                                    <td>₱1,250</td>
                                    <td><span class="status ready">Ready</span></td>
                                    <td><button class="action-btn" onclick="viewOrderDetails(1002)"><i class="fas fa-eye"></i></button></td>
                                </tr>
                                <tr>
                                    <td>#1003</td>
                                    <td>Ana Reyes</td>
                                    <td>4pc Chicken Meal</td>
                                    <td>₱380</td>
                                    <td><span class="status pending">Pending</span></td>
                                    <td><button class="action-btn" onclick="viewOrderDetails(1003)"><i class="fas fa-eye"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- KITCHEN BOARD SECTION -->
                <section class="kitchen-board">
                    <div class="section-header">
                        <div>
                            <h2>Kitchen Monitoring</h2>
                            <p>Live order preparation workflow</p>
                        </div>
                    </div>
                    <div class="kanban">
                        <div class="column">
                            <div class="column-title pending-bg">Pending <span>4</span></div>
                            <div class="order-card">
                                <h4>#1005</h4>
                                <p>Maria Santos</p>
                                <small>2x Chicken Combo</small>
                                <div class="card-footer">⏱ 2 mins</div>
                            </div>
                            <div class="order-card">
                                <h4>#1006</h4>
                                <p>Carlo Reyes</p>
                                <small>Spicy Bucket</small>
                                <div class="card-footer">⏱ 5 mins</div>
                            </div>
                        </div>
                        <div class="column">
                            <div class="column-title preparing-bg">Preparing <span>3</span></div>
                            <div class="order-card">
                                <h4>#1001</h4>
                                <p>John Cruz</p>
                                <small>Family Bucket</small>
                                <div class="card-footer">👨‍🍳 Chef Mark</div>
                            </div>
                        </div>
                        <div class="column">
                            <div class="column-title ready-bg">Ready <span>2</span></div>
                            <div class="order-card">
                                <h4>#0998</h4>
                                <p>Ana Reyes</p>
                                <small>4pc Chicken Meal</small>
                                <div class="card-footer">✅ Notify Cashier</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- PERFORMANCE CENTER SECTION -->
                <section class="performance-center">
                    <div class="section-header">
                        <div>
                            <h2>Restaurant Performance</h2>
                            <p>Business insights for today</p>
                        </div>
                    </div>
                    <div class="performance-grid">
                        <div class="performance-card">
                            <div class="performance-icon orange"><i class="fas fa-heart"></i></div>
                            <div>
                                <h4>Health Score</h4>
                                <h2>96%</h2>
                                <small>Excellent Performance</small>
                            </div>
                        </div>
                        <div class="performance-card">
                            <div class="performance-icon blue"><i class="fas fa-smile"></i></div>
                            <div>
                                <h4>Customer Rating</h4>
                                <h2>4.9</h2>
                                <small>★★★★★</small>
                            </div>
                        </div>
                        <div class="performance-card">
                            <div class="performance-icon green"><i class="fas fa-stopwatch"></i></div>
                            <div>
                                <h4>Average Prep</h4>
                                <h2>8 min</h2>
                                <small>Fast Service</small>
                            </div>
                        </div>
                        <div class="performance-card">
                            <div class="performance-icon purple"><i class="fas fa-peso-sign"></i></div>
                            <div>
                                <h4>Average Order</h4>
                                <h2>₱325</h2>
                                <small>Per Transaction</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TODAY'S OPERATIONS SECTION -->
                <section class="operations">
                    <div class="section-header">
                        <div>
                            <h2>Today's Operations</h2>
                            <p>Daily restaurant overview</p>
                        </div>
                        <span class="live-badge"><i class="fas fa-circle"></i> Live</span>
                    </div>
                    <div class="operations-grid">
                        <div class="operation-item">
                            <div class="operation-icon blue"><i class="fas fa-user-tie"></i></div>
                            <div class="operation-info">
                                <h4>Staff on Duty</h4>
                                <p>6 Employees</p>
                            </div>
                            <span class="badge success">Active</span>
                        </div>
                        <div class="operation-item">
                            <div class="operation-icon orange"><i class="fas fa-clock"></i></div>
                            <div class="operation-info">
                                <h4>Orders Waiting</h4>
                                <p>12 Orders</p>
                            </div>
                            <span class="badge warning">Medium</span>
                        </div>
                        <div class="operation-item">
                            <div class="operation-icon green"><i class="fas fa-box-open"></i></div>
                            <div class="operation-info">
                                <h4>Ready for Pickup</h4>
                                <p>4 Orders</p>
                            </div>
                            <span class="badge info">Ready</span>
                        </div>
                        <div class="operation-item">
                            <div class="operation-icon red"><i class="fas fa-triangle-exclamation"></i></div>
                            <div class="operation-info">
                                <h4>Low Stock Alert</h4>
                                <p>Chicken Wings</p>
                            </div>
                            <span class="badge danger">Critical</span>
                        </div>
                        <div class="operation-item">
                            <div class="operation-icon purple"><i class="fas fa-peso-sign"></i></div>
                            <div class="operation-info">
                                <h4>Cash Sales</h4>
                                <p>₱42,580</p>
                            </div>
                            <span class="badge success">+18%</span>
                        </div>
                        <div class="operation-item">
                            <div class="operation-icon dark"><i class="fas fa-store"></i></div>
                            <div class="operation-info">
                                <h4>Business Hours</h4>
                                <p>9:00 AM - 10:00 PM</p>
                            </div>
                            <span class="badge success">Open</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- 1. ORDERS MANAGEMENT VIEW -->
            <div class="app-view" id="orders-view">
                <div class="breadcrumb">
                    <span>Dashboard</span>
                    <i class="fa-solid fa-angle-right"></i>
                    <span class="active">Order Management</span>
                </div>

                <section class="page-header">
                    <div>
                        <h1>Customer Orders</h1>
                        <p>View, track, and manage incoming customer orders and fulfillment statuses.</p>
                    </div>
                    
                </section>

                <section class="table-card">
                    <div class="table-header">
                        <div>
                            <h2>Active Order Queue</h2>
                            <span>Real-time customer order tracking</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="orders-table" id="ordersModuleTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Items</th>
                                    <th>Total (₱)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated dynamically by script -->
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- 2. KITCHEN MONITORING VIEW -->
            <div class="app-view" id="kitchen-view">
                <div class="breadcrumb">
                    <span>Dashboard</span>
                    <i class="fa-solid fa-angle-right"></i>
                    <span class="active">Kitchen Monitoring</span>
                </div>

                <div class="kitchen-queue-card">
                    <div class="section-header">
                        <div>
                            <h2>Kitchen Queue</h2>
                            <p>Live preparation monitoring and workflow</p>
                        </div>
                        <span class="live-badge">
                            <span class="dot"></span>
                            LIVE
                        </span>
                    </div>

                    <div class="kitchen-grid" id="kitchenQueueGrid">
                        <!-- Populated dynamically by script -->
                    </div>
                </div>
            </div>

            <!-- 3. SALES VIEW -->
            <div class="app-view" id="sales-view">
                <div class="breadcrumb">
                    <span>Dashboard</span>
                    <i class="fa-solid fa-angle-right"></i>
                    <span class="active">Sales Reports</span>
                </div>

                <section class="page-header">
                    <div>
                        <h1>Sales Reports & Transactions</h1>
                        <p>View sales summaries, analyze records, and generate financial reports.</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-primary" id="generateReportBtn" type="button">
                            <i class="fa-solid fa-file-arrow-down"></i> Generate Sales Report
                        </button>
                    </div>
                </section>

                <section class="summary-grid">
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-peso-sign"></i></div>
                        <div class="summary-info">
                            <h2 id="totalSalesMetric">₱24,850</h2>
                            <p>Total Sales</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-receipt"></i></div>
                        <div class="summary-info">
                            <h2 id="totalTransactionsMetric">52</h2>
                            <p>Total Transactions</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="summary-info">
                            <h2 id="avgOrderMetric">₱477.88</h2>
                            <p>Average Order Value</p>
                        </div>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="salesSearchInput" type="text" placeholder="Search transaction ID or customer...">
                    </div>
                    <select id="paymentMethodFilter">
                        <option value="All">All Payment Methods</option>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                    </select>
                    <input id="salesDateFilter" type="date">
                </section>

                <section class="table-card">
                    <div class="table-header">
                        <div>
                            <h2>Transaction History</h2>
                            <span>Comprehensive view of completed customer transactions</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="orders-table" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Transaction #</th>
                                    <th>Customer Name</th>
                                    <th>Items Count</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
                                    <th>Date & Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic Content via JS -->
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- 4. MENU MANAGEMENT VIEW -->
            <div class="app-view" id="menu-view">
                <div class="breadcrumb">
                    <span>Dashboard</span>
                    <i class="fa-solid fa-angle-right"></i>
                    <span class="active">Menu Management</span>
                </div>

                <section class="page-header">
                    <div>
                        <h1>Menu Management</h1>
                        <p>Add, edit, and remove dishes or items from your store offerings.</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-primary" id="openAddModalBtn" type="button">
                            <i class="fa-solid fa-plus"></i> Add Menu Item
                        </button>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="menuSearchInput" type="text" placeholder="Search item name...">
                    </div>
                    <select id="categoryFilter">
                        <option value="All">All Categories</option>
                        <option value="Chicken">Chicken</option>
                        <option value="Meals">Meals</option>
                        <option value="Drinks">Drinks</option>
                        <option value="Sides">Sides</option>
                    </select>
                </section>

                <section class="table-card">
                    <div class="table-header">
                        <div>
                            <h2>Menu Items List</h2>
                            <span>Manage availability, prices, and catalog</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="orders-table" id="menuTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic Rows via JS -->
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- 6. USERS VIEW -->
            <div class="app-view" id="users-view">
                <div class="breadcrumb">
                    <span>Administration</span>
                    <i class="fa-solid fa-angle-right"></i>
                    <span class="active">User Management</span>
                </div>
                <section class="page-header">
                    <div>
                        <h1>User Accounts</h1>
                        <p>Manage system staff accounts, roles, and account statuses.</p>
                    </div>
                    <button class="btn-primary" id="addUserBtn" type="button">
                        <i class="fa-solid fa-user-plus"></i> Add New User
                    </button>
                </section>
                <section class="table-card">
                    <div class="table-header">
                        <div>
                            <h2>Registered Staff</h2>
                            <span>Active system operators and permissions</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="orders-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

           

            <!-- 7. AUDIT LOGS VIEW -->
            <div class="app-view" id="audit-view">
                <div class="breadcrumb">
                    <span>Administration</span>
                    <i class="fa-solid fa-angle-right"></i>
                    <span class="active">Audit Logs</span>
                </div>
                <section class="page-header">
                    <div>
                        <h1>System Audit Logs</h1>
                        <p>Trace administrative changes, security modifications, and critical actions.</p>
                    </div>
                    <button class="btn-primary" id="exportLogsBtn" type="button">
                        <i class="fa-solid fa-download"></i> Export Logs
                    </button>
                </section>
                <section class="table-card">
                    <div class="table-header">
                        <div>
                            <h2>Activity Trail</h2>
                            <span>Real-time accountability tracking</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="orders-table" id="auditTable">
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>User</th>
                                    <th>Action Performed</th>
                                    <th>Target / Resource</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Menu Item Slide-out Modal Drawer -->
<div class="menu-modal-drawer" id="menuModal">
    <div class="modal-header">
        <h3 id="modalTitle">Add New Menu Item</h3>
        <button id="closeModalBtn" type="button"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
        <form id="menuForm">
            <input type="hidden" id="itemId">
            <div class="form-group">
                <label for="itemName">Item Name</label>
                <input type="text" id="itemName" placeholder="e.g., Spicy Chicken Wings" required>
            </div>
            <div class="form-group">
    <label for="itemCategory">Category</label>
    <select id="itemCategory" required>
        <option value="">Select Category</option>
        <option value="barkada meals">Barkada Meals</option>
        <option value="flavored chicken combo">Flavored Chicken Combo</option>
        <option value="soda">Soda</option>
        <option value="dessert">Dessert</option>
        <option value="add ons">Add Ons</option>
        <option value="juice">Juice</option>
    </select>
</div>
            <div class="form-group">
                <label for="itemPrice">Price (₱)</label>
                <input type="number" id="itemPrice" step="0.01" min="0" placeholder="e.g., 199" required>
            </div>
            <div class="form-group">
                <label for="itemStock">Stock Quantity</label>
                <input type="number" id="itemStock" min="0" step="1" placeholder="e.g., 100" required>
            </div>
            <div class="form-group">
                <label for="itemStatus">Status</label>
                <select id="itemStatus" required>
                    <option value="Available">Available</option>
                    <option value="Out of Stock">Out of Stock</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Save Menu Item</button>
        </form>
    </div>
</div>



<!-- Add New User Modal Drawer -->
<div class="menu-modal-drawer" id="userModal">
    <div class="modal-header">
        <h3>Create Employee Account</h3>
        <button id="closeUserModalBtn" type="button"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
        <form method="POST">
            <div class="form-group">
                <input type="text" name="fullname" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password (min. 9 characters, letters and numbers)" minlength="9" required>
            </div>
            <div class="form-group">
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="cashier">Cashier</option>
                    <option value="kitchen">Kitchen</option>
                </select>
            </div>
            <button type="submit" name="create_user" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Create Account</button>
        </form>
    </div>
</div>

<!-- Profile Modal Drawer -->
<div class="menu-modal-drawer" id="profileModal">
    <div class="modal-header">
        <h3>My Profile Settings</h3>
        <button id="closeProfileModalBtn" type="button"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
        <form id="profileForm">
            <div class="form-group" style="text-align: center; margin-bottom: 20px;">
                <img src="https://i.pravatar.cc/100" alt="Profile" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid var(--primary);">
                <h4 id="profileDisplayName">Lucky T. Tico</h4>
                <small id="profileDisplayRole" style="color: var(--text-light);">Administrator</small>
            </div>
            <div class="form-group">
                <label for="profileNameInput">Full Name</label>
                <input type="text" id="profileNameInput" value="Lucky T. Tico" required>
            </div>
            <div class="form-group">
                <label for="profileEmailInput">Email Address</label>
                <input type="email" id="profileEmailInput" value="admin@sweetchicken.com" required>
            </div>
            <div class="form-group">
                <label for="profileRoleInput">Role</label>
                <input type="text" id="profileRoleInput" value="Administrator" disabled style="background: #E5E7EB; cursor: not-allowed;">
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Save Profile Changes</button>
        </form>
    </div>
</div>

<!-- Notification Settings Modal Drawer -->
<div class="menu-modal-drawer" id="notificationSettingsModal">
    <div class="modal-header">
        <h3>Notification Settings</h3>
        <button id="closeNotifSettingsModalBtn" type="button"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
        <form id="notificationSettingsForm">
            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <label style="margin-bottom: 2px;">Push Notifications</label>
                    <small style="color: var(--text-light);">Receive alerts for new orders</small>
                </div>
                <label class="switch">
                    <input type="checkbox" id="notifPush" checked>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <label style="margin-bottom: 2px;">Email Notifications</label>
                    <small style="color: var(--text-light);">Get daily summary reports</small>
                </div>
                <label class="switch">
                    <input type="checkbox" id="notifEmail">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <label style="margin-bottom: 2px;">Kitchen Sound Alerts</label>
                    <small style="color: var(--text-light);">Play sound on queue updates</small>
                </div>
                <label class="switch">
                    <input type="checkbox" id="notifSound" checked>
                    <span class="slider"></span>
                </label>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Save Preferences</button>
        </form>
    </div>
</div>

<!-- JAVASCRIPT LIBRARIES & INTERACTION ENGINE -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="js/session_guard.js"></script>
<script src="js/admin_dashboard.js"></script>

</body>
</html>