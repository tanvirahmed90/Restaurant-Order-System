# Restaurant Order Management System (Gourmet Royale)

A full-stack, enterprise-grade **Restaurant Order Management System** featuring a luxury gourmet design system with glassmorphism, real-time live order tracking, complete cart and checkout flow, customer review/rating engine, dedicated delivery rider portal, and an admin operations center with sales reports and analytics.

---

## 🛠️ Technology Stack
- **Frontend**: HTML5, Vanilla CSS3 (Custom Luxury Gourmand Glassmorphism Design System), JavaScript (ES6+ Asynchronous Fetch Engine).
- **Backend**: PHP (RESTful JSON APIs, PDO Object-Relational Database Abstraction).
- **Database**: MySQL / MariaDB (Auto-migrating schema with automatic seeding of dishes, users, categories, orders, and reviews).
- **Asset Generation**: High-resolution commercial food photography generated for burgers, artisan pizzas, truffle pastas, glazed wings, lava cakes, poke bowls, and craft beverages.

---

## 🚀 The 10 Core Modules

| # | Module | Key Features & Implementation |
|---|---|---|
| **1** | **User Authentication Module** | User registration, login with hashed passwords (`password_hash`), session management, profile updating, address management, role-based access (`customer`, `admin`, `rider`). |
| **2** | **Food Menu Module** | Dynamic menu categorized into Burgers, Artisanal Pizzas, Pastas & Bowls, Appetizers, Desserts, and Beverages with high-res photos, preparation time, calories, dietary tags, and prices. |
| **3** | **Search & Filter Module** | Instant debounced live search by dish name or ingredient, category pill carousel, vegetarian / non-veg dietary toggle, and multi-criteria sorting (Price, Rating, Popularity). |
| **4** | **Cart Module** | Slide-in frosted glass basket drawer, real-time quantity adjustments, promotional coupon discounts (`WELCOME20`, `FEAST10`, `FREEDEL`), subtotal, sales tax (8%), and delivery fee calculation. |
| **5** | **Order Management Module** | Order creation, unique order numbers (e.g., `ORD-B18E52`), customer special cooking notes, order history tab, and interactive 5-stage progress tracker. |
| **6** | **Payment Module** | Cash on Delivery (COD), simulated Credit/Debit card gateway with interactive 3D visualizer and brand detection, and Instant UPI / QR Code scan option. |
| **7** | **Admin Dashboard Module** | Operations control center: manage dishes (Add, Edit, Delete, Toggle Out-of-Stock), manage live incoming orders queue, and customer management. |
| **8** | **Delivery Management Module** | Real-time rider portal: courier assignment, contact patron button, delivery address route preview, one-click "Pick Up" and "Mark as Delivered" status transitions. |
| **9** | **Review & Rating Module** | Interactive 5-star rating picker and customer reviews submission; automatic recalculation of overall food rating and review counts. |
| **10** | **Report & Analytics Module** | Executive KPI metric widgets (Total Revenue, Total Orders, Active Couriers, Total Patrons), daily sales trend bar chart, and Top 5 best-selling dishes ranking. |

---

## 📂 Project Directory Structure

```
d:\Restaurant Order System Website\
├── assets/
│   └── images/
│       ├── burger.jpg         # High-res smash double cheeseburger
│       ├── pizza.jpg          # Neapolitan woodfired Margherita pizza
│       ├── pasta.jpg          # Black truffle fettuccine Alfredo
│       ├── wings.jpg          # Korean glazed sweet & spicy wings
│       ├── lava_cake.jpg      # Molten Belgian chocolate lava cake
│       ├── salmon_bowl.jpg    # Fresh salmon & avocado poke bowl
│       └── mojito.jpg         # Sparkling wild berry craft mojito
├── config/
│   └── database.php           # PDO connection, auto-database creation, and initial seeders
├── api/
│   ├── auth.php               # Login, Register, Logout, Profile update endpoints
│   ├── menu.php               # Food categories, search, filtering, and item details
│   ├── orders.php             # Order placement, tracking timeline, promo code validation
│   ├── reviews.php            # 5-star ratings and customer food review submission
│   ├── admin.php              # KPI statistics, Food CRUD, order status changer, user management
│   └── rider.php              # Delivery courier run sheet, pickup, and completion
├── css/
│   ├── style.css              # Main luxury gourmet design system & responsive layout
│   └── admin.css              # Dark operations center, KPI cards, charts, and data tables
├── js/
│   ├── app.js                 # Customer frontend logic, cart engine, live tracking, checkout
│   └── admin.js               # Admin dashboard operations and analytics renderer
├── admin/
│   └── index.php              # Dedicated Admin Operations Console & Analytics Center
├── index.php                  # Customer Storefront, menu, cart drawer & live order tracker
├── rider.php                  # Delivery Courier Portal
└── README.md                  # Complete documentation
```

---

## 🔑 Default Accounts (Auto-Seeded)

| Role | Email | Password | Access / Interface |
|---|---|---|---|
| **Admin** | `admin@restaurant.com` | `admin123` | [Admin Panel](http://localhost:8080/admin/index.php) |
| **Delivery Rider** | `rider@restaurant.com` | `rider123` | [Courier Portal](http://localhost:8080/rider.php) |
| **Customer** | `customer@restaurant.com` | `customer123` | [Storefront](http://localhost:8080/index.php) |

---

## 🏷️ Test Promo Codes
- `WELCOME20`: 20% discount on orders over $15
- `FEAST10`: $10.00 off orders over $30
- `FREEDEL`: Free delivery ($4.99 discount)
