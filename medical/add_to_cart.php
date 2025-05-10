<?php
session_start();
require_once('../api/db.php'); // Ensure database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to add to cart.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
        exit;
    }

    // Check if product exists
    $check_product = "SELECT stock_quantity FROM products WHERE id = ?";
    $stmt = $conn->prepare($check_product);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    $product = $result->fetch_assoc();
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
        exit;
    }

    // Check if product is already in cart
    $check_cart = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_cart);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();

    if ($cart_result->num_rows > 0) {
        // Update quantity if already in cart
        $cart_item = $cart_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        $update_cart = "UPDATE cart SET quantity = ? WHERE id = ?";
        $stmt = $conn->prepare($update_cart);
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt->execute();
    } else {
        // Insert new cart item
        $insert_cart = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_cart);
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $stmt->execute();
    }

    // Get updated cart count
    $cart_count_query = "SELECT SUM(quantity) AS cart_count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($cart_count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_count_result = $stmt->get_result();
    $cart_count = $cart_count_result->fetch_assoc()['cart_count'];

    echo json_encode(['success' => true, 'message' => 'Product added to cart.', 'cart_count' => $cart_count]);
}
?>
