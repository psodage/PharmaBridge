<?php 
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../signin.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharma";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get company ID from session
$company_id = $_SESSION['user_id'];

// Query to get all orders with items from this company
// Query to get all orders with items from this company
$sql = "SELECT o.*, mu.name as medical_user_name, mu.email as medical_user_email
        FROM orders o
        JOIN medical_users mu ON o.user_id = mu.id
        WHERE o.id IN (
            SELECT DISTINCT oi.order_id 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE p.company_id = ?
        )
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();

// Pagination settings
$records_per_page = 10;
$total_records = $result->num_rows;
$total_pages = ceil($total_records / $records_per_page);

// Get current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get orders for current page
$sql_with_limit = $sql . " LIMIT ?, ?";
$stmt = $conn->prepare($sql_with_limit);
$stmt->bind_param("iii", $company_id, $offset, $records_per_page);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Orders - BridgeRx Supply Hub</title>
  <link rel="stylesheet" href="../css/home_company.css">
  <style>
    
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f0f2f5;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  position: fixed;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #3a7bd5;
  padding: 10px 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  width: 100%; /* Add this line to make it full width */
  top: 0; /* Add this to ensure it stays at the top */
  left: 0; /* Add this to ensure it starts from the left edge */
  z-index: 1000; /* Add this to ensure it stays above other elements */
  box-sizing: border-box; /* Add this to include padding in width calculation */
}

.logo {
  display: flex;
  align-items: center;
  color: white;
  font-size: 22px;
  font-weight: bold;
}

.logo-icon {
  margin-right: 10px;
  font-size: 26px;
}

.header-controls {
  display: flex;
  align-items: center;
}

.header-btn {
  background: none;
  border: none;
  color: white;
  margin-left: 15px;
  font-size: 20px;
  cursor: pointer;
  position: relative;
}

.account-info {
  display: flex;
  align-items: center;
  color: white;
  margin-left: 20px;
}

.account-icon {
  width: 38px;
  height: 38px;
  background-color: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: 15px;
  color: #3a7bd5;
  font-weight: bold;
}

.alert-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background-color: #e74c3c;
  color: white;
  border-radius: 50%;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.sidebar {
  position: fixed;
  top: 60px;
  left: 0;
  bottom: 0;
  width: 250px;
  background-color: #ffffff; /* Changed to white */
  color: #333333; /* Changed to dark gray/black */
  overflow-y: auto;
  transition: all 0.3s ease;
  z-index: 100;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05); /* Added subtle shadow for depth */
}

.sidebar-collapsed {
  width: 70px;
}

.menu {
  list-style-type: none;
  margin: 0;
  padding: 0;
}

.menu-item {
  position: relative;
  border-bottom: 1px solid #f0f0f0; /* Added subtle separator */
}

.menu-btn {
  background: none;
  border: none;
  color: #333333; /* Changed to dark gray/black */
  cursor: pointer;
  font-size: 15px;
  padding: 15px;
  text-align: left;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  width: 100%;
}

.menu-btn:hover {
  background-color: #f5f5f5; /* Light gray hover state */
  color: #3a7bd5; /* Teal color on hover to match header */
}

.menu-icon {
  margin-right: 15px;
  width: 20px;
  text-align: center;
  font-size: 18px;
  color: #3a7bd5; /* Teal color for icons to match header */
}

.menu-text {
  white-space: nowrap;
  overflow: hidden;
  font-weight: 500; /* Added medium weight for better readability */
}

.sidebar-collapsed .menu-text {
  display: none;
}

.dropdown-indicator {
  margin-left: auto;
  transition: transform 0.3s ease;
  color: #888888; /* Lighter color for the indicator */
}

.active {
  background-color: #e0f2f1; /* Very light teal background */
  color: #3a7bd5; /* Teal text color */
  border-left: 4px solid #3a7bd5; /* Added teal border indicator */
}

.active:hover {
  background-color: #e0f2f1; /* Keep consistent with active state */
}

.active .menu-icon {
  color: #3a7bd5; /* Ensure icon is teal in active state */
}

.dropdown {
  background-color: #f9f9f9; /* Slightly different white for dropdown */
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.3s ease;
}

.dropdown.show {
  max-height: 1000px;
}

.dropdown-item {
  color: #555555; /* Dark gray for dropdown items */
  display: block;
  padding: 12px 15px 12px 45px;
  text-decoration: none;
  transition: all 0.3s ease;
  font-size: 14px;
}

.dropdown-item:hover {
  background-color: #f0f0f0; /* Light gray hover */
  color: #3a7bd5; /* Teal text on hover */
}

.sidebar-collapsed .dropdown {
  position: absolute;
  left: 70px;
  top: 0;
  min-width: 200px;
  z-index: 1;
  max-height: none;
  display: none;
  border-radius: 0 4px 4px 0;
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.1); /* Added shadow for popout menu */
}

.sidebar-collapsed .menu-item:hover .dropdown {
  display: block;
}

.sidebar-collapsed .dropdown-item {
  padding: 12px 15px;
}

.content {
  margin-left: 250px;
  padding: 20px;
  transition: margin-left 0.3s ease;
  margin-top: 60px; /* Add this to prevent content from hiding under header */
}

.content-full {
  margin-left: 70px;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.content-title {
  color: #2c3e50;
  margin: 0;
  font-size: 24px;
}

.dashboard-stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background-color: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.stat-title {
  color: #7f8c8d;
  font-size: 16px;
  margin: 0;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.stat-users {
  background-color: #e6f7ff;
  color: #2980b9;
}

.stat-transactions {
  background-color: #e6fffb;
  color: #20b2aa;
}

.stat-alerts {
  background-color: #fff2e8;
  color: #e74c3c;
}

.stat-verifications {
  background-color: #fcf8e3;
  color: #f39c12;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #2c3e50;
  margin: 5px 0;
}

.stat-change {
  font-size: 14px;
}

.mobile-menu-toggle {
  display: none;
  background: none;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    width: 250px;
  }
  
  .sidebar.show {
    transform: translateX(0);
  }
  
  .content {
    margin-left: 0;
  }
  
  .mobile-menu-toggle {
    display: block;
  }
}
    /* Additional styles for order table */
    .content-container {
      margin-top:60px;
      padding: 20px;
      margin-left: 260px;
      transition: margin-left 0.3s;
    }
    
    .sidebar-collapsed + .content-container {
      margin-left: 70px;
    }
    
    .page-title {
      font-size: 24px;
      margin-bottom: 20px;
      color: #2a5298;
      border-bottom: 2px solid #eaeaea;
      padding-bottom: 10px;
    }
    
    .dashboard-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .order-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    
    .order-table th {
      background-color: #f7f9fc;
      color: #2a5298;
      text-align: left;
      padding: 12px 15px;
      border-bottom: 2px solid #eaeaea;
      font-weight: 600;
    }
    
    .order-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #eaeaea;
      color: #333;
    }
    
    .order-table tr:hover {
      background-color: #f7f9fc;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .status-pending {
      background-color: #ffeaa7;
      color: #b7791f;
    }
    
    .status-processing {
      background-color: #bee3f8;
      color: #2b6cb0;
    }
    
    .status-shipped {
      background-color: #c6f6d5;
      color: #2f855a;
    }
    
    .status-delivered {
      background-color: #9ae6b4;
      color: #276749;
    }
    
    .status-cancelled {
      background-color: #fed7d7;
      color: #c53030;
    }
    
    .btn {
      display: inline-block;
      background-color: #4a6bef;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      text-decoration: none;
      transition: background-color 0.3s;
    }
    
    .btn:hover {
      background-color: #3755d9;
    }
    
    .btn-view {
      background-color: #4a6bef;
    }
    
    .btn-primary {
      background-color: #2a5298;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 5px;
    }
    
    .pagination a {
      color: #4a6bef;
      padding: 8px 12px;
      text-decoration: none;
      border: 1px solid #dee2e6;
      border-radius: 4px;
    }
    
    .pagination a.active {
      background-color: #4a6bef;
      color: white;
      border: 1px solid #4a6bef;
    }
    
    .pagination a:hover:not(.active) {
      background-color: #f0f2ff;
    }
    
    .filter-section {
      margin-bottom: 15px;
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }
    
    .filter-section label {
      font-weight: 500;
      margin-right: 5px;
    }
    
    .filter-section select {
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: white;
    }
    
    .search-box {
      display: flex;
      gap: 5px;
      margin-left: auto;
    }
    
    .search-box input {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      width: 200px;
    }
    
    .order-details {
      display: none;
      background-color: #f9fafc;
      border: 1px solid #eaeaea;
      border-radius: 4px;
      margin: 10px 0;
      padding: 15px;
    }
    
    .order-items {
      margin-top: 10px;
    }
    
    .order-items h4 {
      margin-bottom: 10px;
      color: #2a5298;
    }
    
    .item-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .item-table th {
      background-color: #eef2f7;
      padding: 8px 10px;
      text-align: left;
      font-size: 13px;
    }
    
    .item-table td {
      padding: 8px 10px;
      border-bottom: 1px solid #eaeaea;
      font-size: 13px;
    }
    
    .no-orders {
      text-align: center;
      padding: 40px;
      color: #666;
      font-style: italic;
    }
    
    @media (max-width: 768px) {
      .content-container {
        margin-left: 0;
        padding: 15px;
      }
      
      .order-table {
        font-size: 14px;
      }
      
      .order-table th, .order-table td {
        padding: 8px 10px;
      }
      
      .filter-section {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .search-box {
        margin-left: 0;
        width: 100%;
      }
      
      .search-box input {
        width: 100%;
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
        <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'M'; ?></div>
      </div>
    </div>
  </div>
  
  <div class="sidebar" id="sidebar">
    <ul class="menu">
      <li class="menu-item">
        <button class="menu-btn" data-page="../company.php">
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
          <a href="#" class="dropdown-item" data-page="../products/add_products.php">Add New <br>Products</a>
          <a href="#" class="dropdown-item" data-page="../products/add_products.php">Manage <br>Products</a>
          <a href="#" class="dropdown-item" data-page="../products/view_products.php">View Product <br>Listings</a>
        </div>
      </li>
     
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🛒</span>
          <span class="menu-text">Orders</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown show">
          <a href="#" class="dropdown-item active" data-page="view_orders.php">View <br>Orders</a>
          <a href="#" class="dropdown-item" data-page="update_order_status.php">Update Order <br>Status</a>
       
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Inquiries</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../view_inquiries.php">View Inquiries</a>
          <a href="#" class="dropdown-item" data-page="../inquires.php">Respond to <br>Inquiries</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Settings</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../profile.php">Profile <br>Management</a>
       
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🔍</span>
          <span class="menu-text">Support</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../contact_admin.php">Contact Admin</a>
         
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
    <h1 class="page-title">Order Management</h1>
    
    <div class="dashboard-card">
      <div class="filter-section">
        <div>
          <label for="status-filter">Filter by Status:</label>
          <select id="status-filter" onchange="filterOrders()">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label for="date-filter">Filter by Date:</label>
          <select id="date-filter" onchange="filterOrders()">
            <option value="">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
          </select>
        </div>
        <div class="search-box">
          <input type="text" id="search-input" placeholder="Search orders..." onkeyup="filterOrders()">
          <button class="btn" onclick="filterOrders()">Search</button>
        </div>
      </div>
      
      <?php if ($result->num_rows > 0): ?>
      <table class="order-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Status</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
          <?php
            // Get order items for this order from this company
            $order_id = $row['id'];
            $items_sql = "SELECT oi.*, p.product_name, p.product_image, p.company_id
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.id
                         WHERE oi.order_id = ? AND p.company_id = ?";
            $items_stmt = $conn->prepare($items_sql);
            $items_stmt->bind_param("ii", $order_id, $company_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            // Calculate subtotal for company's items in this order
            $subtotal = 0.20;
            while($item = $items_result->fetch_assoc()) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            // Reset result pointer
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            // Format date
            $order_date = date("M d, Y", strtotime($row['order_date']));
            
            // Get status class
            $status_class = "";
            switch(strtolower($row['order_status'])) {
              case 'pending': 
                $status_class = "status-pending"; 
                break;
              case 'processing': 
                $status_class = "status-processing"; 
                break;
              case 'shipped': 
                $status_class = "status-shipped"; 
                break;
              case 'delivered': 
                $status_class = "status-delivered"; 
                break;
              case 'cancelled': 
                $status_class = "status-cancelled"; 
                break;
              default: 
                $status_class = "status-pending";
            }
          ?>
          <tr class="order-row" data-status="<?php echo strtolower($row['order_status']); ?>" data-date="<?php echo $row['order_date']; ?>">
            <td>#<?php echo $row['id']; ?></td>
            <td><?php echo $row['medical_user_name']; ?><br><small><?php echo $row['medical_user_email']; ?></small></td>
            <td><?php echo $order_date; ?></td>
            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $row['order_status']; ?></span></td>
            <td>$<?php echo number_format($subtotal, 2); ?></td>
            <td>
              <button class="btn btn-view" onclick="toggleOrderDetails(<?php echo $row['id']; ?>)">View Details</button>
              <a href="update_order_status.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Update Status</a>
            </td>
          </tr>
          <tr>
            <td colspan="6" class="order-details" id="order-details-<?php echo $row['id']; ?>">
              <div class="order-info">
                <p><strong>Order ID:</strong> #<?php echo $row['id']; ?></p>
                <p><strong>Date Placed:</strong> <?php echo $order_date; ?></p>
                <p><strong>Customer:</strong> <?php echo $row['medical_user_name']; ?> (<?php echo $row['medical_user_email']; ?>)</p>
                <p><strong>Status:</strong> <span class="status-badge <?php echo $status_class; ?>"><?php echo $row['order_status']; ?></span></p>
                <?php if (!empty($row['notes'])): ?>
                <p><strong>Notes:</strong> <?php echo $row['notes']; ?></p>
                <?php endif; ?>
              </div>
              
              <div class="order-items">
                <h4>Order Items (From Your Company)</h4>
                <?php if ($items_result->num_rows > 0): ?>
                <table class="item-table">
                  <thead>
                    <tr>
                      <th>Product</th>
                      <th>Quantity</th>
                      <th>Price</th>
                      <th>Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($item = $items_result->fetch_assoc()): ?>
                    <tr>
                      <td><?php echo $item['product_name']; ?></td>
                      <td><?php echo $item['quantity']; ?></td>
                      <td>$<?php echo number_format($item['price'], 2); ?></td>
                      <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="3" align="right"><strong>Total:</strong></td>
                      <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                    </tr>
                  </tfoot>
                </table>
                <?php else: ?>
                <p>No items from your company in this order.</p>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div class="pagination">
        <?php if($total_pages > 1): ?>
          <?php if($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>">&laquo; Previous</a>
          <?php endif; ?>
          
          <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" <?php echo $i == $current_page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
          <?php endfor; ?>
          
          <?php if($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>">Next &raquo;</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="no-orders">
        <p>No orders found. When customers place orders for your products, they will appear here.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Admin Dashboard
    
    // On page load, initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
      initializeEventListeners();
    });
    
    // Initialize all event listeners for the dashboard
    function initializeEventListeners() {
      // Toggle sidebar
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      const content = document.querySelector('.content-container');
      
      sidebarToggle.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
          sidebar.classList.toggle('show');
        } else {
          sidebar.classList.toggle('sidebar-collapsed');
          content.classList.toggle('sidebar-collapsed');
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
    
    // Toggle order details
    function toggleOrderDetails(orderId) {
      const detailsRow = document.getElementById('order-details-' + orderId);
      const allDetails = document.querySelectorAll('.order-details');
      
      // Close all other open details
      allDetails.forEach(function(details) {
        if (details.id !== 'order-details-' + orderId && details.style.display === 'block') {
          details.style.display = 'none';
        }
      });
      
      // Toggle current details
      if (detailsRow.style.display === 'block') {
        detailsRow.style.display = 'none';
      } else {
        detailsRow.style.display = 'block';
      }
    }
    
    // Filter orders
    function filterOrders() {
      const statusFilter = document.getElementById('status-filter').value.toLowerCase();
      const dateFilter = document.getElementById('date-filter').value;
      const searchInput = document.getElementById('search-input').value.toLowerCase();
      const rows = document.querySelectorAll('.order-row');
      
      rows.forEach(function(row) {
        const status = row.getAttribute('data-status').toLowerCase();
        const date = new Date(row.getAttribute('data-date'));
        const rowText = row.textContent.toLowerCase();
        let showRow = true;
        
        // Apply status filter
        if (statusFilter !== '' && status !== statusFilter) {
          showRow = false;
        }
        
        // Apply date filter
        if (dateFilter !== '' && showRow) {
          const today = new Date();
          const weekAgo = new Date();
          weekAgo.setDate(today.getDate() - 7);
          
          const monthAgo = new Date();
          monthAgo.setMonth(today.getMonth() - 1);
          
          const yearAgo = new Date();
          yearAgo.setFullYear(today.getFullYear() - 1);
          
          switch(dateFilter) {
            case 'today':
              if (date.toDateString() !== today.toDateString()) {
                showRow = false;
              }
              break;
            case 'week':
              if (date < weekAgo) {
                showRow = false;
              }
              break;
            case 'month':
              if (date < monthAgo) {
                showRow = false;
              }
              break;
            case 'year':
              if (date < yearAgo) {
                showRow = false;
              }
              break;
          }
        }
        
        // Apply search filter
        if (searchInput !== '' && showRow) {
          if (!rowText.includes(searchInput)) {
            showRow = false;
          }
        }
        
        // Show or hide row based on filters
        if (showRow) {
          row.style.display = '';
          // Also need to show or hide the details row
          const detailsRow = row.nextElementSibling;
          if (detailsRow) {
            detailsRow.style.display = '';
            // But keep the details container hidden unless it was explicitly shown
            const detailsContainer = detailsRow.querySelector('.order-details');
            if (detailsContainer) {
              // Don't change the display if it's already set
              if (detailsContainer.style.display !== 'block') {
                detailsContainer.style.display = 'none';
              }
            }
          }
        } else {
          row.style.display = 'none';
          // Hide the details row too
          const detailsRow = row.nextElementSibling;
          if (detailsRow) {
            detailsRow.style.display = 'none';
          }
        }
      });
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