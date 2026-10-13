<?php
// api/auth.php - User Authentication & Profile API
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'me') {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode(['success' => true, 'user' => $user]);
            exit;
        }
    }
    echo json_encode(['success' => true, 'user' => null]);
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    if ($action === 'register') {
        $name = trim($data['name'] ?? '');
        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $role = in_array($data['role'] ?? '', ['customer', 'rider']) ? $data['role'] : 'customer';

        if (!$name || !$email || !$password) {
            echo json_encode(['success' => false, 'error' => 'Please provide name, email, and password.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Please provide a valid email address.']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'An account with this email already exists.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$name, $email, $hash, $phone, $address, $role]);
        $newUserId = $pdo->lastInsertId();

        // If rider, create rider entry
        if ($role === 'rider') {
            $pdo->prepare("INSERT INTO riders (user_id, vehicle_type, vehicle_number, status) VALUES (?, 'Motorbike', 'RIDER-".rand(100,999)."', 'available')")->execute([$newUserId]);
        }

        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_role'] = $role;

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful!',
            'user' => [
                'id' => $newUserId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'role' => $role
            ]
        ]);
        exit;
    }

    if ($action === 'login') {
        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'error' => 'Please provide both email and password.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'message' => 'Logged in successfully!',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'address' => $user['address'],
                'role' => $user['role']
            ]
        ]);
        exit;
    }

    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
        exit;
    }

    if ($action === 'update_profile') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
            exit;
        }

        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');

        if (!$name) {
            echo json_encode(['success' => false, 'error' => 'Name cannot be empty.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $address, $_SESSION['user_id']]);

        // Optional password update
        if (!empty($data['new_password'])) {
            $hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $pwdStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwdStmt->execute([$hash, $_SESSION['user_id']]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $name,
                'phone' => $phone,
                'address' => $address
            ]
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
