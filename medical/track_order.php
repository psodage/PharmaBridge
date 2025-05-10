<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') {
    header("Location: ../../../signin.php");
    exit;
}

// Connect to database
require_once('../api/db.php');

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Default values
$orders = [];
$error = null;
$success = null;

// Get order details if order_id is provided
$order_details = null;
$order_items = [];

if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    // Fetch order details
    $order_query = "SELECT o.*, DATE_FORMAT(o.order_date, '%M %d, %Y %h:%i %p') AS formatted_date 
                   FROM orders o
                   WHERE o.id = ? AND o.user_id = ?";
    
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        
        // Fetch order items
        $items_query = "SELECT oi.*, p.product_name, p.product_image 
                       FROM order_items oi
                       JOIN products p ON oi.product_id = p.id
                       WHERE oi.order_id = ?";
        
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
    } else {
        $error = "Order not found or you don't have permission to view it.";
    }
}

// Get all orders for user with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// Count total number of orders
$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Get orders for current page
$orders_query = "SELECT o.*, DATE_FORMAT(o.order_date, '%M %d, %Y') AS formatted_date
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.order_date DESC
                LIMIT ? OFFSET ?";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("iii", $user_id, $items_per_page, $offset);
$stmt->execute();
$orders_result = $stmt->get_result();

while ($order = $orders_result->fetch_assoc()) {
    $orders[] = $order;
}

// Handle order cancellation if requested
if (isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    $order_id_to_cancel = $_POST['order_id'];
    
    // Check if order exists and belongs to user
    $check_query = "SELECT order_status FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $order_id_to_cancel, $user_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $order_status = $check_result->fetch_assoc()['order_status'];
        
        // Only allow cancellation of pending orders
        if ($order_status == 'Pending') {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Update order status
                $update_query = "UPDATE orders SET order_status = 'Cancelled' WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $order_id_to_cancel);
                $stmt->execute();
                
                // Get order items to restore stock
                $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                $stmt = $conn->prepare($items_query);
                $stmt->bind_param("i", $order_id_to_cancel);
                $stmt->execute();
                $items_result = $stmt->get_result();
                
                // Restore stock for each item
                while ($item = $items_result->fetch_assoc()) {
                    $restore_query = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                    $stmt = $conn->prepare($restore_query);
                    $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                
                $success = "Order #$order_id_to_cancel has been cancelled successfully.";
                
                // Refresh the page to show updated status
                header("Location: track_order.php?success=Order cancelled successfully");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error = "Error cancelling order: " . $e->getMessage();
            }
        } else {
            $error = "Only pending orders can be cancelled.";
        }
    } else {
        $error = "Order not found or you don't have permission to cancel it.";
    }
}

// Get success message from URL if redirected
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Order Status - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <style>
    .content-container {
      margin-top:30px;
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s;
    }
    
    .sidebar-collapsed + .content-container {
      margin-left: 60px;
    }
    
    @media (max-width: 768px) {
      .content-container {
        margin-left: 0;
      }
    }
    
    .alert {
      padding: 15px;
      border-radius: 4px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .alert-icon {
      margin-right: 10px;
      font-size: 18px;
    }
    
    .orders-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      background-color: #fff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      border-radius: 5px;
      overflow: hidden;
    }
    
    .orders-table th, .orders-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    
    .orders-table th {
      background-color: #f8f9fa;
      font-weight: 600;
    }
    
    .orders-table tr:hover {
      background-color: #f6f9fc;
    }
    
    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      text-align: center;
    }
    
    .status-pending {
      background-color: #fff8e1;
      color: #f57c00;
      border: 1px solid #ffe0b2;
    }
    
    .status-processing {
      background-color: #e3f2fd;
      color: #1976d2;
      border: 1px solid #bbdefb;
    }
    
    .status-shipped {
      background-color: #e0f7fa;
      color: #0097a7;
      border: 1px solid #b2ebf2;
    }
    
    .status-delivered {
      background-color: #e8f5e9;
      color: #388e3c;
      border: 1px solid #c8e6c9;
    }
    
    .status-cancelled {
      background-color: #ffebee;
      color: #d32f2f;
      border: 1px solid #ffcdd2;
    }
    
    .action-btn {
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.8rem;
      text-decoration: none;
      display: inline-block;
      margin-right: 5px;
      border: none;
      transition: background-color 0.2s;
    }
    
    .view-btn {
      background-color: #4a6cf7;
      color: white;
    }
    
    .view-btn:hover {
      background-color: #3a5be6;
    }
    
    .cancel-btn {
      background-color: #f44336;
      color: white;
    }
    
    .cancel-btn:hover {
      background-color: #d32f2f;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    
    .pagination a, .pagination span {
      display: inline-block;
      padding: 8px 16px;
      text-decoration: none;
      color: #4a6cf7;
      border: 1px solid #ddd;
      margin: 0 4px;
      border-radius: 4px;
    }
    
    .pagination a:hover {
      background-color: #f1f1f1;
    }
    
    .pagination .active {
      background-color: #4a6cf7;
      color: white;
      border: 1px solid #4a6cf7;
    }
    
    .pagination .disabled {
      color: #ddd;
      cursor: not-allowed;
    }
    
    .order-details {
      background-color: #fff;
      border-radius: 5px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }
    
    .order-id {
      font-size: 1.2rem;
      font-weight: 600;
    }
    
    .order-timeline {
      margin: 30px 0;
      position: relative;
    }
    
    .timeline-track {
      position: absolute;
      top: 15px;
      left: 0;
      right: 0;
      height: 4px;
      background-color: #e0e0e0;
      z-index: 1;
    }
    
    .timeline-progress {
      position: absolute;
      top: 15px;
      left: 0;
      height: 4px;
      background-color: #4caf50;
      z-index: 2;
    }
    
    .timeline-steps {
      display: flex;
      justify-content: space-between;
      position: relative;
      z-index: 3;
    }
    
    .timeline-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 20%;
    }
    
    .step-icon {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: #fff;
      border: 2px solid #e0e0e0;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
      font-size: 14px;
      color: #757575;
    }
    
    .step-active .step-icon {
      background-color: #4caf50;
      border-color: #4caf50;
      color: white;
    }
    
    .step-completed .step-icon {
      background-color: #4caf50;
      border-color: #4caf50;
      color: white;
    }
    
    .step-cancelled .step-icon {
      background-color: #f44336;
      border-color: #f44336;
      color: white;
    }
    
    .step-label {
      font-size: 0.8rem;
      text-align: center;
      color: #757575;
    }
    
    .step-active .step-label, .step-completed .step-label {
      color: #212121;
      font-weight: 500;
    }
    
    .order-items {
      margin-top: 30px;
    }
    
    .order-item {
      display: flex;
      padding: 15px 0;
      border-bottom: 1px solid #eee;
    }
    
    .item-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
      margin-right: 15px;
    }
    
    .item-details {
      flex: 1;
    }
    
    .item-name {
      font-weight: 500;
      margin-bottom: 5px;
    }
    
    .item-meta {
      display: flex;
      color: #757575;
      font-size: 0.85rem;
    }
    
    .item-price {
      margin-right: 20px;
    }
    
    .back-btn {
      display: inline-block;
      margin-bottom: 20px;
      color: #4a6cf7;
      text-decoration: none;
    }
    
    .back-btn i {
      margin-right: 5px;
    }
    
    .order-summary {
      background-color: #f9f9f9;
      padding: 15px;
      border-radius: 4px;
      margin-top: 20px;
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #eee;
    }
    
    .summary-row:last-child {
      border-bottom: none;
      font-weight: 600;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      background-color: #fff;
      border-radius: 5px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .empty-icon {
      font-size: 3rem;
      color: #e0e0e0;
      margin-bottom: 20px;
    }
    
    .empty-text {
      font-size: 1.1rem;
      color: #757575;
      margin-bottom: 20px;
    }
    
    .shop-btn {
      display: inline-block;
      padding: 10px 20px;
      background-color: #4a6cf7;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: 500;
    }
    
    .shop-btn:hover {
      background-color: #3a5be6;
    }
    
    @media (max-width: 576px) {
      .orders-table {
        display: block;
        overflow-x: auto;
      }
      
      .timeline-step {
        width: 22%;
      }
      
      .step-label {
        font-size: 0.7rem;
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
        <button class="menu-btn" data-page="medical.php">
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
          <a href="#" class="dropdown-item" data-page="place_order.php">Place New Order</a>
          <a href="#" class="dropdown-item active" data-page="track_order.php">Track Order Status</a>
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
    <h1>Track Order Status</h1>
    
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
      </div>
    <?php endif; ?>
    
    <?php if(isset($order_details)): ?>
      <!-- Order details view -->
      <a href="track_order.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
      
      <div class="order-details">
        <div class="order-header">
          <div>
            <div class="order-id">Order #<?php echo $order_details['id']; ?></div>
            <div><?php echo $order_details['formatted_date']; ?></div>
          </div>
          <div>
            <?php
            $status_class = '';
            switch($order_details['order_status']) {
                case 'Pending': $status_class = 'status-pending'; break;
                case 'Processing': $status_class = 'status-processing'; break;
                case 'Shipped': $status_class = 'status-shipped'; break;
                case 'Delivered': $status_class = 'status-delivered'; break;
                case 'Cancelled': $status_class = 'status-cancelled'; break;
            }
            ?>
            <span class="status-badge <?php echo $status_class; ?>"><?php echo $order_details['order_status']; ?></span>
          </div>
        </div>
        
        <!-- Order timeline -->
        <div class="order-timeline">
          <div class="timeline-track"></div>
          
          <?php
          $progress_width = "0%";
          if ($order_details['order_status'] == 'Cancelled') {
              $progress_width = "0%";
          } else {
              switch($order_details['order_status']) {
                  case 'Pending': $progress_width = "10%"; break;
                  case 'Processing': $progress_width = "37.5%"; break;
                  case 'Shipped': $progress_width = "62.5%"; break;
                  case 'Delivered': $progress_width = "100%"; break;
              }
          }
          ?>
          
          <div class="timeline-progress" style="width: <?php echo $progress_width; ?>"></div>
          
          <div class="timeline-steps">
            <div class="timeline-step <?php echo ($order_details['order_status'] != 'Cancelled') ? 'step-completed' : ''; ?>">
              <div class="step-icon"><i class="fas fa-clipboard-check"></i></div>
              <div class="step-label">Order Placed</div>
            </div>
            
            <div class="timeline-step <?php echo (in_array($order_details['order_status'], ['Processing', 'Shipped', 'Delivered'])) ? 'step-completed' : (($order_details['order_status'] == 'Pending') ? 'step-active' : ''); ?>">
              <div class="step-icon"><i class="fas fa-cog"></i></div>
              <div class="step-label">Processing</div>
            </div>
            
            <div class="timeline-step <?php echo (in_array($order_details['order_status'], ['Shipped', 'Delivered'])) ? 'step-completed' : (($order_details['order_status'] == 'Processing') ? 'step-active' : ''); ?>">
              <div class="step-icon"><i class="fas fa-truck"></i></div>
              <div class="step-label">Shipped</div>
            </div>
            
            <div class="timeline-step <?php echo ($order_details['order_status'] == 'Delivered') ? 'step-completed' : (($order_details['order_status'] == 'Shipped') ? 'step-active' : ''); ?>">
              <div class="step-icon"><i class="fas fa-box-open"></i></div>
              <div class="step-label">Delivered</div>
            </div>
            
            <div class="timeline-step <?php echo ($order_details['order_status'] == 'Cancelled') ? 'step-cancelled' : ''; ?>">
              <div class="step-icon"><i class="fas <?php echo ($order_details['order_status'] == 'Cancelled') ? 'fa-times' : 'fa-flag-checkered'; ?>"></i></div>
              <div class="step-label"><?php echo ($order_details['order_status'] == 'Cancelled') ? 'Cancelled' : 'Completed'; ?></div>
            </div>
          </div>
        </div>
        
        <!-- Order items -->
        <div class="order-items">
          <h3>Order Items</h3>
          <?php foreach($order_items as $item): ?>
            <div class="order-item">
              <img src="<?php echo !empty($item['product_image']) ? $item['product_image'] : '/api/placeholder/60/60'; ?>" alt="<?php echo $item['product_name']; ?>" class="item-image">
              <div class="item-details">
                <div class="item-name"><?php echo $item['product_name']; ?></div>
                <div class="item-meta">
                  <div class="item-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                  <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                </div>
              </div>
              <div class="item-total">
                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <!-- Order summary -->
        <div class="order-summary">
          <div class="summary-row">
            <div>Subtotal</div>
            <div>$<?php echo number_format($order_details['subtotal'], 2); ?></div>
          </div>
          <div class="summary-row">
            <div>Tax</div>
            <div>$<?php echo number_format($order_details['tax'], 2); ?></div>
          </div>
          <div class="summary-row">
            <div>Shipping</div>
            <div>$<?php echo number_format($order_details['shipping'], 2); ?></div>
          </div>
          <div class="summary-row">
            <div>Total</div>
            <div>$<?php echo number_format($order_details['total'], 2); ?></div>
          </div>
        </div>
        
        <?php if($order_details['order_status'] == 'Pending'): ?>
          <div style="margin-top: 20px; text-align: right;">
            <form method="post" action="" onsubmit="return confirm('Are you sure you want to cancel this order?')">
              <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
              <button type="submit" name="cancel_order" class="cancel-btn">Cancel Order</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <!-- Orders list view -->
      <?php if(empty($orders)): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-shopping-bag"></i>
          </div>
          <div class="empty-text">You haven't placed any orders yet.</div>
          <a href="place_order.php" class="shop-btn">Start Shopping</a>
        </div>
      <?php else: ?>
        <table class="orders-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Date</th>
              <th>Total</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($orders as $order): ?>
              <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo $order['formatted_date']; ?></td>
                <td>$<?php echo number_format($order['total'], 2); ?></td>
                <td>
                  <?php
                  $status_class = '';
                  switch($order['order_status']) {
                      case 'Pending': $status_class = 'status-pending'; break;
                      case 'Processing': $status_class = 'status-processing'; break;
                      case 'Shipped': $status_class = 'status-shipped'; break;
                      case 'Delivered': $status_class = 'status-delivered'; break;
                      case 'Cancelled': $status_class = 'status-cancelled'; break;
                  }
                  ?>
                  <span class="status-badge <?php echo $status_class; ?>"><?php echo $order['order_status']; ?></span>
                </td>
                <td>
                  <a href="track_order.php?order_id=<?php echo $order['id']; ?>" class="action-btn view-btn">View Details</a>
                  <?php if($order['order_status'] == 'Pending'): ?>
                    <form method="post" action="" style="display: inline" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                      <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                      <button type="submit" name="cancel_order" class="action-btn cancel-btn">Cancel</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
          <div class="pagination">
            <?php if($page > 1): ?>
              <a href="track_order.php?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
            <?php else: ?>
              <span class="disabled">&laquo; Previous</span>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
              <?php if($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="track_order.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
              <a href="track_order.php?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php else: ?>
              <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <script>
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

