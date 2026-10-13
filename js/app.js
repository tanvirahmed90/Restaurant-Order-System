/**
 * RESTAURANT ORDER MANAGEMENT SYSTEM - CLIENT APPLICATION (js/app.js)
 * Manages Auth, Menu, Search/Filter, Cart, Checkout, Tracking & Reviews
 */

const App = {
  user: null,
  cart: [],
  promo: null,
  activeCategory: 'all',
  activeDietary: 'all',
  activeSort: 'featured',
  searchQuery: '',
  categories: [],
  menuItems: [],

  init() {
    this.loadCartFromStorage();
    this.checkAuthStatus();
    this.loadCategories();
    this.loadMenuItems();
    this.bindEvents();
  },

  // =========================================================================
  // TOAST NOTIFICATIONS
  // =========================================================================
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

  // =========================================================================
  // MODULE 1: AUTHENTICATION & PROFILE
  // =========================================================================
  async checkAuthStatus() {
    try {
      const res = await fetch('api/auth.php?action=me');
      const data = await res.json();
      if (data.success && data.user) {
        this.setUser(data.user);
      } else {
        this.setUser(null);
      }
    } catch (err) {
      console.error('Auth check error:', err);
    }
  },

  setUser(user) {
    this.user = user;
    const authContainer = document.getElementById('nav-user-area');
    if (!authContainer) return;

    if (user) {
      authContainer.innerHTML = `
        <button class="user-trigger" onclick="App.openProfileModal()">
          <div class="user-avatar">${user.name.charAt(0).toUpperCase()}</div>
          <span>${user.name.split(' ')[0]}</span>
          <span class="module-badge">${user.role}</span>
        </button>
      `;
    } else {
      authContainer.innerHTML = `
        <button class="btn-secondary" onclick="App.openAuthModal('login')">
          <i class="fa-solid fa-user"></i> Login
        </button>
      `;
    }
  },

  openAuthModal(mode = 'login') {
    const modal = document.getElementById('auth-modal');
    if (!modal) return;
    this.switchAuthTab(mode);
    modal.classList.add('open');
  },

  closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    if (modal) modal.classList.remove('open');
  },

  switchAuthTab(tab) {
    const loginForm = document.getElementById('login-form-pane');
    const regForm = document.getElementById('register-form-pane');
    const tabLogin = document.getElementById('tab-btn-login');
    const tabReg = document.getElementById('tab-btn-register');

    if (tab === 'login') {
      loginForm.style.display = 'block';
      regForm.style.display = 'none';
      tabLogin.classList.add('active');
      tabReg.classList.remove('active');
    } else {
      loginForm.style.display = 'none';
      regForm.style.display = 'block';
      tabReg.classList.add('active');
      tabLogin.classList.remove('active');
    }
  },

  async handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
      const res = await fetch('api/auth.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();
      if (data.success) {
        this.setUser(data.user);
        this.closeAuthModal();
        this.showToast(data.message, 'success');
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Login failed. Please try again.', 'error');
    }
  },

  async handleRegister(e) {
    e.preventDefault();
    const name = document.getElementById('reg-name').value;
    const email = document.getElementById('reg-email').value;
    const password = document.getElementById('reg-password').value;
    const phone = document.getElementById('reg-phone').value;
    const address = document.getElementById('reg-address').value;

    try {
      const res = await fetch('api/auth.php?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password, phone, address, role: 'customer' })
      });
      const data = await res.json();
      if (data.success) {
        this.setUser(data.user);
        this.closeAuthModal();
        this.showToast('Account created successfully!', 'success');
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Registration failed.', 'error');
    }
  },

  async handleLogout() {
    try {
      await fetch('api/auth.php?action=logout', { method: 'POST' });
      this.setUser(null);
      this.closeProfileModal();
      this.showToast('Logged out successfully.', 'success');
    } catch (err) {
      console.error(err);
    }
  },

  async openProfileModal() {
    if (!this.user) return;
    const modal = document.getElementById('profile-modal');
    if (!modal) return;

    document.getElementById('profile-name').value = this.user.name;
    document.getElementById('profile-email').value = this.user.email;
    document.getElementById('profile-phone').value = this.user.phone || '';
    document.getElementById('profile-address').value = this.user.address || '';

    // Load user's recent orders
    this.loadUserOrders();
    modal.classList.add('open');
  },

  closeProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) modal.classList.remove('open');
  },

  async updateProfile(e) {
    e.preventDefault();
    const name = document.getElementById('profile-name').value;
    const phone = document.getElementById('profile-phone').value;
    const address = document.getElementById('profile-address').value;
    const new_password = document.getElementById('profile-new-pwd').value;

    try {
      const res = await fetch('api/auth.php?action=update_profile', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, phone, address, new_password })
      });
      const data = await res.json();
      if (data.success) {
        this.user.name = name;
        this.user.phone = phone;
        this.user.address = address;
        this.setUser(this.user);
        this.showToast('Profile updated!', 'success');
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to update profile.', 'error');
    }
  },

  async loadUserOrders() {
    const listEl = document.getElementById('profile-orders-list');
    if (!listEl || !this.user) return;

    listEl.innerHTML = '<p style="color:var(--text-dim);">Loading order history...</p>';
    try {
      const res = await fetch(`api/orders.php?action=my_orders&user_id=${this.user.id}`);
      const data = await res.json();
      if (data.success && data.orders.length > 0) {
        listEl.innerHTML = data.orders.map(o => `
          <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-glass); border-radius:var(--radius-md); padding:1rem; margin-bottom:0.75rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
              <strong style="color:#fff;">${o.order_number}</strong>
              <span class="status-pill status-${o.order_status}">${o.order_status.replace('_', ' ')}</span>
            </div>
            <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">
              Date: ${o.created_at} | Total: <strong style="color:var(--primary);">$${parseFloat(o.total_amount).toFixed(2)}</strong>
            </div>
            <div style="display:flex; gap:0.5rem;">
              <button class="btn-secondary" style="font-size:0.8rem; padding:0.35rem 0.75rem;" onclick="App.trackOrder('${o.order_number}')">
                <i class="fa-solid fa-location-crosshairs"></i> Track Live
              </button>
            </div>
          </div>
        `).join('');
      } else {
        listEl.innerHTML = '<p style="color:var(--text-dim); padding:1rem 0;">No past orders found.</p>';
      }
    } catch (err) {
      listEl.innerHTML = '<p style="color:var(--accent-flame);">Failed to load orders.</p>';
    }
  },

  // =========================================================================
  // MODULE 2 & 3: FOOD MENU, SEARCH & FILTER
  // =========================================================================
  async loadCategories() {
    try {
      const res = await fetch('api/menu.php?action=categories');
      const data = await res.json();
      if (data.success) {
        this.categories = data.categories;
        this.renderCategoryPills();
      }
    } catch (err) {
      console.error(err);
    }
  },

  renderCategoryPills() {
    const container = document.getElementById('category-pills-list');
    if (!container) return;

    container.innerHTML = `
      <button class="cat-pill ${this.activeCategory === 'all' ? 'active' : ''}" onclick="App.selectCategory('all')">
        <i class="fa-solid fa-border-all"></i> All Items
      </button>
      ${this.categories.filter(c => c.slug !== 'all').map(c => `
        <button class="cat-pill ${this.activeCategory === c.slug ? 'active' : ''}" onclick="App.selectCategory('${c.slug}')">
          <i class="fa-solid fa-${c.icon}"></i> ${c.name}
        </button>
      `).join('')}
    `;
  },

  selectCategory(slug) {
    this.activeCategory = slug;
    this.renderCategoryPills();
    this.loadMenuItems();
  },

  setDietary(diet) {
    this.activeDietary = diet;
    document.querySelectorAll('.dietary-btn').forEach(btn => {
      btn.classList.remove('active', 'veg-active');
    });
    const currentBtn = document.getElementById(`diet-btn-${diet}`);
    if (currentBtn) {
      if (diet === 'veg') currentBtn.classList.add('veg-active');
      else currentBtn.classList.add('active');
    }
    this.loadMenuItems();
  },

  async loadMenuItems() {
    const grid = document.getElementById('food-menu-grid');
    if (!grid) return;

    grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:3rem; color:var(--text-dim);"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i><p style="margin-top:0.75rem;">Fetching chef creations...</p></div>';

    const params = new URLSearchParams({
      action: 'items',
      category: this.activeCategory,
      dietary: this.activeDietary,
      sort: this.activeSort,
      search: this.searchQuery
    });

    try {
      const res = await fetch(`api/menu.php?${params.toString()}`);
      const data = await res.json();
      if (data.success) {
        this.menuItems = data.items;
        this.renderMenuGrid();
      }
    } catch (err) {
      grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:2rem; color:var(--accent-flame);">Failed to load menu items.</div>';
    }
  },

  renderMenuGrid() {
    const grid = document.getElementById('food-menu-grid');
    if (!grid) return;

    if (this.menuItems.length === 0) {
      grid.innerHTML = `
        <div style="grid-column:1/-1; text-align:center; padding:4rem 1rem; color:var(--text-muted);">
          <i class="fa-solid fa-magnifying-glass" style="font-size:2.5rem; margin-bottom:1rem; color:var(--text-dim);"></i>
          <h3>No culinary dishes match your criteria</h3>
          <p style="color:var(--text-dim); margin-top:0.5rem;">Try resetting your filters or search query.</p>
        </div>
      `;
      return;
    }

    grid.innerHTML = this.menuItems.map(item => {
      const hasDiscount = item.discount_price !== null && parseFloat(item.discount_price) > 0;
      const currentPrice = hasDiscount ? parseFloat(item.discount_price).toFixed(2) : parseFloat(item.price).toFixed(2);
      const isVeg = item.dietary === 'veg';

      return `
        <div class="food-card" data-id="${item.id}">
          <div class="food-img-wrapper" onclick="App.openItemDetail(${item.id})">
            <img class="food-img" src="${item.image}" alt="${item.name}" loading="lazy">
            <span class="dietary-badge ${isVeg ? 'badge-veg' : 'badge-non-veg'}">
              <i class="fa-solid fa-leaf"></i> ${item.dietary}
            </span>
            <div class="rating-badge">
              <i class="fa-solid fa-star"></i> ${item.rating}
            </div>
          </div>
          <div class="food-details">
            <div class="food-meta">
              <span><i class="fa-regular fa-clock"></i> ${item.prep_time}</span>
              <span><i class="fa-solid fa-fire"></i> ${item.calories} kcal</span>
            </div>
            <h3 class="food-title" onclick="App.openItemDetail(${item.id})" style="cursor:pointer;">${item.name}</h3>
            <p class="food-desc">${item.description}</p>
            <div class="food-footer">
              <div class="price-box">
                <span class="current-price">$${currentPrice}</span>
                ${hasDiscount ? `<span class="old-price">$${parseFloat(item.price).toFixed(2)}</span>` : ''}
              </div>
              <button class="btn-add-cart" onclick="App.addToCart(${item.id})">
                <i class="fa-solid fa-plus"></i> Add
              </button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  },

  // =========================================================================
  // MODULE 9: DETAIL & REVIEWS MODAL
  // =========================================================================
  async openItemDetail(id) {
    const modal = document.getElementById('food-detail-modal');
    if (!modal) return;

    try {
      const res = await fetch(`api/menu.php?action=item_detail&id=${id}`);
      const data = await res.json();
      if (!data.success) return;

      const item = data.item;
      const hasDiscount = item.discount_price !== null && parseFloat(item.discount_price) > 0;
      const currentPrice = hasDiscount ? parseFloat(item.discount_price).toFixed(2) : parseFloat(item.price).toFixed(2);

      document.getElementById('detail-modal-body').innerHTML = `
        <div class="detail-modal-grid">
          <div class="detail-img-box">
            <img src="${item.image}" alt="${item.name}">
            <div style="display:flex; gap:1rem; margin-top:1rem;">
              <span class="module-badge"><i class="fa-solid fa-clock"></i> ${item.prep_time}</span>
              <span class="module-badge"><i class="fa-solid fa-fire"></i> ${item.calories} kcal</span>
              <span class="module-badge" style="color:#fbbf24;"><i class="fa-solid fa-star"></i> ${item.rating} (${item.reviews.length} reviews)</span>
            </div>
          </div>
          <div>
            <span style="color:var(--primary); font-size:0.85rem; text-transform:uppercase; font-weight:700;">${item.category_name}</span>
            <h2 style="font-size:1.8rem; margin:0.35rem 0 0.85rem;">${item.name}</h2>
            <p style="color:var(--text-muted); line-height:1.6; margin-bottom:1.5rem;">${item.description}</p>
            
            <div style="display:flex; align-items:baseline; gap:0.75rem; margin-bottom:1.5rem;">
              <span style="font-size:2rem; font-weight:800; color:var(--primary);">$${currentPrice}</span>
              ${hasDiscount ? `<span style="font-size:1.1rem; text-decoration:line-through; color:var(--text-dim);">$${parseFloat(item.price).toFixed(2)}</span>` : ''}
            </div>

            <button class="btn-primary" style="width:100%; justify-content:center; padding:0.85rem;" onclick="App.addToCart(${item.id}); App.closeDetailModal();">
              <i class="fa-solid fa-cart-shopping"></i> Add to Cart Now
            </button>
          </div>
        </div>

        <div class="reviews-container">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3>Customer Reviews (${item.reviews.length})</h3>
            <button class="btn-secondary" style="font-size:0.85rem; padding:0.4rem 0.85rem;" onclick="App.toggleReviewForm(${item.id})">
              <i class="fa-solid fa-pen"></i> Leave a Review
            </button>
          </div>

          <!-- Hidden Review Form -->
          <div id="review-submit-form-${item.id}" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--border-glass); border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1.25rem;">
            <h4 style="margin-bottom:0.5rem;">Share your culinary experience</h4>
            <div class="star-picker" id="star-picker-${item.id}">
              <i class="fa-solid fa-star selected" onclick="App.setStarRating(1, ${item.id})"></i>
              <i class="fa-solid fa-star selected" onclick="App.setStarRating(2, ${item.id})"></i>
              <i class="fa-solid fa-star selected" onclick="App.setStarRating(3, ${item.id})"></i>
              <i class="fa-solid fa-star selected" onclick="App.setStarRating(4, ${item.id})"></i>
              <i class="fa-solid fa-star selected" onclick="App.setStarRating(5, ${item.id})"></i>
            </div>
            <textarea id="review-comment-${item.id}" rows="3" placeholder="Tell us what you liked about this dish..." style="width:100%; margin-bottom:0.75rem;"></textarea>
            <button class="btn-primary" onclick="App.submitReview(${item.id})">Submit Review</button>
          </div>

          <div class="reviews-list">
            ${item.reviews.length > 0 ? item.reviews.map(r => `
              <div class="review-card">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
                  <strong style="color:#fff;">${r.user_name}</strong>
                  <span style="font-size:0.75rem; color:var(--text-dim);">${r.created_at.split(' ')[0]}</span>
                </div>
                <div class="review-stars">
                  ${'<i class="fa-solid fa-star"></i>'.repeat(r.rating)}
                </div>
                <p style="font-size:0.88rem; color:var(--text-muted);">${r.comment}</p>
              </div>
            `).join('') : '<p style="color:var(--text-dim);">Be the first food enthusiast to review this item!</p>'}
          </div>
        </div>
      `;

      modal.classList.add('open');
    } catch (err) {
      console.error(err);
    }
  },

  closeDetailModal() {
    const modal = document.getElementById('food-detail-modal');
    if (modal) modal.classList.remove('open');
  },

  currentSelectedRating: 5,
  setStarRating(val, itemId) {
    this.currentSelectedRating = val;
    const picker = document.getElementById(`star-picker-${itemId}`);
    if (!picker) return;
    const stars = picker.querySelectorAll('i');
    stars.forEach((s, idx) => {
      if (idx < val) s.classList.add('selected');
      else s.classList.remove('selected');
    });
  },

  toggleReviewForm(itemId) {
    const form = document.getElementById(`review-submit-form-${itemId}`);
    if (form) {
      form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
  },

  async submitReview(itemId) {
    const comment = document.getElementById(`review-comment-${itemId}`).value;
    if (!comment.trim()) {
      this.showToast('Please type your review comment.', 'error');
      return;
    }

    try {
      const res = await fetch('api/reviews.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          food_item_id: itemId,
          rating: this.currentSelectedRating,
          comment: comment,
          user_id: this.user ? this.user.id : null
        })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message, 'success');
        this.openItemDetail(itemId); // Refresh detail
        this.loadMenuItems(); // Refresh card rating
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to submit review.', 'error');
    }
  },

  // =========================================================================
  // MODULE 4: CART MANAGEMENT & PROMO CODES
  // =========================================================================
  loadCartFromStorage() {
    try {
      const saved = localStorage.getItem('gourmet_cart');
      if (saved) {
        this.cart = JSON.parse(saved);
      }
    } catch (e) {
      this.cart = [];
    }
    this.updateCartBadge();
  },

  saveCartToStorage() {
    localStorage.setItem('gourmet_cart', JSON.stringify(this.cart));
    this.updateCartBadge();
    this.renderCartDrawer();
  },

  addToCart(id) {
    const item = this.menuItems.find(i => i.id == id);
    if (!item) return;

    const existing = this.cart.find(c => c.id == id);
    if (existing) {
      existing.quantity += 1;
    } else {
      const unitPrice = (item.discount_price !== null && parseFloat(item.discount_price) > 0)
        ? parseFloat(item.discount_price)
        : parseFloat(item.price);

      this.cart.push({
        id: item.id,
        name: item.name,
        price: unitPrice,
        image: item.image,
        quantity: 1
      });
    }

    this.saveCartToStorage();
    this.showToast(`Added "${item.name}" to cart!`);
    this.openCart();
  },

  updateCartQty(id, delta) {
    const item = this.cart.find(c => c.id == id);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
      this.cart = this.cart.filter(c => c.id != id);
    }
    this.saveCartToStorage();
  },

  updateCartBadge() {
    const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
    const badges = document.querySelectorAll('.cart-badge');
    badges.forEach(b => {
      b.innerText = totalItems;
      b.style.display = totalItems > 0 ? 'flex' : 'none';
    });
  },

  openCart() {
    const overlay = document.getElementById('cart-overlay');
    if (overlay) overlay.classList.add('open');
    this.renderCartDrawer();
  },

  closeCart() {
    const overlay = document.getElementById('cart-overlay');
    if (overlay) overlay.classList.remove('open');
  },

  async applyPromoCode() {
    const codeInput = document.getElementById('promo-input');
    if (!codeInput) return;
    const code = codeInput.value.trim().toUpperCase();

    if (!code) {
      this.showToast('Please enter a coupon code.', 'error');
      return;
    }

    const subtotal = this.cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);

    try {
      const res = await fetch('api/orders.php?action=validate_promo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, subtotal })
      });
      const data = await res.json();
      if (data.success) {
        this.promo = { code: data.code, discount: data.discount };
        this.showToast(data.message, 'success');
        this.renderCartDrawer();
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to validate promo code.', 'error');
    }
  },

  renderCartDrawer() {
    const list = document.getElementById('cart-items-list');
    const footer = document.getElementById('cart-footer-area');
    if (!list) return;

    if (this.cart.length === 0) {
      list.innerHTML = `
        <div class="empty-cart-state">
          <i class="fa-solid fa-basket-shopping"></i>
          <h4>Your Feast Basket is Empty</h4>
          <p style="font-size:0.85rem; margin-top:0.4rem;">Select from our artisanal burgers, pizzas, and desserts!</p>
        </div>
      `;
      if (footer) footer.style.display = 'none';
      return;
    }

    if (footer) footer.style.display = 'block';

    list.innerHTML = this.cart.map(item => `
      <div class="cart-item-row">
        <img src="${item.image}" alt="${item.name}">
        <div class="cart-item-info">
          <div class="cart-item-title">${item.name}</div>
          <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)}</div>
        </div>
        <div class="qty-control">
          <button class="qty-btn" onclick="App.updateCartQty(${item.id}, -1)">-</button>
          <span class="qty-count">${item.quantity}</span>
          <button class="qty-btn" onclick="App.updateCartQty(${item.id}, 1)">+</button>
        </div>
      </div>
    `).join('');

    // Calculations
    const subtotal = this.cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
    const discount = this.promo ? this.promo.discount : 0;
    let deliveryFee = 4.99;
    if (this.promo && this.promo.code === 'FREEDEL') deliveryFee = 0.00;
    const tax = Math.max(0, (subtotal - discount) * 0.08);
    const total = Math.max(0, subtotal - discount + deliveryFee + tax);

    const billEl = document.getElementById('bill-breakdown');
    if (billEl) {
      billEl.innerHTML = `
        <div class="bill-row">
          <span>Subtotal</span>
          <span>$${subtotal.toFixed(2)}</span>
        </div>
        ${discount > 0 ? `
          <div class="bill-row discount-row">
            <span>Discount (${this.promo.code})</span>
            <span>-$${discount.toFixed(2)}</span>
          </div>
        ` : ''}
        <div class="bill-row">
          <span>Delivery Express</span>
          <span>${deliveryFee === 0 ? '<span style="color:var(--accent-emerald);">FREE</span>' : `$${deliveryFee.toFixed(2)}`}</span>
        </div>
        <div class="bill-row">
          <span>Estimated Taxes (8%)</span>
          <span>$${tax.toFixed(2)}</span>
        </div>
        <div class="bill-row total-row">
          <span>Total Due</span>
          <span style="color:var(--primary);">$${total.toFixed(2)}</span>
        </div>
      `;
    }
  },

  // =========================================================================
  // MODULE 5 & 6: CHECKOUT & PAYMENT
  // =========================================================================
  selectedPaymentMethod: 'cod',

  openCheckout() {
    if (this.cart.length === 0) {
      this.showToast('Please add items to cart first.', 'error');
      return;
    }
    this.closeCart();
    const modal = document.getElementById('checkout-modal');
    if (!modal) return;

    // Prefill user data if logged in
    if (this.user) {
      document.getElementById('checkout-name').value = this.user.name;
      document.getElementById('checkout-phone').value = this.user.phone || '';
      document.getElementById('checkout-address').value = this.user.address || '';
    }

    this.selectPaymentMethod('cod');
    this.renderCheckoutSummary();
    modal.classList.add('open');
  },

  closeCheckout() {
    const modal = document.getElementById('checkout-modal');
    if (modal) modal.classList.remove('open');
  },

  selectPaymentMethod(method) {
    this.selectedPaymentMethod = method;
    document.querySelectorAll('.pay-tab').forEach(t => t.classList.remove('active'));
    const activeTab = document.getElementById(`pay-tab-${method}`);
    if (activeTab) activeTab.classList.add('active');

    // Toggle conditional card / UPI elements
    const cardBox = document.getElementById('card-payment-view');
    const upiBox = document.getElementById('upi-payment-view');
    const codBox = document.getElementById('cod-payment-view');

    if (cardBox) cardBox.style.display = (method === 'card') ? 'block' : 'none';
    if (upiBox) upiBox.style.display = (method === 'upi') ? 'block' : 'none';
    if (codBox) codBox.style.display = (method === 'cod') ? 'block' : 'none';
  },

  renderCheckoutSummary() {
    const subtotal = this.cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
    const discount = this.promo ? this.promo.discount : 0;
    let deliveryFee = (this.promo && this.promo.code === 'FREEDEL') ? 0.00 : 4.99;
    const tax = Math.max(0, (subtotal - discount) * 0.08);
    const total = Math.max(0, subtotal - discount + deliveryFee + tax);

    const sumEl = document.getElementById('checkout-total-val');
    if (sumEl) sumEl.innerText = `$${total.toFixed(2)}`;
  },

  async placeOrder(e) {
    e.preventDefault();
    const customer_name = document.getElementById('checkout-name').value.trim();
    const delivery_phone = document.getElementById('checkout-phone').value.trim();
    const delivery_address = document.getElementById('checkout-address').value.trim();
    const special_notes = document.getElementById('checkout-notes').value.trim();

    if (!delivery_phone || !delivery_address) {
      this.showToast('Please provide delivery phone and address.', 'error');
      return;
    }

    const payload = {
      user_id: this.user ? this.user.id : null,
      customer_name: customer_name,
      delivery_phone: delivery_phone,
      delivery_address: delivery_address,
      payment_method: this.selectedPaymentMethod,
      special_notes: special_notes,
      promo_code: this.promo ? this.promo.code : '',
      items: this.cart
    };

    const submitBtn = document.getElementById('btn-confirm-order');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing Payment & Order...';
    }

    try {
      const res = await fetch('api/orders.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        this.cart = [];
        this.promo = null;
        this.saveCartToStorage();
        this.closeCheckout();
        this.showToast('🎉 Order placed successfully!', 'success');
        
        // Immediately open Live Order Tracker!
        this.trackOrder(data.order_number);
      } else {
        this.showToast(data.error, 'error');
      }
    } catch (err) {
      this.showToast('Failed to place order.', 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-lock"></i> Authorize & Place Order';
      }
    }
  },

  // =========================================================================
  // MODULE 5: LIVE ORDER TRACKING
  // =========================================================================
  async trackOrder(orderNumber) {
    if (!orderNumber) {
      orderNumber = document.getElementById('track-search-input')?.value.trim();
    }
    if (!orderNumber) {
      this.showToast('Please enter an order number.', 'error');
      return;
    }

    const modal = document.getElementById('tracker-modal');
    if (!modal) return;
    modal.classList.add('open');

    const content = document.getElementById('tracker-content');
    content.innerHTML = '<div style="text-align:center; padding:3rem;"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i><p style="margin-top:0.75rem;">Fetching live order timeline...</p></div>';

    try {
      const res = await fetch(`api/orders.php?action=track&order_number=${orderNumber}`);
      const data = await res.json();
      if (!data.success) {
        content.innerHTML = `<div style="text-align:center; padding:2rem; color:var(--accent-flame);"><i class="fa-solid fa-circle-exclamation fa-2x"></i><p style="margin-top:0.5rem;">${data.error}</p></div>`;
        return;
      }

      const order = data.order;
      const statusMap = {
        'placed': 1,
        'confirmed': 2,
        'preparing': 3,
        'out_for_delivery': 4,
        'delivered': 5,
        'cancelled': 0
      };
      const currentLevel = statusMap[order.order_status] || 1;
      const progressPercent = order.order_status === 'cancelled' ? 0 : Math.min(100, (currentLevel - 1) * 25);

      content.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-glass); padding-bottom:1.25rem;">
          <div>
            <h2 style="font-size:1.6rem; color:#fff;">Order #${order.order_number}</h2>
            <p style="color:var(--text-muted); font-size:0.85rem;">Placed on ${order.created_at}</p>
          </div>
          <div>
            <span class="status-pill status-${order.order_status}" style="font-size:0.85rem; padding:0.4rem 1rem;">
              ${order.order_status.replace('_', ' ')}
            </span>
          </div>
        </div>

        <!-- Animated Progress Timeline -->
        <div class="tracker-timeline">
          <div class="tracker-progress-line" style="width: ${progressPercent}%;"></div>
          
          <div class="step-node ${currentLevel >= 1 ? 'completed' : ''} ${currentLevel === 1 ? 'active' : ''}">
            <div class="node-icon"><i class="fa-solid fa-receipt"></i></div>
            <span class="step-label">Placed</span>
          </div>
          <div class="step-node ${currentLevel >= 2 ? 'completed' : ''} ${currentLevel === 2 ? 'active' : ''}">
            <div class="node-icon"><i class="fa-solid fa-check-double"></i></div>
            <span class="step-label">Confirmed</span>
          </div>
          <div class="step-node ${currentLevel >= 3 ? 'completed' : ''} ${currentLevel === 3 ? 'active' : ''}">
            <div class="node-icon"><i class="fa-solid fa-utensils"></i></div>
            <span class="step-label">Kitchen</span>
          </div>
          <div class="step-node ${currentLevel >= 4 ? 'completed' : ''} ${currentLevel === 4 ? 'active' : ''}">
            <div class="node-icon"><i class="fa-solid fa-motorcycle"></i></div>
            <span class="step-label">On The Way</span>
          </div>
          <div class="step-node ${currentLevel >= 5 ? 'completed' : ''}">
            <div class="node-icon"><i class="fa-solid fa-house-chimney"></i></div>
            <span class="step-label">Delivered</span>
          </div>
        </div>

        ${order.rider_name ? `
          <div class="rider-dispatch-card">
            <div class="rider-info">
              <div class="rider-photo"><i class="fa-solid fa-helmet-safety"></i></div>
              <div>
                <h4 style="color:#fff;">${order.rider_name}</h4>
                <p style="color:var(--text-dim); font-size:0.82rem;">Assigned Courier • ${order.vehicle_type} (${order.vehicle_number})</p>
              </div>
            </div>
            <div>
              <a href="tel:${order.rider_phone}" class="btn-secondary" style="font-size:0.85rem; padding:0.4rem 0.85rem;">
                <i class="fa-solid fa-phone"></i> Call Courier
              </a>
            </div>
          </div>
        ` : ''}

        <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-glass); border-radius:var(--radius-md); padding:1.25rem; margin-top:1.5rem;">
          <h4 style="margin-bottom:0.75rem;">Items in this Order</h4>
          ${order.items.map(it => `
            <div style="display:flex; justify-content:space-between; font-size:0.9rem; padding:0.35rem 0; color:var(--text-muted);">
              <span>${it.quantity}x ${it.item_name}</span>
              <strong style="color:#fff;">$${parseFloat(it.subtotal).toFixed(2)}</strong>
            </div>
          `).join('')}
          <div style="display:flex; justify-content:space-between; margin-top:0.75rem; padding-top:0.75rem; border-top:1px dashed var(--border-glass); font-size:1.05rem;">
            <span>Total Paid (${order.payment_method.toUpperCase()}):</span>
            <strong style="color:var(--primary);">$${parseFloat(order.total_amount).toFixed(2)}</strong>
          </div>
        </div>

        <div style="margin-top:1.25rem; font-size:0.85rem; color:var(--text-dim);">
          <i class="fa-solid fa-location-dot"></i> Delivering to: <strong style="color:var(--text-main);">${order.delivery_address}</strong>
        </div>
      `;
    } catch (err) {
      content.innerHTML = '<div style="color:var(--accent-flame); text-align:center; padding:2rem;">Failed to fetch order status.</div>';
    }
  },

  closeTracker() {
    const modal = document.getElementById('tracker-modal');
    if (modal) modal.classList.remove('open');
  },

  // =========================================================================
  // EVENT BINDINGS
  // =========================================================================
  bindEvents() {
    // Live Search
    const searchInput = document.getElementById('search-food-input');
    if (searchInput) {
      let timeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          this.searchQuery = e.target.value;
          this.loadMenuItems();
        }, 300);
      });
    }

    // Sort Dropdown
    const sortSelect = document.getElementById('sort-menu-select');
    if (sortSelect) {
      sortSelect.addEventListener('change', (e) => {
        this.activeSort = e.target.value;
        this.loadMenuItems();
      });
    }
  }
};

// Auto boot on DOM load
document.addEventListener('DOMContentLoaded', () => App.init());
