<?php 
session_start();
require_once('../api/db.php');
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../../signin.php");
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Build query conditions
$conditions = ["approval_status = 'approved'"]; // Only show approved companies
$params = [];
$types = '';

if (!empty($search)) {
    $conditions[] = "(name LIKE ? OR email LIKE ? OR license_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

// Finalize the query
$query = "SELECT id, name, email, license_number, created_at, approval_status FROM company_users";
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Add sorting
switch ($sort) {
    case 'name_desc':
        $query .= " ORDER BY name DESC";
        break;
    case 'newest':
        $query .= " ORDER BY created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'name_asc':
    default:
        $query .= " ORDER BY name ASC";
        break;
}

// Execute query
$stmt = $conn->prepare($query);

// Bind parameters if any
if (!empty($params)) {
    // Create a reference array for binding
    $refParams = [];
    $refParams[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $refParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $refParams);
}

$stmt->execute();
$result = $stmt->get_result();
$companies = [];
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Pharmaceutical Companies - BridgeRx Connect</title>
    <link rel="stylesheet" href="../css/medical_nav.css">
    <style>
        :root {
            --primary: #4a6fa5;
            --primary-dark: #3d5d8a;
            --secondary: #6abfb0;
            --text-dark: #333;
            --text-light: #666;
            --background: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e0e0e0;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --pending: #6c757d;
        }

        body {
            background-color: var(--background);
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .content-container {
            padding: 20px;
            margin-left: 250px;
            transition: margin-left 0.3s;
        }

        @media (max-width: 768px) {
            .content-container {
                margin-left: 0;
            }
        }

        .sidebar-collapsed + .content-container {
            margin-left: 70px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 24px;
            color: var(--primary-dark);
            margin: 0;
        }

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        .filters-area {
            background-color: rgba(106, 191, 176, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 15px;
        }

        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 15px;
            background-color: white;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            border: none;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .companies-table {
            width: 100%;
            border-collapse: collapse;
        }

        .companies-table th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 12px 15px;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid var(--border);
        }

        .companies-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            color: var(--text-dark);
        }

        .companies-table tr:hover {
            background-color: rgba(74, 111, 165, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .status-approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-right: 5px;
        }

        .view-btn {
            background-color: var(--primary);
            color: white;
        }

        .view-btn:hover {
            background-color: var(--primary-dark);
        }

        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 50px;
            margin-bottom: 15px;
            color: var(--border);
        }

        .empty-state-text {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .empty-state-subtext {
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .pagination-btn {
            padding: 8px 15px;
            border: 1px solid var(--border);
            border-radius: 4px;
            background-color: white;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover, .pagination-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .count-display {
            text-align: right;
            margin-top: 10px;
            color: var(--text-light);
            font-size: 14px;
        }
        
        /* Modal styles for license view */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: var(--card-bg);
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .close-modal:hover {
            color: var(--text-dark);
        }
        
        .license-details {
            margin-top: 20px;
        }
        
        .license-field {
            margin-bottom: 15px;
        }
        
        .license-label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .license-value {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid var(--border);
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
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">🏢</span>
          <span class="menu-text">Suppliers</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item active" data-page="view_company.php">Browse Pharmaceutical Companies</a>
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
        <div class="page-header">
            <h1 class="page-title">Browse Approved Pharmaceutical Companies</h1>
        </div>
        

        
        <div class="card">
            <div class="card-header">
                Approved Pharmaceutical Companies
            </div>
            <div class="card-body">
            <div class="card-body filters-area">
                <form action="" method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Name, email or license number" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="sort" class="form-label">Sort By</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 0 0 auto;">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="view_company.php" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </div>
                <?php if (count($companies) > 0): ?>
                    <table class="companies-table">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Email</th>
                                <th>License Number</th>
                                <th>Date Added</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                                    <td><?php echo htmlspecialchars($company['email']); ?></td>
                                    <td><?php echo htmlspecialchars($company['license_number']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($company['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-approved">
                                            Approved
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="viewLicense('<?php echo $company['id']; ?>', '<?php echo htmlspecialchars($company['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($company['license_number'], ENT_QUOTES); ?>')" class="action-btn view-btn">View License</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="count-display">
                        Showing <?php echo count($companies); ?> approved companies
                    </div>
                    
                    <!-- Pagination (for future implementation) -->
                    <div class="pagination">
                        <button class="pagination-btn disabled">&laquo; Previous</button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn disabled">Next &raquo;</button>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <div class="empty-state-text">No approved companies found</div>
                        <div class="empty-state-subtext">Try adjusting your search terms</div>
                        <a href="view_company.php" class="btn btn-primary">Reset Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- License Modal -->
    <div id="licenseModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>Company License Details</h2>
            <div class="license-details">
                <div class="license-field">
                    <span class="license-label">Company Name:</span>
                    <div id="companyName" class="license-value"></div>
                </div>
                <div class="license-field">
                    <span class="license-label">License Number:</span>
                    <div id="licenseNumber" class="license-value"></div>
                </div>
                <div class="license-field">
                    <span class="license-label">License Status:</span>
                    <div class="license-value">
                        <span class="status-badge status-approved">Valid & Active</span>
                    </div>
                </div>
                <div class="license-field">
                    <span class="license-label">Verification:</span>
                    <div class="license-value">License verified with regulatory authorities</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
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
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const contentContainer = document.querySelector('.content-container');
            
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('sidebar-collapsed');
                    contentContainer.classList.toggle('sidebar-collapsed');
                }
            });
            
            // Table row highlighting on hover
            const tableRows = document.querySelectorAll('.companies-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseover', () => {
                    row.style.cursor = 'pointer';
                });
                
                row.addEventListener('click', (e) => {
                    // Don't trigger anything if clicked on the button
                    if (!e.target.classList.contains('action-btn')) {
                        // Get the view license button in this row and trigger its click
                        const viewBtn = row.querySelector('.view-btn');
                        if (viewBtn) viewBtn.click();
                    }
                });
            });
        });
        
        // Toggle dropdown (reusing from the existing code)
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
        
        // License modal functions
        function viewLicense(id, name, licenseNumber) {
            // Set the license data in the modal
            document.getElementById('companyName').textContent = name;
            document.getElementById('licenseNumber').textContent = licenseNumber;
            
            // Display the modal
            document.getElementById('licenseModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('licenseModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('licenseModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Logout function (reusing from the existing code)
        function logout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "../logout.php";
            }
        }
    </script>
</body>
</html>