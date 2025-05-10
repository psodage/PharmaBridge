<?php
session_start();
require_once('../api/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to manage your cart.']);
    exit;
}

// Check if required parameter is set
if (!isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = (int)$_POST['cart_id'];

// Check if cart item belongs to the user
$check_query = "SELECT id FROM cart WHERE id = $cart_id AND user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found or does not belong to you.']);
    exit;
}

// Remove the item from cart
$delete_query = "DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id";
$delete_result = mysqli_query($conn, $delete_query);

if ($delete_result) {
    // Get updated cart count
    $count_query = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = $user_id";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $cart_count = $count_row['cart_count'] ? $count_row['cart_count'] : 0;
    
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart.']);
}
?>