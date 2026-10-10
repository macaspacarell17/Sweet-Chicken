<?php
// Public landing page - no auth required.
// Staff/customers continue on to loginpage.php to sign in or register.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sweet Chicken - Fried Fresh, Glazed Sweet</title>
<meta name="description" content="Sweet Chicken serves house-glazed fried chicken combos, sides, and drinks for dine-in, take-out, or online order with live tracking.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="css/index.css">
</head>
<body>

<nav class="site-nav">
    <div class="wrap">
        <a href="#top" class="brand">
            <img src="images/logo.png" alt="Sweet Chicken logo">
            <span>Sweet Chicken</span>
        </a>

        <div class="nav-links" id="navLinks">
            <a href="#menu">Menu</a>
            <a href="#how-it-works">How it works</a>
            <a href="#gallery">Gallery</a>
        </div>

        <div class="nav-actions">
            <a href="loginpage.php" class="btn btn-flame">Order Online</a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<header class="hero" id="top">
    <div class="wrap">
        <div class="hero-copy">
            <span class="eyebrow">Est. 2025 &middot; Original Taste</span>
            <h1>Fried today.<br>Glazed the <em>Sweet Chicken</em> way.</h1>
            <p>House-made glaze on chicken fried fresh to order, plus rice, dips, and drinks &mdash; ready for dine-in or take-out, with an online order you can track live from the kitchen to your table.</p>

            <div class="hero-actions">
                <a href="loginpage.php" class="btn btn-flame">Order Online</a>
                <a href="#menu" class="btn btn-ghost">See the Menu</a>
            </div>

            <div class="hero-meta">
                <div>
                    <strong>3</strong>
                    <span>Ways to order &mdash; dine-in, take-out, online</span>
                </div>
                <div>
                    <strong>Live</strong>
                    <span>Order tracking from kitchen to pickup</span>
                </div>
                <div>
                    <strong>Cash / GCash</strong>
                    <span>Pay however's easiest</span>
                </div>
            </div>
        </div>

        <div class="hero-art">
            <div class="frame-back"></div>
            <div class="frame-photo">
                <img src="images/524115301_122152772348731309_886394131801260326_n.jpg" alt="Sweet Chicken glazed chicken tray with rice">
            </div>
            <div class="sticker">Est.<br>2025<br>Original</div>
        </div>
    </div>
</header>

<section class="value-strip">
    <div class="wrap">
        <div class="value-item">
            <div class="icon">&#127855;</div>
            <div>
                <h4>House-glazed sauce</h4>
                <p>Made fresh in-house, not bottled</p>
            </div>
        </div>
        <div class="value-item">
            <div class="icon">&#128293;</div>
            <div>
                <h4>Fried to order</h4>
                <p>Nothing sits under a heat lamp</p>
            </div>
        </div>
        <div class="value-item">
            <div class="icon">&#129377;</div>
            <div>
                <h4>Dine-in, take-out, or online</h4>
                <p>Order the way that fits your day</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="menu">
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow">On the menu</span>
            <h2>Built for sharing, sized for one</h2>
            <p>From solo cravings to a full barkada feast &mdash; every combo comes with your pick of flavor.</p>
        </div>

        <div class="menu-hero-cards">
            <div class="ticket">
                <div class="ticket-photo">
                    <img src="images/chicken.png" alt="Barkada Feast Combo glazed chicken">
                </div>
                <div class="ticket-body">
                    <div class="ticket-row">
                        <h3>Barkada Feast Combo</h3>
                        <span class="ticket-price">&#8369;439</span>
                    </div>
                    <p>7 pcs chicken &middot; pick 3 flavors &middot; 2 rice &middot; 2 drinks</p>
                    <div class="ticket-perf"></div>
                </div>
            </div>

            <div class="ticket">
                <div class="ticket-photo">
                    <img src="images/fries.png" alt="3pcs Chicken Combo with fries">
                </div>
                <div class="ticket-body">
                    <div class="ticket-row">
                        <h3>3pcs Chicken Combo</h3>
                        <span class="ticket-price">&#8369;209</span>
                    </div>
                    <p>Crispy fried &middot; pick 1 flavor &middot; served with fries</p>
                    <div class="ticket-perf"></div>
                </div>
            </div>
        </div>

        <div class="menu-list">
            <div class="menu-list-row">
                <div>
                    <h4>Barkada Feast Combo &ndash; 4 pcs</h4>
                    <span class="tag">Pick 2 flavors &middot; 2 rice &middot; 2 drinks</span>
                </div>
                <span class="price">&#8369;299</span>
            </div>
            <div class="menu-list-row">
                <div>
                    <h4>2pcs Chicken Combo</h4>
                    <span class="tag">Pick 1 flavor</span>
                </div>
                <span class="price">&#8369;169</span>
            </div>
            <div class="menu-list-row">
                <div>
                    <h4>Chicken Pops Combo</h4>
                    <span class="tag">Pick 1 flavor</span>
                </div>
                <span class="price">&#8369;119</span>
            </div>
            <div class="menu-list-row">
                <div>
                    <h4>Chao Fan Rice</h4>
                    <span class="tag">Add-on</span>
                </div>
                <span class="price">&#8369;30</span>
            </div>
            <div class="menu-list-row">
                <div>
                    <h4>Graham Ice Cream</h4>
                    <span class="tag">Blueberry &middot; Strawberry &middot; Mango</span>
                </div>
                <span class="price">&#8369;39</span>
            </div>
            <div class="menu-list-row">
                <div>
                    <h4>Juice Pitcher</h4>
                    <span class="tag">Iced Tea &middot; Red Tea</span>
                </div>
                <span class="price">&#8369;99</span>
            </div>
        </div>
    </div>
</section>

<section class="gallery-banner" id="gallery">
    <img src="images/668999135_122187738278731309_9045860258197357056_n.jpg" alt="Full Sweet Chicken table spread with chicken, pasta, fries, and rice">
    <div class="overlay">
        <div class="wrap">
            <h2>Every table, loaded.</h2>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="gallery-strip">
            <img src="images/chicken.png" alt="Glazed chicken close-up">
            <img src="images/fries.png" alt="Fried chicken and fries basket">
            <img src="images/pasta.png" alt="Creamy chicken pasta">
            <img src="images/sandwich.png" alt="Chicken sandwich sliced in half">
        </div>
    </div>
</section>

<section class="section" id="how-it-works" style="background: var(--white);">
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow">Ordering online</span>
            <h2>From craving to counter in three steps</h2>
        </div>

        <div class="steps">
            <div class="step">
                <span class="num">1</span>
                <h3>Browse and build your order</h3>
                <p>Pick your combo, flavors, and add-ons, then choose dine-in or take-out.</p>
            </div>
            <div class="step">
                <span class="num">2</span>
                <h3>Pay cash or GCash</h3>
                <p>Settle up online with GCash or at the counter &mdash; whichever's easier for you.</p>
            </div>
            <div class="step">
                <span class="num">3</span>
                <h3>Track it live</h3>
                <p>Watch your order move from the kitchen to ready-for-pickup in real time.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-banner">
    <div class="wrap">
        <div>
            <h2>Hungry yet?</h2>
            <p>Create an account or sign in to start your order.</p>
        </div>
        <a href="loginpage.php" class="btn btn-cream">Order Online</a>
    </div>
</section>

<footer>
    <div class="wrap">
        <div class="footer-top">
            <div>
                <div class="footer-brand">
                    <img src="images/logo.png" alt="Sweet Chicken logo">
                    <span>Sweet Chicken</span>
                </div>
                <p>House-glazed fried chicken, fried fresh and served your way &mdash; dine-in, take-out, or online.</p>
            </div>

            <div class="footer-col">
                <h5>Explore</h5>
                <a href="#menu">Menu</a>
                <a href="#how-it-works">How it works</a>
                <a href="#gallery">Gallery</a>
            </div>

            <div class="footer-col">
                <h5>Account</h5>
                <a href="loginpage.php">Order Online</a>
                <a href="loginpage.php?view=register">Create an Account</a>
                <a href="loginpage.php">Staff Login</a>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; 2026 Sweet Chicken POS. All rights reserved.</span>
            <span>Original Taste &middot; Est. 2025</span>
        </div>
    </div>
</footer>

<script src="js/index.js"></script>
</body>
</html>
