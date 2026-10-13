<?php
// rider.php - Delivery Courier & Rider Operations Portal (Module 8)
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Courier Delivery Portal | Gourmet Royale</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="css/style.css">
</head>
<body style="background:var(--bg-dark);">

  <header class="navbar">
    <div class="container nav-container">
      <a href="index.php" class="brand-logo">
        <div class="brand-icon" style="background:linear-gradient(135deg, #10b981 0%, #06b6d4 100%);">
          <i class="fa-solid fa-motorcycle"></i>
        </div>
        <span class="brand-text">Courier Portal</span>
      </a>

      <div style="display:flex; align-items:center; gap:1rem;">
        <a href="index.php" class="btn-secondary" style="font-size:0.85rem;">
          <i class="fa-solid fa-store"></i> Storefront
        </a>
        <a href="admin/index.php" class="btn-secondary" style="font-size:0.85rem;">
          <i class="fa-solid fa-shield-halved"></i> Admin HQ
        </a>
      </div>
    </div>
  </header>

  <main class="container" style="max-width:760px; padding:2rem 1.5rem 4rem;">
    <!-- Courier Profile Card -->
    <div style="background:var(--bg-card); backdrop-filter:var(--glass-blur); border:1px solid var(--border-glass); border-radius:var(--radius-xl); padding:1.75rem; margin-bottom:2rem; box-shadow:var(--shadow-md);">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:1.25rem;">
          <div style="width:60px; height:60px; border-radius:var(--radius-full); background:var(--primary-gradient); display:flex; align-items:center; justify-content:center; font-size:1.6rem; color:#fff;">
            <i class="fa-solid fa-helmet-safety"></i>
          </div>
          <div>
            <div style="display:flex; align-items:center; gap:0.5rem;">
              <h2 style="font-size:1.4rem; color:#fff;" id="rider-name">Alex Swift</h2>
              <span class="status-pill status-delivered" id="rider-status-pill">AVAILABLE</span>
            </div>
            <p style="font-size:0.85rem; color:var(--text-dim); margin-top:0.2rem;" id="rider-vehicle">
              High-Speed E-Bike • BK-9920 • Courier ID #1
            </p>
          </div>
        </div>
        <button class="btn-secondary" onclick="RiderPortal.refreshDeliveries()" style="font-size:0.85rem; padding:0.4rem 0.85rem;">
          <i class="fa-solid fa-rotate-right"></i> Sync
        </button>
      </div>
    </div>

    <!-- Active Runs Section -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
      <h3 style="font-size:1.3rem;"><i class="fa-solid fa-route" style="color:var(--primary);"></i> Assigned Delivery Runs</h3>
      <span class="module-badge" id="active-count-badge">0 Active</span>
    </div>

    <div id="rider-deliveries-list" style="display:flex; flex-direction:column; gap:1.25rem;">
      <!-- Populated dynamically -->
    </div>
  </main>

  <div id="toast-container" class="toast-box"></div>

  <script>
    const RiderPortal = {
      riderId: 1,

      init() {
        this.loadProfile();
        this.refreshDeliveries();
      },

      showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${msg}</span>`;
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
      },

      async loadProfile() {
        try {
          const res = await fetch(`api/rider.php?action=profile&rider_id=${this.riderId}`);
          const data = await res.json();
          if (data.success && data.rider) {
            document.getElementById('rider-name').innerText = data.rider.name;
            document.getElementById('rider-vehicle').innerText = `${data.rider.vehicle_type} • ${data.rider.vehicle_number} • Phone: ${data.rider.phone || 'N/A'}`;
            const pill = document.getElementById('rider-status-pill');
            pill.innerText = data.rider.status.toUpperCase();
            pill.className = `status-pill ${data.rider.status === 'available' ? 'status-delivered' : 'status-preparing'}`;
          }
        } catch (e) {
          console.error(e);
        }
      },

      async refreshDeliveries() {
        const list = document.getElementById('rider-deliveries-list');
        list.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-dim);"><i class="fa-solid fa-circle-notch fa-spin"></i> Refreshing routes...</div>';

        try {
          const res = await fetch(`api/rider.php?action=assigned_orders&rider_id=${this.riderId}`);
          const data = await res.json();
          if (!data.success) return;

          const activeOrders = data.orders.filter(o => o.order_status !== 'delivered');
          document.getElementById('active-count-badge').innerText = `${activeOrders.length} Active`;

          if (data.orders.length === 0) {
            list.innerHTML = `
              <div style="background:var(--bg-card); border:1px solid var(--border-glass); border-radius:var(--radius-lg); padding:3rem; text-align:center; color:var(--text-muted);">
                <i class="fa-solid fa-check-circle" style="font-size:3rem; color:var(--accent-emerald); margin-bottom:1rem;"></i>
                <h4>All Clear! No pending deliveries.</h4>
                <p style="font-size:0.85rem; color:var(--text-dim); margin-top:0.4rem;">New incoming orders dispatched from kitchen will appear here automatically.</p>
              </div>
            `;
            return;
          }

          list.innerHTML = data.orders.map(o => {
            const isDelivered = o.order_status === 'delivered';
            const isTransit = o.order_status === 'out_for_delivery';

            return `
              <div style="background:var(--bg-card); backdrop-filter:var(--glass-blur); border:1px solid var(--border-glass); border-radius:var(--radius-lg); padding:1.5rem; box-shadow:var(--shadow-sm);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                  <div>
                    <strong style="font-size:1.15rem; color:#fff;">Order #${o.order_number}</strong>
                    <div style="font-size:0.75rem; color:var(--text-dim);">${o.created_at}</div>
                  </div>
                  <span class="status-pill status-${o.order_status}">${o.order_status.replace('_', ' ')}</span>
                </div>

                <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-glass); border-radius:var(--radius-md); padding:1rem; margin-bottom:1rem;">
                  <div style="display:flex; justify-content:space-between; margin-bottom:0.35rem;">
                    <strong style="color:#fff;">👤 ${o.customer_name}</strong>
                    <a href="tel:${o.delivery_phone}" style="color:var(--primary); font-weight:700;"><i class="fa-solid fa-phone"></i> ${o.delivery_phone}</a>
                  </div>
                  <div style="font-size:0.88rem; color:var(--text-muted); margin-bottom:0.5rem;">
                    📍 <strong>Address:</strong> ${o.delivery_address}
                  </div>
                  ${o.special_notes ? `<div style="font-size:0.82rem; color:var(--primary);"><i class="fa-solid fa-comment-dots"></i> Note: "${o.special_notes}"</div>` : ''}
                </div>

                <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
                  <strong>Items:</strong> ${o.items.map(it => `${it.quantity}x ${it.item_name}`).join(', ')}
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-glass); padding-top:1rem;">
                  <div>
                    <span style="font-size:0.8rem; color:var(--text-dim);">Collection:</span>
                    <strong style="color:var(--primary); font-size:1.15rem;">$${parseFloat(o.total_amount).toFixed(2)}</strong>
                    <span style="font-size:0.75rem; color:var(--text-dim);">(${o.payment_method.toUpperCase()} - ${o.payment_status.toUpperCase()})</span>
                  </div>

                  <div style="display:flex; gap:0.6rem;">
                    ${!isDelivered && !isTransit ? `
                      <button class="btn-primary" style="font-size:0.85rem; padding:0.5rem 1rem;" onclick="RiderPortal.updateStatus(${o.id}, 'out_for_delivery')">
                        <i class="fa-solid fa-box"></i> Pick Up & Start Transit
                      </button>
                    ` : ''}

                    ${isTransit ? `
                      <button class="btn-primary" style="background:var(--accent-emerald); font-size:0.85rem; padding:0.5rem 1rem;" onclick="RiderPortal.updateStatus(${o.id}, 'delivered')">
                        <i class="fa-solid fa-house-chimney-check"></i> Mark as Delivered
                      </button>
                    ` : ''}

                    ${isDelivered ? `
                      <span style="color:var(--accent-emerald); font-size:0.85rem; font-weight:700;">
                        <i class="fa-solid fa-circle-check"></i> Delivery Completed
                      </span>
                    ` : ''}
                  </div>
                </div>
              </div>
            `;
          }).join('');
        } catch (e) {
          list.innerHTML = '<div style="color:var(--accent-flame); text-align:center;">Failed to load deliveries.</div>';
        }
      },

      async updateStatus(orderId, status) {
        try {
          const res = await fetch('api/rider.php?action=update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: status })
          });
          const data = await res.json();
          if (data.success) {
            this.showToast(data.message);
            this.loadProfile();
            this.refreshDeliveries();
          } else {
            this.showToast(data.error, 'error');
          }
        } catch (e) {
          this.showToast('Failed to update status.', 'error');
        }
      }
    };

    document.addEventListener('DOMContentLoaded', () => RiderPortal.init());
  </script>
</body>
</html>
