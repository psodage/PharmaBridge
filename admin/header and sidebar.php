<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
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
        <button class="menu-btn active" data-page="home.php">
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
          <a href="#" class="dropdown-item" data-page="manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item" data-page="manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="#" class="dropdown-item" data-page="manage-user-status.php">Approve / Suspend / Remove Users</a>
        </div>
      </li>
     
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💱</span>
          <span class="menu-text">Transactions Monitoring</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="view-transactions.php">View All Transactions</a>
          <a href="#" class="dropdown-item" data-page="track-payments.php">Track Payments & Orders</a>
          <a href="#" class="dropdown-item" data-page="resolve-disputes.php">Resolve Payment Disputes</a>
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
          <a href="#" class="dropdown-item" data-page="flag-products.php">Flag Fake or Unauthorized Products</a>
          <a href="#" class="dropdown-item" data-page="review-orders.php">Review Order Activities</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📈</span>
          <span class="menu-text">Reports & Inqurires</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="user-reports.php">User Activity Reports</a>
          <a href="#" class="dropdown-item" data-page="sales-reports.php">Sales & Revenue Reports</a>
          <a href="#" class="dropdown-item" data-page="trend-reports.php">Order & Product Trends</a>
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
          <a href="#" class="dropdown-item" data-page="system-config.php">System Configuration</a>
          <a href="#" class="dropdown-item" data-page="notification-settings.php">Manage Notifications</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🛡️</span>
          <span class="menu-text">Support & Security</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="handle-complaints.php">Handle Complaints & Disputes</a>
          <a href="#" class="dropdown-item" data-page="security-logs.php">Security Logs & Fraud Detection</a>
          <a href="#" class="dropdown-item" data-page="support-contact.php">Contact Support Team</a>
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
      <!-- Content will be loaded here -->
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
