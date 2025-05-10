<?php 
session_start();
require_once('../api/db.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../../signin.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get order history for the current user
$query = "SELECT id, order_date, order_status, subtotal, tax, shipping, total, notes, created_at 
          FROM orders 
          WHERE user_id = ? 
          ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order History - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #4a6fdc;
      --secondary-color: #6c87e0;
      --accent-color: #3d5cb8;
      --text-color: #333;
      --light-bg: #f8f9fa;
      --border-color: #e0e0e0;
      --success-color: #28a745;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
      --pending-color: #17a2b8;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f7fa;
      color: var(--text-color);
    }

    .content-container {
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s;
    }

    .sidebar-collapsed + .content-container {
      margin-left: 80px;
    }

    .page-title {
      font-size: 24px;
      margin-bottom: 20px;
      color: var(--primary-color);
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 10px;
      display: flex;
      align-items: center;
    }

    .page-title i {
      margin-right: 10px;
    }

    .card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }

    .filters {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }

    .filter-group {
      display: flex;
      align-items: center;
    }

    .filter-label {
      margin-right: 10px;
      font-weight: 500;
    }

    .filter-input {
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      font-size: 14px;
    }

    .search-btn {
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 4px;
      padding: 8px 15px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .search-btn:hover {
      background-color: var(--accent-color);
    }

    .reset-btn {
      background-color: #f8f9fa;
      color: #555;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      padding: 8px 15px;
      cursor: pointer;
      margin-left: 10px;
      transition: background-color 0.3s;
    }

    .reset-btn:hover {
      background-color: #e9ecef;
    }

    .orders-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .orders-table th {
      background-color: #f8f9fa;
      color: #555;
      text-align: left;
      padding: 12px;
      font-weight: 600;
      border-bottom: 2px solid var(--border-color);
    }

    .orders-table td {
      padding: 12px;
      border-bottom: 1px solid var(--border-color);
      color: #444;
    }

    .orders-table tr:hover {
      background-color: #f5f8ff;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      color: white;
    }

    .status-pending {
      background-color: var(--pending-color);
    }

    .status-processing {
      background-color: var(--warning-color);
      color: #333;
    }

    .status-shipped {
      background-color: var(--primary-color);
    }

    .status-delivered {
      background-color: var(--success-color);
    }

    .status-cancelled {
      background-color: var(--danger-color);
    }

    .action-btn {
      padding: 6px 10px;
      border-radius: 4px;
      font-size: 13px;
      text-align: center;
      text-decoration: none;
      margin-right: 5px;
      color: white;
      cursor: pointer;
      transition: opacity 0.3s;
      display: inline-block;
    }

    .action-btn:hover {
      opacity: 0.9;
    }

    .view-btn {
      background-color: var(--primary-color);
    }

    .print-btn {
      background-color: #6c757d;
    }

    .no-orders {
      text-align: center;
      padding: 30px;
      color: #666;
      font-style: italic;
    }

    .order-details {
      display: none;
      background-color: #f8f9fa;
      border-radius: 5px;
      padding: 15px;
      margin-top: 10px;
    }

    .details-title {
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--primary-color);
    }

    .details-table {
      width: 100%;
      border-collapse: collapse;
    }

    .details-table th {
      background-color: #eef1f7;
      padding: 8px;
      text-align: left;
      font-size: 13px;
    }

    .details-table td {
      padding: 8px;
      font-size: 13px;
    }

    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 5px;
    }

    .pagination a {
      display: inline-block;
      padding: 8px 12px;
      background-color: white;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      text-decoration: none;
      color: var(--primary-color);
      transition: background-color 0.3s;
    }

    .pagination a.active {
      background-color: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }

    .pagination a:hover:not(.active) {
      background-color: #f5f5f5;
    }

    @media (max-width: 1024px) {
      .content-container {
        margin-left: 0;
        padding: 15px;
      }
      
      .filters {
        flex-direction: column;
        gap: 10px;
      }
      
      .filter-group {
        width: 100%;
      }
      
      .action-btn {
        margin-bottom: 5px;
      }
    }
    .feedback-btn {
  background-color: #6c5ce7;
}

.feedback-btn:hover {
  background-color: #5649c9;
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
          <a href="#" class="dropdown-item" data-page="track_order.php">Track Order Status</a>
          <a href="#" class="dropdown-item active" data-page="view_history.php">View Order History</a>
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
  
  <div class="content-container">
    <div class="page-title">
      <i class="fas fa-history"></i> Order History
    </div>
    
    <div class="card">
      <div class="filters">
        <div class="filter-group">
          <span class="filter-label">Date Range:</span>
          <input type="date" class="filter-input" id="startDate" placeholder="Start Date">
          <span style="margin: 0 10px;">to</span>
          <input type="date" class="filter-input" id="endDate" placeholder="End Date">
        </div>
        
        <div class="filter-group">
          <span class="filter-label">Status:</span>
          <select class="filter-input" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        
        <div class="filter-group">
          <button class="search-btn" id="filterBtn">
            <i class="fas fa-search"></i> Filter
          </button>
          <button class="reset-btn" id="resetBtn">
            <i class="fas fa-undo"></i> Reset
          </button>
        </div>
      </div>
      
      <?php if($result->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while($order = $result->fetch_assoc()): ?>
                <tr>
                  <td>#<?php echo $order['id']; ?></td>
                  <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                  <td>
                    <?php 
                      $status = strtolower($order['order_status']);
                      $statusClass = 'status-' . $status;
                      echo '<span class="status-badge ' . $statusClass . '">' . ucfirst($status) . '</span>';
                    ?>
                  </td>
                  <td>$<?php echo number_format($order['total'], 2); ?></td>
                  <td>
                    <a href="javascript:void(0)" class="action-btn view-btn view-details" data-order-id="<?php echo $order['id']; ?>">
                      <i class="fas fa-eye"></i> View
                    </a>
                    <a href="javascript:void(0)" class="action-btn print-btn" onclick="printOrder(<?php echo $order['id']; ?>)">
                      <i class="fas fa-print"></i> Print
                    </a>
                    <?php if(strtolower($order['order_status']) === 'delivered'): ?>
                    <a href="view_supplier.php?id=<?php echo $order['id']; ?>" class="action-btn feedback-btn">Feedback</a>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td colspan="5">
                    <div id="details-<?php echo $order['id']; ?>" class="order-details">
                      <div class="details-title">Order Details</div>
                      <div class="order-summary">
                        <p><strong>Order Date:</strong> <?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($order['order_status']); ?></p>
                        <p><strong>Subtotal:</strong> $<?php echo number_format($order['subtotal'], 2); ?></p>
                        <p><strong>Tax:</strong> $<?php echo number_format($order['tax'], 2); ?></p>
                        <p><strong>Shipping:</strong> $<?php echo number_format($order['shipping'], 2); ?></p>
                        <p><strong>Total:</strong> $<?php echo number_format($order['total'], 2); ?></p>
                        <?php if(!empty($order['notes'])): ?>
                          <p><strong>Notes:</strong> <?php echo $order['notes']; ?></p>
                        <?php endif; ?>
                      </div>
                      
                      <div class="details-title" style="margin-top: 15px;">Items</div>
                      <?php
                        // Get order items
                        $itemsQuery = "SELECT oi.*, p.product_name 
                                      FROM order_items oi 
                                      JOIN products p ON oi.product_id = p.id 
                                      WHERE oi.order_id = ?";
                        $itemsStmt = $conn->prepare($itemsQuery);
                        $itemsStmt->bind_param("i", $order['id']);
                        $itemsStmt->execute();
                        $itemsResult = $itemsStmt->get_result();
                      ?>
                      
                      <table class="details-table">
                        <thead>
                          <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if($itemsResult->num_rows > 0): ?>
                            <?php while($item = $itemsResult->fetch_assoc()): ?>
                              <tr>
                                <td><?php echo $item['product_name']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                              </tr>
                            <?php endwhile; ?>
                          <?php else: ?>
                            <tr>
                              <td colspan="4" style="text-align: center;">No items found for this order.</td>
                            </tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        
        <div class="pagination">
          <a href="#">&laquo;</a>
          <a href="#" class="active">1</a>
          <a href="#">2</a>
          <a href="#">3</a>
          <a href="#">&raquo;</a>
        </div>
      <?php else: ?>
        <div class="no-orders">
          <p><i class="fas fa-info-circle"></i> No order history found.</p>
          <a href="place_order.php" class="action-btn view-btn" style="margin-top: 15px;">
            <i class="fas fa-plus"></i> Place New Order
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Admin Dashboard
    
    // On page load, initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
      initializeEventListeners();
      
      // Add event listeners for view details buttons
      const viewButtons = document.querySelectorAll('.view-details');
      viewButtons.forEach(button => {
        button.addEventListener('click', function() {
          const orderId = this.getAttribute('data-order-id');
          const detailsDiv = document.getElementById('details-' + orderId);
          
          // Close all other open details
          document.querySelectorAll('.order-details').forEach(div => {
            if (div.id !== 'details-' + orderId && div.style.display === 'block') {
              div.style.display = 'none';
            }
          });
          
          // Toggle current details
          if (detailsDiv.style.display === 'block') {
            detailsDiv.style.display = 'none';
          } else {
            detailsDiv.style.display = 'block';
          }
        });
      });
      
      // Add event listeners for filter buttons
      document.getElementById('filterBtn').addEventListener('click', filterOrders);
      document.getElementById('resetBtn').addEventListener('click', resetFilters);
    });
    
    // Initialize all event listeners for the dashboard
    function initializeEventListeners() {
      // Toggle sidebar
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      const content = document.querySelector('.content-container');
      
      if (sidebarToggle && sidebar && content) {
        sidebarToggle.addEventListener('click', function() {
          if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
          } else {
            sidebar.classList.toggle('sidebar-collapsed');
            content.classList.toggle('content-full');
          }
        });
      }
      
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
    
    // Filter orders function
    function filterOrders() {
      const startDate = document.getElementById('startDate').value;
      const endDate = document.getElementById('endDate').value;
      const status = document.getElementById('statusFilter').value;
      
      // Here you would normally submit a form or make an AJAX request
      // For demo purposes, we're just showing an alert
      alert(`Filtering orders with: Start Date: ${startDate}, End Date: ${endDate}, Status: ${status || 'All'}`);
      
      // In a real implementation, you'd redirect or do an AJAX call:
      // window.location.href = `view_history.php?start=${startDate}&end=${endDate}&status=${status}`;
    }
    
    // Reset filters function
    function resetFilters() {
      document.getElementById('startDate').value = '';
      document.getElementById('endDate').value = '';
      document.getElementById('statusFilter').value = '';
      
      // Reload the page to show all orders
      // window.location.href = 'view_history.php';
    }
    
    // Print order function
    function printOrder(orderId) {
      // Here you would normally open a print-friendly page
      alert(`Printing order #${orderId}`);
      
      // In a real implementation:
      // window.open(`print_order.php?id=${orderId}`, '_blank');
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