<?php
session_start();
require_once('../api/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to update your cart.']);
    exit;
}

// Check if required parameters are set
if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = (int)$_POST['cart_id'];
$quantity = (int)$_POST['quantity'];

// Validate quantity
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be greater than zero.']);
    exit;
}

// Check if cart item belongs to the user
$check_query = "SELECT c.product_id, p.stock_quantity FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.id = $cart_id AND c.user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found or does not belong to you.']);
    exit;
}

$cart_item = mysqli_fetch_assoc($check_result);

// Check if requested quantity is available in stock
if ($quantity > $cart_item['stock_quantity']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Only ' . $cart_item['stock_quantity'] . ' items are available in stock.'
    ]);
    exit;
}

// Update the cart
$update_query = "UPDATE cart SET quantity = $quantity WHERE id = $cart_id AND user_id = $user_id";
$update_result = mysqli_query($conn, $update_query);

if ($update_result) {
    // Get updated cart count
    $count_query = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = $user_id";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $cart_count = $count_row['cart_count'] ? $count_row['cart_count'] : 0;
    
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart.']);
}
?>