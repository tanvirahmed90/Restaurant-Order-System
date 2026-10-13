<?php
// api/reviews.php - Customer Food Reviews & Ratings API
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'list') {
    $foodItemId = intval($_GET['food_item_id'] ?? 0);
    $sql = "
        SELECT r.*, u.name as user_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
    ";
    $params = [];
    if ($foodItemId > 0) {
        $sql .= " WHERE r.food_item_id = ?";
        $params[] = $foodItemId;
    }
    $sql .= " ORDER BY r.created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    echo json_encode(['success' => true, 'reviews' => $reviews]);
    exit;
}

if ($method === 'POST' && $action === 'add') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];

    $userId = $_SESSION['user_id'] ?? intval($data['user_id'] ?? 0);
    $foodItemId = intval($data['food_item_id'] ?? 0);
    $rating = intval($data['rating'] ?? 5);
    $comment = trim($data['comment'] ?? '');

    if (!$userId) {
        // Fallback for guest submitting review: use guest id or prompt
        $userId = $pdo->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1")->fetchColumn();
    }

    if (!$foodItemId) {
        echo json_encode(['success' => false, 'error' => 'Food item ID is required.']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5 stars.']);
        exit;
    }

    if (empty($comment)) {
        echo json_encode(['success' => false, 'error' => 'Please write a brief review comment.']);
        exit;
    }

    // Insert review
    $stmt = $pdo->prepare("INSERT INTO reviews (food_item_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$foodItemId, $userId, $rating, $comment]);

    // Recalculate average rating & count for this item
    $aggStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rev_count FROM reviews WHERE food_item_id = ?");
    $aggStmt->execute([$foodItemId]);
    $agg = $aggStmt->fetch();

    $newRating = round(floatval($agg['avg_rating']), 1);
    $newCount = intval($agg['rev_count']);

    $updStmt = $pdo->prepare("UPDATE food_items SET rating = ?, reviews_count = ? WHERE id = ?");
    $updStmt->execute([$newRating, $newCount, $foodItemId]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your review!',
        'new_rating' => $newRating,
        'reviews_count' => $newCount
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
