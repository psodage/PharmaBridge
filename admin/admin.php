<?php
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

// Get total users (pharmaceutical companies + medical stores)
$total_users_query = "SELECT 
    (SELECT COUNT(*) FROM company_users) + 
    (SELECT COUNT(*) FROM medical_users) AS total_users,
    (SELECT COUNT(*) FROM company_users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) + 
    (SELECT COUNT(*) FROM medical_users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_users_this_week";
$total_users_result = $conn->query($total_users_query);
$users_data = $total_users_result->fetch_assoc();
$total_users = $users_data['total_users'];
$new_users_this_week = $users_data['new_users_this_week'];

// Get transactions data (orders)
$transactions_query = "SELECT 
    COUNT(*) AS total_transactions,
    SUM(CASE WHEN order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS transactions_this_week
    FROM orders";
$transactions_result = $conn->query($transactions_query);
$transactions_data = $transactions_result->fetch_assoc();
$total_transactions = $transactions_data['total_transactions'];
$transactions_this_week = $transactions_data['transactions_this_week'];

// Get pending verifications (users with pending approval status)
$pending_verifications_query = "SELECT 
    (SELECT COUNT(*) FROM company_users WHERE approval_status = 'pending') + 
    (SELECT COUNT(*) FROM medical_users WHERE approval_status = 'pending') AS pending_verifications,
    (SELECT COUNT(*) FROM company_users WHERE approval_status = 'pending' AND created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)) + 
    (SELECT COUNT(*) FROM medical_users WHERE approval_status = 'pending' AND created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)) AS urgent_verifications";
$pending_verifications_result = $conn->query($pending_verifications_query);
$verifications_data = $pending_verifications_result->fetch_assoc();
$pending_verifications = $verifications_data['pending_verifications'];
$urgent_verifications = $verifications_data['urgent_verifications'];

// For alerts, we can consider products that are about to expire soon
$alerts_query = "SELECT 
    COUNT(*) AS total_alerts,
    SUM(CASE WHEN expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS critical_alerts
    FROM products";
$alerts_result = $conn->query($alerts_query);
$alerts_data = $alerts_result->fetch_assoc();
$total_alerts = $alerts_data['total_alerts'];
$critical_alerts = $alerts_data['critical_alerts'];

// Get recent activities
// This query combines recent activities from different tables
$recent_activities_query = "
    (SELECT 
        'new_registration' AS activity_type,
        'Company' AS user_type,
        name AS entity_name,
        created_at AS activity_time,
        id
    FROM company_users
    WHERE approval_status = 'pending'
    ORDER BY created_at DESC
    LIMIT 3)
    
    UNION
    
    (SELECT 
        'new_registration' AS activity_type,
        'Medical Store' AS user_type,
        name AS entity_name,
        created_at AS activity_time,
        id
    FROM medical_users
    WHERE approval_status = 'pending'
    ORDER BY created_at DESC
    LIMIT 3)
    
    UNION
    
    (SELECT 
        'new_order' AS activity_type,
        'Order' AS user_type,
        CONCAT('Order #', id) AS entity_name,
        created_at AS activity_time,
        id
    FROM orders
    ORDER BY created_at DESC
    LIMIT 3)
    
    ORDER BY activity_time DESC
    LIMIT 3";
$recent_activities_result = $conn->query($recent_activities_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/home_admin.css">
  <link rel="stylesheet" href="../css/admin.css">
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
        <span class="alert-badge"><?php echo $pending_verifications; ?></span>
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
        <button class="menu-btn active" data-page="home.php">
          <span class="menu-icon">📊</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn " onclick="toggleDropdown(this)">
          <span class="menu-icon">👥</span>
          <span class="menu-text">User Management</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown show">
          <a href="#" class="dropdown-item " data-page="user_management/manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item " data-page="user_management/manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="#" class="dropdown-item" data-page="user_management/manage-user-status.php">Approve / Suspend / Remove Users</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📦</span>
          <span class="menu-text">Product & Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="monitor-products.php">Monitor Listed Products</a>
         
          <a href="#" class="dropdown-item" data-page="review-orders.php">Review Order Activities</a>
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
  
  <div class="content" id="content">
    <div id="content-container">
      <div class="content-header">
        <h1 class="content-title">Admin Dashboard</h1>
      </div>
      
      <div class="dashboard-stats">
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Total Users</h3>
            <div class="stat-icon stat-users">👥</div>
          </div>
          <div class="stat-value"><?php echo number_format($total_users); ?></div>
          <div class="stat-change" style="color: #27ae60;">+<?php echo $new_users_this_week; ?> this week</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Transactions</h3>
            <div class="stat-icon stat-transactions">💱</div>
          </div>
          <div class="stat-value"><?php echo number_format($total_transactions); ?></div>
          <div class="stat-change" style="color: #27ae60;">+<?php echo $transactions_this_week; ?> this week</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Pending Verifications</h3>
            <div class="stat-icon stat-verifications">🔐</div>
          </div>
          <div class="stat-value"><?php echo $pending_verifications; ?></div>
          <div class="stat-change" style="color: #e67e22;"><?php echo $urgent_verifications; ?> urgent</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-header">
            <h3 class="stat-title">Alerts</h3>
            <div class="stat-icon stat-alerts">⚠️</div>
          </div>
          <div class="stat-value"><?php echo $total_alerts; ?></div>
          <div class="stat-change" style="color: #e74c3c;"><?php echo $critical_alerts; ?> critical</div>
        </div>
      </div>
      
      <div class="recent-activity">
        <h2>Recent Activity</h2>
        <div class="activity-list">
          <div class="activity-item">
            <div class="activity-icon">🆕</div>
            <div class="activity-details">
              <div class="activity-title">New Medical Store Registration</div>
              <div class="activity-time">10 minutes ago</div>
              <div class="activity-description">MediCare Pharmacy submitted registration documents</div>
            </div>
           <a href="user_management/manage-pharma-companies.php"> <button class="activity-action">Review</button></a>
          </div>
          
          <div class="activity-item">
            <div class="activity-icon">⚠️</div>
            <div class="activity-details">
              <div class="activity-title">Suspicious Order Flagged</div>
              <div class="activity-time">45 minutes ago</div>
              <div class="activity-description">Multiple large orders from new account detected</div>
            </div>
        <a href="review-orders.php">  <button class="activity-action">Investigate</button></a>
          </div>
          
          <div class="activity-item">
            <div class="activity-icon">✅</div>
            <div class="activity-details">
              <div class="activity-title">License Verification Completed</div>
              <div class="activity-time">2 hours ago</div>
              <div class="activity-description">HealthPlus Distributor's license verified successfully</div>
            </div>
            <a href="user_management/manage-medical-stores.php"><button class="activity-action">View</button></a>
          </div>
        </div>
      </div>
    </div>
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