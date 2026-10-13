<?php
// api/menu.php - Food Menu, Search, and Filter API
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'items';

if ($action === 'categories') {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(f.id) as items_count 
        FROM categories c
        LEFT JOIN food_items f ON c.id = f.category_id AND f.is_available = 1
        GROUP BY c.id
        ORDER BY c.id ASC
    ");
    $categories = $stmt->fetchAll();
    echo json_encode(['success' => true, 'categories' => $categories]);
    exit;
}

if ($action === 'items') {
    $categorySlug = $_GET['category'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $dietary = $_GET['dietary'] ?? 'all';
    $sortBy = $_GET['sort'] ?? 'featured';
    $minPrice = floatval($_GET['min_price'] ?? 0);
    $maxPrice = floatval($_GET['max_price'] ?? 1000);

    $sql = "
        SELECT f.*, c.name as category_name, c.slug as category_slug
        FROM food_items f
        JOIN categories c ON f.category_id = c.id
        WHERE 1=1
    ";
    $params = [];

    // Filter by category
    if ($categorySlug !== 'all' && !empty($categorySlug)) {
        $sql .= " AND c.slug = ?";
        $params[] = $categorySlug;
    }

    // Filter by search query
    if (!empty($search)) {
        $sql .= " AND (f.name LIKE ? OR f.description LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    // Filter by dietary
    if ($dietary !== 'all' && in_array($dietary, ['veg', 'non-veg'])) {
        $sql .= " AND f.dietary = ?";
        $params[] = $dietary;
    }

    // Filter by price range
    if ($minPrice > 0) {
        $sql .= " AND (CASE WHEN f.discount_price IS NOT NULL THEN f.discount_price ELSE f.price END) >= ?";
        $params[] = $minPrice;
    }
    if ($maxPrice < 1000 && $maxPrice > 0) {
        $sql .= " AND (CASE WHEN f.discount_price IS NOT NULL THEN f.discount_price ELSE f.price END) <= ?";
        $params[] = $maxPrice;
    }

    // Sorting
    switch ($sortBy) {
        case 'price_asc':
            $sql .= " ORDER BY (CASE WHEN f.discount_price IS NOT NULL THEN f.discount_price ELSE f.price END) ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY (CASE WHEN f.discount_price IS NOT NULL THEN f.discount_price ELSE f.price END) DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY f.rating DESC, f.reviews_count DESC";
            break;
        case 'popular':
            $sql .= " ORDER BY f.reviews_count DESC";
            break;
        case 'featured':
        default:
            $sql .= " ORDER BY f.is_featured DESC, f.id ASC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($items),
        'items' => $items
    ]);
    exit;
}

if ($action === 'item_detail') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Item ID is required.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT f.*, c.name as category_name, c.slug as category_slug
        FROM food_items f
        JOIN categories c ON f.category_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Food item not found.']);
        exit;
    }

    // Fetch reviews for this item
    $revStmt = $pdo->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.food_item_id = ?
        ORDER BY r.created_at DESC
    ");
    $revStmt->execute([$id]);
    $reviews = $revStmt->fetchAll();

    $item['reviews'] = $reviews;

    echo json_encode(['success' => true, 'item' => $item]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
