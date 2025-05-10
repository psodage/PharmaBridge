<?php
session_start();
require_once '../api/db.php';

// Check if user is logged in and is a medical professional
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Validate order ID
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

$order_id = intval($_GET['order_id']);

// Fetch order details
$order_query = "SELECT o.*, mu.name AS user_name 
                FROM orders o
                JOIN medical_users mu ON o.user_id = mu.id
                WHERE o.id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = "SELECT oi.*, p.product_name, p.product_image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}

// Prepare response
$response = [
    'order_id' => $order['id'],
    'user_name' => $order['user_name'],
    'total_price' => $order['total_price'],
    'status' => $order['status'],
    'created_at' => $order['created_at'],
    'items' => $order_items
];

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close statements and connection
$order_stmt->close();
$items_stmt->close();
$conn->close();
?>