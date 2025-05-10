<?php
// This code should be added at the beginning of your place_order.php file

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or handle accordingly
    header("Location: login.php");
    exit;
}

// Initialize cart items array
$cart_items = [];
$cart_total = 0;

// Connect to database
$conn = new mysqli('localhost', 'root', '', 'pharma');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare and execute query to retrieve cart items
$user_id = $_SESSION['user_id'];
$query = "SELECT c.id, c.product_id, c.quantity, p.product_name, p.price, p.stock_quantity, p.product_image 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch cart items
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $cart_total += ($row['price'] * $row['quantity']);
    }
}

$stmt->close();

// Get categories for filter dropdown
$category_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''";
$category_result = $conn->query($category_query);
$categories = [];

if ($category_result && $category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Check if user has billing details
$has_billing_details = false;
$billing_details = null;

$billing_query = "SELECT * FROM billing_details WHERE user_id = ?";
$billing_stmt = $conn->prepare($billing_query);
$billing_stmt->bind_param("i", $user_id);
$billing_stmt->execute();
$billing_result = $billing_stmt->get_result();

if ($billing_result->num_rows > 0) {
    $has_billing_details = true;
    $billing_details = $billing_result->fetch_assoc();
}

$billing_stmt->close();

// Process checkout if form submitted
if (isset($_POST['checkout']) && !empty($cart_items)) {
  // Check if billing details exist
  if (!$has_billing_details) {
      // Redirect to billing details page with return URL
      $_SESSION['checkout_pending'] = true;
      $error = "Please add billing details before placing an order.";
      // You could also redirect to a billing details page instead
      // header("Location: billing_details.php?redirect=place_order.php");
      // exit;
  } else {
      // Begin transaction to ensure data integrity
      $conn->begin_transaction();
      
      try {
          // 1. Create order in the orders table
          $order_date = date('Y-m-d H:i:s');
          $order_status = 'Pending';
          $subtotal = $cart_total;
          $tax = $cart_total * 0.1;
          $shipping = 0;
          $total = $subtotal + $tax + $shipping;
          
          $order_query = "INSERT INTO orders (user_id, order_date, order_status, subtotal, tax, shipping, total) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
          $order_stmt = $conn->prepare($order_query);
          $order_stmt->bind_param("issdddd", $user_id, $order_date, $order_status, $subtotal, $tax, $shipping, $total);
          $order_stmt->execute();
          
          // Get the last inserted order ID
          $order_id = $conn->insert_id;
          $order_stmt->close();
          
          // 2. Create order items for each product in cart
          $order_items_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
          $order_items_stmt = $conn->prepare($order_items_query);
          
          foreach ($cart_items as $item) {
              // Insert order item
              $order_items_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
              $order_items_stmt->execute();
              
              // Update product stock quantity
              $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
              $update_stock_stmt = $conn->prepare($update_stock_query);
              $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
              $update_stock_stmt->execute();
              $update_stock_stmt->close();
          }
          
          $order_items_stmt->close();
          
          // 3. Clear the user's cart
          $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
          $clear_cart_stmt = $conn->prepare($clear_cart_query);
          $clear_cart_stmt->bind_param("i", $user_id);
          $clear_cart_stmt->execute();
          $clear_cart_stmt->close();
          
          // Commit the transaction
          $conn->commit();
          
          // Set success message
          $success = "Order placed successfully! Your order ID is #$order_id";
          
      } catch (Exception $e) {
          // An error occurred, rollback the transaction
          $conn->rollback();
          $error = "Error processing your order: " . $e->getMessage();
      }
  }
}

// Query to get products (with search and category filters)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT id, product_name, category, price, stock_quantity, product_image, manufacturer FROM products WHERE 1=1";

if (!empty($search)) {
    $search = "%$search%";
    $query .= " AND (product_name LIKE ? OR generic_name LIKE ? OR description LIKE ?)";
}

if (!empty($category)) {
    $query .= " AND category = ?";
}

$query .= " ORDER BY product_name ASC";

$stmt = $conn->prepare($query);

// Bind parameters based on search and category
if (!empty($search) && !empty($category)) {
    $stmt->bind_param("ssss", $search, $search, $search, $category);
} elseif (!empty($search)) {
    $stmt->bind_param("sss", $search, $search, $search);
} elseif (!empty($category)) {
    $stmt->bind_param("s", $category);
}

$stmt->execute();
$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

// DO NOT close the connection here - only close it at the end of the file
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Place New Order - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <link rel="stylesheet" href="../css/place_order.css">
  <style>
    /* Billing details card styling */
.billing-info {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.billing-info p {
  margin: 10px 0;
  font-size: 15px;
  line-height: 1.5;
  color: #333;
}

.billing-info p strong {
  font-weight: 600;
  color: #2c3e50;
  display: inline-block;
  width: 80px;
}

.billing-info-header {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 15px;
  color: #2c3e50;
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 10px;
}

/* Edit button styling */
.edit-billing-btn {
  display: inline-flex;
  align-items: center;
  background-color: #fff;
  color: #4a6bff;
  border: 1px solid #4a6bff;
  border-radius: 4px;
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  margin-top: 10px;
}

.edit-billing-btn:hover {
  background-color: #4a6bff;
  color: #fff;
}

.edit-billing-btn i {
  margin-right: 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .billing-info {
    padding: 15px;
  }
  
  .billing-info p strong {
    width: 70px;
    font-size: 14px;
  }
  
  .billing-info p {
    font-size: 14px;
  }
}
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="logo-icon">🏥</span> BridgeRx Connect
    </div>
    <div class="header-controls">
      <button class="header-btn" id="sidebarToggle" title="Toggle Sidebar">☰</button>
      <div class="account-info">
        <span>Welcome, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest'; ?></span>
        <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'A'; ?></div>
      </div>
    </div>
  </div>
  
  <div class="sidebar" id="sidebar">
    <ul class="menu">
      <li class="menu-item">
        <button class="menu-btn " data-page="medical.php">
          <span class="menu-icon">🏠</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🏢</span>
          <span class="menu-text">Suppliers</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="view_company.php">Browse Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item" data-page="view_supplier.php">View Supplier <br>Details</a>
        </div>
      </li>
     
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💊</span>
          <span class="menu-text">Products</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="view_product.php">Search & Browse <br>Products</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">📋</span>
          <span class="menu-text">Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item active" data-page="place_order.php">Place New Order</a>
          <a href="#" class="dropdown-item" data-page="track_order.php">Track Order Status</a>
          <a href="#" class="dropdown-item" data-page="view_history.php">View Order History</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Communication</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="send_inquiries.php">Send Inquiries <br>to Suppliers</a>
          <a href="#" class="dropdown-item" data-page="view_messages.php">View & Respond <br>to Messages</a>
        
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Settings</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="profile.php">Profile Management</a>
      
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🔍</span>
          <span class="menu-text">Support</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="admin.php">Contact Admin</a>
      
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="logout()">
          <span class="menu-icon">🚪</span>
          <span class="menu-text">Logout</span>
        </button>
      </li>
    </ul>
  </div>
  
  <div class="content-container" id="content">
    <h1>Place New Order</h1>
    
    <?php if(isset($success)): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <?php echo $success; ?>
      </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <?php echo $error; ?>
        <?php if(!$has_billing_details): ?>
          <div class="mt-2">
            <a href="details.php" class="btn btn-primary">
              <i class="fas fa-address-card"></i> Add Billing Details
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    
    <?php if(isset($message)): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
    
    <!-- Search container -->
    <div class="search-container">
      <form action="" method="GET" class="search-form" style="width: 100%; display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="text" name="search" class="search-input" placeholder="Search products by name, generic name or description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        
        <select name="category" class="select-category">
          <option value="">All Categories</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat); ?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <button type="submit" class="search-btn">
          <i class="fas fa-search"></i> Search
        </button>
      </form>
    </div>
    
    <!-- Display billing details status -->
    <?php if(!empty($cart_items)): ?>
      <div class="billing-status-container">
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-address-card"></i> Billing Details Status</h3>
          </div>
          <div class="card-body">
            <?php if($has_billing_details): ?>
              <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Billing details are complete - ready to checkout
              </div>
              <div class="billing-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($billing_details['billing_name']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($billing_details['billing_address']); ?>, 
                  <?php echo htmlspecialchars($billing_details['billing_city']); ?>, 
                  <?php echo htmlspecialchars($billing_details['billing_state']); ?> 
                  <?php echo htmlspecialchars($billing_details['billing_zip']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($billing_details['billing_email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($billing_details['billing_phone']); ?></p>
              </div>
              <a href="details.php" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> Edit Billing Details
              </a>
            <?php else: ?>
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> You need to add billing details before placing an order
              </div>
              <a href="details.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add Billing Details
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
    <!-- Shopping Cart Section -->
    <div class="cart-section">
      <div class="cart-header">
        <div class="cart-title">
          <i class="fas fa-shopping-cart"></i> Your Cart
        </div>
      </div>
      
      <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
          <div class="empty-cart-icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <div class="empty-cart-message">Your cart is empty. Add products below to place an order.</div>
          <a href="view_product.php" class="start-shopping-btn">
            <i class="fas fa-tag"></i> Start Shopping
          </a>
        </div>
      <?php else: ?>
        <div class="cart-items">
          <?php foreach($cart_items as $item): ?>
            <div class="cart-item">
              <div class="cart-product-info">
                <img src="<?php echo !empty($item['product_image']) ? $item['product_image'] : '/api/placeholder/60/60'; ?>" alt="<?php echo $item['product_name']; ?>" class="cart-product-image">
                <div class="cart-product-details">
                  <div class="cart-product-name"><?php echo $item['product_name']; ?></div>
                  <div class="cart-product-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                </div>
              </div>
              
              <div class="cart-item-actions">
                <div class="cart-quantity">
                  <button type="button" class="cart-quantity-btn decrease-qty" data-id="<?php echo $item['id']; ?>">-</button>
                  <input type="number" class="cart-quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" readonly>
                  <button type="button" class="cart-quantity-btn increase-qty" data-id="<?php echo $item['id']; ?>" data-max="<?php echo $item['stock_quantity']; ?>">+</button>
                </div>
                
                <div class="cart-item-total">
                  $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </div>
                
                <button type="button" class="cart-remove-btn" data-id="<?php echo $item['id']; ?>">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div class="cart-summary">
          <div class="cart-subtotal">
            <span>Subtotal:</span>
            <span>$<?php echo number_format($cart_total, 2); ?></span>
          </div>
          
          <div class="cart-shipping">
            <span>Shipping:</span>
            <span>$<?php echo number_format(0, 2); ?></span>
          </div>
          
          <div class="cart-tax">
            <span>Tax (10%):</span>
            <span>$<?php echo number_format($cart_total * 0.1, 2); ?></span>
          </div>
          
          <div class="cart-total">
            <span>Total:</span>
            <span>$<?php echo number_format($cart_total + ($cart_total * 0.1), 2); ?></span>
          </div>
        </div>
        
        <form method="post" action="">
          <button type="submit" name="checkout" class="checkout-btn" <?php echo !$has_billing_details ? 'disabled' : ''; ?>>
            <i class="fas fa-check-circle"></i> Place Order
          </button>
          <?php if(!$has_billing_details): ?>
            <div class="checkout-warning">
              <i class="fas fa-exclamation-triangle"></i> You must add billing details before placing an order
            </div>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>

    <!-- Products Grid Section -->
    
  </div>
  
  <script>
    // Function to add item to cart
    function addToCart(event, productId) {
        event.preventDefault();
        
        // Get quantity
        const quantityInput = document.getElementById('qty-' + productId);
        const quantity = parseInt(quantityInput.value);
        
        // Validate quantity
        if (isNaN(quantity) || quantity <= 0) {
            alert('Please enter a valid quantity');
            return;
        }
        
        // Send AJAX request to add to cart
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'add_to_cart.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.status === 'success') {
                        // Show success message
                        showAlert('Product added to cart successfully!', 'success');
                        
                        // Reload the page after a short delay to update cart
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        // Show error message
                        showAlert(response.message, 'error');
                    }
                } catch (e) {
                    showAlert('An error occurred. Please try again.', 'error');
                }
            } else {
                showAlert('An error occurred. Please try again.', 'error');
            }
        };
        
        xhr.send('product_id=' + productId + '&quantity=' + quantity);
    }

    // Function to show alert message
    function showAlert(message, type) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type;
        
        // Add icon based on alert type
        const icon = document.createElement('i');
        icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' alert-icon';
        alertDiv.appendChild(icon);
        
        // Add message
        const messageSpan = document.createElement('span');
        messageSpan.textContent = message;
        alertDiv.appendChild(messageSpan);
        
        // Insert alert at the top of the content container
        const contentContainer = document.getElementById('content');
        contentContainer.insertBefore(alertDiv, contentContainer.firstChild);
        
        // Remove alert after 3 seconds
        setTimeout(function() {
            alertDiv.remove();
        }, 3000);
    }

    // Main JavaScript for Admin Dashboard
    
    // On page load, initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize event listeners
      initializeEventListeners();
      
      // Initialize cart functionality
      initializeCartFunctionality();
    });
    
    // Initialize all event listeners for the dashboard
    function initializeEventListeners() {
      // Toggle sidebar
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      const content = document.getElementById('content');
      
      sidebarToggle.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
          sidebar.classList.toggle('show');
        } else {
          sidebar.classList.toggle('sidebar-collapsed');
          content.classList.toggle('content-full');
        }
      });
      
      // Handle menu buttons and dropdown items clicks using event delegation
      document.addEventListener('click', function(event) {
        // For main menu buttons
        if (event.target.classList.contains('menu-btn') && !event.target.hasAttribute('onclick')) {
          const page = event.target.getAttribute('data-page');
          if (page) {
            window.location.href = page;
          }
        }
        
        // For dropdown items
        if (event.target.classList.contains('dropdown-item')) {
          event.preventDefault();
          const page = event.target.getAttribute('data-page');
          if (page) {
            window.location.href = page;
          }
        }

        // Close dropdowns when clicking outside
        if (!event.target.closest('.menu-item')) {
          document.querySelectorAll('.dropdown.show').forEach(function(el) {
            el.classList.remove('show');
          });
        }
      });
    }

    // Function to toggle dropdown menus
    function toggleDropdown(button) {
      const dropdown = button.nextElementSibling;
      
      // Close all other open dropdowns
      document.querySelectorAll('.dropdown.show').forEach(function(el) {
        if (el !== dropdown) {
          el.classList.remove('show');
        }
      });
      
      // Toggle the clicked dropdown
      dropdown.classList.toggle('show');
    }

    // Initialize cart functionality
    function initializeCartFunctionality() {
      // Decrease quantity button
      document.querySelectorAll('.decrease-qty').forEach(function(button) {
        button.addEventListener('click', function() {
          const input = this.nextElementSibling;
          if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
            updateCartItem(this.getAttribute('data-id'), parseInt(input.value));
          }
        });
      });
      
      // Increase quantity button
      document.querySelectorAll('.increase-qty').forEach(function(button) {
        button.addEventListener('click', function() {
          const input = this.previousElementSibling;
          const maxQuantity = parseInt(this.getAttribute('data-max'));
          if (parseInt(input.value) < maxQuantity) {
            input.value = parseInt(input.value) + 1;
            updateCartItem(this.getAttribute('data-id'), parseInt(input.value));
          }
        });
      });
      
      // Remove item button
      document.querySelectorAll('.cart-remove-btn').forEach(function(button) {
        button.addEventListener('click', function() {
          if (confirm('Are you sure you want to remove this item from your cart?')) {
            removeCartItem(this.getAttribute('data-id'));
          }
        });
      });
    }

    // Function to update cart item quantity
    function updateCartItem(cartId, quantity) {
      // Send AJAX request to update cart
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'update_cart.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        if (xhr.status === 200) {
          // Refresh the page to update cart display
          location.reload();
        }
      };
      xhr.send('cart_id=' + cartId + '&quantity=' + quantity);
    }

    // Function to remove item from cart
    function removeCartItem(cartId) {
      // Send AJAX request to remove from cart
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'remove_cart_item.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        if (xhr.status === 200) {
          location.reload();
        }
      };
      xhr.send('cart_id=' + cartId);
    }

    // Function to handle logout
    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
      }
    }
  </script>
</body>
</html>
<?php
// Close the database connection at the very end of the file
$conn->close();
?>