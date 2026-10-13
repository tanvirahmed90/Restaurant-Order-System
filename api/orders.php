<?php
// api/orders.php - Order Management & Payment Processing API
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Promo codes table mock
$VALID_PROMOS = [
    'WELCOME20' => ['type' => 'percent', 'value' => 20, 'min_order' => 15],
    'FEAST10' => ['type' => 'fixed', 'value' => 10, 'min_order' => 30],
    'FREEDEL' => ['type' => 'fixed', 'value' => 4.99, 'min_order' => 20],
];

if ($method === 'POST' && $action === 'validate_promo') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    $code = strtoupper(trim($data['code'] ?? ''));
    $subtotal = floatval($data['subtotal'] ?? 0);

    if (isset($VALID_PROMOS[$code])) {
        $promo = $VALID_PROMOS[$code];
        if ($subtotal < $promo['min_order']) {
            echo json_encode([
                'success' => false,
                'error' => "Minimum order of \${$promo['min_order']} required for this code."
            ]);
            exit;
        }

        $discount = ($promo['type'] === 'percent') ? round(($subtotal * $promo['value']) / 100, 2) : $promo['value'];
        if ($discount > $subtotal) $discount = $subtotal;

        echo json_encode([
            'success' => true,
            'discount' => $discount,
            'code' => $code,
            'message' => "Coupon '{$code}' applied successfully!"
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid or expired coupon code.']);
    exit;
}

if ($method === 'POST' && $action === 'create') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];

    $userId = $_SESSION['user_id'] ?? intval($data['user_id'] ?? 0);
    $items = $data['items'] ?? [];
    $deliveryAddress = trim($data['delivery_address'] ?? '');
    $deliveryPhone = trim($data['delivery_phone'] ?? '');
    $paymentMethod = $data['payment_method'] ?? 'cod';
    $specialNotes = trim($data['special_notes'] ?? '');
    $promoCode = strtoupper(trim($data['promo_code'] ?? ''));

    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'Your cart is empty.']);
        exit;
    }

    if (!$deliveryAddress || !$deliveryPhone) {
        echo json_encode(['success' => false, 'error' => 'Delivery address and phone number are required.']);
        exit;
    }

    // Guest checkout fallback: create or get guest user if not logged in
    if (!$userId) {
        $guestEmail = 'guest_' . uniqid() . '@example.com';
        $guestName = trim($data['customer_name'] ?? 'Guest Customer');
        $pwd = password_hash('guest123', PASSWORD_DEFAULT);
        $uStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, 'customer')");
        $uStmt->execute([$guestName, $guestEmail, $pwd, $deliveryPhone, $deliveryAddress]);
        $userId = $pdo->lastInsertId();
    }

    // Compute subtotal from DB items
    $subtotal = 0.00;
    $orderItemsData = [];
    $itemIds = array_column($items, 'id');

    if (!empty($itemIds)) {
        $inQuery = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, discount_price, is_available FROM food_items WHERE id IN ($inQuery)");
        $stmt->execute($itemIds);
        $dbItems = [];
        while ($row = $stmt->fetch()) {
            $dbItems[$row['id']] = $row;
        }

        foreach ($items as $ci) {
            $itemId = intval($ci['id']);
            $qty = max(1, intval($ci['quantity']));
            if (isset($dbItems[$itemId])) {
                $itemInfo = $dbItems[$itemId];
                $unitPrice = ($itemInfo['discount_price'] !== null && $itemInfo['discount_price'] > 0) ? floatval($itemInfo['discount_price']) : floatval($itemInfo['price']);
                $itemSubtotal = $unitPrice * $qty;
                $subtotal += $itemSubtotal;

                $orderItemsData[] = [
                    'food_item_id' => $itemId,
                    'item_name' => $itemInfo['name'],
                    'price' => $unitPrice,
                    'quantity' => $qty,
                    'subtotal' => $itemSubtotal
                ];
            }
        }
    }

    if (empty($orderItemsData)) {
        echo json_encode(['success' => false, 'error' => 'No valid food items found in cart.']);
        exit;
    }

    // Calculate discount
    $discount = 0.00;
    if ($promoCode && isset($VALID_PROMOS[$promoCode])) {
        $promo = $VALID_PROMOS[$promoCode];
        if ($subtotal >= $promo['min_order']) {
            $discount = ($promo['type'] === 'percent') ? round(($subtotal * $promo['value']) / 100, 2) : $promo['value'];
            if ($discount > $subtotal) $discount = $subtotal;
        }
    }

    $deliveryFee = 4.99;
    if ($promoCode === 'FREEDEL') {
        $deliveryFee = 0.00;
    }
    $tax = round(($subtotal - $discount) * 0.08, 2); // 8% sales tax
    if ($tax < 0) $tax = 0.00;
    $totalAmount = round($subtotal - $discount + $deliveryFee + $tax, 2);

    // Auto-assign available rider if exists
    $riderStmt = $pdo->query("SELECT id FROM riders WHERE status = 'available' LIMIT 1");
    $riderId = $riderStmt->fetchColumn() ?: null;

    $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -6));
    $paymentStatus = ($paymentMethod === 'cod') ? 'pending' : 'completed';

    // Insert order
    $orderInsert = $pdo->prepare("
        INSERT INTO orders (
            order_number, user_id, rider_id, subtotal, discount, delivery_fee, tax, 
            total_amount, payment_method, payment_status, order_status, 
            delivery_address, delivery_phone, special_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'placed', ?, ?, ?)
    ");
    $orderInsert->execute([
        $orderNumber, $userId, $riderId, $subtotal, $discount, $deliveryFee, $tax,
        $totalAmount, $paymentMethod, $paymentStatus, $deliveryAddress, $deliveryPhone, $specialNotes
    ]);
    $orderId = $pdo->lastInsertId();

    // Insert order items
    $itemInsert = $pdo->prepare("
        INSERT INTO order_items (order_id, food_item_id, item_name, price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($orderItemsData as $oi) {
        $itemInsert->execute([$orderId, $oi['food_item_id'], $oi['item_name'], $oi['price'], $oi['quantity'], $oi['subtotal']]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_number' => $orderNumber,
        'order_id' => $orderId,
        'total_amount' => $totalAmount,
        'payment_status' => $paymentStatus,
        'order_status' => 'placed'
    ]);
    exit;
}

if ($action === 'track') {
    $orderNumber = trim($_GET['order_number'] ?? '');
    if (!$orderNumber) {
        echo json_encode(['success' => false, 'error' => 'Order number is required.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email,
               r.vehicle_type, r.vehicle_number, ru.name as rider_name, ru.phone as rider_phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN riders r ON o.rider_id = r.id
        LEFT JOIN users ru ON r.user_id = ru.id
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found. Please check order number.']);
        exit;
    }

    // Fetch items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, fi.image, fi.dietary 
        FROM order_items oi
        LEFT JOIN food_items fi ON oi.food_item_id = fi.id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$order['id']]);
    $order['items'] = $itemsStmt->fetchAll();

    echo json_encode(['success' => true, 'order' => $order]);
    exit;
}

if ($action === 'my_orders') {
    $userId = $_SESSION['user_id'] ?? intval($_GET['user_id'] ?? 0);
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Please log in to view your orders.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as total_items
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    // Attach items to each order
    foreach ($orders as &$ord) {
        $itStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itStmt->execute([$ord['id']]);
        $ord['items'] = $itStmt->fetchAll();
    }

    echo json_encode(['success' => true, 'orders' => $orders]);
    exit;
}

if ($method === 'POST' && $action === 'cancel') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    $orderId = intval($data['order_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found.']);
        exit;
    }

    if ($order['order_status'] !== 'placed') {
        echo json_encode(['success' => false, 'error' => 'Cannot cancel an order that is already confirmed or in preparation.']);
        exit;
    }

    $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?")->execute([$orderId]);
    echo json_encode(['success' => true, 'message' => 'Order has been cancelled successfully.']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
