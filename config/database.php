<?php
// config/database.php - PDO Database Connection and Auto-Migration

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurant_db');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Connect to server first without specifying DB
        $pdoInit = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Create database if it doesn't exist
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Connect to the specific database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Ensure tables exist and seed default data
        initDatabaseTables($pdo);

        return $pdo;
    } catch (PDOException $e) {
        // Return JSON error if API request
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
        die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
    }
}

function initDatabaseTables($pdo) {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(25) NULL,
        `address` TEXT NULL,
        `role` ENUM('customer', 'admin', 'rider') DEFAULT 'customer',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 2. Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(80) NOT NULL,
        `slug` VARCHAR(80) NOT NULL UNIQUE,
        `icon` VARCHAR(50) DEFAULT 'utensils',
        `description` VARCHAR(255) NULL
    ) ENGINE=InnoDB;");

    // 3. Food Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `food_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT NOT NULL,
        `name` VARCHAR(120) NOT NULL,
        `description` TEXT NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `discount_price` DECIMAL(10,2) NULL,
        `image` VARCHAR(255) NOT NULL,
        `is_featured` TINYINT(1) DEFAULT 0,
        `is_available` TINYINT(1) DEFAULT 1,
        `dietary` ENUM('veg', 'non-veg') DEFAULT 'non-veg',
        `prep_time` VARCHAR(20) DEFAULT '20-25 mins',
        `calories` INT DEFAULT 500,
        `rating` DECIMAL(2,1) DEFAULT 5.0,
        `reviews_count` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 4. Riders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `riders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `vehicle_type` VARCHAR(50) DEFAULT 'Motorbike',
        `vehicle_number` VARCHAR(50) DEFAULT 'EV-7892',
        `status` ENUM('available', 'on_delivery', 'offline') DEFAULT 'available',
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 5. Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_number` VARCHAR(30) NOT NULL UNIQUE,
        `user_id` INT NOT NULL,
        `rider_id` INT NULL,
        `subtotal` DECIMAL(10,2) NOT NULL,
        `discount` DECIMAL(10,2) DEFAULT 0.00,
        `delivery_fee` DECIMAL(10,2) DEFAULT 4.99,
        `tax` DECIMAL(10,2) DEFAULT 0.00,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `payment_method` ENUM('cod', 'card', 'upi') DEFAULT 'cod',
        `payment_status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        `order_status` ENUM('placed', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'placed',
        `delivery_address` TEXT NOT NULL,
        `delivery_phone` VARCHAR(25) NOT NULL,
        `special_notes` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`rider_id`) REFERENCES `riders`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    // 6. Order Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `food_item_id` INT NOT NULL,
        `item_name` VARCHAR(120) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT NOT NULL,
        `subtotal` DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 7. Reviews Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reviews` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `food_item_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
        `comment` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Seed initial users if table is empty
    $userCount = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($userCount == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $riderPass = password_hash('rider123', PASSWORD_DEFAULT);
        $custPass = password_hash('customer123', PASSWORD_DEFAULT);

        $pdo->exec("INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `role`) VALUES
            ('Admin Maestro', 'admin@restaurant.com', '{$adminPass}', '+1 (555) 019-2834', '742 Evergreen Terrace, Suite 100', 'admin'),
            ('Alex Swift (Rider)', 'rider@restaurant.com', '{$riderPass}', '+1 (555) 392-8172', '124 Conch Street, Bay Area', 'rider'),
            ('Sarah Jenkins', 'customer@restaurant.com', '{$custPass}', '+1 (555) 837-1928', '405 Skyline Blvd, Apt 4B', 'customer');
        ");

        // Seed rider record
        $riderUserId = $pdo->query("SELECT id FROM users WHERE email = 'rider@restaurant.com'")->fetchColumn();
        if ($riderUserId) {
            $pdo->exec("INSERT INTO `riders` (`user_id`, `vehicle_type`, `vehicle_number`, `status`) VALUES
                ({$riderUserId}, 'High-Speed E-Bike', 'BK-9920', 'available');
            ");
        }
    }

    // Seed categories if empty
    $catCount = $pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
    if ($catCount == 0) {
        $pdo->exec("INSERT INTO `categories` (`name`, `slug`, `icon`, `description`) VALUES
            ('All Items', 'all', 'border-all', 'Browse our complete culinary collection'),
            ('Burgers & Sandwiches', 'burgers', 'hamburger', 'Flame-broiled artisan smash burgers and brioche sandwiches'),
            ('Artisanal Pizzas', 'pizzas', 'pizza-slice', 'Neapolitan stone-oven baked pizzas with organic crust'),
            ('Pastas & Bowls', 'pastas-bowls', 'bowl-rice', 'Silky handmade pasta and vibrant nourishing poke bowls'),
            ('Appetizers & Sides', 'appetizers', 'drumstick-bite', 'Crispy glazed wings, seasoned fries, and bites'),
            ('Gourmet Desserts', 'desserts', 'ice-cream', 'Decadent lava cakes, artisanal gelatos, and sweets'),
            ('Craft Beverages', 'beverages', 'cocktail', 'Artisanal mocktails, sparkling coolers, and cold brews');
        ");
    }

    // Seed food items if empty
    $itemCount = $pdo->query("SELECT COUNT(*) FROM `food_items`")->fetchColumn();
    if ($itemCount == 0) {
        // Get category IDs
        $cats = $pdo->query("SELECT id, slug FROM `categories`")->fetchAll(PDO::FETCH_KEY_PAIR);
        $catBurgers = $cats['burgers'] ?? 2;
        $catPizzas = $cats['pizzas'] ?? 3;
        $catPastas = $cats['pastas-bowls'] ?? 4;
        $catAppetizers = $cats['appetizers'] ?? 5;
        $catDesserts = $cats['desserts'] ?? 6;
        $catDrinks = $cats['beverages'] ?? 7;

        $pdo->exec("INSERT INTO `food_items` (`category_id`, `name`, `description`, `price`, `discount_price`, `image`, `is_featured`, `is_available`, `dietary`, `prep_time`, `calories`, `rating`, `reviews_count`) VALUES
            ({$catBurgers}, 'Smash Truffle Double Cheeseburger', 'Two 100% Angus beef smashed patties, aged sharp cheddar, caramelized onions, house truffle aioli, on toasted buttered brioche served with golden seasoned fries.', 15.99, 13.99, 'assets/images/burger.jpg', 1, 1, 'non-veg', '15-20 min', 820, 4.9, 18),
            ({$catPizzas}, 'Artisanal Woodfired Margherita', 'Classic Neapolitan bubbly blistered sourdough crust, San Marzano tomato reduction, fresh fior di latte mozzarella, fragrant sweet basil, and Cold-Pressed Sicilian EVOO.', 16.50, 14.50, 'assets/images/pizza.jpg', 1, 1, 'veg', '18-22 min', 680, 4.8, 24),
            ({$catPastas}, 'Black Truffle Fettuccine Alfredo', 'Silky fresh house-made fettuccine enveloped in rich black winter truffle butter, 24-month aged Parmigiano Reggiano cream, and fresh cracked Tellicherry pepper.', 18.99, NULL, 'assets/images/pasta.jpg', 1, 1, 'veg', '20-25 min', 750, 4.9, 15),
            ({$catAppetizers}, 'Korean Sweet & Spicy Glazed Wings', 'Double-crisped jumbo chicken wings coated in sticky gochujang honey glaze, toasted white sesame seeds, fresh scallions, and cool house garlic herb ranch.', 12.99, 11.50, 'assets/images/wings.jpg', 1, 1, 'non-veg', '12-15 min', 620, 4.7, 31),
            ({$catDesserts}, 'Molten Belgian Chocolate Lava Cake', 'Warm molten center with Valrhona 70% dark chocolate, served with a velvety scoop of Madagascar vanilla bean gelato, fresh raspberries, and powdered sugar.', 9.99, 8.99, 'assets/images/lava_cake.jpg', 1, 1, 'veg', '10-12 min', 540, 5.0, 42),
            ({$catPastas}, 'Fresh Salmon & Avocado Poke Bowl', 'Sashimi-grade Norwegian salmon cubes, ripe avocado slices, edamame, wakame seaweed salad, pickled sushi ginger, toasted sesame, on seasoned warm sushi rice.', 17.50, 15.99, 'assets/images/salmon_bowl.jpg', 1, 1, 'non-veg', '10-15 min', 490, 4.9, 19),
            ({$catDrinks}, 'Sparkling Wild Berry Mojito', 'Hand-muddled blackberries, fresh garden mint, zesty Key lime juice, pure cane syrup, topped with effervescent sparkling soda and crushed crystal ice.', 6.99, 5.99, 'assets/images/mojito.jpg', 1, 1, 'veg', '5 min', 140, 4.8, 12);
        ");

        // Seed initial reviews
        $burgerId = $pdo->query("SELECT id FROM food_items WHERE name LIKE '%Smash%' LIMIT 1")->fetchColumn();
        $pizzaId = $pdo->query("SELECT id FROM food_items WHERE name LIKE '%Margherita%' LIMIT 1")->fetchColumn();
        $custId = $pdo->query("SELECT id FROM users WHERE email = 'customer@restaurant.com' LIMIT 1")->fetchColumn();

        if ($burgerId && $custId) {
            $pdo->exec("INSERT INTO `reviews` (`food_item_id`, `user_id`, `rating`, `comment`) VALUES
                ({$burgerId}, {$custId}, 5, 'Hands down the best smash burger in town! The brioche was ultra soft and truffle sauce gives it an incredible kick.'),
                ({$pizzaId}, {$custId}, 5, 'Authentic woodfired crust with the right leopard spotting. Fresh basil aroma filled the room as soon as it arrived!');
            ");
        }

        // Seed initial sample order for order tracking & analytics demo
        $riderRecord = $pdo->query("SELECT id FROM riders LIMIT 1")->fetchColumn();
        if ($custId && $burgerId) {
            $pdo->exec("INSERT INTO `orders` (`order_number`, `user_id`, `rider_id`, `subtotal`, `discount`, `delivery_fee`, `tax`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `delivery_address`, `delivery_phone`, `special_notes`) VALUES
                ('ORD-882194', {$custId}, {$riderRecord}, 22.98, 3.00, 4.99, 1.84, 26.81, 'card', 'completed', 'out_for_delivery', '405 Skyline Blvd, Apt 4B, Metro City', '+1 (555) 837-1928', 'Please leave at the door with doorman if not answering.');
            ");
            $orderId = $pdo->lastInsertId();
            $pdo->exec("INSERT INTO `order_items` (`order_id`, `food_item_id`, `item_name`, `price`, `quantity`, `subtotal`) VALUES
                ({$orderId}, {$burgerId}, 'Smash Truffle Double Cheeseburger', 13.99, 1, 13.99),
                ({$orderId}, {$burgerId} + 3, 'Korean Sweet & Spicy Glazed Wings', 11.50, 1, 11.50);
            ");

            // Another delivered order for revenue stats
            $pdo->exec("INSERT INTO `orders` (`order_number`, `user_id`, `rider_id`, `subtotal`, `discount`, `delivery_fee`, `tax`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `delivery_address`, `delivery_phone`, `special_notes`) VALUES
                ('ORD-741029', {$custId}, {$riderRecord}, 35.49, 5.00, 4.99, 2.84, 38.32, 'upi', 'completed', 'delivered', '405 Skyline Blvd, Apt 4B, Metro City', '+1 (555) 837-1928', 'Extra napkins appreciated!');
            ");
            $orderId2 = $pdo->lastInsertId();
            $pdo->exec("INSERT INTO `order_items` (`order_id`, `food_item_id`, `item_name`, `price`, `quantity`, `subtotal`) VALUES
                ({$orderId2}, {$pizzaId}, 'Artisanal Woodfired Margherita', 14.50, 1, 14.50),
                ({$orderId2}, {$burgerId} + 4, 'Molten Belgian Chocolate Lava Cake', 8.99, 2, 17.98);
            ");
        }
    }
}
