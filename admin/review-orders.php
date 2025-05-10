<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Order Activities - BridgeRx Admin</title>
  <link rel="stylesheet" href="../css/home_admin.css">
  <link rel="stylesheet" href="../css/admin.css">
  <style>
    /* Order Review Specific Styles */
    .filter-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      min-width: 200px;
    }

    .filter-label {
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: #495057;
    }

    .filter-input {
      padding: 8px 12px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 0.9rem;
    }

    .filter-input:focus {
      border-color: #4e73df;
      outline: none;
      box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    .date-range {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .action-buttons {
      display: flex;
      gap: 10px;
      margin-top: auto;
      align-self: flex-end;
    }

    .action-button {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }

    .primary-button {
      background-color: #4e73df;
      color: white;
    }

    .primary-button:hover {
      background-color: #3a5cbe;
    }

    .secondary-button {
      background-color: #f8f9fa;
      border: 1px solid #ced4da;
      color: #495057;
    }

    .secondary-button:hover {
      background-color: #e9ecef;
    }

    .order-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .order-table th {
      background-color: #f2f6ff;
      color: #495057;
      font-weight: 600;
      text-align: left;
      padding: 12px 15px;
      border-bottom: 2px solid #e3e6f0;
    }

    .order-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #f0f0f0;
      color: #333;
      font-size: 0.9rem;
    }

    .order-table tr:nth-child(even) {
      background-color: #f8f9fc;
    }

    .order-table tr:last-child td {
      border-bottom: none;
    }

    .order-table tr:hover {
      background-color: #f2f6ff;
    }

    .status-tag {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-processing {
      background-color: #e6f3ff;
      color: #0074d9;
    }

    .status-shipped {
      background-color: #e6f8ed;
      color: #28a745;
    }

    .status-delivered {
      background-color: #d1e7dd;
      color: #20c997;
    }

    .status-cancelled {
      background-color: #f8d7da;
      color: #dc3545;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #fd7e14;
    }

    .action-icon {
      color: #4e73df;
      cursor: pointer;
      margin-right: 10px;
      font-size: 1.1rem;
    }

    .action-icon:hover {
      color: #3a5cbe;
    }

    .pagination {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 20px;
    }

    .page-info {
      color: #6c757d;
      font-size: 0.9rem;
    }

    .page-controls {
      display: flex;
      gap: 5px;
    }

    .page-button {
      padding: 5px 10px;
      border: 1px solid #dee2e6;
      background-color: white;
      color: #495057;
      border-radius: 4px;
      cursor: pointer;
    }

    .page-button.active {
      background-color: #4e73df;
      color: white;
      border-color: #4e73df;
    }

    .page-button:hover:not(.active) {
      background-color: #f8f9fa;
    }

    .order-details {
      display: flex;
      flex-direction: column;
    }

    .order-id {
      font-weight: 600;
      margin-bottom: 4px;
    }

    .order-date {
      font-size: 0.8rem;
      color: #6c757d;
    }

    .store-badge {
      background-color: #e9ecef;
      color: #495057;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .order-notes {
      max-width: 200px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .order-notes:hover {
      white-space: normal;
      overflow: visible;
      position: relative;
      z-index: 1;
      background-color: #f8f9fa;
      border-radius: 4px;
      padding: 5px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .summary-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .summary-card {
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      display: flex;
      flex-direction: column;
    }

    .summary-title {
      color: #495057;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .summary-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: #4e73df;
      margin-bottom: 10px;
    }

    .summary-trend {
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .trend-up {
      color: #28a745;
    }

    .trend-down {
      color: #dc3545;
    }

    .timeline-icon {
      margin-right: 8px;
      display: inline-block;
      width: 20px;
      height: 20px;
      line-height: 20px;
      text-align: center;
      background-color: #e9ecef;
      color: #495057;
      border-radius: 50%;
      font-size: 0.8rem;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .user-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: #e9ecef;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: #495057;
    }

    .user-name {
      font-weight: 500;
    }

    /* Timeline styles for order tracking */
    .timeline-container {
      margin-top: 15px;
      position: relative;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease-out;
    }

    .timeline-container.show {
      max-height: 500px;
    }

    .timeline {
      position: relative;
      padding-left: 30px;
      margin-bottom: 15px;
    }

    .timeline:before {
      content: '';
      position: absolute;
      left: 10px;
      top: 0;
      bottom: 0;
      width: 2px;
      background-color: #e9ecef;
    }

    .timeline-item {
      position: relative;
      padding-bottom: 15px;
    }

    .timeline-item:last-child {
      padding-bottom: 0;
    }

    .timeline-dot {
      position: absolute;
      left: -30px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background-color: #4e73df;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 0.7rem;
    }

    .timeline-content {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 10px;
    }

    .timeline-date {
      font-size: 0.8rem;
      color: #6c757d;
      margin-bottom: 5px;
    }

    .timeline-title {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .timeline-message {
      font-size: 0.9rem;
    }
    /* Update the timeline-container CSS to ensure it's hidden by default */
    .timeline-container {
    margin-top: 15px;
    position: relative;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
    display: none; /* Hidden by default */
    opacity: 0;
  }

  .timeline-container.show {
    max-height: 500px;
    display: table-row; /* Display as table-row when shown */
    opacity: 1;

  }
  /* Improved Filter Section Styles */
.filter-container {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 20px;
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.filter-container form {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  width: 100%;
}

.filter-group {
  display: flex;
  flex-direction: column;
  flex: 1 1 200px;
  min-width: 200px;
  max-width: 300px;
}

.filter-label {
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: #495057;
}

.filter-input {
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 0.9rem;
  width: 100%;
}

.date-range {
  display: flex;
  gap: 10px;
  align-items: center;
}

.date-range input {
  flex: 1;
}

.date-range span {
  color: #6c757d;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  gap: 10px;
  margin-top: auto;
  align-self: flex-end;
  margin-left: auto;
}

.action-button {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .filter-group {
    flex: 1 1 100%;
    max-width: 100%;
  }
  
  .action-buttons {
    width: 100%;
    justify-content: flex-end;
    margin-top: 15px;
  }
}
  </style>
</head>
<body>
<div class="header">
    <div class="logo">
      <span class="logo-icon">⚕️</span> BridgeRx Admin
    </div>
    <div class="header-controls">
      <button class="header-btn" id="sidebarToggle" title="Toggle Sidebar">☰</button>
      <button class="header-btn" title="Notifications">
        🔔
        <span class="alert-badge">8</span>
      </button>
      
      <div class="account-info">
        <span>System Admin</span>
        <div class="account-icon">A</div>
      </div>
    </div>
  </div>
  
  <div class="sidebar" id="sidebar">
    <ul class="menu">
      <li class="menu-item">
        <button class="menu-btn" data-page="home.php">
          <span class="menu-icon">📊</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">👥</span>
          <span class="menu-text">User Management</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="user_management/manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item" data-page="user_management/manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="#" class="dropdown-item" data-page="user_management/manage-user-status.php">Approve / Suspend / Remove Users</a>
        </div>
      </li>
     
 
      <li class="menu-item active">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📦</span>
          <span class="menu-text">Product & Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="monitor-products.php">Monitor Listed Products</a>
          <a href="#" class="dropdown-item active" data-page="review-orders.php">Review Order Activities</a>
        </div>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Settings</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="admin-profile.php">Admin Profile Management</a>
  
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🛡️</span>
          <span class="menu-text">Support & Security</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
        <a href="#" class="dropdown-item" data-page="handle-complaints_medical.php">Handle Complaints From Medical</a>
        <a href="#" class="dropdown-item" data-page="handle-complaints_company.php">Handle Complaints From Company</a>
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
  
  <div id="content" class="content">
    <?php

    // Database connection
    require_once '../api/db_con.php';

    // Handle filters
    $where_conditions = [];
    $params = [];

    // Search filter
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    if (!empty($search)) {
        $where_conditions[] = "(o.id LIKE ? OR o.user_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Status filter
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    if (!empty($status)) {
        $where_conditions[] = "o.order_status = ?";
        $params[] = $status;
    }

    // Date range filter
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    if (!empty($start_date) && !empty($end_date)) {
        $where_conditions[] = "o.order_date BETWEEN ? AND ?";
        $params[] = $start_date . " 00:00:00";
        $params[] = $end_date . " 23:59:59";
    }

    // Store filter
    $store = isset($_GET['store']) ? $_GET['store'] : '';
    if (!empty($store)) {
        $where_conditions[] = "o.user_id = ?";
        $params[] = $store;
    }

    // Build WHERE clause
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(" AND ", $where_conditions);
    }

    // Pagination settings
    $records_per_page = 5;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;

    // Count total records for pagination
    $count_sql = "SELECT COUNT(*) as total FROM orders o" . $where_clause;
    $stmt = $pdo->prepare($count_sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Get orders with pagination
    $sql = "SELECT o.*, 
            CASE 
                WHEN mu.id IS NOT NULL THEN mu.name 
                WHEN cu.id IS NOT NULL THEN cu.name 
                ELSE 'Unknown User' 
            END as user_name,
            CASE 
                WHEN mu.id IS NOT NULL THEN 'M' 
                WHEN cu.id IS NOT NULL THEN SUBSTRING(cu.name, 1, 1) 
                ELSE 'U' 
            END as user_initial
            FROM orders o
            LEFT JOIN medical_users mu ON o.user_id = mu.id AND mu.approval_status = 'approved'
            LEFT JOIN company_users cu ON o.user_id = cu.id AND cu.approval_status = 'approved'"
            . $where_clause . 
            " ORDER BY o.updated_at DESC 
            LIMIT $offset, $records_per_page";

    $stmt = $pdo->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all medical stores for filter dropdown
    $store_sql = "SELECT id, name FROM medical_users WHERE approval_status = 'approved' ORDER BY name";
    $store_stmt = $pdo->prepare($store_sql);
    $store_stmt->execute();
    $stores = $store_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order summary statistics
    $summary_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN order_status = 'Processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN order_status = 'Shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(total) as total_revenue
                    FROM orders";
    $summary_stmt = $pdo->prepare($summary_sql);
    $summary_stmt->execute();
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // Function to get order timeline
    function getOrderTimeline($pdo, $order_id) {
        $timeline_sql = "SELECT * FROM order_history WHERE order_id = ? ORDER BY timestamp ASC";
        $timeline_stmt = $pdo->prepare($timeline_sql);
        $timeline_stmt->execute([$order_id]);
        return $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate percentage change for summary statistics
    $prev_period_sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(total) as total_revenue
                        FROM orders 
                        WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $prev_period_stmt = $pdo->prepare($prev_period_sql);
    $prev_period_stmt->execute();
    $prev_period = $prev_period_stmt->fetch(PDO::FETCH_ASSOC);

    $order_trend = 0;
    $revenue_trend = 0;

    if ($prev_period['total_orders'] > 0) {
        $order_trend = (($summary['total_orders'] - $prev_period['total_orders']) / $prev_period['total_orders']) * 100;
    }

    if ($prev_period['total_revenue'] > 0) {
        $revenue_trend = (($summary['total_revenue'] - $prev_period['total_revenue']) / $prev_period['total_revenue']) * 100;
    }
    ?>

    <div class="main-content">
      <div class="page-header">
        <h1>Review Order Activities</h1>
        <p>Monitor, track, and manage order activities across the platform</p>
      </div>
      
      <!-- Summary Cards -->
      <div class="summary-cards">
        <div class="summary-card">
          <div class="summary-title">Total Orders</div>
          <div class="summary-value"><?php echo number_format($summary['total_orders']); ?></div>
          <div class="summary-trend <?php echo $order_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
            <?php echo ($order_trend >= 0 ? '↑' : '↓') . ' ' . abs(round($order_trend, 1)) . '%'; ?> from last month
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-title">Processing Orders</div>
          <div class="summary-value"><?php echo number_format($summary['processing_orders']); ?></div>
          <div class="summary-trend">
            <?php echo round(($summary['processing_orders'] / max(1, $summary['total_orders'])) * 100, 1); ?>% of total orders
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-title">Shipped Orders</div>
          <div class="summary-value"><?php echo number_format($summary['shipped_orders']); ?></div>
          <div class="summary-trend">
            <?php echo round(($summary['shipped_orders'] / max(1, $summary['total_orders'])) * 100, 1); ?>% of total orders
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-title">Total Revenue</div>
          <div class="summary-value">$<?php echo number_format($summary['total_revenue'], 2); ?></div>
          <div class="summary-trend <?php echo $revenue_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
            <?php echo ($revenue_trend >= 0 ? '↑' : '↓') . ' ' . abs(round($revenue_trend, 1)) . '%'; ?> from last month
          </div>
        </div>
      </div>
      
      <!-- Filters -->
      <div class="filter-container">
        <form method="GET" action="">
          <div class="filter-group">
            <label class="filter-label" for="search-order">Search Order</label>
            <input type="text" id="search-order" name="search" class="filter-input" placeholder="Order ID, User ID..." 
                  value="<?php echo htmlspecialchars($search); ?>">
          </div>
          
          <div class="filter-group">
            <label class="filter-label" for="filter-status">Order Status</label>
            <select id="filter-status" name="status" class="filter-input">
              <option value="">All Statuses</option>
              <option value="Processing" <?php echo $status == 'Processing' ? 'selected' : ''; ?>>Processing</option>
              <option value="Shipped" <?php echo $status == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
              <option value="Delivered" <?php echo $status == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
              <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
              <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Order Date</label>
            <div class="date-range">
              <input type="date" name="start_date" class="filter-input" placeholder="From" 
                    value="<?php echo htmlspecialchars($start_date); ?>">
              <span>to</span>
              <input type="date" name="end_date" class="filter-input" placeholder="To" 
                    value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
          </div>
          
          <div class="filter-group">
            <label class="filter-label" for="filter-store">Medical Store</label>
            <select id="filter-store" name="store" class="filter-input">
              <option value="">All Stores</option>
              <?php foreach ($stores as $store_item): ?>
                <option value="<?php echo $store_item['id']; ?>" <?php echo $store == $store_item['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($store_item['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="action-buttons">
            <button type="reset" class="action-button secondary-button">Reset</button>
            <button type="submit" class="action-button primary-button">Apply Filters</button>
          </div>
        </form>
      </div>
      
      <!-- Orders Table -->
      <table class="order-table">
        <thead>
          <tr>
            <th>Order Details</th>
            <th>User</th>
            <th>Order Total</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Last Updated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="7" style="text-align: center;">No orders found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $order): ?>
              <tr data-order-id="<?php echo $order['id']; ?>">
                <td>
                  <div class="order-details">
                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                    <div class="order-date"><?php echo $order['order_date']; ?></div>
                  </div>
                </td>
                <td>
                  <div class="user-info">
                    <div class="user-avatar"><?php echo htmlspecialchars($order['user_initial']); ?></div>
                    <div class="user-name"><?php echo htmlspecialchars($order['user_name']); ?></div>
                  </div>
                </td>
                <td>$<?php echo number_format($order['total'], 2); ?></td>
                <td>
                  <span class="status-tag status-<?php echo strtolower($order['order_status']); ?>">
                    <?php echo $order['order_status']; ?>
                  </span>
                </td>
                <td class="order-notes"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></td>
                <td><?php echo $order['updated_at']; ?></td>
                <td>
                  <span class="action-icon view-details" data-order-id="<?php echo $order['id']; ?>" title="View Details">👁️</span>

                </td>
              </tr>
              
              <!-- Order Timeline (Hidden by default) -->
              <tr id="timeline-<?php echo $order['id']; ?>" class="timeline-container">
                <td colspan="7">
                  <div class="timeline">
                    <?php 
                    // Check if we're in a demo environment with dummy data
                    if (isset($order['id']) && $order['id'] <= 2):
                      // Dummy timeline data for the first two orders
                      if ($order['id'] == 1):
                        $timeline_events = [
                          ['timestamp' => '2025-03-06 18:03:35', 'title' => 'Order Placed', 'message' => 'Order was created successfully'],
                          ['timestamp' => '2025-03-06 18:10:22', 'title' => 'Payment Received', 'message' => 'Payment of $2.20 processed successfully'],
                          ['timestamp' => '2025-03-06 22:33:35', 'title' => 'Cancellation Requested', 'message' => 'Customer requested order cancellation'],
                          ['timestamp' => '2025-03-06 22:40:15', 'title' => 'Order Cancelled', 'message' => 'Order was cancelled and refund initiated']
                        ];
                      elseif ($order['id'] == 2):
                        $timeline_events = [
                          ['timestamp' => '2025-03-07 10:50:08', 'title' => 'Order Placed', 'message' => 'Order was created successfully'],
                          ['timestamp' => '2025-03-07 10:55:30', 'title' => 'Payment Received', 'message' => 'Payment of $2.20 processed successfully'],
                          ['timestamp' => '2025-03-07 15:20:08', 'title' => 'Order Processing', 'message' => 'Order has been processed and is being prepared for shipment'],
                          ['timestamp' => '2025-03-07 16:52:59', 'title' => 'Order Shipped', 'message' => 'Order has been shipped with tracking number TRK123456789']
                        ];
                      endif;
                    else:
                      // Get real timeline data from database
                      $timeline_events = getOrderTimeline($pdo, $order['id']);
                    endif;
                    
                    $i = 1;
                    foreach ($timeline_events as $event):
                    ?>
                      <div class="timeline-item">
                        <div class="timeline-dot"><?php echo $i++; ?></div>
                        <div class="timeline-content">
                          <div class="timeline-date"><?php echo isset($event['timestamp']) ? $event['timestamp'] : $event['created_at']; ?></div>
                          <div class="timeline-title"><?php echo isset($event['title']) ? $event['title'] : $event['status']; ?></div>
                          <div class="timeline-message"><?php echo isset($event['message']) ? $event['message'] : $event['details']; ?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      
 
      </div>
    </div>
  </div>

  <script>
// Main JavaScript for Admin Dashboard
document.addEventListener('DOMContentLoaded', function() {
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
  
  // Handle window resize
  window.addEventListener('resize', function() {
    if (window.innerWidth <= 768) {
      sidebar.classList.remove('sidebar-collapsed');
      content.classList.remove('content-full');
      sidebar.classList.remove('show');
    }
  });
  
  // Handle form reset
  document.querySelector('button[type="reset"]').addEventListener('click', function(e) {
    e.preventDefault();
    window.location.href = 'review-orders.php';
  });

  // View details handler
  const viewDetailsButtons = document.querySelectorAll('.view-details');
  viewDetailsButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      const orderId = this.getAttribute('data-order-id');
      const timelineRow = document.getElementById('timeline-' + orderId);
      
      if (timelineRow) {
        // Toggle the show class
        timelineRow.classList.toggle('show');
        
        // Update the icon to indicate expanded/collapsed state
        if (timelineRow.classList.contains('show')) {
          this.textContent = '⮟'; // Down arrow when expanded
          this.title = "Hide Details";
        } else {
          this.textContent = '👁️'; // Eye icon when collapsed
          this.title = "View Details";
        }
      }
    });
  });
});

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