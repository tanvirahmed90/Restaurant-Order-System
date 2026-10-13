/**
 * RESTAURANT ORDER MANAGEMENT SYSTEM - ADMIN OPERATIONS (js/admin.js)
 * Manages Dashboard KPIs, Food Menu CRUD, Live Order Dispatch, Riders & Analytics
 */

const AdminApp = {
  activeTab: 'overview',
  categories: [],
  riders: [],

  init() {
    this.loadStats();
    this.loadCategories();
    this.loadOrders();
    this.loadMenuItems();
    this.loadRiders();
    this.loadUsers();
  },

  showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-box';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    toast.innerHTML = `<i class="fa-solid fa-${icon}"></i> <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  },

  switchTab(tabId) {
    this.activeTab = tabId;
    document.querySelectorAll('.admin-menu-item').forEach(el => el.classList.remove('active'));
    const activeItem = document.getElementById(`tab-nav-${tabId}`);
    if (activeItem) activeItem.classList.add('active');

    const sections = ['overview', 'orders', 'menu', 'riders', 'users'];
    sections.forEach(s => {
      const pane = document.getElementById(`pane-${s}`);
      if (pane) pane.style.display = (s === tabId) ? 'block' : 'none';
    });

    if (tabId === 'overview') this.loadStats();
    if (tabId === 'orders') this.loadOrders();
    if (tabId === 'menu') this.loadMenuItems();
    if (tabId === 'riders') this.loadRiders();
    if (tabId === 'users') this.loadUsers();
  },

  // =========================================================================
  // MODULE 10: REPORTS & ANALYTICS / OVERVIEW
  // =========================================================================
  async loadStats() {
    try {
      const res = await fetch('../api/admin.php?action=stats');
      const data = await res.json();
      if (!data.success) return;

      const s = data.stats;
      document.getElementById('kpi-revenue').innerText = `$${s.total_revenue.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
      document.getElementById('kpi-orders').innerText = s.total_orders;
      document.getElementById('kpi-deliveries').innerText = s.active_deliveries;
      document.getElementById('kpi-customers').innerText = s.total_customers;

      this.renderRevenueChart(s.revenue_trend);
      this.renderBestSellers(s.popular_items);
    } catch (err) {
      console.error('Stats loading error:', err);
    }
  },

  renderRevenueChart(trend) {
    const container = document.getElementById('chart-bars-container');
    if (!container || !trend || trend.length === 0) return;

    const maxRev = Math.max(...trend.map(t => parseFloat(t.daily_revenue)), 100);

    container.innerHTML = trend.map(t => {
      const rev = parseFloat(t.daily_revenue);
      const heightPercent = Math.max(10, (rev / maxRev) * 100);
      return `
        <div class="bar-col">
          <span class="bar-val">$${rev.toFixed(0)}</span>
          <div class="bar-fill" style="height: ${heightPercent}%;" title="${t.date_label}: $${rev.toFixed(2)} (${t.orders_count} orders)"></div>
          <span class="bar-label">${t.date_label}</span>
        </div>
      `;
    }).join('');
  },

  renderBestSellers(items) {
    const container = document.getElementById('best-sellers-list');
    if (!container || !items) return;

    if (items.length === 0) {
      container.innerHTML = '<p style="color:var(--text-dim); padding:1rem 0;">No sales recorded yet.</p>';
      return;
    }

    container.innerHTML = items.map((it, idx) => `
      <div class="seller-item">
        <span style="font-weight:800; color:var(--primary); font-size:1rem; width:20px;">#${idx + 1}</span>
        <img src="../${it.image}" alt="${it.item_name}">
        <div class="seller-info">
          <div class="seller-name">${it.item_name}</div>
          <div class="seller-sales">${it.total_qty} units sold • ★ ${it.rating || '5.0'}</div>
        </div>
        <div class="seller-rev">$${parseFloat(it.total_sales).toFixed(2)}</div>
      </div>
    `).join('');
  },

  // =========================================================================
  // MODULE 7: FOOD MENU CRUD
  // =========================================================================
  async loadCategories() {
    try {
      const res = await fetch('../api/menu.php?action=categories');
      const data = await res.json();
      if (data.success) {
        this.categories = data.categories;
        const select = document.getElementById('item-category-id');
        if (select) {
          select.innerHTML = this.categories.filter(c => c.slug !== 'all').map(c => `
            <option value="${c.id}">${c.name}</option>
          `).join('');
        }
      }
    } catch (err) {
      console.error(err);
    }
  },

  async loadMenuItems() {
    const tbody = document.getElementById('admin-menu-tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:2rem;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading dishes...</td></tr>';

    try {
      const res = await fetch('../api/admin.php?action=items_list');
      const data = await res.json();
      if (!data.success) return;

      tbody.innerHTML = data.items.map(item => `
        <tr>
          <td><img src="../${item.image}" style="width:48px; height:48px; border-radius:var(--radius-sm); object-fit:cover;"></td>
          <td>
            <strong style="color:#fff;">${item.name}</strong>
            <div style="font-size:0.75rem; color:var(--text-dim);">${item.dietary.toUpperCase()} • ${item.prep_time} • ${item.calories} kcal</div>
          </td>
          <td><span class="module-badge">${item.category_name}</span></td>
          <td>
            <strong style="color:var(--primary);">$${parseFloat(item.price).toFixed(2)}</strong>
            ${item.discount_price ? `<div style="font-size:0.8rem; color:var(--accent-emerald);">Sale: $${parseFloat(item.discount_price).toFixed(2)}</div>` : ''}
          </td>
          <td>★ ${item.rating} (${item.reviews_count})</td>
          <td>
            <button class="status-pill ${item.is_available == 1 ? 'status-delivered' : 'status-cancelled'}" onclick="AdminApp.toggleItemAvailability(${item.id})">
              ${item.is_available == 1 ? 'In Stock' : 'Out of Stock'}
            </button>
          </td>
          <td>
            <div class="action-btn-group">
              <button class="btn-icon" onclick="AdminApp.openEditItemModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn-icon danger" onclick="AdminApp.deleteItem(${item.id})">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="7" style="color:var(--accent-flame); text-align:center;">Failed to load items.</td></tr>';
    }
  },

  openAddItemModal() {
    document.getElementById('item-modal-title').innerText = 'Add New Culinary Dish';
    document.getElementById('item-id-input').value = '';
    document.getElementById('item-name').value = '';
    document.getElementById('item-price').value = '';
    document.getElementById('item-discount-price').value = '';
    document.getElementById('item-prep-time').value = '15-20 min';
    document.getElementById('item-calories').value = '500';
    document.getElementById('item-desc').value = '';
    document.getElementById('item-image').value = 'assets/images/burger.jpg';
    document.getElementById('item-crud-modal').classList.add('open');
  },

  openEditItemModal(item) {
    document.getElementById('item-modal-title').innerText = 'Edit Food Item';
    document.getElementById('item-id-input').value = item.id;
    document.getElementById('item-name').value = item.name;
    document.getElementById('item-category-id').value = item.category_id;
    document.getElementById('item-price').value = item.price;
    document.getElementById('item-discount-price').value = item.discount_price || '';
    document.getElementById('item-dietary').value = item.dietary;
    document.getElementById('item-prep-time').value = item.prep_time;
    document.getElementById('item-calories').value = item.calories;
    document.getElementById('item-desc').value = item.description;
    document.getElementById('item-image').value = item.image;
    document.getElementById('item-crud-modal').classList.add('open');
  },

  closeItemModal() {
    document.getElementById('item-crud-modal').classList.remove('open');
  },

  async saveItem(e) {
    e.preventDefault();
    const id = document.getElementById('item-id-input').value;
    const isEdit = Boolean(id);

    const payload = {
      id: id ? parseInt(id) : undefined,
      name: document.getElementById('item-name').value.trim(),
      category_id: parseInt(document.getElementById('item-category-id').value),
      price: parseFloat(document.getElementById('item-price').value),
      discount_price: document.getElementById('item-discount-price').value ? parseFloat(document.getElementById('item-discount-price').value) : null,
      dietary: document.getElementById('item-dietary').value,
      prep_time: document.getElementById('item-prep-time').value.trim(),
      calories: parseInt(document.getElementById('item-calories').value),
      description: document.getElementById('item-desc').value.trim(),
      image: document.getElementById('item-image').value.trim()
    };

    const action = isEdit ? 'item_update' : 'item_create';

    try {
      const res = await fetch(`../api/admin.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message);
        this.closeItemModal();
        this.loadMenuItems();
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to save item.', 'error');
    }
  },

  async toggleItemAvailability(id) {
    try {
      const res = await fetch('../api/admin.php?action=item_toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast('Availability status updated.');
        this.loadMenuItems();
      }
    } catch (err) {
      this.showToast('Failed to toggle status.', 'error');
    }
  },

  async deleteItem(id) {
    if (!confirm('Are you sure you want to permanently delete this culinary item?')) return;
    try {
      const res = await fetch('../api/admin.php?action=item_delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message);
        this.loadMenuItems();
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to delete item.', 'error');
    }
  },

  // =========================================================================
  // MODULE 5 & 7: LIVE ORDERS & DISPATCH
  // =========================================================================
  async loadOrders() {
    const tbody = document.getElementById('admin-orders-tbody');
    if (!tbody) return;

    const filterStatus = document.getElementById('order-filter-status')?.value || 'all';

    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:2rem;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading live orders...</td></tr>';

    try {
      const res = await fetch(`../api/admin.php?action=orders_list&status=${filterStatus}`);
      const data = await res.json();
      if (!data.success) return;

      tbody.innerHTML = data.orders.map(o => `
        <tr>
          <td><strong style="color:#fff;">${o.order_number}</strong></td>
          <td>
            <div style="font-weight:600; color:#fff;">${o.customer_name}</div>
            <div style="font-size:0.8rem; color:var(--text-dim);">${o.delivery_phone}</div>
          </td>
          <td>
            <div style="font-size:0.85rem; color:var(--text-muted);">
              ${o.items.map(it => `${it.quantity}x ${it.item_name}`).join(', ')}
            </div>
            <div style="font-size:0.8rem; color:var(--text-dim); margin-top:2px;">📍 ${o.delivery_address}</div>
          </td>
          <td>
            <strong style="color:var(--primary);">$${parseFloat(o.total_amount).toFixed(2)}</strong>
            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-dim);">${o.payment_method} (${o.payment_status})</div>
          </td>
          <td>
            <select class="status-pill status-${o.order_status}" onchange="AdminApp.updateOrderStatus(${o.id}, this.value)" style="background:var(--bg-surface); cursor:pointer; font-weight:700;">
              <option value="placed" ${o.order_status === 'placed' ? 'selected' : ''}>Placed</option>
              <option value="confirmed" ${o.order_status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
              <option value="preparing" ${o.order_status === 'preparing' ? 'selected' : ''}>Preparing</option>
              <option value="out_for_delivery" ${o.order_status === 'out_for_delivery' ? 'selected' : ''}>Out for Delivery</option>
              <option value="delivered" ${o.order_status === 'delivered' ? 'selected' : ''}>Delivered</option>
              <option value="cancelled" ${o.order_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
            </select>
          </td>
          <td>
            <select onchange="AdminApp.assignRider(${o.id}, this.value)" style="font-size:0.85rem; padding:0.35rem 0.65rem;">
              <option value="">${o.rider_name ? `Assigned: ${o.rider_name}` : 'Assign Courier...'}</option>
              ${this.riders.map(r => `
                <option value="${r.id}" ${o.rider_id == r.id ? 'selected' : ''}>${r.name} (${r.status})</option>
              `).join('')}
            </select>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="7" style="color:var(--accent-flame); text-align:center;">Failed to load orders.</td></tr>';
    }
  },

  async updateOrderStatus(order_id, status) {
    try {
      const res = await fetch('../api/admin.php?action=order_update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id, status })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message);
        this.loadOrders();
        this.loadStats();
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to update order status.', 'error');
    }
  },

  async assignRider(order_id, rider_id) {
    if (!rider_id) return;
    try {
      const res = await fetch('../api/admin.php?action=assign_rider', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id, rider_id })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message);
        this.loadOrders();
        this.loadRiders();
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to assign rider.', 'error');
    }
  },

  // =========================================================================
  // MODULE 8: DELIVERY MANAGEMENT MODULE
  // =========================================================================
  async loadRiders() {
    const tbody = document.getElementById('admin-riders-tbody');
    try {
      const res = await fetch('../api/admin.php?action=riders_list');
      const data = await res.json();
      if (!data.success) return;

      this.riders = data.riders;

      if (tbody) {
        tbody.innerHTML = data.riders.map(r => `
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:0.75rem;">
                <div class="user-avatar" style="width:36px; height:36px;"><i class="fa-solid fa-motorcycle"></i></div>
                <div>
                  <strong style="color:#fff;">${r.name}</strong>
                  <div style="font-size:0.75rem; color:var(--text-dim);">${r.email}</div>
                </div>
              </div>
            </td>
            <td>${r.phone || 'N/A'}</td>
            <td>${r.vehicle_type} (${r.vehicle_number})</td>
            <td><strong style="color:var(--accent-cyan);">${r.active_deliveries}</strong> active</td>
            <td><strong style="color:var(--accent-emerald);">${r.completed_deliveries}</strong> orders</td>
            <td>
              <span class="status-pill ${r.status === 'available' ? 'status-delivered' : (r.status === 'on_delivery' ? 'status-preparing' : 'status-cancelled')}">
                ${r.status.replace('_', ' ')}
              </span>
            </td>
          </tr>
        `).join('');
      }
    } catch (err) {
      console.error(err);
    }
  },

  // =========================================================================
  // MODULE 1: USERS MANAGEMENT
  // =========================================================================
  async loadUsers() {
    const tbody = document.getElementById('admin-users-tbody');
    if (!tbody) return;

    try {
      const res = await fetch('../api/admin.php?action=users_list');
      const data = await res.json();
      if (!data.success) return;

      tbody.innerHTML = data.users.map(u => `
        <tr>
          <td>#${u.id}</td>
          <td><strong style="color:#fff;">${u.name}</strong></td>
          <td>${u.email}</td>
          <td>${u.phone || '—'}</td>
          <td><span class="module-badge">${u.role.toUpperCase()}</span></td>
          <td>${u.orders_count} orders placed</td>
          <td style="font-size:0.8rem; color:var(--text-dim);">${u.created_at.split(' ')[0]}</td>
        </tr>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }
};

document.addEventListener('DOMContentLoaded', () => AdminApp.init());
