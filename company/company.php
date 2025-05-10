<?php 
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../signin.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "pharma";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get company ID from session
$company_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get dashboard statistics for the company
// Count total products
$products_query = "SELECT 
    COUNT(*) AS total_products,
    SUM(CASE WHEN stock_quantity <= 10 THEN 1 ELSE 0 END) AS low_stock_products,
    SUM(CASE WHEN DATEDIFF(expiry_date, CURDATE()) <= 90 THEN 1 ELSE 0 END) AS expiring_soon
    FROM products WHERE company_id = $company_id";
$products_result = $conn->query($products_query);
$products_data = $products_result->fetch_assoc();
$total_products = $products_data['total_products'] ?? 0;
$low_stock_products = $products_data['low_stock_products'] ?? 0;
$expiring_soon = $products_data['expiring_soon'] ?? 0;

// Get orders data
$orders_query = "SELECT 
    COUNT(DISTINCT o.id) AS total_orders,
    SUM(CASE WHEN o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS orders_this_week,
    SUM(CASE WHEN o.order_status = 'Pending' THEN 1 ELSE 0 END) AS pending_orders,
    SUM(CASE WHEN o.order_status = 'Shipped' THEN 1 ELSE 0 END) AS shipped_orders,
    SUM(CASE WHEN o.order_status = 'Delivered' THEN 1 ELSE 0 END) AS delivered_orders,
    SUM(CASE WHEN o.order_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_orders
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.company_id = $company_id";
$orders_result = $conn->query($orders_query);
$orders_data = $orders_result->fetch_assoc();
$total_orders = $orders_data['total_orders'] ?? 0;
$orders_this_week = $orders_data['orders_this_week'] ?? 0;
$pending_orders = $orders_data['pending_orders'] ?? 0;
$shipped_orders = $orders_data['shipped_orders'] ?? 0;

// Calculate total revenue
$revenue_query = "SELECT 
    SUM(oi.quantity * oi.price) AS total_revenue,
    SUM(CASE WHEN o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN oi.quantity * oi.price ELSE 0 END) AS monthly_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.company_id = $company_id AND o.order_status != 'Cancelled'";
$revenue_result = $conn->query($revenue_query);
$revenue_data = $revenue_result->fetch_assoc();
$total_revenue = $revenue_data['total_revenue'] ?? 0;
$monthly_revenue = $revenue_data['monthly_revenue'] ?? 0;

// Get recent orders (limit to 5)
$recent_orders_query = "SELECT 
    o.id, o.order_date, o.order_status, o.total, mu.name as medical_store
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN medical_users mu ON o.user_id = mu.id
    WHERE p.company_id = $company_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_query);

// Get top selling products (limit to 5)
$top_products_query = "SELECT 
    p.id, p.product_name, p.price, p.stock_quantity,
    SUM(oi.quantity) as total_sold
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE p.company_id = $company_id AND o.order_status != 'Cancelled'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5";
$top_products_result = $conn->query($top_products_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Dashboard</title>
  <link rel="stylesheet" href="../css/home_company.css">
  <style>
    /* Additional CSS for the dashboard content */
    .content {
      padding: 20px;
      margin-left: 240px;
      transition: margin-left 0.3s;
    }
    
    .sidebar-collapsed + .content {
      margin-left: 60px;
    }
    
    .content-header {
      margin-bottom: 20px;
    }
    
    .content-title {
      margin: 0;
      font-size: 24px;
      color: #333;
    }
    
    .dashboard-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
    }
    
    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .stat-title {
      margin: 0;
      font-size: 16px;
      color: #555;
    }
    
    .stat-icon {
      font-size: 24px;
      padding: 10px;
      border-radius: 50%;
    }
    
    .stat-users {
      background-color: rgba(52, 152, 219, 0.2);
      color: #3498db;
    }
    
    .stat-products {
      background-color: rgba(46, 204, 113, 0.2);
      color: #2ecc71;
    }
    
    .stat-orders {
      background-color: rgba(155, 89, 182, 0.2);
      color: #9b59b6;
    }
    
    .stat-revenue {
      background-color: rgba(241, 196, 15, 0.2);
      color: #f1c40f;
    }
    
    .stat-value {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .stat-change {
      font-size: 14px;
    }
    
    .dashboard-sections {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .dashboard-section {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }
    
    .section-title {
      margin: 0;
      font-size: 18px;
      color: #333;
    }
    
    .section-link {
      color: #3498db;
      text-decoration: none;
      font-size: 14px;
    }
    
    .section-link:hover {
      text-decoration: underline;
    }
    
    .order-list {
      border-collapse: collapse;
      width: 100%;
    }
    
    .order-list th, .order-list td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    
    .order-list th {
      background-color: #f9f9f9;
      font-weight: 600;
    }
    
    .order-status {
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .status-pending {
      background-color: #FFEAA7;
      color: #D68910;
    }
    
    .status-shipped {
      background-color: #D6EAF8;
      color: #2874A6;
    }
    
    .status-delivered {
      background-color: #D5F5E3;
      color: #196F3D;
    }
    
    .status-cancelled {
      background-color: #FADBD8;
      color: #943126;
    }
    
    .product-list {
      width: 100%;
    }
    
    .product-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }
    
    .product-details {
      flex: 1;
    }
    
    .product-name {
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .product-stats {
      display: flex;
      font-size: 14px;
      color: #777;
    }
    
    .product-stats div {
      margin-right: 15px;
    }
    
    .product-sold {
      font-weight: 600;
      color: #2ecc71;
    }
    
    .warning-indicator {
      color: #e74c3c;
    }
    
    /* Responsive adjustment */
    @media (max-width: 768px) {
      .content {
        margin-left: 0;
        padding: 15px;
      }
      
      .dashboard-sections {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="logo-icon">💊</span> BridgeRx Supply Hub
    </div>
    <div class="header-controls">
      <button class="header-btn" id="sidebarToggle" title="Toggle Sidebar">☰</button>
      
      <div class="account-info">
        <span>Welcome, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest'; ?></span>
        <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'G'; ?></div>
      </div>
    </div>
  </div>
  
  <div class="sidebar" id="sidebar">
    <ul class="menu">
      <li class="menu-item">
        <button class="menu-btn active" data-page="company.php">
          <span class="menu-icon">🏠</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📦</span>
          <span class="menu-text">Products</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="products/add_products.php">Add New <br>Products</a>
          <a href="#" class="dropdown-item" data-page="products/manage_products.php">Manage <br>Products</a>
          <a href="#" class="dropdown-item" data-page="products/view_products.php">View Product <br>Listings</a>
        </div>
      </li>
     
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🛒</span>
          <span class="menu-text">Orders</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="order/view_orders.php">View <br>Orders</a>
          <a href="#" class="dropdown-item" data-page="order/update_order_status.php">Update Order <br>Status</a>
        
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Inquiries</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="view_inquiries.php">View Inquiries</a>
          <a href="#" class="dropdown-item" data-page="inquires.php">Respond to <br>Inquiries</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Settings</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="profile.php">Profile <br>Management</a>
        
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🔍</span>
          <span class="menu-text">Support</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="contact_admin.php">Contact Admin</a>
         
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
        <h1 class="content-title">Company Dashboard</h1>
      </div>
      
      <div class="dashboard-stats">
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Products</h3>
            <div class="stat-icon stat-products">📦</div>
          </div>
          <div class="stat-value"><?php echo number_format($total_products); ?></div>
          <div class="stat-change" style="color: <?php echo $low_stock_products > 0 ? '#e67e22' : '#27ae60'; ?>;">
            <?php echo $low_stock_products > 0 ? "$low_stock_products low stock" : "Stock levels good"; ?>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Orders</h3>
            <div class="stat-icon stat-orders">🛒</div>
          </div>
          <div class="stat-value"><?php echo number_format($total_orders); ?></div>
          <div class="stat-change" style="color: #27ae60;">+<?php echo $orders_this_week; ?> this week</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Revenue</h3>
            <div class="stat-icon stat-revenue">💰</div>
          </div>
          <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
          <div class="stat-change" style="color: #27ae60;">₹<?php echo number_format($monthly_revenue, 2); ?> this month</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Alerts</h3>
            <div class="stat-icon stat-alerts">⚠️</div>
          </div>
          <div class="stat-value"><?php echo $pending_orders; ?></div>
          <div class="stat-change" style="color: <?php echo $expiring_soon > 0 ? '#e74c3c' : '#27ae60'; ?>;">
            <?php echo $expiring_soon > 0 ? "$expiring_soon products expiring soon" : "No critical alerts"; ?>
          </div>
        </div>
      </div>
      
      <div class="dashboard-sections">
        <div class="dashboard-section">
          <div class="section-header">
            <h2 class="section-title">Recent Orders</h2>
            <a href="order/view_orders.php" class="section-link">View All</a>
          </div>
          
          <table class="order-list">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Medical Store</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
                <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                  <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                    <td><?php echo $order['medical_store']; ?></td>
                    <td>₹<?php echo number_format($order['total'], 2); ?></td>
                    <td>
                      <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                        <?php echo $order['order_status']; ?>
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="text-align: center;">No recent orders found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <div class="dashboard-section">
          <div class="section-header">
            <h2 class="section-title">Top Selling Products</h2>
            <a href="products/view_products.php" class="section-link">View All Products</a>
          </div>
          
          <div class="product-list">
            <?php if ($top_products_result && $top_products_result->num_rows > 0): ?>
              <?php while ($product = $top_products_result->fetch_assoc()): ?>
                <div class="product-item">
                  <div class="product-details">
                    <div class="product-name"><?php echo $product['product_name']; ?></div>
                    <div class="product-stats">
                      <div>Price: ₹<?php echo number_format($product['price'], 2); ?></div>
                      <div>Stock: 
                        <?php if ($product['stock_quantity'] <= 10): ?>
                          <span class="warning-indicator"><?php echo $product['stock_quantity']; ?></span>
                        <?php else: ?>
                          <?php echo $product['stock_quantity']; ?>
                        <?php endif; ?>
                      </div>
                      <div class="product-sold">Sold: <?php echo $product['total_sold']; ?></div>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <div class="product-item">
                <div class="product-details">No product sales data available</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <div class="dashboard-sections">
        <div class="dashboard-section">
          <div class="section-header">
            <h2 class="section-title">Order Status Overview</h2>
          </div>
          <div style="display: flex; justify-content: space-around; text-align: center; padding: 20px 0;">
            <div>
              <div style="font-size: 36px; font-weight: bold; color: #D68910;"><?php echo $pending_orders; ?></div>
              <div style="color: #777;">Pending</div>
            </div>
            <div>
              <div style="font-size: 36px; font-weight: bold; color: #2874A6;"><?php echo $shipped_orders; ?></div>
              <div style="color: #777;">Shipped</div>
            </div>
            <div>
              <div style="font-size: 36px; font-weight: bold; color: #196F3D;"><?php echo $orders_data['delivered_orders'] ?? 0; ?></div>
              <div style="color: #777;">Delivered</div>
            </div>
            <div>
              <div style="font-size: 36px; font-weight: bold; color: #943126;"><?php echo $orders_data['cancelled_orders'] ?? 0; ?></div>
              <div style="color: #777;">Cancelled</div>
            </div>
          </div>
        </div>
        
        <div class="dashboard-section">
          <div class="section-header">
            <h2 class="section-title">Quick Actions</h2>
          </div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; padding: 10px;">
            <a href="products/add_products.php" style="text-decoration: none;">
              <div style="background-color: #D6EAF8; padding: 15px; border-radius: 8px; text-align: center; color: #2874A6;">
                <div style="font-size: 24px; margin-bottom: 10px;">📦</div>
                <div style="font-weight: 600;">Add New Product</div>
              </div>
            </a>
            <a href="order/update_order_status.php" style="text-decoration: none;">
              <div style="background-color: #D5F5E3; padding: 15px; border-radius: 8px; text-align: center; color: #196F3D;">
                <div style="font-size: 24px; margin-bottom: 10px;">🚚</div>
                <div style="font-weight: 600;">Update Order Status</div>
              </div>
            </a>
            <a href="products/manage_products.php" style="text-decoration: none;">
              <div style="background-color: #FDEBD0; padding: 15px; border-radius: 8px; text-align: center; color: #B9770E;">
                <div style="font-size: 24px; margin-bottom: 10px;">🔄</div>
                <div style="font-weight: 600;">Update Inventory</div>
              </div>
            </a>
            <a href="view_inquires.php" style="text-decoration: none;">
              <div style="background-color: #E8DAEF; padding: 15px; border-radius: 8px; text-align: center; color: #7D3C98;">
                <div style="font-size: 24px; margin-bottom: 10px;">💬</div>
                <div style="font-weight: 600;">View Inquiries</div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Company Dashboard
    
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