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

if (($_SESSION['role'] ?? '') !== 'customer') {
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
    <title>Sweet Chicken | Order Online</title>

    <!-- GOOGLE FONT -->
     <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

    <link rel="stylesheet" href="css/customer_dashboard.css">
</head>
<body>

<!-- ================================
     NAVBAR
================================ -->
<header class="navbar">
    <a href="#home" class="logo">
        <img src="images/logo.png" alt="Sweet Chicken Logo" onerror="this.style.display='none'">
        <span>Sweet Chicken</span>
    </a>

    <nav>
        <a href="#home">Home</a>
        <a href="#menu">Menu</a>
        <a href="#about">About</a>
    </nav>

    <div class="navbar-right">
        <button id="trackOrderBtn" class="header-action-btn" type="button">
            <i class="fas fa-location-dot"></i>
            <span>Track Order</span>
        </button>

        <!-- PROFILE WIDGET -->
        <div class="profile" id="profileBtn">
            <img src="https://i.pravatar.cc/100" alt="Profile">
            <i class="fas fa-chevron-down" style="font-size: 10px; color: rgba(255, 255, 255, 0.75);"></i>
        </div>
    </div>
</header>

<!-- Profile Overlay & Dropdown Menu -->
<div class="profile-overlay" id="profileOverlay"></div>
<div class="dropdown-menu" id="profileMenu">
    <div class="profile-dropdown-header">
        <img src="https://i.pravatar.cc/100" alt="Profile">
        <div>
            <h4 style="font-size: 16px; margin: 0; font-weight: 700;">John Customer</h4>
            <small style="color: var(--text-light);">Customer Account</small>
        </div>
    </div>
    <a href="#"><i class="fas fa-user"></i> My Profile</a>
    <a href="#"><i class="fas fa-key"></i> Change Password</a>
    <a href="#"><i class="fas fa-history"></i> Order History</a>
    <div class="dropdown-divider"></div>
    <a href="#" id="darkModeToggle">
        <i class="fas fa-moon" id="darkModeIcon"></i> 
        <span id="darkModeText">Dark Mode</span>
    </a>
    <div class="dropdown-divider"></div>
    <a href="logout.php"
   onclick="return confirm('Are you sure you want to logout?');"
   style="color: #DC2626;">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>

</div>

<!-- ================================
     HERO SECTION
================================ -->
<section class="hero" id="home">
    <div class="hero-content">
        <p class="hero-label">FRESH • CRISPY • DELICIOUS</p>
        <h1>Your Favorite <span>Sweet Chicken</span> Is Here!</h1>
        <p>Enjoy delicious chicken meals, refreshing drinks, desserts, and more.</p>
        <a href="#menu" class="hero-button">
            Order Now <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</section>

<!-- ================================
     MENU SECTION
================================ -->
<section class="menu-section" id="menu">
    <div class="section-heading">
        <p>OUR MENU</p>
        <h2>Choose Your Favorite Meal</h2>
        <span>Customize your order and add it to your cart.</span>
    </div>

    <div class="category-buttons">
        <button class="category-btn active" data-category="all">All</button>
        <button class="category-btn" data-category="barkada">Barkada Meals</button>
        <button class="category-btn" data-category="chicken">Flavored Chicken Combo</button>
        <button class="category-btn" data-category="soda">Soda</button>
        <button class="category-btn" data-category="dessert">Dessert</button>
        <button class="category-btn" data-category="addons">Add Ons</button>
        <button class="category-btn" data-category="juice">Juice</button>
    </div>

    <!-- PRODUCT GRID -->
    <div class="product-grid" id="productGrid">
        <!-- BARKADA 7 -->
        <div class="product-card" data-category="barkada" data-id="barkada-7" data-name="Barkada Feast Combo - 7 pcs" data-price="439" data-flavors="3" data-rice="2" data-drinks="2">
            <div class="product-icon"><i class="fas fa-drumstick-bite"></i></div>
            <div class="product-details">
                <h3>Barkada Feast Combo</h3>
                <p>7 pcs • Choose 3 flavors<br>2 rice • 2 drinks</p>
                <div class="product-footer">
                    <strong>₱439.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- BARKADA 4 -->
        <div class="product-card" data-category="barkada" data-id="barkada-4" data-name="Barkada Feast Combo - 4 pcs" data-price="299" data-flavors="2" data-rice="2" data-drinks="2">
            <div class="product-icon"><i class="fas fa-drumstick-bite"></i></div>
            <div class="product-details">
                <h3>Barkada Feast Combo</h3>
                <p>4 pcs • Choose 2 flavors<br>2 rice • 2 drinks</p>
                <div class="product-footer">
                    <strong>₱299.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- 3 PCS CHICKEN -->
        <div class="product-card" data-category="chicken" data-id="chicken-3" data-name="3pcs Chicken Combo" data-price="209" data-flavors="1">
            <div class="product-icon"><i class="fas fa-drumstick-bite"></i></div>
            <div class="product-details">
                <h3>3pcs Chicken Combo</h3>
                <p>Choose 1 flavor</p>
                <div class="product-footer">
                    <strong>₱209.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- 2 PCS CHICKEN -->
        <div class="product-card" data-category="chicken" data-id="chicken-2" data-name="2pcs Chicken Combo" data-price="169" data-flavors="1">
            <div class="product-icon"><i class="fas fa-drumstick-bite"></i></div>
            <div class="product-details">
                <h3>2pcs Chicken Combo</h3>
                <p>Choose 1 flavor</p>
                <div class="product-footer">
                    <strong>₱169.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- CHICKEN POPS -->
        <div class="product-card" data-category="chicken" data-id="chicken-pops" data-name="Chicken Pops Combo" data-price="119" data-flavors="1">
            <div class="product-icon"><i class="fas fa-bowl-food"></i></div>
            <div class="product-details">
                <h3>Chicken Pops Combo</h3>
                <p>Choose 1 flavor</p>
                <div class="product-footer">
                    <strong>₱119.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- SODA 1.5L -->
        <div class="product-card" data-category="soda" data-id="soda-15l" data-name="Soda 1.5L" data-price="85" data-drink="true">
            <div class="product-icon"><i class="fas fa-bottle-water"></i></div>
            <div class="product-details">
                <h3>Soda 1.5L</h3>
                <p>Coke / Sprite / Royal</p>
                <div class="product-footer">
                    <strong>₱85.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- SODA MISMO -->
        <div class="product-card" data-category="soda" data-id="soda-mismo" data-name="Soda Mismo" data-price="25" data-drink="true">
            <div class="product-icon"><i class="fas fa-bottle-water"></i></div>
            <div class="product-details">
                <h3>Soda Mismo</h3>
                <p>Coke / Sprite / Royal</p>
                <div class="product-footer">
                    <strong>₱25.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- DESSERT -->
        <div class="product-card" data-category="dessert" data-id="graham-blueberry" data-name="Graham Ice Cream - Blueberry" data-price="39">
            <div class="product-icon"><i class="fas fa-ice-cream"></i></div>
            <div class="product-details">
                <h3>Graham Ice Cream</h3>
                <p>Blueberry</p>
                <div class="product-footer">
                    <strong>₱39.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="product-card" data-category="dessert" data-id="graham-strawberry" data-name="Graham Ice Cream - Strawberry" data-price="39">
            <div class="product-icon"><i class="fas fa-ice-cream"></i></div>
            <div class="product-details">
                <h3>Graham Ice Cream</h3>
                <p>Strawberry</p>
                <div class="product-footer">
                    <strong>₱39.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="product-card" data-category="dessert" data-id="graham-mango" data-name="Graham Ice Cream - Mango" data-price="39">
            <div class="product-icon"><i class="fas fa-ice-cream"></i></div>
            <div class="product-details">
                <h3>Graham Ice Cream</h3>
                <p>Mango</p>
                <div class="product-footer">
                    <strong>₱39.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- ADD ONS -->
        <div class="product-card" data-category="addons" data-id="chao-fan" data-name="Chao Fan Rice" data-price="30">
            <div class="product-icon"><i class="fas fa-bowl-rice"></i></div>
            <div class="product-details">
                <h3>Chao Fan Rice</h3>
                <p>Add-on</p>
                <div class="product-footer">
                    <strong>₱30.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="product-card" data-category="addons" data-id="plain-rice" data-name="Plain Rice" data-price="20">
            <div class="product-icon"><i class="fas fa-bowl-rice"></i></div>
            <div class="product-details">
                <h3>Plain Rice</h3>
                <p>Add-on</p>
                <div class="product-footer">
                    <strong>₱20.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="product-card" data-category="addons" data-id="garlic-mayo" data-name="Garlic Mayo Dip" data-price="20">
            <div class="product-icon"><i class="fas fa-bottle-droplet"></i></div>
            <div class="product-details">
                <h3>Garlic Mayo Dip</h3>
                <p>Add-on</p>
                <div class="product-footer">
                    <strong>₱20.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="product-card" data-category="addons" data-id="extra-chicken" data-name="Extra Chicken" data-price="59">
            <div class="product-icon"><i class="fas fa-drumstick-bite"></i></div>
            <div class="product-details">
                <h3>Extra Chicken</h3>
                <p>Add-on</p>
                <div class="product-footer">
                    <strong>₱59.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="product-card" data-category="addons" data-id="extra-sauce" data-name="Extra Sauce" data-price="15">
            <div class="product-icon"><i class="fas fa-bottle-droplet"></i></div>
            <div class="product-details">
                <h3>Extra Sauce</h3>
                <p>Add-on</p>
                <div class="product-footer">
                    <strong>₱15.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <!-- JUICE -->
        <div class="product-card" data-category="juice" data-id="juice-pitcher" data-name="Juice Pitcher" data-price="99" data-juice="true">
            <div class="product-icon"><i class="fas fa-glass-water"></i></div>
            <div class="product-details">
                <h3>Juice Pitcher</h3>
                <p>Iced Tea / Red Tea</p>
                <div class="product-footer">
                    <strong>₱99.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="product-card" data-category="juice" data-id="juice-cup" data-name="Juice Cup" data-price="25" data-juice="true">
            <div class="product-icon"><i class="fas fa-glass-water"></i></div>
            <div class="product-details">
                <h3>Juice Cup</h3>
                <p>Iced Tea / Red Tea</p>
                <div class="product-footer">
                    <strong>₱25.00</strong>
                    <button class="add-button"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================
     ABOUT SECTION
================================ -->

<section class="about-section" id="about">
    <div class="about-container">

    <div class="about-visual">
        <div class="about-visual-content">
            <i class="fas fa-drumstick-bite"></i>
            <h3>Sweet Chicken</h3>
            <p>Good food. Great moments. Made for you.</p>
        </div>
    </div>

    <div class="about-content">

        <span class="about-label">ABOUT SWEET CHICKEN</span>

        <h2>Made with care,<br><span>served with heart.</span></h2>

        <p class="about-lead">
            Welcome to Sweet Chicken — your place for delicious,
            freshly prepared chicken meals, refreshing drinks,
            and satisfying food for every occasion.
        </p>

        <p>
            We believe great food should be simple, enjoyable, and
            made with care. Whether you're dining in, taking your
            meal to go, or ordering for the whole barkada, we're
            here to give you a convenient and satisfying experience.
        </p>

        <div class="about-highlights">

            <div class="about-highlight">
                <i class="fas fa-utensils"></i>
                <strong>Quality Food</strong>
                <span>Prepared with care</span>
            </div>

            <div class="about-highlight">
                <i class="fas fa-heart"></i>
                <strong>Customer First</strong>
                <span>Your satisfaction matters</span>
            </div>

            <div class="about-highlight">
                <i class="fas fa-bolt"></i>
                <strong>Fast Service</strong>
                <span>Simple & convenient</span>
            </div>

        </div>

    </div>

</div>

</section>

<!-- ================================
     CART DRAWER
================================ -->

<div class="cart-overlay" id="cartOverlay"></div>
<aside class="cart-drawer" id="cartDrawer">
    <div class="cart-header">
        <div>
            <h2>Your Cart</h2>
            <p id="cartItemCount">0 items</p>
        </div>
        <button id="closeCart"><i class="fas fa-times"></i></button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="empty-cart">
            <i class="fas fa-cart-shopping"></i>
            <h3>Your cart is empty</h3>
            <p>Add delicious food to get started.</p>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Total</span>
            <strong id="cartTotal">₱0.00</strong>
        </div>
        <button class="checkout-button" id="checkoutButton">
            Proceed to Checkout <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</aside>

<!-- ================================
     FLOATING CART BUTTON
================================ -->
<button class="cart-button" id="openCart" type="button">
    <i class="fas fa-cart-shopping" style="font-size: 22px;"></i>
    <span>Cart</span>
    <b id="cartCount" class="cart-count">0</b>
</button>

<!-- ================================
     TRACK ORDER MODAL
================================ -->
<div id="trackOrderModal" class="track-modal">
    <div class="track-box">
        <button type="button" id="closeTrackModal" class="close-track"><i class="fas fa-times"></i></button>
        <div class="track-header">
            <h2>Order Tracking</h2>
            <p id="trackModalOrderNum">Order #SC-ONLINE-000000</p>
        </div>

        <div class="track-status-card">
            <div class="track-status-info">
                <span>Current Status</span>
                <span id="trackStatusBadge" class="track-status-badge">Processing</span>
            </div>
            <!-- Timeline progress -->
            <div class="order-timeline">
                <div class="timeline-step completed" id="stepPlaced">
                    <div class="timeline-icon"><i class="fas fa-check"></i></div>
                    <span>Placed</span>
                </div>
                <div class="timeline-step active" id="stepPreparing">
                    <div class="timeline-icon"><i class="fas fa-fire-burner"></i></div>
                    <span>Preparing</span>
                </div>
                <div class="timeline-step" id="stepReady">
                    <div class="timeline-icon"><i class="fas fa-box"></i></div>
                    <span>Ready</span>
                </div>
                <div class="timeline-step" id="stepCompleted">
                    <div class="timeline-icon"><i class="fas fa-flag-checkered"></i></div>
                    <span>Completed</span>
                </div>
            </div>
        </div>

        <div class="track-details-grid">
            <div class="track-detail-item">
                <span>Order Type</span>
                <strong id="trackOrderType">Dine-In</strong>
            </div>
            <div class="track-detail-item">
                <span>Payment Method</span>
                <strong id="trackPaymentMethod">Cash</strong>
            </div>
            <div class="track-detail-item" style="grid-column: span 2;">
                <span>Customer Details</span>
                <strong id="trackCustomerInfo">John Doe (09123456789)</strong>
            </div>
        </div>

        <div class="track-items-summary" id="trackItemsSummary"></div>

        <div class="checkout-total" style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(0, 0, 0, 0.04);">
            <span>Total Amount</span>
            <strong id="trackTotal">₱0.00</strong>
        </div>
    </div>
</div>

<!-- ================================
     CUSTOMIZATION MODAL
================================ -->
<div class="customization-modal" id="customModal">
    <div class="customization-content">
        <button type="button" class="close-customization" id="closeCustomModal">×</button>
        <h2 id="customProductName">Customize Product</h2>
        <p id="customProductDescription"></p>
        <div class="custom-price" id="customProductPrice">₱0.00</div>

        <!-- FLAVOR -->
        <div class="custom-option" id="flavorOption">
            <label>Choose Flavor</label>
            <div id="flavorSelectors"></div>
            <small id="flavorError"></small>
        </div>

        <!-- WITH -->
        <div class="custom-option" id="withOption">
            <label>With</label>
            <div id="withSelectors"></div>
        </div>

        <!-- RICE -->
        <div class="custom-option" id="riceOption">
            <label>Add Rice</label>
            <select id="riceSelect" class="drink-rice-selector"></select>
        </div>

        <!-- ADD DRINKS -->
        <div class="custom-option" id="addDrinkOption">
            <label>Add Drinks</label>
            <select id="addDrinkSelect" class="drink-rice-selector">
                <option value="">Select Drink</option>
                <option value="Coke">Coke</option>
                <option value="Sprite">Sprite</option>
                <option value="Royal">Royal</option>
            </select>
        </div>

        <!-- JUICE -->
        <div class="custom-option" id="juiceOption">
            <label>Choose Juice</label>
            <select id="juiceSelect" class="drink-rice-selector">
                <option value="Iced Tea">Iced Tea</option>
                <option value="Red Tea">Red Tea</option>
            </select>
        </div>

        <!-- QUANTITY -->
        <div class="quantity-section">
            <label>Quantity</label>
            <div class="quantity-controls">
                <button type="button" id="decreaseQuantity">−</button>
                <span id="customQuantity">1</span>
                <button type="button" id="increaseQuantity">+</button>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="customization-actions">
            <button type="button" id="cancelCustomModal">Cancel</button>
            <button type="button" id="addToCart">Add to Cart</button>
        </div>
    </div>
</div>

<!-- ================================
     CHECKOUT MODAL
================================ -->
<div id="checkoutModal" class="checkout-modal">
    <div class="checkout-box">
        <button type="button" id="closeCheckout" class="close-checkout">×</button>
        <div class="checkout-header">
            <h2>Confirm Your Order</h2>
            <p>Please review your order details before placing your order.</p>
        </div>

        <!-- ORDER SUMMARY -->
        <div class="checkout-section">
            <h3>Order Summary</h3>
            <div id="checkoutOrderSummary" class="checkout-order-summary"></div>
        </div>

        <!-- ORDER TYPE -->
        <div class="checkout-section">
            <h3>Order Type</h3>
            <div class="order-type-options">
                <label class="order-type-option">
                    <input type="radio" name="orderType" value="Dine-In">
                    <span>🍽️ Dine-In</span>
                </label>
                <label class="order-type-option">
                    <input type="radio" name="orderType" value="Take-Out">
                    <span>🥡 Take-Out</span>
                </label>
            </div>
            <p id="orderTypeError" class="checkout-error"></p>
        </div>

        <!-- CUSTOMER INFORMATION -->
        <div id="customerInformation" class="checkout-section">
            <h3>Customer Information</h3>
            <div class="form-group">
                <label for="customerName">Full Name</label>
                <input type="text" id="customerName" placeholder="Enter your full name">
            </div>
            <div class="form-group">
                <label for="customerPhone">Phone Number</label>
                <input type="text" id="customerPhone" placeholder="09XXXXXXXXX">
            </div>
        </div>

        <!-- PAYMENT METHOD -->
        <div class="checkout-section">
            <h3>Payment Method</h3>
            <div id="paymentMethods" class="payment-methods"></div>
            <p id="paymentError" class="checkout-error"></p>
        </div>

        <!-- GCASH PAYMENT -->
        <div id="gcashPayment" class="gcash-payment">
            <h3>GCash Payment</h3>
            <p>Scan the QR code to complete your payment.</p>
            <img src="images/QR_Gcash.jpg" alt="GCash QR Code" onerror="this.style.display='none'">
            <label for="gcashReference">GCash Reference Number</label>
            <input type="text" id="gcashReference" placeholder="Enter GCash reference number">
        </div>

        <!-- TOTAL -->
        <div class="checkout-total">
            <span>Order Total</span>
            <strong id="checkoutTotal">₱0.00</strong>
        </div>

        <!-- BUTTONS -->
        <div class="checkout-buttons">
            <button type="button" id="backToCart">Back to Cart</button>
            <button type="button" id="placeOrderBtn">Confirm Order</button>
        </div>
    </div>
</div>

<!-- ================================
     SUCCESS MODAL
================================ -->
<div class="success-overlay" id="successOverlay">
    <div class="success-modal">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h2>Order Placed Successfully!</h2>
        <p>Thank you for ordering from Sweet Chicken.</p>
        <div class="order-number">
            Order No.
            <strong id="orderNumber">SC-ONLINE-000001</strong>
        </div>
        <button id="closeSuccess" type="button">Continue Shopping</button>
    </div>
</div>

<!-- ================================
     JAVASCRIPT
================================ -->
<script src="js/session_guard.js"></script>
<script src="js/customer_dashboard.js"></script>

</body>
</html>