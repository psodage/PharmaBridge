<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharma";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize filter variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$user_type = isset($_GET['user_type']) ? $_GET['user_type'] : 'all';
$registration_date = isset($_GET['registration_date']) ? $_GET['registration_date'] : 'all';

// Build the date filter condition
$date_condition = "";
if ($registration_date != 'all') {
    switch ($registration_date) {
        case 'today':
            $date_condition = "AND DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "AND WEEK(created_at) = WEEK(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
        case 'month':
            $date_condition = "AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
    }
}

// Build the search condition
$search_condition = "";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $search_condition = "AND (name LIKE '%$search%' OR email LIKE '%$search%' OR license_number LIKE '%$search%')";
}

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Query for medical users
$medical_query = "SELECT 
                    id, 
                    name, 
                    email, 
                    license_number, 
                    license_document, 
                    approval_status, 
                    created_at, 
                    'Medical Store' as user_type 
                  FROM 
                    medical_users 
                  WHERE 
                    approval_status = 'pending' 
                    $search_condition 
                    $date_condition";

// Query for company users
$company_query = "SELECT 
                    id, 
                    name, 
                    email, 
                    license_number, 
                    license_document, 
                    approval_status, 
                    created_at, 
                    'Pharmaceutical Company' as user_type 
                  FROM 
                    company_users 
                  WHERE 
                    approval_status = 'pending' 
                    $search_condition 
                    $date_condition";

// Combine the queries based on user type filter
if ($user_type == 'all') {
    $query = "($medical_query) UNION ($company_query) ORDER BY created_at DESC LIMIT $offset, $results_per_page";
    $count_query = "SELECT COUNT(*) as total FROM (($medical_query) UNION ($company_query)) as combined_results";
} elseif ($user_type == 'medical') {
    $query = "$medical_query ORDER BY created_at DESC LIMIT $offset, $results_per_page";
    $count_query = "SELECT COUNT(*) as total FROM ($medical_query) as medical_results";
} else {
    $query = "$company_query ORDER BY created_at DESC LIMIT $offset, $results_per_page";
    $count_query = "SELECT COUNT(*) as total FROM ($company_query) as company_results";
}

// Execute the main query
$result = $conn->query($query);

// Get total records for pagination
$count_result = $conn->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $results_per_page);

// Format date helper function
function formatDate($datetime) {
    $timestamp = strtotime($datetime);
    $today = strtotime(date('Y-m-d'));
    $yesterday = strtotime(date('Y-m-d', strtotime('-1 day')));
    
    if (date('Y-m-d', $timestamp) == date('Y-m-d', $today)) {
        return 'Today, ' . date('g:i A', $timestamp);
    } elseif (date('Y-m-d', $timestamp) == date('Y-m-d', $yesterday)) {
        return 'Yesterday, ' . date('g:i A', $timestamp);
    } else {
        return date('M d, Y, g:i A', $timestamp);
    }
}

// Function to generate a user ID from database ID
function generateUserID($id) {
    return 'USR' . str_pad($id, 3, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Status Management</title>
    <link rel="stylesheet" href="../../css/manage_status_admin.css">
    <link rel="stylesheet" href="../../css/admin.css">
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
        <button class="menu-btn" data-page="../home.php">
          <span class="menu-icon">📊</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">👥</span>
          <span class="menu-text">User Management</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown show">
          <a href="#" class="dropdown-item" data-page="manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item" data-page="manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="#" class="dropdown-item active" data-page="manage-user-status.php">Approve / Suspend / Remove Users</a>
        </div>
      </li>
     
    
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📦</span>
          <span class="menu-text">Product & Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../monitor-products.php">Monitor Listed Products</a>
         
          <a href="#" class="dropdown-item" data-page="../review-orders.php">Review Order Activities</a>
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
          <h1 class="content-title">User Status Management</h1>
      </div>

      <div class="tab-container">
          <div class="tabs">
              <button class="tab-btn active">Pending Approval</button>
          </div>
          
          <div class="tab-content">
              <form method="GET" action="" class="search-filters">
                  <div class="search-box">
                      <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                      <button type="submit" class="search-btn">🔍</button>
                  </div>
                  <div class="filters">
                      <select name="user_type">
                          <option value="all" <?php echo $user_type == 'all' ? 'selected' : ''; ?>>All User Types</option>
                          <option value="company" <?php echo $user_type == 'company' ? 'selected' : ''; ?>>Pharmaceutical Companies</option>
                          <option value="medical" <?php echo $user_type == 'medical' ? 'selected' : ''; ?>>Medical Stores</option>
                      </select>
                      <select name="registration_date">
                          <option value="all" <?php echo $registration_date == 'all' ? 'selected' : ''; ?>>All Registration Dates</option>
                          <option value="today" <?php echo $registration_date == 'today' ? 'selected' : ''; ?>>Today</option>
                          <option value="week" <?php echo $registration_date == 'week' ? 'selected' : ''; ?>>This Week</option>
                          <option value="month" <?php echo $registration_date == 'month' ? 'selected' : ''; ?>>This Month</option>
                      </select>
                      <button type="submit" class="filter-btn">Apply Filters</button>
                  </div>
              </form>
              
              <div class="data-table-container">
                  <table class="data-table">
                      <thead>
                          <tr>
                              <th>User ID</th>
                              <th>Name</th>
                              <th>User Type</th>
                              <th>Registration Date</th>
                              <th>Documents</th>
                              <th>Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php
                          if ($result && $result->num_rows > 0) {
                              while($row = $result->fetch_assoc()) {
                                  $has_documents = !empty($row['license_document']);
                                  ?>
                                  <tr>
                                      <td><?php echo generateUserID($row['id']); ?></td>
                                      <td><?php echo htmlspecialchars($row['name']); ?></td>
                                      <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                                      <td><?php echo formatDate($row['created_at']); ?></td>
                                      <td>
                                          <?php if ($has_documents): ?>
                                              <button class="view-docs-btn" onclick="viewDocuments('<?php echo $row['id']; ?>', '<?php echo $row['user_type']; ?>')">View Documents</button>
                                          <?php else: ?>
                                              <button class="warning-btn">Documents Missing</button>
                                          <?php endif; ?>
                                      </td>
                                      <td>
                                          <div class="action-buttons">
                                      
                                                  <button class="table-action approve-btn" title="Approve" onclick="approveUser('<?php echo $row['id']; ?>', '<?php echo $row['user_type']; ?>')">✓</button>
                                                  <button class="table-action reject-btn" title="Reject" onclick="rejectUser('<?php echo $row['id']; ?>', '<?php echo $row['user_type']; ?>')">✗</button>
            
                                  
                                          </div>
                                      </td>
                                  </tr>
                                  <?php
                              }
                          } else {
                              ?>
                              <tr>
                                  <td colspan="6" style="text-align: center; padding: 20px;">No pending users found</td>
                              </tr>
                              <?php
                          }
                          ?>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
    </div>
  </div>
  
  <script>
    // Main JavaScript for Admin Dashboard
    document.addEventListener('DOMContentLoaded', function() {
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
    
    // JavaScript functions for user actions
    function viewDocuments(userId, userType) {
      // Redirect to document viewer or open modal
      const table = userType === 'Medical Store' ? 'medical_users' : 'company_users';
      window.location.href = `view_documents.php?id=${userId}&table=${table}`;
    }
    
    function approveUser(userId, userType) {
      if (confirm("Are you sure you want to approve this user?")) {
        const table = userType === 'Medical Store' ? 'medical_users' : 'company_users';
        window.location.href = `update_status.php?id=${userId}&table=${table}&action=approved`;
      }
    }
    
    function rejectUser(userId, userType) {
      if (confirm("Are you sure you want to reject this user?")) {
        const table = userType === 'Medical Store' ? 'medical_users' : 'company_users';
        window.location.href = `update_status.php?id=${userId}&table=${table}&action=reject`;
      }
    }

    function requestDocuments(userId, userType) {
      if (confirm("Send document request to this user?")) {
        const table = userType === 'Medical Store' ? 'medical_users' : 'company_users';
        window.location.href = `request_documents.php?id=${userId}&table=${table}`;
      }
    }
  </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>