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

// Message variables
$message = "";
$messageType = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $status_notes = $_POST['status_notes'];
    
    // Validate that this company has products in this order
    $check_sql = "SELECT COUNT(*) as count
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ? AND p.company_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $order_id, $company_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        // Update order status
        $update_sql = "UPDATE orders SET order_status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', 'Update ', NOW(), ': ', ?), updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $new_status, $status_notes, $order_id);
        
        if ($update_stmt->execute()) {
            $message = "Order #$order_id status updated successfully to $new_status!";
            $messageType = "success";
        } else {
            $message = "Error updating order status: " . $conn->error;
            $messageType = "error";
        }
    } else {
        $message = "Error: You don't have permission to update this order";
        $messageType = "error";
    }
}

// Get order details if ID is provided
$order = null;
$order_items = null;

if (isset($_GET['id'])) {
    $order_id = $_GET['id'];
    
    // Get order details
    $sql = "SELECT o.*, mu.name as medical_user_name, mu.email as medical_user_email
            FROM orders o
            JOIN medical_users mu ON o.user_id = mu.id
            WHERE o.id = ? AND o.id IN (
                SELECT DISTINCT oi.order_id 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE p.company_id = ?
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Get order items for this company
        $items_sql = "SELECT oi.*, p.product_name, p.product_image
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = ? AND p.company_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("ii", $order_id, $company_id);
        $items_stmt->execute();
        $order_items = $items_stmt->get_result();
    }
}

// Get all orders related to this company for dropdown
$all_orders_sql = "SELECT DISTINCT o.id, o.order_date, o.order_status
                  FROM orders o
                  JOIN order_items oi ON o.id = oi.order_id
                  JOIN products p ON oi.product_id = p.id
                  WHERE p.company_id = ?
                  ORDER BY o.created_at DESC";
$all_orders_stmt = $conn->prepare($all_orders_sql);
$all_orders_stmt->bind_param("i", $company_id);
$all_orders_stmt->execute();
$all_orders_result = $all_orders_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Order Status - BridgeRx Supply Hub</title>

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
    /* Additional styles for order status update */
    .content-container {
      margin-top:40px;
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
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }
    
    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      transition: border-color 0.3s;
    }
    
    .form-control:focus {
      border-color: #4a6bef;
      outline: none;
    }
    
    select.form-control {
      appearance: none;
      background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 16px;
      padding-right: 30px;
    }
    
    .btn {
      display: inline-block;
      background-color: #4a6bef;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      transition: background-color 0.3s;
    }
    
    .btn:hover {
      background-color: #3755d9;
    }
    
    .btn-block {
      display: block;
      width: 100%;
    }
    
    .btn-primary {
      background-color: #2a5298;
    }
    
    .btn-secondary {
      background-color: #6c757d;
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
    
    .order-info-box {
      background-color: #f7f9fc;
      border: 1px solid #e2e8f0;
      border-radius: 4px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .order-info-row {
      display: flex;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }
    
    .order-info-label {
      width: 150px;
      font-weight: 600;
      color: #4a5568;
    }
    
    .order-info-value {
      flex: 1;
      color: #2d3748;
    }
    
    .order-items-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    
    .order-items-table th {
      background-color: #f7f9fc;
      text-align: left;
      padding: 10px;
      border-bottom: 2px solid #e2e8f0;
      color: #4a5568;
      font-weight: 600;
    }
    
    .order-items-table td {
      padding: 10px;
      border-bottom: 1px solid #e2e8f0;
      color: #2d3748;
    }
    
    .order-items-table tr:last-child td {
      border-bottom: none;
    }
    
    .alert {
      padding: 12px 15px;
      border-radius: 4px;
      margin-bottom: 20px;
      animation: fadeIn 0.5s;
    }
    
    .alert-success {
      background-color: #c6f6d5;
      color: #276749;
      border: 1px solid #9ae6b4;
    }
    
    .alert-error {
      background-color: #fed7d7;
      color: #c53030;
      border: 1px solid #feb2b2;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .order-notes {
      white-space: pre-line;
      background-color: #f7fafc;
      padding: 10px;
      border-radius: 4px;
      border: 1px solid #e2e8f0;
      margin-top: 5px;
      max-height: 100px;
      overflow-y: auto;
      font-size: 13px;
    }
    
    .order-history-title {
      font-size: 16px;
      font-weight: 600;
      color: #2a5298;
      margin-top: 20px;
      margin-bottom: 10px;
    }
    
    .two-column {
      display: flex;
      gap: 20px;
    }
    
    .two-column > div {
      flex: 1;
    }
    
    @media (max-width: 768px) {
      .content-container {
        margin-left: 0;
        padding: 15px;
      }
      
      .two-column {
        flex-direction: column;
      }
      
      .order-info-label {
        width: 120px;
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
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">🛒</span>
          <span class="menu-text">Orders</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown show">
          <a href="#" class="dropdown-item" data-page="view_orders.php">View <br>Orders</a>
         
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
    <h1 class="page-title">Update Order Status</h1>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
      <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <div class="dashboard-card">
      <?php if ($order): ?>
        <?php
          // Get status class
          $status_class = "";
          switch(strtolower($order['order_status'])) {
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
          
          // Format date
          $order_date = date("F d, Y", strtotime($order['order_date']));
          
          // Calculate subtotal for company items
          $subtotal = 0;
          while($item = $order_items->fetch_assoc()) {
              $subtotal += $item['price'] * $item['quantity'];
          }
          
          // Reset result pointer
          $order_items->data_seek(0);
        ?>
        
        <div class="two-column">
          <div>
            <h3>Order Information</h3>
            <div class="order-info-box">
              <div class="order-info-row">
                <div class="order-info-label">Order ID:</div>
                <div class="order-info-value">#<?php echo $order['id']; ?></div>
              </div>
              <div class="order-info-row">
                <div class="order-info-label">Date Placed:</div>
                <div class="order-info-value"><?php echo $order_date; ?></div>
              </div>
              <div class="order-info-row">
                <div class="order-info-label">Customer:</div>
                <div class="order-info-value"><?php echo $order['medical_user_name']; ?> (<?php echo $order['medical_user_email']; ?>)</div>
              </div>
              <div class="order-info-row">
                <div class="order-info-label">Current Status:</div>
                <div class="order-info-value">
                  <span class="status-badge <?php echo $status_class; ?>"><?php echo $order['order_status']; ?></span>
                </div>
              </div>
              <div class="order-info-row">
                <div class="order-info-label">Total (Your Items):</div>
                <div class="order-info-value">$<?php echo number_format($subtotal, 2); ?></div>
              </div>
              <?php if (!empty($order['notes'])): ?>
              <div class="order-info-row">
                <div class="order-info-label">Order Notes:</div>
                <div class="order-info-value">
                  <div class="order-notes"><?php echo nl2br($order['notes']); ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
            
            <h3 class="order-history-title">Order Items</h3>
            <?php if ($order_items->num_rows > 0): ?>
            <table class="order-items-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity</th>
                  <th>Price</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php while($item = $order_items->fetch_assoc()): ?>
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
          
          <div>
            <h3>Update Status</h3>
            <form action="update_order_status.php" method="post">
              <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
              
              <div class="form-group">
                <label for="new_status">New Status</label>
                <select class="form-control" id="new_status" name="new_status" required>
                  <option value="">Select Status...</option>
                  <option value="Pending" <?php if ($order['order_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                  <option value="Processing" <?php if ($order['order_status'] == 'Processing') echo 'selected'; ?>>Processing</option>
                  <option value="Shipped" <?php if ($order['order_status'] == 'Shipped') echo 'selected'; ?>>Shipped</option>
                  <option value="Delivered" <?php if ($order['order_status'] == 'Delivered') echo 'selected'; ?>>Delivered</option>
                  <option value="Cancelled" <?php if ($order['order_status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="status_notes">Status Update Notes</label>
                <textarea class="form-control" id="status_notes" name="status_notes" rows="4" placeholder="Add notes about this status update..."><?php echo isset($_POST['status_notes']) ? $_POST['status_notes'] : ''; ?></textarea>
              </div>
              
              <div class="form-group">
                <button type="submit" name="update_status" class="btn btn-primary btn-block">Update Order Status</button>
              </div>
              
              <div class="form-group">
                <a href="view_orders.php" class="btn btn-secondary btn-block">Back to Orders</a>
              </div>
            </form>
          </div>
        </div>
      <?php else: ?>
        <h3>Select an Order to Update</h3>
        
        <?php if ($all_orders_result->num_rows > 0): ?>
          <form action="update_order_status.php" method="get">
            <div class="form-group">
              <label for="order_select">Select Order</label>
              <select class="form-control" id="order_select" name="id" onchange="this.form.submit()">
                <option value="">Choose an order...</option>
                <?php while($order_row = $all_orders_result->fetch_assoc()): ?>
                  <option value="<?php echo $order_row['id']; ?>">
                    Order #<?php echo $order_row['id']; ?> - 
                    <?php echo date("M d, Y", strtotime($order_row['order_date'])); ?> - 
                    Status: <?php echo $order_row['order_status']; ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </form>
          
          <div class="form-group">
            <a href="view_orders.php" class="btn btn-primary">View All Orders</a>
          </div>
        <?php else: ?>
          <p>No orders found. When customers place orders for your products, they will appear here.</p>
          <div class="form-group">
            <a href="view_orders.php" class="btn btn-primary">View All Orders</a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Admin Dashboard
    
    // On page load, initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
      initializeEventListeners();
      
      // Fade out alert after 5 seconds
      const alert = document.querySelector('.alert');
      if (alert) {
        setTimeout(function() {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(function() {
            alert.style.display = 'none';
          }, 500);
        }, 5000);
      }
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
    
    // Logout function
    function logout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = "../logout.php";
      }
    }
  </script>
</body>
</html>