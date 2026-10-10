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

if (($_SESSION['role'] ?? '') !== 'cashier') {
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
    <title>Sweet Chicken Restaurant - All-in-One System</title>
    <!-- GOOGLE FONT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <!-- AOS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    <!-- EMBEDDED COMBINED STYLES -->
    <link rel="stylesheet" href="css/cashier_dashboard.css">
</head>
<body>

<div class="layout">
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
                <li class="active" data-target="pos-content">
                    <a href="#pos"><i class="fas fa-cash-register"></i><span>POS</span></a>
                </li>
                <li data-target="summary-content">
                    <a href="#summary"><i class="fas fa-receipt"></i><span>Sales Summary</span></a>
                </li>
                <li data-target="online-content">
                    <a href="#online">
                        <i class="fas fa-globe"></i>
                        <span>Online Orders</span>
                        <span class="menu-badge" id="onlineOrderBadge">0</span>
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
                
                <div class="notification" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span>8</span>
                </div>

                <div class="profile" id="profileBtn">
                    <img src="https://i.pravatar.cc/100" alt="Profile">
                    <div>
                        <h4>Sarah Jenkins</h4>
                        <small>Cashier</small>
                    </div>
                    <i class="fas fa-chevron-down text-light" style="font-size: 11px;"></i>
                </div>

                <button id="fullscreenBtn" type="button" class="fullscreen-btn" title="Toggle Fullscreen">
                    <i class="fas fa-expand" id="fullscreenIcon"></i>
                </button>
            </div>
        </header>

        <!-- Profile Overlay -->
        <div class="profile-overlay" id="profileOverlay"></div>

        <!-- Profile Dropdown Menu -->
        <div class="dropdown-menu" id="profileMenu">
            <div class="profile-dropdown-header">
                <img src="https://i.pravatar.cc/100" alt="Profile">
                <div>
                    <h4 style="font-size: 16px; margin: 0; font-weight: 700;">Sarah Jenkins</h4>
                    <small style="color: var(--text-light);">Cashier Operator</small>
                </div>
            </div>
            <a href="#" id="openProfileModalLink"><i class="fas fa-user"></i> My Profile</a>
            <a href="#"><i class="fas fa-key"></i> Change Password</a>
            <a href="#"><i class="fas fa-history"></i> Activity Logs</a>
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
                        <h4 style="font-size: 13px;">New Order #1052</h4>
                        <small style="font-size: 11px;">Just now</small>
                    </div>
                </div>
                <div class="notify-card">
                    <div class="notify-icon warning"><i class="fas fa-utensils"></i></div>
                    <div>
                        <h4 style="font-size: 13px;">Kitchen Finished Order #1048</h4>
                        <small style="font-size: 11px;">2 mins ago</small>
                    </div>
                </div>
                <div class="notify-card">
                    <div class="notify-icon danger"><i class="fas fa-triangle-exclamation"></i></div>
                    <div>
                        <h4 style="font-size: 13px;">Low Stock Alert</h4>
                        <small style="font-size: 11px;">Chicken Wings Remaining: 8</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. POS CONTENT -->
        <main class="pos-content active" id="pos-content">
            <div class="pos-header">
                <div>
                    <h1>Point of Sale</h1>
                    <p>Create and process customer orders</p>
                </div>
                <div class="transaction-number">
                    <span>Transaction No.</span>
                    <strong id="transactionNumber">SC-000001</strong>
                </div>
            </div>

            <div class="pos-grid">
                <!-- MENU SECTION - LEFT -->
                <section class="menu-section">
                    <div class="category-bar">
                        <button class="category-btn active" data-category="all">All</button>
                        <button class="category-btn" data-category="barkada">Barkada Feast Combo</button>
                        <button class="category-btn" data-category="chicken">Flavored Chicken Combo</button>
                        <button class="category-btn" data-category="soda">Soda</button>
                        <button class="category-btn" data-category="juice">Juice</button>
                        <button class="category-btn" data-category="dessert">Dessert</button>
                        <button class="category-btn" data-category="add-on">Add Ons</button>
                    </div>

                    <div class="product-grid" id="productGrid">
                        <!-- Barkada Feast Combo - 7 pcs -->
                        <div class="product-card" data-category="barkada" data-id="barkada-7" data-name="Barkada Feast Combo - 7 pcs" data-price="439">
                            <div class="product-image"><i class="fas fa-drumstick-bite"></i></div>
                            <div class="product-info">
                                <h3>Barkada Feast Combo</h3>
                                <p>7 pcs • Choose 3 flavors • 2 rice • 2 drinks</p>
                                <div class="product-bottom">
                                    <strong>₱439.00</strong>
                                    <span class="click-to-add">Customize</span>
                                </div>
                            </div>
                        </div>

                        <!-- Barkada Feast Combo - 4 pcs -->
                        <div class="product-card" data-category="barkada" data-id="barkada-4" data-name="Barkada Feast Combo - 4 pcs" data-price="299">
                            <div class="product-image"><i class="fas fa-drumstick-bite"></i></div>
                            <div class="product-info">
                                <h3>Barkada Feast Combo</h3>
                                <p>4 pcs • Choose 2 flavors • 2 rice • 2 drinks</p>
                                <div class="product-bottom">
                                    <strong>₱299.00</strong>
                                    <span class="click-to-add">Customize</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3pcs Chicken Combo -->
                        <div class="product-card" data-category="chicken" data-id="chicken-3" data-name="3pcs Chicken Combo" data-price="209">
                            <div class="product-image"><i class="fas fa-drumstick-bite"></i></div>
                            <div class="product-info">
                                <h3>3pcs Chicken Combo</h3>
                                <p>Choose 1 flavor</p>
                                <div class="product-bottom">
                                    <strong>₱209.00</strong>
                                    <span class="click-to-add">Customize</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2pcs Chicken Combo -->
                        <div class="product-card" data-category="chicken" data-id="chicken-2" data-name="2pcs Chicken Combo" data-price="169">
                            <div class="product-image"><i class="fas fa-drumstick-bite"></i></div>
                            <div class="product-info">
                                <h3>2pcs Chicken Combo</h3>
                                <p>Choose 1 flavor</p>
                                <div class="product-bottom">
                                    <strong>₱169.00</strong>
                                    <span class="click-to-add">Customize</span>
                                </div>
                            </div>
                        </div>

                        <!-- Chicken Pops Combo -->
                        <div class="product-card" data-category="chicken" data-id="chicken-pops" data-name="Chicken Pops Combo" data-price="119">
                            <div class="product-image"><i class="fas fa-bowl-food"></i></div>
                            <div class="product-info">
                                <h3>Chicken Pops Combo</h3>
                                <p>Choose 1 flavor</p>
                                <div class="product-bottom">
                                    <strong>₱119.00</strong>
                                    <span class="click-to-add">Customize</span>
                                </div>
                            </div>
                        </div>

                        <!-- Soda 1.5L -->
                        <div class="product-card" data-category="soda" data-id="soda-15l" data-name="Soda 1.5L" data-price="85">
                            <div class="product-image"><i class="fas fa-bottle-water"></i></div>
                            <div class="product-info">
                                <h3>Soda 1.5L</h3>
                                <p>Coke / Sprite / Royal</p>
                                <div class="product-bottom">
                                    <strong>₱85.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Soda Mismo -->
                        <div class="product-card" data-category="soda" data-id="soda-mismo" data-name="Soda Mismo" data-price="25">
                            <div class="product-image"><i class="fas fa-bottle-water"></i></div>
                            <div class="product-info">
                                <h3>Soda Mismo</h3>
                                <p>Coke / Sprite / Royal</p>
                                <div class="product-bottom">
                                    <strong>₱25.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Graham Ice Cream - Blueberry -->
                        <div class="product-card" data-category="dessert" data-id="graham-blueberry" data-name="Graham Ice Cream - Blueberry" data-price="39">
                            <div class="product-image"><i class="fas fa-ice-cream"></i></div>
                            <div class="product-info">
                                <h3>Graham Ice Cream</h3>
                                <p>Blueberry</p>
                                <div class="product-bottom">
                                    <strong>₱39.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Graham Ice Cream - Strawberry -->
                        <div class="product-card" data-category="dessert" data-id="graham-strawberry" data-name="Graham Ice Cream - Strawberry" data-price="39">
                            <div class="product-image"><i class="fas fa-ice-cream"></i></div>
                            <div class="product-info">
                                <h3>Graham Ice Cream</h3>
                                <p>Strawberry</p>
                                <div class="product-bottom">
                                    <strong>₱39.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Graham Ice Cream - Mango -->
                        <div class="product-card" data-category="dessert" data-id="graham-mango" data-name="Graham Ice Cream - Mango" data-price="39">
                            <div class="product-image"><i class="fas fa-ice-cream"></i></div>
                            <div class="product-info">
                                <h3>Graham Ice Cream</h3>
                                <p>Mango</p>
                                <div class="product-bottom">
                                    <strong>₱39.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Chao Fan Rice -->
                        <div class="product-card" data-category="add-on" data-id="chao-fan-rice" data-name="Chao Fan Rice" data-price="30">
                            <div class="product-image"><i class="fas fa-bowl-rice"></i></div>
                            <div class="product-info">
                                <h3>Chao Fan Rice</h3>
                                <p>Add-on</p>
                                <div class="product-bottom">
                                    <strong>₱30.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Plain Rice -->
                        <div class="product-card" data-category="add-on" data-id="plain-rice" data-name="Plain Rice" data-price="20">
                            <div class="product-image"><i class="fas fa-bowl-rice"></i></div>
                            <div class="product-info">
                                <h3>Plain Rice</h3>
                                <p>Add-on</p>
                                <div class="product-bottom">
                                    <strong>₱20.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Garlic Mayo Dip -->
                        <div class="product-card" data-category="add-on" data-id="garlic-mayo-dip" data-name="Garlic Mayo Dip" data-price="20">
                            <div class="product-image"><i class="fas fa-jar"></i></div>
                            <div class="product-info">
                                <h3>Garlic Mayo Dip</h3>
                                <p>Add-on sauce</p>
                                <div class="product-bottom">
                                    <strong>₱20.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Extra Chicken -->
                        <div class="product-card" data-category="add-on" data-id="extra-chicken" data-name="Extra Chicken" data-price="59">
                            <div class="product-image"><i class="fas fa-drumstick-bite"></i></div>
                            <div class="product-info">
                                <h3>Extra Chicken</h3>
                                <p>Add-on</p>
                                <div class="product-bottom">
                                    <strong>₱59.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Extra Sauce -->
                        <div class="product-card" data-category="add-on" data-id="extra-sauce" data-name="Extra Sauce" data-price="15">
                            <div class="product-image"><i class="fas fa-jar"></i></div>
                            <div class="product-info">
                                <h3>Extra Sauce</h3>
                                <p>Add-on</p>
                                <div class="product-bottom">
                                    <strong>₱15.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Juice Pitcher -->
                        <div class="product-card" data-category="juice" data-id="juice-pitcher" data-name="Juice Pitcher" data-price="99">
                            <div class="product-image"><i class="fas fa-wine-glass"></i></div>
                            <div class="product-info">
                                <h3>Juice Pitcher</h3>
                                <p>Iced Tea / Red Tea</p>
                                <div class="product-bottom">
                                    <strong>₱99.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>

                        <!-- Juice Cup -->
                        <div class="product-card" data-category="juice" data-id="juice-cup" data-name="Juice Cup" data-price="25">
                            <div class="product-image"><i class="fas fa-cup-straw"></i></div>
                            <div class="product-info">
                                <h3>Juice Cup</h3>
                                <p>Iced Tea / Red Tea</p>
                                <div class="product-bottom">
                                    <strong>₱25.00</strong>
                                    <span class="click-to-add">Click to add</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ORDER PANEL - RIGHT SIDE -->
                <section class="order-panel">
                    <div class="order-panel-header">
                        <div>
                            <h2>Current Order</h2>
                            <p id="itemCount">0 items</p>
                        </div>
                        <button class="clear-btn" id="clearCart"><i class="fas fa-trash"></i> Clear</button>
                    </div>

                    <div style="padding:0 18px 12px; display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label for="walkinCustomerName" style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">Customer Name</label>
                            <input id="walkinCustomerName" type="text" placeholder="Walk-in Customer" value="" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label for="walkinOrderType" style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">Order Type</label>
                            <select id="walkinOrderType" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;box-sizing:border-box;">
                                <option value="Dine-In">Dine-In</option>
                                <option value="Take-Out">Take-Out</option>
                            </select>
                        </div>
                        <div id="walkinTableNumberWrap" style="grid-column: 1 / -1;">
                            <label for="walkinTableNumber" style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">Table Number</label>
                            <input id="walkinTableNumber" type="text" placeholder="e.g. 12" maxlength="20" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;box-sizing:border-box;">
                        </div>
                    </div>

                    <div class="cart-items" id="cartItems">
                        <div class="empty-cart">
                            <i class="fas fa-cart-shopping"></i>
                            <h3>No items added</h3>
                            <p>Select a product to start an order</p>
                        </div>
                    </div>

                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <strong id="subtotal">₱0.00</strong>
                        </div>
                        <div class="total-row">
                            <span>Total</span>
                            <strong id="total">₱0.00</strong>
                        </div>
                    </div>

                    <div class="order-note">
                        <label for="orderNote">Note:</label>
                        <textarea id="orderNote" placeholder="Add a note for this order..."></textarea>
                    </div>

                    <div class="payment-method-section">
                        <label for="paymentMethod">Payment Method</label>
                        <select id="paymentMethod">
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                    <div id="gcashQRSection" class="gcash-qr-section">
                        <h4>Scan to Pay using GCash</h4>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=SweetChickenGCash" alt="GCash QR Code" class="gcash-qr">
                        <p>Please scan the QR code to complete the payment.</p>
                    </div>

                    <div class="payment-section">
                        <label for="payment">Cash Received</label>
                        <div class="payment-input">
                            <span>₱</span>
                            <input type="number" id="payment" placeholder="0.00" min="0">
                        </div>
                        <div class="change-row">
                            <span>Change</span>
                            <strong id="change">₱0.00</strong>
                        </div>
                    </div>

                    <button type="button" class="complete-btn" id="completeTransaction">
                        <i class="fas fa-check-circle"></i> Complete Transaction
                    </button>
                </section>
            </div>
        </main>

        <!-- 2. SALES SUMMARY CONTENT -->
        <main class="page-content" id="summary-content">
            <div class="page-header">
                <div>
                    <h1>Sales Summary</h1>
                    <p>Overview of completed transactions</p>
                </div>
                <div>
                    <select id="salesPeriodDropdown" style="padding: 8px 14px; border: 1px solid var(--border); border-radius: 10px; background: #fff; font-size: 14px; font-weight: 500; outline: none; cursor: pointer;">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="annual">Annual</option>
                    </select>
                </div>
            </div>

            <!-- Analytics Summary Cards -->
            <div class="analytics-cards-grid">
                <div class="analytics-card">
                    <div class="analytics-icon sales">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="analytics-info">
                        <h4>Total Sales</h4>
                        <h2 id="summaryTotalSales">₱0.00</h2>
                    </div>
                </div>
                <div class="analytics-card">
                    <div class="analytics-icon orders">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="analytics-info">
                        <h4>Total Orders</h4>
                        <h2 id="summaryTotalOrders">0</h2>
                    </div>
                </div>
                <div class="analytics-card">
                    <div class="analytics-icon items">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="analytics-info">
                        <h4>Items Sold</h4>
                        <h2 id="summaryItemsSold">0</h2>
                    </div>
                </div>
            </div>

            <!-- SALES CHART SECTION -->
            <div class="menu-section" style="margin-bottom: 24px;">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Sales Performance Graph</h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Transaction Records History Section -->
            <div class="menu-section">
                <h3 style="font-size: 16px; margin-bottom: 4px;">Transaction Records</h3>
                <p style="font-size: 12px; color: var(--text-light); margin-bottom: 12px;">History of completed transactions</p>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date & Time</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Order Type</th>
                            <th>Payment Method</th>
                            <th>Payment Status</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="transactionRecordsList">
                        <tr><td colspan="8" style="text-align:center;">No transaction records found.</td></tr>
                    </tbody>
                </table>
            </div>
        </main>

        <!-- 3. ONLINE ORDERS CONTENT -->
        <main class="page-content" id="online-content">
            <div class="page-header">
                <div>
                    <h1>Online Orders</h1>
                    <p>Manage online and walk-in orders sent to the kitchen</p>
                </div>
                <div class="live-status">
                    <span class="live-dot"></span>
                    Live Orders
                </div>
            </div>

            <div class="order-filters">
                <button class="filter-btn active" data-filter="all" type="button">All Orders</button>
                <button class="filter-btn" data-filter="pending" type="button">Pending</button>
                <button class="filter-btn" data-filter="preparing" type="button">Preparing</button>
                <button class="filter-btn" data-filter="ready" type="button">Ready</button>
                <button class="filter-btn" data-filter="completed" type="button">Completed</button>
            </div>

            <div class="orders-layout">
                <section class="online-order-list" id="onlineOrderList"></section>
                <section class="order-details" id="orderDetails">
                    <div class="no-order-selected">
                        <i class="fas fa-receipt"></i>
                        <h2>Select an Order</h2>
                        <p>Select an online order to view its details.</p>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<!-- CUSTOMIZATION MODAL -->
<div class="customization-modal" id="customizationModal">
    <div class="customization-modal-content">
        <h2 id="modalProductName" style="font-size: 18px;">Product Name</h2>
        <p id="modalProductDescription" style="color:var(--gray); font-size:12px; margin:4px 0 12px;">Description</p>
        <strong id="modalProductPrice" style="color:var(--primary); font-size:16px;">₱0.00</strong>

        <div id="flavorSection" class="custom-section" style="display:none;">
            <h4 style="font-size:13px; margin-bottom:4px;">Flavors</h4>
            <p id="flavorInstruction" style="font-size:11px; color:var(--gray); margin-bottom:6px;"></p>
            <div id="flavorSelectors"></div>
            <small id="flavorError" style="color:var(--danger); font-size: 11px;"></small>
        </div>

        <div id="riceSection" class="custom-section" style="display:none;">
            <h4 style="font-size:13px; margin-bottom:4px;">Rice</h4>
            <p id="riceInstruction" style="font-size:11px; color:var(--gray); margin-bottom:6px;"></p>
            <div id="riceSelectors"></div>
            <small id="riceError" style="color:var(--danger); font-size: 11px;"></small>
        </div>

        <div id="drinkSection" class="custom-section" style="display:none;">
            <h4 style="font-size:13px; margin-bottom:4px;">Drinks</h4>
            <p id="drinkInstruction" style="font-size:11px; color:var(--gray); margin-bottom:6px;"></p>
            <div id="drinkSelectors"></div>
            <small id="drinkError" style="color:var(--danger); font-size: 11px;"></small>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:14px; font-size: 13px;">
            <span>Quantity:</span>
            <div style="display:flex; align-items:center; gap:8px;">
                <button class="quantity-btn" id="decreaseQuantity">−</button>
                <span id="modalQuantity" style="font-weight: 600;">1</span>
                <button class="quantity-btn" id="increaseQuantity">+</button>
            </div>
        </div>

        <div class="modal-footer">
            <button class="add-to-cart-btn" id="addToCart">Add to Cart</button>
            <button id="cancelCustomization" style="border:none; padding:10px; border-radius:8px; cursor:pointer; font-size: 13px; font-weight: 600;">Cancel</button>
        </div>
    </div>
</div>

<!-- RECEIPT MODAL -->
<div class="receipt-overlay" id="receiptOverlay">
    <div class="receipt-modal">
        <div class="receipt-header">
            <h2 style="font-size: 18px;">Transaction Completed</h2>
            <p style="font-size: 12px;">Receipt for your completed order</p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <div><span>Transaction No.</span> <strong id="receiptTransactionNumber">SC-000001</strong></div>
            <div><span>Date & Time</span> <strong id="receiptDateTime">-</strong></div>
            <div id="receiptTableNumberRow" style="display:none;"><span>Table No.</span> <strong id="receiptTableNumber">-</strong></div>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-items" id="receiptItems" style="max-height: 150px; overflow-y: auto;"></div>
        <div class="receipt-divider"></div>
        <div class="receipt-summary">
            <div><span>Subtotal</span> <strong id="receiptSubtotal">₱0.00</strong></div>
            <div class="receipt-total"><span>Total</span> <strong id="receiptTotal">₱0.00</strong></div>
            <div><span>Cash Received</span> <strong id="receiptPayment">₱0.00</strong></div>
            <div><span>Change</span> <strong id="receiptChange" style="color: #16a34a;">₱0.00</strong></div>
        </div>
        <div class="receipt-actions">
            <button id="printReceipt"><i class="fas fa-print"></i> Print Receipt</button>
            <button id="closeReceipt">Close</button>
        </div>
    </div>
</div>

<!-- JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="js/session_guard.js"></script>
<script src="js/cashier_dashboard.js"></script>
</body>
</html>