<?php
// api/rider.php - Delivery Rider Module API
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Get default or current rider
$riderId = intval($_GET['rider_id'] ?? 0);
if (!$riderId && isset($_SESSION['user_id'])) {
    $rStmt = $pdo->prepare("SELECT id FROM riders WHERE user_id = ?");
    $rStmt->execute([$_SESSION['user_id']]);
    $riderId = $rStmt->fetchColumn();
}
if (!$riderId) {
    // Default to first rider for demonstration
    $riderId = $pdo->query("SELECT id FROM riders LIMIT 1")->fetchColumn();
}

if ($action === 'profile') {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name, u.email, u.phone 
        FROM riders r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$riderId]);
    $rider = $stmt->fetch();
    echo json_encode(['success' => true, 'rider' => $rider]);
    exit;
}

if ($action === 'assigned_orders') {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.rider_id = ? AND o.order_status IN ('confirmed', 'preparing', 'out_for_delivery', 'delivered')
        ORDER BY FIELD(o.order_status, 'out_for_delivery', 'preparing', 'confirmed', 'delivered'), o.created_at DESC
    ");
    $stmt->execute([$riderId]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$ord) {
        $itStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itStmt->execute([$ord['id']]);
        $ord['items'] = $itStmt->fetchAll();
    }

    echo json_encode(['success' => true, 'orders' => $orders]);
    exit;
}

if ($method === 'POST' && $action === 'update_status') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    $orderId = intval($data['order_id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!in_array($status, ['out_for_delivery', 'delivered'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid delivery status update.']);
        exit;
    }

    $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?")->execute([$status, $orderId]);

    if ($status === 'delivered') {
        $pdo->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?")->execute([$orderId]);
        $pdo->prepare("UPDATE riders SET status = 'available' WHERE id = ?")->execute([$riderId]);
    } else if ($status === 'out_for_delivery') {
        $pdo->prepare("UPDATE riders SET status = 'on_delivery' WHERE id = ?")->execute([$riderId]);
    }

    echo json_encode(['success' => true, 'message' => "Order #{$orderId} status updated to '{$status}'."]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
