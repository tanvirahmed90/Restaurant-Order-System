<?php
// index.php - Restaurant Order Management System Customer Storefront
require_once __DIR__ . '/config/database.php';
// Boot DB connection to ensure tables and seeds exist
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gourmet Royale | Artisan Restaurant & Instant Delivery</title>
  <meta name="description" content="Experience award-winning chef delicacies delivered fresh to your door with real-time order tracking and seamless payments.">

  <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- ================================================================= -->
  <!-- NAVIGATION BAR (MODULE 1: AUTH & GLOBAL NAVIGATION) -->
  <!-- ================================================================= -->
  <header class="navbar">
    <div class="container nav-container">
      <a href="index.php" class="brand-logo">
        <div class="brand-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span class="brand-text">Gourmet Royale</span>
      </a>

      <ul class="nav-links">
        <li><a href="#menu" class="nav-link active">Menu</a></li>
        <li><a href="javascript:void(0)" onclick="App.trackOrder('ORD-882194')" class="nav-link"><i class="fa-solid fa-location-dot"></i> Live Tracker</a></li>
        <li><a href="rider.php" class="nav-link"><i class="fa-solid fa-motorcycle"></i> Courier Portal</a></li>
        <li><a href="admin/index.php" class="nav-link"><i class="fa-solid fa-shield-halved"></i> Admin Panel</a></li>
      </ul>

      <div class="nav-actions">
        <!-- Live Cart Trigger -->
        <button class="cart-trigger" id="cart-btn" onclick="App.openCart()" aria-label="Open Cart">
          <i class="fa-solid fa-bag-shopping"></i>
          <span class="cart-badge" style="display:none;">0</span>
        </button>

        <!-- User Profile / Auth Area -->
        <div id="nav-user-area">
          <button class="btn-secondary" onclick="App.openAuthModal('login')">
            <i class="fa-solid fa-user"></i> Login
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- ================================================================= -->
  <!-- HERO SHOWCASE SECTION -->
  <!-- ================================================================= -->
  <section class="hero-section">
    <div class="container hero-grid">
      <div class="hero-content">
        <div class="hero-tag">
          <i class="fa-solid fa-fire"></i>
          <span>Handcrafted Culinary Masterpieces</span>
        </div>
        <h1 class="hero-title">
          Gourmet Flavors, Delivered <span>Hot & Fresh</span> In Minutes.
        </h1>
        <p class="hero-desc">
          Crafted with prime Angus beef, hand-tossed Neapolitan dough, and farm-fresh ingredients. Track your courier in real-time right to your door.
        </p>

        <div class="hero-cta">
          <a href="#menu" class="btn-primary">
            <i class="fa-solid fa-bowl-food"></i> Explore Menu
          </a>
          <button class="btn-secondary" onclick="App.openAuthModal('register')">
            <i class="fa-solid fa-gift"></i> Get $15 Off (WELCOME20)
          </button>
        </div>

        <div class="hero-stats">
          <div class="stat-item">
            <h3>25-35m</h3>
            <p>Lightning Delivery</p>
          </div>
          <div class="stat-item">
            <h3>4.9 ★</h3>
            <p>15,000+ Reviews</p>
          </div>
          <div class="stat-item">
            <h3>100%</h3>
            <p>Artisanal Fresh</p>
          </div>
        </div>
      </div>

      <div class="hero-card-showcase">
        <div class="showcase-card">
          <img src="assets/images/burger.jpg" alt="Chef Special Smash Truffle Double Cheeseburger">
          <div class="showcase-overlay">
            <div>
              <span class="module-badge">Chef's Signature</span>
              <h3 style="color:#fff; margin-top:0.4rem; font-size:1.35rem;">Smash Truffle Double Cheeseburger</h3>
              <p style="color:var(--text-muted); font-size:0.85rem;">Angus beef, aged cheddar, truffle aioli & crispy fries</p>
            </div>
            <button class="btn-primary" onclick="App.addToCart(1)" style="padding:0.6rem 1.25rem;">
              Order $13.99
            </button>
          </div>
        </div>

        <div class="floating-delivery-badge">
          <div class="rider-dot"></div>
          <div>
            <div style="font-size:0.85rem; font-weight:700; color:#fff;">Live Courier Active</div>
            <div style="font-size:0.75rem; color:var(--text-dim);">Avg. dispatch in 4 mins</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================================================= -->
  <!-- MODULE 3: SEARCH & FILTER SECTION -->
  <!-- ================================================================= -->
  <section class="filter-section" id="menu">
    <div class="container filter-bar">
      <div class="search-row">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="search-food-input" placeholder="Search delicacies, ingredients (e.g. Truffle, Wings, Salmon, Lava Cake)...">
        </div>

        <div class="filter-controls">
          <!-- Dietary Filter -->
          <div class="dietary-toggle">
            <button class="dietary-btn active" id="diet-btn-all" onclick="App.setDietary('all')">All</button>
            <button class="dietary-btn" id="diet-btn-veg" onclick="App.setDietary('veg')"><i class="fa-solid fa-leaf"></i> Veg</button>
            <button class="dietary-btn" id="diet-btn-non-veg" onclick="App.setDietary('non-veg')"><i class="fa-solid fa-drumstick-bite"></i> Non-Veg</button>
          </div>

          <!-- Sort Select -->
          <select class="sort-select" id="sort-menu-select">
            <option value="featured">Featured Chef Dishes</option>
            <option value="rating">Top Rated (★ 5.0)</option>
            <option value="popular">Most Popular</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
          </select>
        </div>
      </div>

      <!-- Categories Pills -->
      <div class="category-pills" id="category-pills-list">
        <!-- Rendered dynamically by js/app.js -->
      </div>
    </div>
  </section>

  <!-- ================================================================= -->
  <!-- MODULE 2: FOOD MENU GRID -->
  <!-- ================================================================= -->
  <main class="container menu-section">
    <div class="section-header">
      <div>
        <h2>Our Culinary Collection</h2>
        <p>Freshly prepared upon your order by master artisans</p>
      </div>
      <div>
        <span class="module-badge" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
          <i class="fa-solid fa-sparkles"></i> 100% Quality Guaranteed
        </span>
      </div>
    </div>

    <div class="food-grid" id="food-menu-grid">
      <!-- Dynamic cards rendered by App.renderMenuGrid() -->
    </div>
  </main>

  <!-- ================================================================= -->
  <!-- MODULE 4: CART SLIDING DRAWER -->
  <!-- ================================================================= -->
  <div class="cart-overlay" id="cart-overlay" onclick="App.closeCart()">
    <div class="cart-drawer" onclick="event.stopPropagation()">
      <div class="drawer-header">
        <h3><i class="fa-solid fa-bag-shopping" style="color:var(--primary);"></i> Your Gourmet Basket</h3>
        <button class="btn-close" onclick="App.closeCart()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div class="cart-items-list" id="cart-items-list">
        <!-- Rendered dynamically -->
      </div>

      <div class="cart-drawer-footer" id="cart-footer-area">
        <!-- Promo Coupon Input -->
        <div class="promo-box">
          <input type="text" id="promo-input" placeholder="Promo code (WELCOME20, FEAST10, FREEDEL)">
          <button class="promo-btn" onclick="App.applyPromoCode()">Apply</button>
        </div>

        <!-- Bill Breakdown -->
        <div class="bill-breakdown" id="bill-breakdown">
          <!-- Dynamically populated -->
        </div>

        <button class="btn-primary" style="width:100%; justify-content:center; padding:0.95rem; font-size:1rem;" onclick="App.openCheckout()">
          Proceed to Checkout <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- MODULE 5 & 6: CHECKOUT & PAYMENT MODAL -->
  <!-- ================================================================= -->
  <div class="modal-overlay" id="checkout-modal">
    <div class="modal-box">
      <div class="modal-header">
        <h3><i class="fa-solid fa-shield-halved" style="color:var(--primary);"></i> Secure Checkout</h3>
        <button class="btn-close" onclick="App.closeCheckout()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <form onsubmit="App.placeOrder(event)">
        <!-- Step 1: Customer & Address Information -->
        <h4 style="margin-bottom:0.75rem; color:#fff;">1. Delivery Details</h4>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Full Name</label>
            <input type="text" id="checkout-name" placeholder="John Doe" required style="width:100%;">
          </div>
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Phone Number</label>
            <input type="tel" id="checkout-phone" placeholder="+1 (555) 000-0000" required style="width:100%;">
          </div>
        </div>

        <div style="margin-bottom:0.75rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Delivery Address (Street, Apt / Suite, City)</label>
          <textarea id="checkout-address" rows="2" placeholder="456 Luxury Ave, Apt 12B" required style="width:100%;"></textarea>
        </div>

        <div style="margin-bottom:1.5rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Special Cooking / Courier Instructions (Optional)</label>
          <input type="text" id="checkout-notes" placeholder="e.g. Ring doorbell, contactless delivery" style="width:100%;">
        </div>

        <!-- Step 2: Payment Method Module -->
        <h4 style="margin-bottom:0.5rem; color:#fff;">2. Choose Payment Method</h4>
        <div class="payment-methods">
          <div class="pay-tab active" id="pay-tab-cod" onclick="App.selectPaymentMethod('cod')">
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <span>Cash on Delivery</span>
          </div>
          <div class="pay-tab" id="pay-tab-card" onclick="App.selectPaymentMethod('card')">
            <i class="fa-solid fa-credit-card"></i>
            <span>Credit / Debit Card</span>
          </div>
          <div class="pay-tab" id="pay-tab-upi" onclick="App.selectPaymentMethod('upi')">
            <i class="fa-solid fa-qrcode"></i>
            <span>Instant UPI / QR</span>
          </div>
        </div>

        <!-- Card Payment Simulation View -->
        <div id="card-payment-view" style="display:none;">
          <div class="card-preview">
            <div class="card-chip"></div>
            <div class="card-number-display" id="card-display-number">•••• •••• •••• 4242</div>
            <div class="card-meta">
              <span>Cardholder: VALUED PATRON</span>
              <span>EXP: 12/28</span>
            </div>
          </div>
          <div style="display:grid; grid-template-columns:2fr 1fr 1fr; gap:0.5rem; margin-bottom:1rem;">
            <input type="text" placeholder="Card Number (4242 ...)" value="4242 •••• •••• 8891">
            <input type="text" placeholder="MM/YY" value="08/28">
            <input type="text" placeholder="CVV" value="982">
          </div>
        </div>

        <!-- UPI Simulation View -->
        <div id="upi-payment-view" style="display:none;" class="qr-preview">
          <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=upi://pay?pa=restaurant@bank&pn=GourmetRoyale" alt="UPI QR Code" style="width:100%; height:100%;">
          </div>
          <p style="font-size:0.85rem; color:var(--text-muted);">Scan with Google Pay, Apple Pay, PhonePe, or Banking App</p>
        </div>

        <!-- COD Info -->
        <div id="cod-payment-view" style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.25rem;">
          <i class="fa-solid fa-circle-check" style="color:var(--accent-emerald);"></i> Pay with cash or card upon delivery to the courier.
        </div>

        <!-- Submit Button -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-glass); padding-top:1.25rem; margin-top:1rem;">
          <div>
            <div style="font-size:0.8rem; color:var(--text-dim);">Total Amount to Pay:</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--primary);" id="checkout-total-val">$0.00</div>
          </div>
          <button type="submit" class="btn-primary" id="btn-confirm-order" style="padding:0.85rem 1.8rem; font-size:1rem;">
            <i class="fa-solid fa-lock"></i> Authorize & Place Order
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- MODULE 5: ORDER TRACKER MODAL -->
  <!-- ================================================================= -->
  <div class="modal-overlay" id="tracker-modal">
    <div class="modal-box" style="width:640px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-satellite-dish" style="color:var(--primary);"></i> Live Order Timeline</h3>
        <button class="btn-close" onclick="App.closeTracker()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="tracker-content">
        <!-- Rendered dynamically by App.trackOrder() -->
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- MODULE 9: FOOD DETAIL & CUSTOMER REVIEWS MODAL -->
  <!-- ================================================================= -->
  <div class="modal-overlay" id="food-detail-modal">
    <div class="modal-box" style="width:680px;">
      <div class="modal-header">
        <h3>Culinary Details</h3>
        <button class="btn-close" onclick="App.closeDetailModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="detail-modal-body">
        <!-- Populated dynamically by App.openItemDetail() -->
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- MODULE 1: AUTH MODAL (LOGIN / REGISTER) -->
  <!-- ================================================================= -->
  <div class="modal-overlay" id="auth-modal">
    <div class="modal-box" style="width:440px;">
      <div class="modal-header">
        <div style="display:flex; gap:0.5rem;">
          <button class="btn-secondary active" id="tab-btn-login" onclick="App.switchAuthTab('login')">Login</button>
          <button class="btn-secondary" id="tab-btn-register" onclick="App.switchAuthTab('register')">Register</button>
        </div>
        <button class="btn-close" onclick="App.closeAuthModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <!-- Login Form Pane -->
      <form id="login-form-pane" onsubmit="App.handleLogin(event)">
        <h3 style="margin-bottom:0.25rem;">Welcome Back</h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.25rem;">Enter credentials to access saved addresses and orders.</p>
        <div style="margin-bottom:0.85rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Email</label>
          <input type="email" id="login-email" placeholder="customer@restaurant.com" required style="width:100%;">
        </div>
        <div style="margin-bottom:1.25rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Password</label>
          <input type="password" id="login-password" placeholder="••••••••" required style="width:100%;">
        </div>
        <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:0.8rem;">
          Sign In
        </button>
        <div style="margin-top:1rem; font-size:0.8rem; color:var(--text-dim); text-align:center;">
          Demo Accounts: <code>customer@restaurant.com</code> (Pass: <code>customer123</code>)<br>
          Admin: <code>admin@restaurant.com</code> (Pass: <code>admin123</code>)
        </div>
      </form>

      <!-- Register Form Pane -->
      <form id="register-form-pane" onsubmit="App.handleRegister(event)" style="display:none;">
        <h3 style="margin-bottom:0.25rem;">Create an Account</h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.25rem;">Join Gourmet Royale for express ordering & rewards.</p>
        <div style="margin-bottom:0.75rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Full Name</label>
          <input type="text" id="reg-name" placeholder="Sarah Jenkins" required style="width:100%;">
        </div>
        <div style="margin-bottom:0.75rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Email Address</label>
          <input type="email" id="reg-email" placeholder="sarah@example.com" required style="width:100%;">
        </div>
        <div style="margin-bottom:0.75rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Phone</label>
          <input type="tel" id="reg-phone" placeholder="+1 (555) 000-0000" style="width:100%;">
        </div>
        <div style="margin-bottom:0.75rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Default Delivery Address</label>
          <input type="text" id="reg-address" placeholder="123 Blossom Hill Rd" style="width:100%;">
        </div>
        <div style="margin-bottom:1.25rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Create Password</label>
          <input type="password" id="reg-password" placeholder="••••••••" required style="width:100%;">
        </div>
        <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:0.8rem;">
          Complete Registration
        </button>
      </form>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- MODULE 1: USER PROFILE & ORDER HISTORY DRAWER/MODAL -->
  <!-- ================================================================= -->
  <div class="modal-overlay" id="profile-modal">
    <div class="modal-box" style="width:580px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Your Profile & Past Orders</h3>
        <button class="btn-close" onclick="App.closeProfileModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <form onsubmit="App.updateProfile(event)" style="margin-bottom:1.5rem; border-bottom:1px solid var(--border-glass); padding-bottom:1.25rem;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Name</label>
            <input type="text" id="profile-name" required style="width:100%;">
          </div>
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Email (Read-only)</label>
            <input type="email" id="profile-email" disabled style="width:100%; opacity:0.6;">
          </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Phone</label>
            <input type="text" id="profile-phone" style="width:100%;">
          </div>
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Change Password</label>
            <input type="password" id="profile-new-pwd" placeholder="Leave empty to keep current" style="width:100%;">
          </div>
        </div>
        <div style="margin-bottom:1rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Delivery Address</label>
          <input type="text" id="profile-address" style="width:100%;">
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <button type="submit" class="btn-primary" style="font-size:0.88rem; padding:0.5rem 1.25rem;">
            Save Profile Changes
          </button>
          <button type="button" class="btn-secondary" onclick="App.handleLogout()" style="color:var(--accent-flame);">
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
          </button>
        </div>
      </form>

      <!-- Past Orders List -->
      <h4 style="margin-bottom:0.75rem;">Past Order History</h4>
      <div id="profile-orders-list" style="max-height:220px; overflow-y:auto;">
        <!-- Populated dynamically -->
      </div>
    </div>
  </div>

  <!-- Toast Notification Mount -->
  <div id="toast-container" class="toast-box"></div>

  <!-- Scripts -->
  <script src="js/app.js"></script>
</body>
</html>
