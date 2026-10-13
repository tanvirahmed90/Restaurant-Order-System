<?php
// admin/index.php - Dedicated Restaurant Admin & Analytics Operations Center
require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Operations & Analytics | Gourmet Royale</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Core & Admin Stylesheets -->
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body style="background:var(--bg-dark);">

  <div class="admin-layout">
    <!-- ============================================================= -->
    <!-- ADMIN SIDEBAR -->
    <!-- ============================================================= -->
    <aside class="admin-sidebar">
      <div class="admin-brand">
        <div class="brand-icon" style="width:38px; height:38px; font-size:1.15rem;">
          <i class="fa-solid fa-crown"></i>
        </div>
        <span class="brand-text">Gourmet HQ</span>
      </div>

      <ul class="admin-menu">
        <li class="admin-menu-item active" id="tab-nav-overview" onclick="AdminApp.switchTab('overview')">
          <i class="fa-solid fa-chart-pie"></i>
          <span>Overview & Analytics</span>
        </li>
        <li class="admin-menu-item" id="tab-nav-orders" onclick="AdminApp.switchTab('orders')">
          <i class="fa-solid fa-box-archive"></i>
          <span>Live Orders Queue</span>
        </li>
        <li class="admin-menu-item" id="tab-nav-menu" onclick="AdminApp.switchTab('menu')">
          <i class="fa-solid fa-utensils"></i>
          <span>Food Catalog CRUD</span>
        </li>
        <li class="admin-menu-item" id="tab-nav-riders" onclick="AdminApp.switchTab('riders')">
          <i class="fa-solid fa-motorcycle"></i>
          <span>Delivery Couriers</span>
        </li>
        <li class="admin-menu-item" id="tab-nav-users" onclick="AdminApp.switchTab('users')">
          <i class="fa-solid fa-users"></i>
          <span>Registered Patrons</span>
        </li>
      </ul>

      <div style="border-top:1px solid var(--border-glass); padding-top:1rem; margin-top:auto;">
        <a href="../index.php" class="btn-secondary" style="width:100%; justify-content:center; font-size:0.85rem;">
          <i class="fa-solid fa-arrow-left"></i> Customer Storefront
        </a>
      </div>
    </aside>

    <!-- ============================================================= -->
    <!-- ADMIN MAIN CONTENT AREA -->
    <!-- ============================================================= -->
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <span class="module-badge" style="margin-bottom:0.4rem;">
            <i class="fa-solid fa-shield-check"></i> System Operational
          </span>
          <h1>Restaurant Management Console</h1>
        </div>
        <div style="display:flex; align-items:center; gap:1rem;">
          <a href="../rider.php" target="_blank" class="btn-secondary" style="font-size:0.85rem;">
            <i class="fa-solid fa-mobile-screen"></i> Courier App View
          </a>
          <button class="btn-primary" onclick="AdminApp.openAddItemModal()">
            <i class="fa-solid fa-plus"></i> Add New Dish
          </button>
        </div>
      </div>

      <!-- =========================================================== -->
      <!-- PANE 1: OVERVIEW & ANALYTICS (MODULE 10) -->
      <!-- =========================================================== -->
      <section id="pane-overview">
        <!-- KPI Cards Grid -->
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-data">
              <h4>Total Revenue</h4>
              <div class="kpi-val" id="kpi-revenue" style="color:var(--primary);">$0.00</div>
            </div>
            <div class="kpi-icon icon-revenue">
              <i class="fa-solid fa-wallet"></i>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-data">
              <h4>Total Orders</h4>
              <div class="kpi-val" id="kpi-orders">0</div>
            </div>
            <div class="kpi-icon icon-orders">
              <i class="fa-solid fa-receipt"></i>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-data">
              <h4>Live Deliveries</h4>
              <div class="kpi-val" id="kpi-deliveries" style="color:var(--accent-flame);">0</div>
            </div>
            <div class="kpi-icon icon-deliveries">
              <i class="fa-solid fa-truck-fast"></i>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-data">
              <h4>Customers</h4>
              <div class="kpi-val" id="kpi-customers" style="color:var(--accent-emerald);">0</div>
            </div>
            <div class="kpi-icon icon-users">
              <i class="fa-solid fa-users"></i>
            </div>
          </div>
        </div>

        <!-- Revenue Chart & Best Sellers Section -->
        <div class="analytics-grid">
          <div class="chart-card">
            <h3><i class="fa-solid fa-chart-simple" style="color:var(--primary);"></i> Revenue Trend & Daily Volume</h3>
            <div class="chart-bars" id="chart-bars-container">
              <!-- Rendered dynamically -->
            </div>
          </div>

          <div class="sellers-card">
            <h3><i class="fa-solid fa-fire" style="color:var(--accent-flame);"></i> Top 5 Best-Selling Dishes</h3>
            <div id="best-sellers-list">
              <!-- Rendered dynamically -->
            </div>
          </div>
        </div>
      </section>

      <!-- =========================================================== -->
      <!-- PANE 2: LIVE ORDERS DISPATCH (MODULE 5 & 7) -->
      <!-- =========================================================== -->
      <section id="pane-orders" style="display:none;">
        <div class="table-card">
          <div class="table-header">
            <h3>Live Order Management Queue</h3>
            <div style="display:flex; gap:0.75rem;">
              <select id="order-filter-status" onchange="AdminApp.loadOrders()" style="height:40px; font-size:0.85rem;">
                <option value="all">All Orders</option>
                <option value="placed">Placed (Pending confirmation)</option>
                <option value="confirmed">Confirmed</option>
                <option value="preparing">In Kitchen Preparation</option>
                <option value="out_for_delivery">Out for Delivery</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <button class="btn-secondary" onclick="AdminApp.loadOrders()" style="padding:0.4rem 0.9rem; font-size:0.85rem;">
                <i class="fa-solid fa-rotate-right"></i> Refresh
              </button>
            </div>
          </div>

          <table class="admin-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Dishes & Address</th>
                <th>Total & Pay Status</th>
                <th>Order State</th>
                <th>Assign Courier</th>
              </tr>
            </thead>
            <tbody id="admin-orders-tbody">
              <!-- Dynamic rows -->
            </tbody>
          </table>
        </div>
      </section>

      <!-- =========================================================== -->
      <!-- PANE 3: FOOD MENU CRUD (MODULE 2 & 7) -->
      <!-- =========================================================== -->
      <section id="pane-menu" style="display:none;">
        <div class="table-card">
          <div class="table-header">
            <h3>Culinary Catalog & Inventory</h3>
            <button class="btn-primary" onclick="AdminApp.openAddItemModal()" style="font-size:0.85rem; padding:0.45rem 1rem;">
              <i class="fa-solid fa-plus"></i> New Dish
            </button>
          </div>

          <table class="admin-table">
            <thead>
              <tr>
                <th>Image</th>
                <th>Dish Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Rating</th>
                <th>Inventory</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="admin-menu-tbody">
              <!-- Dynamic rows -->
            </tbody>
          </table>
        </div>
      </section>

      <!-- =========================================================== -->
      <!-- PANE 4: DELIVERY FLEET (MODULE 8) -->
      <!-- =========================================================== -->
      <section id="pane-riders" style="display:none;">
        <div class="table-card">
          <div class="table-header">
            <h3>Delivery Courier Fleet</h3>
            <span class="module-badge"><i class="fa-solid fa-bolt"></i> Real-Time Courier Sync</span>
          </div>

          <table class="admin-table">
            <thead>
              <tr>
                <th>Rider Name</th>
                <th>Contact</th>
                <th>Vehicle Info</th>
                <th>Active Loads</th>
                <th>Lifetime Completed</th>
                <th>Current Status</th>
              </tr>
            </thead>
            <tbody id="admin-riders-tbody">
              <!-- Dynamic rows -->
            </tbody>
          </table>
        </div>
      </section>

      <!-- =========================================================== -->
      <!-- PANE 5: USERS MANAGEMENT (MODULE 1 & 7) -->
      <!-- =========================================================== -->
      <section id="pane-users" style="display:none;">
        <div class="table-card">
          <div class="table-header">
            <h3>Registered Users & Patrons</h3>
          </div>

          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Orders Placed</th>
                <th>Registered Date</th>
              </tr>
            </thead>
            <tbody id="admin-users-tbody">
              <!-- Dynamic rows -->
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <!-- ============================================================= -->
  <!-- ADD / EDIT DISH MODAL -->
  <!-- ============================================================= -->
  <div class="modal-overlay" id="item-crud-modal">
    <div class="modal-box" style="width:580px;">
      <div class="modal-header">
        <h3 id="item-modal-title">Add Culinary Dish</h3>
        <button class="btn-close" onclick="AdminApp.closeItemModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <form onsubmit="AdminApp.saveItem(event)">
        <input type="hidden" id="item-id-input">

        <div style="margin-bottom:0.85rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Dish Name</label>
          <input type="text" id="item-name" placeholder="Artisan Woodfired Truffle Pizza" required style="width:100%;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.85rem;">
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Category</label>
            <select id="item-category-id" style="width:100%;">
              <!-- Dynamic -->
            </select>
          </div>
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Dietary Classification</label>
            <select id="item-dietary" style="width:100%;">
              <option value="non-veg">Non-Vegetarian</option>
              <option value="veg">Vegetarian</option>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.85rem;">
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Regular Price ($)</label>
            <input type="number" step="0.01" id="item-price" placeholder="15.99" required style="width:100%;">
          </div>
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Discounted Price ($) (Optional)</label>
            <input type="number" step="0.01" id="item-discount-price" placeholder="13.99" style="width:100%;">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.85rem;">
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Prep Time</label>
            <input type="text" id="item-prep-time" value="15-20 min" style="width:100%;">
          </div>
          <div>
            <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Calories (kcal)</label>
            <input type="number" id="item-calories" value="550" style="width:100%;">
          </div>
        </div>

        <div style="margin-bottom:0.85rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Image Path or URL</label>
          <input type="text" id="item-image" value="assets/images/burger.jpg" style="width:100%;">
        </div>

        <div style="margin-bottom:1.25rem;">
          <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:4px;">Description & Ingredients</label>
          <textarea id="item-desc" rows="3" placeholder="Describe the flavors, textures, and key organic ingredients..." style="width:100%;"></textarea>
        </div>

        <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:0.85rem;">
          <i class="fa-solid fa-check"></i> Save Dish to Catalog
        </button>
      </form>
    </div>
  </div>

  <div id="toast-container" class="toast-box"></div>

  <!-- Admin JS Engine -->
  <script src="../js/admin.js"></script>
</body>
</html>
