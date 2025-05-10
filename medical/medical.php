<?php 
session_start();
require_once('../api/db.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../../signin.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM medical_users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get total orders for this medical store
$orders_query = "SELECT 
    COUNT(*) AS total_orders,
    SUM(CASE WHEN order_status = 'Pending' THEN 1 ELSE 0 END) AS pending_orders,
    SUM(CASE WHEN order_status = 'Shipped' THEN 1 ELSE 0 END) AS shipped_orders,
    SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) AS delivered_orders,
    SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
    SUM(CASE WHEN order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS orders_this_week,
    SUM(total) AS total_spending
    FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders_data = $orders_result->fetch_assoc();

// Get cart info
$cart_query = "SELECT COUNT(*) AS cart_items FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_data = $cart_result->fetch_assoc();

// Get latest products added to the system
$latest_products_query = "SELECT p.*, c.name as company_name 
                        FROM products p 
                        JOIN company_users c ON p.company_id = c.id 
                        ORDER BY p.created_at DESC LIMIT 3";
$latest_products_result = $conn->query($latest_products_query);

// Get recent orders
$recent_orders_query = "SELECT o.*, 
                        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
                        FROM orders o 
                        WHERE o.user_id = ? 
                        ORDER BY o.created_at DESC LIMIT 3";
$stmt = $conn->prepare($recent_orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders_result = $stmt->get_result();

// Get upcoming expiry products (products that will expire in the next 30 days)
$expiry_alert_query = "SELECT p.*, c.name as company_name 
                      FROM products p 
                      JOIN company_users c ON p.company_id = c.id 
                      WHERE p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                      ORDER BY p.expiry_date ASC LIMIT 3";
$expiry_alert_result = $conn->query($expiry_alert_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical Dashboard - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">
  <style>
    /* Dashboard styles */
    .dashboard-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    
    .stat-title {
      font-size: 1rem;
      color: #555;
      margin: 0;
    }
    
    .stat-icon {
      font-size: 24px;
      padding: 10px;
      border-radius: 50%;
    }
    
    .stat-orders { background-color: #e6f7ff; }
    .stat-cart { background-color: #fff7e6; }
    .stat-spending { background-color: #f6ffed; }
    .stat-alerts { background-color: #fff1f0; }
    
    .stat-value {
      font-size: 1.8rem;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .stat-change {
      font-size: 0.9rem;
    }
    
    .content-section {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .section-title {
      margin-top: 0;
      margin-bottom: 20px;
      color: #333;
      font-size: 1.4rem;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }
    
    .dual-column {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
    }
    
    /* Cards for products and orders */
    .card-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }
    
    .card-item {
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 15px;
      transition: box-shadow 0.3s ease;
    }
    
    .card-item:hover {
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    
    .card-title {
      font-size: 1.1rem;
      font-weight: bold;
      margin: 0;
    }
    
    .card-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
    }
    
    .badge-pending { background-color: #fff7e6; color: #d48806; }
    .badge-shipped { background-color: #e6f7ff; color: #1890ff; }
    .badge-delivered { background-color: #f6ffed; color: #52c41a; }
    .badge-cancelled { background-color: #fff1f0; color: #f5222d; }
    .badge-expiring { background-color: #fff1f0; color: #f5222d; }
    .badge-new { background-color: #f6ffed; color: #52c41a; }
    
    .card-content {
      margin-bottom: 15px;
    }
    
    .card-detail {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
      font-size: 0.95rem;
    }
    
    .card-label {
      color: #888;
    }
    
    .card-value {
      font-weight: 500;
    }
    
    .card-footer {
      display: flex;
      justify-content: flex-end;
    }
    
    .card-btn {
      padding: 8px 15px;
      background-color: #1890ff;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.3s;
    }
    
    .card-btn:hover {
      background-color: #40a9ff;
    }
    
    /* Alert section */
    .alert-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .alert-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      border-radius: 8px;
      background-color: #fff7e6;
      border-left: 4px solid #faad14;
    }
    
    .alert-icon {
      font-size: 24px;
    }
    
    .alert-content {
      flex: 1;
    }
    
    .alert-title {
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .alert-message {
      font-size: 0.9rem;
      color: #555;
    }
    
    .alert-action {
      padding: 6px 12px;
      background-color: #faad14;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: background-color 0.3s;
    }
    
    .alert-action:hover {
      background-color: #ffc53d;
    }
    
    .content {
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s ease;
    }
    
    .content-full {
      margin-left: 70px;
    }
    
    @media (max-width: 768px) {
      .content {
        margin-left: 0;
        padding: 15px;
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
        <button class="menu-btn active" data-page="medical.php">
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
    <div id="content-container">
      <div class="content-header">
        <h1 class="content-title">Medical Store Dashboard</h1>
       
      </div>
      
      <!-- Dashboard Stats -->
      <div class="dashboard-stats">
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Orders</h3>
            <div class="stat-icon stat-orders">📦</div>
          </div>
          <div class="stat-value"><?php echo number_format($orders_data['total_orders']); ?></div>
          <div class="stat-change" style="color: #27ae60;">+<?php echo $orders_data['orders_this_week']; ?> this week</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Cart Items</h3>
            <div class="stat-icon stat-cart">🛒</div>
          </div>
          <div class="stat-value"><?php echo number_format($cart_data['cart_items']); ?></div>
          <div class="stat-change" style="color: #e67e22;">
            <a href="place_order.php" style="text-decoration: none; color: inherit;">View Cart</a>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Total Spending</h3>
            <div class="stat-icon stat-spending">💵</div>
          </div>
          <div class="stat-value">$<?php echo number_format($orders_data['total_spending'], 2); ?></div>
          <div class="stat-change" style="color: #27ae60;">View analytics</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Alerts</h3>
            <div class="stat-icon stat-alerts">⚠️</div>
          </div>
          <div class="stat-value"><?php echo $expiry_alert_result->num_rows; ?></div>
          <div class="stat-change" style="color: #e74c3c;">Expiring medications</div>
        </div>
      </div>
      
      <!-- Order Status Overview -->
      <div class="content-section">
        <h2 class="section-title">Order Status Overview</h2>
        <div class="dashboard-stats">
          <div class="stat-card">
            <div class="stat-header">
              <h3 class="stat-title">Pending</h3>
              <div class="stat-icon" style="background-color: #fff7e6; color: #d48806;">⏳</div>
            </div>
            <div class="stat-value"><?php echo $orders_data['pending_orders']; ?></div>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <h3 class="stat-title">Shipped</h3>
              <div class="stat-icon" style="background-color: #e6f7ff; color: #1890ff;">🚚</div>
            </div>
            <div class="stat-value"><?php echo $orders_data['shipped_orders']; ?></div>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <h3 class="stat-title">Delivered</h3>
              <div class="stat-icon" style="background-color: #f6ffed; color: #52c41a;">✅</div>
            </div>
            <div class="stat-value"><?php echo $orders_data['delivered_orders']; ?></div>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <h3 class="stat-title">Cancelled</h3>
              <div class="stat-icon" style="background-color: #fff1f0; color: #f5222d;">❌</div>
            </div>
            <div class="stat-value"><?php echo $orders_data['cancelled_orders']; ?></div>
          </div>
        </div>
      </div>
      
      <!-- Dual column layout for Recent Orders and New Products -->
      <div class="dual-column">
        <!-- Recent Orders -->
        <div class="content-section">
          <h2 class="section-title">Recent Orders</h2>
          
          <?php
          if ($recent_orders_result->num_rows > 0) {
            while ($order = $recent_orders_result->fetch_assoc()) {
              // Determine badge class based on order status
              $badge_class = "";
              switch ($order['order_status']) {
                case 'Pending':
                  $badge_class = "badge-pending";
                  break;
                case 'Shipped':
                  $badge_class = "badge-shipped";
                  break;
                case 'Delivered':
                  $badge_class = "badge-delivered";
                  break;
                case 'Cancelled':
                  $badge_class = "badge-cancelled";
                  break;
                default:
                  $badge_class = "badge-pending";
              }
          ?>
            <div class="card-item">
              <div class="card-header">
                <h3 class="card-title">Order #<?php echo $order['id']; ?></h3>
                <span class="card-badge <?php echo $badge_class; ?>"><?php echo $order['order_status']; ?></span>
              </div>
              <div class="card-content">
                <div class="card-detail">
                  <span class="card-label">Date:</span>
                  <span class="card-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="card-detail">
                  <span class="card-label">Items:</span>
                  <span class="card-value"><?php echo $order['item_count']; ?> products</span>
                </div>
                <div class="card-detail">
                  <span class="card-label">Total:</span>
                  <span class="card-value">$<?php echo number_format($order['total'], 2); ?></span>
                </div>
              </div>
              <div class="card-footer">
                <a href="track_order.php?id=<?php echo $order['id']; ?>"><button class="card-btn">Track Order</button></a>
              </div>
            </div>
          <?php
            }
          } else {
            echo "<p>No recent orders found.</p>";
          }
          ?>
          
          <div style="text-align: center; margin-top: 20px;">
            <a href="view_history.php"><button class="card-btn">View All Orders</button></a>
          </div>
        </div>
        
        <!-- Latest Products -->
        <div class="content-section">
          <h2 class="section-title">Latest Products</h2>
          
          <?php
          if ($latest_products_result->num_rows > 0) {
            while ($product = $latest_products_result->fetch_assoc()) {
              // Calculate days until expiry
              $today = new DateTime();
              $expiry = new DateTime($product['expiry_date']);
              $days_until_expiry = $today->diff($expiry)->days;
              
              // Determine if product is expiring soon (within 30 days)
              $expiring_soon = $days_until_expiry <= 30 && $expiry > $today;
              $badge_class = $expiring_soon ? "badge-expiring" : "badge-new";
              $badge_text = $expiring_soon ? "Expires soon" : "New";
          ?>
            <div class="card-item">
              <div class="card-header">
                <h3 class="card-title"><?php echo $product['product_name']; ?></h3>
                <span class="card-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
              </div>
              <div class="card-content">
                <div class="card-detail">
                  <span class="card-label">Manufacturer:</span>
                  <span class="card-value"><?php echo $product['manufacturer']; ?></span>
                </div>
                <div class="card-detail">
                  <span class="card-label">Supplier:</span>
                  <span class="card-value"><?php echo $product['company_name']; ?></span>
                </div>
                <div class="card-detail">
                  <span class="card-label">Price:</span>
                  <span class="card-value">$<?php echo number_format($product['price'], 2); ?></span>
                </div>
                <div class="card-detail">
                  <span class="card-label">Expiry:</span>
                  <span class="card-value"><?php echo date('M d, Y', strtotime($product['expiry_date'])); ?></span>
                </div>
              </div>
              <div class="card-footer">
                <a href="product_details.php?id=<?php echo $product['id']; ?>"><button class="card-btn">View Details</button></a>
              </div>
            </div>
          <?php
            }
          } else {
            echo "<p>No products found.</p>";
          }
          ?>
          
          <div style="text-align: center; margin-top: 20px;">
            <a href="view_product.php"><button class="card-btn">Browse All Products</button></a>
          </div>
        </div>
      </div>
      
      <!-- Alerts Section -->
      <?php if ($expiry_alert_result->num_rows > 0): ?>
      <div class="content-section">
        <h2 class="section-title">Alerts & Notifications</h2>
        <div class="alert-list">
          <?php while ($alert = $expiry_alert_result->fetch_assoc()): 
            $days_until_expiry = (strtotime($alert['expiry_date']) - time()) / (60 * 60 * 24);
            $days_until_expiry = floor($days_until_expiry);
          ?>
          <div class="alert-item">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
              <div class="alert-title"><?php echo $alert['product_name']; ?> - Expiring Soon</div>
              <div class="alert-message">This product will expire in <?php echo $days_until_expiry; ?> days (<?php echo date('M d, Y', strtotime($alert['expiry_date'])); ?>).</div>
            </div>
            <a href="product_details.php?id=<?php echo $alert['id']; ?>"><button class="alert-action">View</button></a>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
      <?php endif; ?>
      
    </div>
  </div>
  
  <script>
    // Main JavaScript for Medical Dashboard
    
    // On page load, initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize event listeners
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