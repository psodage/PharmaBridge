<?php 
session_start();
require_once('../api/db.php');
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../../signin.php");
    exit();
}

// Define variables to store messages and errors
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_message'])) {
    // Get user input and sanitize
    $subject = trim(htmlspecialchars($_POST['subject']));
    $message = trim(htmlspecialchars($_POST['message']));
    $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 3; // Default medium priority
    
    // Basic validation
    if (empty($subject) || empty($message)) {
        $error_message = "Both subject and message are required.";
    } else {
        // Prepare the SQL statement
        $sql = "INSERT INTO admin_messages_company (sender_id, sender_name, subject, message, priority, status, date_sent) 
                VALUES (?, ?, ?, ?, ?, 'unread', NOW())";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Bind parameters and execute
            $stmt->bind_param("isssi", 
                $_SESSION['user_id'], 
                $_SESSION['user_name'], 
                $subject, 
                $message, 
                $priority
            );
            
            if ($stmt->execute()) {
                $success_message = "Your message has been sent to the administrators. They will respond as soon as possible.";
                // Clear form data on success
                $subject = $message = '';
                $priority = 3;
            } else {
                $error_message = "Failed to send message: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Database error: " . $conn->error;
        }
    }
}

// Fetch previous messages
$previous_messages = [];
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT am.*, ar.response, ar.date_sent as response_date 
            FROM admin_messages_company am
            LEFT JOIN admin_responses_company ar ON am.message_id = ar.message_id
            WHERE am.sender_id = ?
            ORDER BY am.date_sent DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $previous_messages[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Administrators - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/home_company.css">
  <style>
    .content-container {
      padding: 20px;
      margin-top:20px;
      margin-left: 250px;
      transition: margin-left 0.3s;
    }
    
    @media (max-width: 768px) {
      .content-container {
        margin-left: 0;
      }
    }
    
    .sidebar-collapsed + .content-container {
      margin-left: 60px;
    }
    
    .card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .card-title {
      font-size: 1.4rem;
      color: #333;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    
    input[type="text"], textarea, select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: inherit;
      font-size: 14px;
    }
    
    textarea {
      min-height: 150px;
      resize: vertical;
    }
    
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: background 0.3s;
    }
    
    .btn-primary {
      background-color: #4a90e2;
      color: white;
    }
    
    .btn-primary:hover {
      background-color: #3a7bc8;
    }
    
    .alert {
      padding: 12px;
      border-radius: 4px;
      margin-bottom: 20px;
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
    
    .message-history {
      margin-top: 30px;
    }
    
    .message-item {
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      background-color: #f9f9f9;
    }
    
    .message-header {
      display: flex;
      justify-content: space-between;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
      margin-bottom: 10px;
      font-size: 14px;
    }
    
    .message-subject {
      font-weight: bold;
      color: #333;
    }
    
    .message-date {
      color: #777;
    }
    
    .message-content {
      margin-bottom: 15px;
      line-height: 1.5;
    }
    
    .message-response {
      background-color: #e8f4fd;
      border-radius: 8px;
      padding: 15px;
      margin-top: 10px;
    }
    
    .message-status {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .status-unread {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-read {
      background-color: #d1ecf1;
      color: #0c5460;
    }
    
    .status-responded {
      background-color: #d4edda;
      color: #155724;
    }
    
    .priority-high {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .priority-medium {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .priority-low {
      background-color: #d1ecf1;
      color: #0c5460;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="logo-icon">🏥</span> BridgeRx Supply Hub
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
        <button class="menu-btn " data-page="company.php">
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
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">🔍</span>
          <span class="menu-text">Support</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item active" data-page="contact_admin.php">Contact Admin</a>
       
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
    <h1>Contact Administrators</h1>
    
    <!-- Success/Error Messages -->
    <?php if(!empty($success_message)): ?>
    <div class="alert alert-success">
      <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if(!empty($error_message)): ?>
    <div class="alert alert-danger">
      <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <!-- Contact Form -->
    <div class="card">
      <h2 class="card-title">Send Message to Administrators</h2>
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
          <label for="subject">Subject:</label>
          <input type="text" id="subject" name="subject" value="<?php echo isset($subject) ? $subject : ''; ?>" required>
        </div>
        
        <div class="form-group">
          <label for="priority">Priority:</label>
          <select id="priority" name="priority">
            <option value="1" <?php echo (isset($priority) && $priority == 1) ? 'selected' : ''; ?>>High</option>
            <option value="2" <?php echo (isset($priority) && $priority == 2) ? 'selected' : ''; ?>>Medium</option>
            <option value="3" <?php echo (!isset($priority) || $priority == 3) ? 'selected' : ''; ?>>Low</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="message">Message:</label>
          <textarea id="message" name="message" required><?php echo isset($message) ? $message : ''; ?></textarea>
        </div>
        
        <button type="submit" name="submit_message" class="btn btn-primary">Send Message</button>
      </form>
    </div>
    
    <!-- Message History -->
    <div class="message-history">
      <h2 class="card-title">Message History</h2>
      
      <?php if(empty($previous_messages)): ?>
        <p>You haven't sent any messages to administrators yet.</p>
      <?php else: ?>
        <?php foreach($previous_messages as $msg): ?>
          <div class="message-item">
            <div class="message-header">
              <span class="message-subject"><?php echo $msg['subject']; ?></span>
              <span class="message-date">Sent: <?php echo date('M d, Y h:i A', strtotime($msg['date_sent'])); ?></span>
            </div>
            
            <div>
              <?php 
                $statusClass = '';
                switch($msg['status']) {
                  case 'unread':
                    $statusClass = 'status-unread';
                    break;
                  case 'read':
                    $statusClass = 'status-read';
                    break;
                  case 'responded':
                    $statusClass = 'status-responded';
                    break;
                }
                
                $priorityClass = '';
                switch($msg['priority']) {
                  case 1:
                    $priorityClass = 'priority-high';
                    $priorityText = 'High Priority';
                    break;
                  case 2:
                    $priorityClass = 'priority-medium';
                    $priorityText = 'Medium Priority';
                    break;
                  case 3:
                    $priorityClass = 'priority-low';
                    $priorityText = 'Low Priority';
                    break;
                }
              ?>
              <span class="message-status <?php echo $statusClass; ?>">
                <?php echo ucfirst($msg['status']); ?>
              </span>
              <span class="message-status <?php echo $priorityClass; ?>">
                <?php echo $priorityText; ?>
              </span>
            </div>
            
            <div class="message-content">
              <?php echo nl2br($msg['message']); ?>
            </div>
            
            <?php if(!empty($msg['response'])): ?>
              <div class="message-response">
                <div class="message-header">
                  <span class="message-subject">Admin Response</span>
                  <span class="message-date">Responded: <?php echo date('M d, Y h:i A', strtotime($msg['response_date'])); ?></span>
                </div>
                <div class="message-content">
                  <?php echo nl2br($msg['response']); ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Admin Dashboard
    
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