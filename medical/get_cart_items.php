<?php
session_start();
require_once('../api/db.php');

if (!isset($_SESSION['user_id'])) {
    echo '<p class="empty-cart">Please sign in to view your cart.</p>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart items with product details
$query = "SELECT c.id as cart_id, c.product_id, c.quantity, 
          p.product_name, p.price, p.stock_quantity 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $total = 0;
    echo '<ul class="cart-items">';
    
    while ($item = mysqli_fetch_assoc($result)) {
        $item_total = $item['price'] * $item['quantity'];
        $total += $item_total;
        
        echo '<li class="cart-item">';
        echo '<div class="cart-item-details">';
        echo '<div class="cart-item-name">' . htmlspecialchars($item['product_name']) . '</div>';
        echo '<div class="cart-item-price">₹' . number_format($item['price'], 2) . ' each</div>';
        echo '<div class="cart-item-total">Total: ₹' . number_format($item_total, 2) . '</div>';
        echo '</div>';
        
        echo '<div class="cart-item-controls">';
        echo '<input type="number" id="cart-qty-' . $item['cart_id'] . '" class="cart-quantity-input" 
              value="' . $item['quantity'] . '" min="1" max="' . $item['stock_quantity'] . '">';
        echo '<button class="update-quantity" data-id="' . $item['cart_id'] . '">Update</button>';
        echo '<button class="cart-item-remove" data-id="' . $item['cart_id'] . '">Remove</button>';
        echo '</div>';
        
        echo '</li>';
    }
    
    echo '</ul>';
    
    // Cart summary and checkout button
    echo '<div class="cart-summary">';
    echo '<div class="cart-total">';
    echo '<span>Total Amount:</span>';
    echo '<span>₹' . number_format($total, 2) . '</span>';
    echo '</div>';
    echo '<button class="checkout-button" onclick="proceedToCheckout()">Proceed to Checkout</button>';
    echo '</div>';
} else {
    echo '<p class="empty-cart">Your cart is empty. Add some products to get started!</p>';
}
?>