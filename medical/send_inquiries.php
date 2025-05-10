<?php 
session_start();
require_once('../api/db.php');
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../../signin.php");
    exit();
}

// Get the medical user ID
$user_id = $_SESSION['user_id'];

// Get suppliers that the medical facility has ordered from
$suppliers_query = "
    SELECT DISTINCT cu.id, cu.name
    FROM company_users cu
    JOIN products p ON cu.id = p.company_id
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = ?
    ORDER BY cu.name ASC
";

$stmt = $conn->prepare($suppliers_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$suppliers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Inquiries to Suppliers - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">
  <style>
    .content {
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s;
    }
    
    .sidebar-collapsed + .content {
      margin-left: 70px;
    }
    
    .card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .card-title {
      font-size: 1.5rem;
      margin-bottom: 20px;
      color: #333;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    
    select, textarea, input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    
    textarea {
      min-height: 150px;
      resize: vertical;
    }
    
    .btn {
      background-color: #4a90e2;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .btn:hover {
      background-color: #3a7bc8;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .inquiries-history {
      margin-top: 40px;
    }
    
    .inquiry-item {
      padding: 15px;
      border: 1px solid #eee;
      border-radius: 4px;
      margin-bottom: 10px;
      background-color: #f9f9f9;
    }
    
    .inquiry-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    
    .inquiry-supplier {
      font-weight: bold;
    }
    
    .inquiry-date {
      color: #777;
      font-size: 0.9rem;
    }
    
    .inquiry-subject {
      font-weight: 500;
      margin-bottom: 5px;
    }
    
    .inquiry-message {
      margin-bottom: 10px;
      padding: 10px;
      background-color: white;
      border-radius: 4px;
    }
    
    .inquiry-reply {
      background-color: #e6f7ff;
      padding: 10px;
      border-radius: 4px;
      margin-top: 10px;
    }
    
    .no-suppliers {
      padding: 40px;
      text-align: center;
      background-color: #f5f5f5;
      border-radius: 8px;
      color: #666;
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
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Communication</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item active" data-page="send_inquiries.php">Send Inquiries <br>to Suppliers</a>
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
    <div class="card">
      <h2 class="card-title">Send Inquiry to Supplier</h2>
      
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          Your inquiry has been sent successfully. The supplier will respond to your inquiry soon.
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
          There was an error sending your inquiry. Please try again.
        </div>
      <?php endif; ?>
      
      <?php if (empty($suppliers)): ?>
        <div class="no-suppliers">
          <h3>No suppliers available</h3>
          <p>You can only send inquiries to suppliers from whom you have previously ordered products.</p>
          <p>Place an order first to establish a business relationship with a supplier.</p>
          <a href="view_product.php" class="btn">Browse Products</a>
        </div>
      <?php else: ?>
        <form action="process_inquiry.php" method="post">
          <div class="form-group">
            <label for="supplier_id">Select Supplier</label>
            <select name="supplier_id" id="supplier_id" required>
              <option value="">-- Select a supplier --</option>
              <?php foreach ($suppliers as $supplier): ?>
                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" required placeholder="e.g., Product availability inquiry">
          </div>
          
          <div class="form-group">
            <label for="message">Your Message</label>
            <textarea id="message" name="message" required placeholder="Type your inquiry here..."></textarea>
          </div>
          
          <div class="form-group">
            <button type="submit" class="btn">Send Inquiry</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
    
    <!-- Previous Inquiries Section -->
    <div class="card inquiries-history">
      <h2 class="card-title">Previous Inquiries</h2>
      
      <?php
      // Get previous inquiries
      $inquiries_query = "
          SELECT i.*, cu.name as supplier_name
          FROM inquiries i
          JOIN company_users cu ON i.supplier_id = cu.id
          WHERE i.medical_id = ?
          ORDER BY i.created_at DESC
      ";
      
      $stmt = $conn->prepare($inquiries_query);
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $inquiries = $result->fetch_all(MYSQLI_ASSOC);
      ?>
      
      <?php if (empty($inquiries)): ?>
        <p>You haven't sent any inquiries yet.</p>
      <?php else: ?>
        <?php foreach ($inquiries as $inquiry): ?>
          <div class="inquiry-item">
            <div class="inquiry-header">
              <div class="inquiry-supplier">To: <?php echo htmlspecialchars($inquiry['supplier_name']); ?></div>
              <div class="inquiry-date"><?php echo date("M d, Y h:i A", strtotime($inquiry['created_at'])); ?></div>
            </div>
            <div class="inquiry-subject"><?php echo htmlspecialchars($inquiry['subject']); ?></div>
            <div class="inquiry-message"><?php echo nl2br(htmlspecialchars($inquiry['message'])); ?></div>
            
            <?php if (!empty($inquiry['reply'])): ?>
              <div class="inquiry-reply">
                <strong>Reply:</strong> (<?php echo date("M d, Y h:i A", strtotime($inquiry['reply_at'])); ?>)<br>
                <?php echo nl2br(htmlspecialchars($inquiry['reply'])); ?>
              </div>
            <?php else: ?>
              <p><em>Awaiting response from supplier</em></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Dashboard
    document.addEventListener('DOMContentLoaded', function() {
      initializeEventListeners();
    });
    
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