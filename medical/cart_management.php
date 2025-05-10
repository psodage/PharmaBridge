<?php
session_start();
require_once '../api/db.php';

// Determine the action based on GET/POST parameters
$action = isset($_GET['action']) ? $_GET['action'] : 
          (isset($_POST['action']) ? $_POST['action'] : 'view');

// Authentication and Access Control
function checkMedicalUserAccess() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') {
        // Redirect to login or return error
        header("Location: ../signin.php");
        exit();
    }
    return $_SESSION['user_id'];
}

// Action Handlers
switch ($action) {
    case 'add_to_cart':
        addToCart($conn);
        break;
    case 'get_cart_count':
        getCartCount($conn);
        break;
    case 'update_quantity':
        updateCartQuantity($conn);
        break;
    case 'remove_item':
        removeFromCart($conn);
        break;
    case 'view':
    default:
        viewCart($conn);
        break;
}

// Function to Add Product to Cart
function addToCart($conn) {
    $user_id = checkMedicalUserAccess();

    // Validate input
    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Check product availability
    $product_query = "SELECT * FROM products WHERE id = ? AND stock_quantity >= ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("ii", $product_id, $quantity);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Add or update cart item
        $cart_query = "
            INSERT INTO cart (medical_user_id, product_id, quantity) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ";
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("iiii", $user_id, $product_id, $quantity, $quantity);
        
        if ($stmt->execute()) {
            // Get updated cart count
            $count_query = "SELECT COUNT(DISTINCT product_id) as cart_items FROM cart WHERE medical_user_id = ?";
            $stmt = $conn->prepare($count_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $cart_result = $stmt->get_result()->fetch_assoc();

            echo json_encode([
                'success' => true, 
                'message' => 'Product added to cart', 
                'cart_items' => $cart_result['cart_items']
            ]);
            exit();
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
    exit();
}

// Function to Get Cart Count
function getCartCount($conn) {
    $user_id = checkMedicalUserAccess();

    $cart_count_query = "SELECT COUNT(DISTINCT product_id) as cart_items FROM cart WHERE medical_user_id = ?";
    $stmt = $conn->prepare($cart_count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result()->fetch_assoc();

    echo json_encode(['cart_items' => $cart_result['cart_items']]);
    exit();
}

// Function to Update Cart Quantity
function updateCartQuantity($conn) {
    $user_id = checkMedicalUserAccess();

    if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);

    // Verify cart item belongs to user and check stock
    $verify_query = "
        SELECT p.id AS product_id, p.stock_quantity 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.medical_user_id = ?
    ";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product_data = $result->fetch_assoc();

        if ($quantity <= $product_data['stock_quantity']) {
            // Update cart quantity
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND medical_user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quantity updated']);
                exit();
            }
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
    exit();
}

// Function to Remove Item from Cart
function removeFromCart($conn) {
    $user_id = checkMedicalUserAccess();

    if (!isset($_POST['cart_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    $cart_id = intval($_POST['cart_id']);

    // Remove specific cart item
    $remove_query = "DELETE FROM cart WHERE id = ? AND medical_user_id = ?";
    $stmt = $conn->prepare($remove_query);
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    exit();
}

// Function to View Cart
function viewCart($conn) {
    $user_id = checkMedicalUserAccess();

    // Fetch cart items with product details
    $cart_query = "
        SELECT 
            c.id AS cart_id, 
            c.quantity, 
            p.id AS product_id, 
            p.product_name, 
            p.generic_name, 
            p.price, 
            p.product_image,
            p.stock_quantity
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.medical_user_id = ?
    ";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();

    // Calculate total
    $total_query = "
        SELECT SUM(c.quantity * p.price) AS total_price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.medical_user_id = ?
    ";
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_result = $stmt->get_result()->fetch_assoc();
    $total_price = $total_result['total_price'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BridgeRx Connect</title>
    <link rel="stylesheet" href="../css/add_product_medical.css">
    <style>
        .cart-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 20px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-actions {
            display: flex;
            align-items: center;
        }
        .cart-total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <h1>Your Shopping Cart</h1>
            
            <?php if ($cart_result->num_rows > 0): ?>
                <div class="cart-container">
                    <?php while ($item = $cart_result->fetch_assoc()): ?>
                        <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                            <?php if (!empty($item['product_image'])): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <?php else: ?>
                                <div class="product-image-placeholder">No Image</div>
                            <?php endif; ?>
                            
                            <div class="cart-item-details">
                                <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p><?php echo htmlspecialchars($item['generic_name']); ?></p>
                                <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            
                            <div class="cart-actions">
                                <input 
                                    type="number" 
                                    min="1" 
                                    max="<?php echo $item['stock_quantity']; ?>" 
                                    value="<?php echo $item['quantity']; ?>" 
                                    onchange="updateQuantity(<?php echo $item['cart_id']; ?>, this.value)"
                                >
                                <button onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">Remove</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div class="cart-total">
                        Total: $<?php echo number_format($total_price, 2); ?>
                    </div>
                    
                    <div class="cart-checkout">
                        <button onclick="proceedToCheckout()">Proceed to Checkout</button>
                    </div>
                </div>
            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function updateQuantity(cartId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('cart_id', cartId);
        formData.append('quantity', quantity);

        fetch('cart_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to update total
            } else {
                alert(data.message);
            }
        });
    }

    function removeFromCart(cartId) {
        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('cart_id', cartId);

        fetch('cart_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    function proceedToCheckout() {
        window.location.href = 'checkout.php';
    }
    </script>
</body>
</html>
<?php 
    $conn->close(); 
    exit();
}
?>