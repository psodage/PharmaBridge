<?php 
session_start();
require_once('../api/db.php');
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location:../signin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Products - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">
<link rel="stylesheet" href="../css/add_product_medical.css">
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
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">💊</span>
          <span class="menu-text">Products</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item active" data-page="view_product.php">Search & Browse <br>Products</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📋</span>
          <span class="menu-text">Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="place_order.php">Place New Order</a>
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
  
  <div class="content" id="content">
    <div class="page-header">
      <div>
        <h1 class="page-title">Search Products</h1>
        <p class="page-subtitle">View and Search your pharmaceutical products.</p>
      </div>
      
      <div class="cart-icon" id="cartIconButton">
        <span style="font-size: 24px;">🛒</span>
        <?php
        // Get cart count from database or session
        if (isset($_SESSION['user_id'])) {
          $user_id = $_SESSION['user_id'];
          $cart_query = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = $user_id";
          $cart_result = mysqli_query($conn, $cart_query);
          $cart_row = mysqli_fetch_assoc($cart_result);
          $cart_count = $cart_row['cart_count'] ? $cart_row['cart_count'] : 0;
        } else {
          $cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
        }
        
        if ($cart_count > 0) {
          echo '<span class="cart-count">' . $cart_count . '</span>';
        }
        ?>
      </div>
    </div>
    
    <div class="search-container">
      <h2 class="search-title">Search and Filter Products</h2>
      <form method="GET" action="" class="search-form">
        <input type="text" name="search" class="search-input" placeholder="Search products by name, generic name or description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        
        <select name="category" class="search-select">
          <option value="">All Categories</option>
          <?php
          // Fetch distinct categories from the database
          $category_query = "SELECT DISTINCT category FROM products ORDER BY category";
          $category_result = mysqli_query($conn, $category_query);
          
          while ($category = mysqli_fetch_assoc($category_result)) {
            $selected = (isset($_GET['category']) && $_GET['category'] == $category['category']) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($category['category']) . '" ' . $selected . '>' . htmlspecialchars($category['category']) . '</option>';
          }
          ?>
        </select>
        
        <select name="stock" class="search-select">
          <option value="">All Stock Levels</option>
          <option value="high" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'high') ? 'selected' : ''; ?>>High Stock (50+)</option>
          <option value="medium" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'medium') ? 'selected' : ''; ?>>Medium Stock (10-49)</option>
          <option value="low" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'low') ? 'selected' : ''; ?>>Low Stock (1-9)</option>
          <option value="out" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'out') ? 'selected' : ''; ?>>Out of Stock</option>
        </select>
        
        <div class="search-buttons">
          <button type="submit" class="search-button apply-button">Apply Filters</button>
          <a href="view_product.php" class="search-button reset-button" style="text-decoration: none; text-align: center;">Reset</a>
        </div>
      </form>
    </div>
    
    <div class="product-container">
      <?php
      // Build the query based on search parameters
      $query = "SELECT * FROM products WHERE 1=1";
      
      // Add search conditions
      if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $query .= " AND (product_name LIKE '%$search%' OR generic_name LIKE '%$search%' OR description LIKE '%$search%')";
      }
      
      // Add category filter
      if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category = mysqli_real_escape_string($conn, $_GET['category']);
        $query .= " AND category = '$category'";
      }
      
      // Add stock level filter
      if (isset($_GET['stock']) && !empty($_GET['stock'])) {
        switch ($_GET['stock']) {
          case 'high':
            $query .= " AND stock_quantity >= 50";
            break;
          case 'medium':
            $query .= " AND stock_quantity BETWEEN 10 AND 49";
            break;
          case 'low':
            $query .= " AND stock_quantity BETWEEN 1 AND 9";
            break;
          case 'out':
            $query .= " AND stock_quantity = 0";
            break;
        }
      }
      
      // Order by expiration date (soonest first) and then by name
      $query .= " ORDER BY expiry_date ASC, product_name ASC";
      
      // Execute the query
      $result = mysqli_query($conn, $query);
      
      // Display products or show a message if none found
      if (mysqli_num_rows($result) > 0) {
        $today = date('Y-m-d');
        $warning_date = date('Y-m-d', strtotime('+3 months'));
        
        while ($product = mysqli_fetch_assoc($result)) {
          // Calculate if expiry date is approaching
          $expiry_warning = false;
          $expiry_soon = false;
          if (!empty($product['expiry_date'])) {
            $expiry_date = $product['expiry_date'];
            if ($expiry_date <= $warning_date) {
              $expiry_warning = true;
              if ($expiry_date <= date('Y-m-d', strtotime('+1 month'))) {
                $expiry_soon = true;
              }
            }
          }
          
          // Format price with currency symbol (₹)
          $price = '₹' . number_format($product['price'], 2);
          
          // Default image if none provided
          $image = !empty($product['product_image']) ? '../uploads/products/' . $product['product_image'] : '../assets/images/product-placeholder.jpg';
          
          // Display the product card
          echo '<div class="product-card">';
          echo '<div class="product-image"><img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($product['product_name']) . '"></div>';
          echo '<div class="product-title">' . htmlspecialchars($product['product_name']) . '</div>';
          echo '<div class="product-generic">' . htmlspecialchars($product['generic_name']) . ' - ' . htmlspecialchars($product['category']) . '</div>';
          echo '<div class="product-price">' . $price . '</div>';
          
          // Display stock status with appropriate color
          if ($product['stock_quantity'] > 0) {
            echo '<div class="product-stock">In Stock: ' . $product['stock_quantity'] . '</div>';
          } else {
            echo '<div class="product-stock" style="background-color: #ffebee; color: #d32f2f;">Out of Stock</div>';
          }
          
          // Display expiry date with warning if applicable
          if (!empty($product['expiry_date'])) {
            $expiry_class = $expiry_soon ? 'product-expiry soon' : ($expiry_warning ? 'product-expiry' : 'product-expiry');
            echo '<div class="' . $expiry_class . '">Expiring: ' . date('Y-m-d', strtotime($product['expiry_date'])) . '</div>';
          }
          
          // Add to cart form with AJAX submission
          echo '<div class="add-cart-container">';
          echo '<input type="number" id="qty-' . $product['id'] . '" class="quantity-input" value="1" min="1" max="' . $product['stock_quantity'] . '" ' . ($product['stock_quantity'] <= 0 ? 'disabled' : '') . '>';
          echo '<button onclick="addToCart(' . $product['id'] . ')" class="add-to-cart" ' . ($product['stock_quantity'] <= 0 ? 'disabled' : '') . '>Add to Cart</button>';
          echo '</div>';
          
          echo '</div>'; // End product-card
        }
      } else {
        echo '<div class="no-products">';
        echo '<h3>No products found</h3>';
        echo '<p>Try adjusting your search criteria or browse all products.</p>';
        echo '</div>';
      }
      ?>
    </div>
  </div>
  
  <!-- Cart Modal -->
  <div id="cartModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Your Cart</h2>
        <span class="close-modal">&times;</span>
      </div>
      <div id="cartContent">
        <!-- Cart items will be loaded here via AJAX -->
        <div class="loading-cart" style="text-align: center; padding: 20px;">
          Loading your cart...
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Admin Dashboard
    
    // On page load, initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
      initializeEventListeners();
      
      // Setup cart modal
      const cartModal = document.getElementById('cartModal');
      const cartIconButton = document.getElementById('cartIconButton');
      const closeModal = document.querySelector('.close-modal');
      
      cartIconButton.addEventListener('click', function() {
        loadCartItems();
        cartModal.style.display = 'block';
      });
      
      closeModal.addEventListener('click', function() {
        cartModal.style.display = 'none';
      });
      
      window.addEventListener('click', function(event) {
        if (event.target == cartModal) {
          cartModal.style.display = 'none';
        }
      });
    });
    
    // Load cart items via AJAX
    function loadCartItems() {
      fetch('get_cart_items.php')
        .then(response => response.text())
        .then(data => {
          document.getElementById('cartContent').innerHTML = data;
          
          // Attach event listeners to the new buttons
          attachCartEventListeners();
        })
        .catch(error => {
          console.error('Error loading cart:', error);
          document.getElementById('cartContent').innerHTML = '<p class="empty-cart">Error loading cart. Please try again.</p>';
        });
    }
    
    // Attach event listeners to cart buttons
    function attachCartEventListeners() {
      // Update quantity buttons
      const updateButtons = document.querySelectorAll('.update-quantity');
      updateButtons.forEach(button => {
        button.addEventListener('click', function() {
          const cartId = this.getAttribute('data-id');
          const quantity = document.getElementById('cart-qty-' + cartId).value;
          updateCartQuantity(cartId, quantity);
        });
      });
      
      // Remove item buttons
      const removeButtons = document.querySelectorAll('.cart-item-remove');
      removeButtons.forEach(button => {
        button.addEventListener('click', function() {
          const cartId = this.getAttribute('data-id');
          removeCartItem(cartId);
        });
      });
    }
    
    // Add to cart via AJAX
    function addToCart(productId) {
      const quantity = document.getElementById('qty-' + productId).value;
      
      // Create form data
      const formData = new FormData();
      formData.append('product_id', productId);
      formData.append('quantity', quantity);
      formData.append('ajax', true);
      
      // Send AJAX request
      fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update cart count
          updateCartCount(data.cart_count);
          
          // Show success message
          alert('Product added to cart successfully!');
        } else {
          alert(data.message || 'Error adding product to cart.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    }
    
    // Update cart quantity via AJAX
    function updateCartQuantity(cartId, quantity) {
      const formData = new FormData();
      formData.append('cart_id', cartId);
      formData.append('quantity', quantity);
      
      fetch('update_cart.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload cart items
          loadCartItems();
          
          // Update cart count
          updateCartCount(data.cart_count);
        } else {
          alert(data.message || 'Error updating cart.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    }
    
// Remove cart item via AJAX
function removeCartItem(cartId) {
      if (confirm('Are you sure you want to remove this item from your cart?')) {
        const formData = new FormData();
        formData.append('cart_id', cartId);
        
        fetch('remove_cart_item.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Reload cart items
            loadCartItems();
            
            // Update cart count
            updateCartCount(data.cart_count);
          } else {
            alert(data.message || 'Error removing item from cart.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred. Please try again.');
        });
      }
    }
    
    // Update cart count in UI
    function updateCartCount(count) {
      const cartIcon = document.getElementById('cartIconButton');
      
      // Remove existing cart count element if it exists
      const existingCount = cartIcon.querySelector('.cart-count');
      if (existingCount) {
        existingCount.remove();
      }
      
      // Add new cart count element if count is greater than 0
      if (count > 0) {
        const countElement = document.createElement('span');
        countElement.className = 'cart-count';
        countElement.textContent = count;
        cartIcon.appendChild(countElement);
      }
    }
    
      // Main JavaScript for Admin Dashboard
    
    // On page load, initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
      // No need to load default content since we're navigating directly to pages
      // Just initialize event listeners
      initializeEventListeners();
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
      });
      
      // Handle window resize
      window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('sidebar-collapsed');
          content.classList.remove('content-full');
          sidebar.classList.remove('show');
        }
      });
    }
    
    // Toggle dropdown menus
    function toggleDropdown(button) {
      const dropdown = button.nextElementSibling;
      const arrow = button.querySelector('.dropdown-indicator');
      const sidebar = document.getElementById('sidebar');
      
      if (sidebar.classList.contains('sidebar-collapsed') && window.innerWidth > 768) {
        return; // Don't toggle dropdowns in collapsed mode on desktop
      }
      
      const dropdowns = document.querySelectorAll('.dropdown');
      dropdowns.forEach(function(item) {
        if (item !== dropdown && item.classList.contains('show')) {
          item.classList.remove('show');
          item.previousElementSibling.querySelector('.dropdown-indicator').style.transform = 'rotate(0deg)';
        }
      });
      
      dropdown.classList.toggle('show');
      if (dropdown.classList.contains('show')) {
        arrow.style.transform = 'rotate(180deg)';
      } else {
        arrow.style.transform = 'rotate(0deg)';
      }
    }
    
    // Logout function
    function logout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = "../logout.php";
      }
    }
  </script>
</body>
</html>