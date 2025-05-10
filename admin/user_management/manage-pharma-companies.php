<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Pharmaceutical Companies Management</title>
  <link rel="stylesheet" href="../../css/manage_pharma_admin.css">
  <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>
  <?php
  require '../db.php';

  // Get status filter value
  $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

  // Base query
  $query = "SELECT id, name, email, license_number, license_document, approval_status, created_at 
            FROM company_users WHERE 1=1";

  // Add status filter to query
  if ($statusFilter != 'all') {
      $query .= " AND approval_status = '" . $conn->real_escape_string($statusFilter) . "'";
  }

  // Add search functionality if present
  if (isset($_GET['search']) && !empty($_GET['search'])) {
      $search = $conn->real_escape_string($_GET['search']);
      $query .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR license_number LIKE '%$search%')";
  }

  // Add ordering
  $query .= " ORDER BY id DESC";

  // Execute query
  $result = $conn->query($query);

  // Get status options for the select
  $statusQuery = "SELECT DISTINCT approval_status FROM company_users";
  $statusResult = $conn->query($statusQuery);
  $statusOptions = [];
  while ($row = $statusResult->fetch_assoc()) {
      $statusOptions[] = $row['approval_status'];
  }

  // Current page for pagination
  $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  $limit = 10; // Records per page
  $total_records = $result->num_rows;
  $total_pages = ceil($total_records / $limit);

  // Adjust query for pagination
  $offset = ($page - 1) * $limit;
  $query .= " LIMIT $offset, $limit";
  $result = $conn->query($query); // Re-execute with pagination
  ?>

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
          <a href="#" class="dropdown-item active" data-page="manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item" data-page="manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="#" class="dropdown-item" data-page="manage-user-status.php">Approve / Suspend / Remove Users</a>
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
        <h1 class="content-title">Pharmaceutical Companies Management</h1>
        <div class="content-actions">
          <button class="action-btn">Add New Company</button>
        </div>
      </div>

      <div class="search-filters">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search companies..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
          <button class="search-btn" onclick="applyFilters()">🔍</button>
        </div>
        <div class="filters">
          <select id="statusSelect" onchange="applyFilters()">
            <option value="all" <?= $statusFilter == 'all' ? 'selected' : '' ?>>All Status</option>
            <?php foreach ($statusOptions as $status): ?>
            <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter == $status ? 'selected' : '' ?>>
              <?= ucfirst(htmlspecialchars($status)) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="data-table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Company ID</th>
              <th>Company Name</th>
              <th>Email</th>
              <th>License Number</th>
              <th>Registration Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                // Determine status class
                $statusClass = '';
                switch (strtolower($row['approval_status'])) {
                  case 'approved':
                    $statusClass = 'status-active';
                    break;
                  case 'pending':
                    $statusClass = 'status-pending';
                    break;
                  case 'declined':
                    $statusClass = 'status-suspended';
                    break;
                }
                
                // Format date
                $formattedDate = date('d M Y', strtotime($row['created_at']));
            ?>
            <tr>
              <td>PHR<?= sprintf('%03d', $row['id']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['license_number']) ?></td>
              <td><?= $formattedDate ?></td>
              <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst($row['approval_status']) ?></span></td>
              <td>
                <div class="action-buttons">
                  <?php if ($row['approval_status'] == 'declined'): ?>
                    <button class="table-action delete-btn" title="Delete" onclick="deleteCompany(<?= $row['id'] ?>)">🗑️</button>
                  <?php else: ?>
                    <button class="table-action view-btn" title="View Details" onclick="viewCompany(<?= $row['id'] ?>)">👁️</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php 
              }
            } else {
            ?>
            <tr>
              <td colspan="7" style="text-align: center;">No companies found</td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
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
    
    // Pharmaceutical Companies Management specific functions
    function applyFilters() {
      const status = document.getElementById('statusSelect').value;
      const search = document.getElementById('searchInput').value.trim();
      
      let url = window.location.pathname + '?status=' + status;
      
      if (search) {
        url += '&search=' + encodeURIComponent(search);
      }
      
      window.location.href = url;
    }

    function viewCompany(id) {
      window.location.href = 'view_company.php?id=' + id;
    }

    function deleteCompany(id) {
      if (confirm('Are you sure you want to delete this company? This action cannot be undone.')) {
        // AJAX call to delete_company.php
        fetch('delete_company.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'id=' + id
        })
        .then(response => response.text())
        .then(data => {
          alert(data);
          location.reload();
        });
      }
    }

    function navigatePage(page) {
      // Preserve existing filters when navigating
      const status = document.getElementById('statusSelect').value;
      const search = document.getElementById('searchInput').value.trim();
      
      let url = window.location.pathname + '?page=' + page;
      
      if (status !== 'all') {
        url += '&status=' + status;
      }
      
      if (search) {
        url += '&search=' + encodeURIComponent(search);
      }
      
      window.location.href = url;
    }
  </script>
  <!-- Add this right before the closing </body> tag in manage-pharma-companies.php -->

<!-- Add Company Modal -->
<div id="addCompanyModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add New Pharmaceutical Company</h2>
      <span class="close">&times;</span>
    </div>
    <div class="modal-body">
      <form id="addCompanyForm" method="post" enctype="multipart/form-data" action="add_company.php">
        <div class="form-group">
          <label for="companyName">Company Name *</label>
          <input type="text" id="companyName" name="name" required>
        </div>
        
        <div class="form-group">
          <label for="companyEmail">Email Address *</label>
          <input type="email" id="companyEmail" name="email" required>
        </div>
        
        <div class="form-group">
          <label for="companyPassword">Password *</label>
          <input type="password" id="companyPassword" name="password" required>
        </div>
        
        <div class="form-group">
          <label for="licenseNumber">License Number *</label>
          <input type="text" id="licenseNumber" name="license_number" required>
        </div>
        
        <div class="form-group">
          <label for="licenseDocument">License Document *</label>
          <input type="file" id="licenseDocument" name="license_document" required>
          <small>Upload PDF, JPG, PNG, or GIF (Max 5MB)</small>
        </div>
        
        <div class="form-group">
          <label for="approvalStatus">Status</label>
          <select id="approvalStatus" name="approval_status">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="declined">Declined</option>
          </select>
        </div>
        
        <div class="form-actions">
          <button type="submit" class="action-btn">Save Company</button>
          <button type="button" class="action-btn cancel-btn" onclick="closeModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add this CSS to the existing CSS or include in your manage_pharma_admin.css file -->
<style>
  /* Modal Styles */
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
  }
  
  .modal-content {
    position: relative;
    background-color: #fff;
    margin: 50px auto;
    padding: 0;
    border-radius: 8px;
    width: 80%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    animation-name: animatetop;
    animation-duration: 0.4s;
  }
  
  @keyframes animatetop {
    from {top: -300px; opacity: 0}
    to {top: 0; opacity: 1}
  }
  
  .modal-header {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
  }
  
  .close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }
  
  .close:hover,
  .close:focus {
    color: #000;
    text-decoration: none;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
  }
  
  .form-group input,
  .form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
  }
  
  .form-group small {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 0.875rem;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 30px;
  }
  
  .cancel-btn {
    background-color: #6c757d;
  }
  
  .cancel-btn:hover {
    background-color: #5a6268;
  }
</style>

<!-- Add this JavaScript to the existing script section in manage-pharma-companies.php -->
<script>
  // Add the following to your existing script
  
  // Get the modal
  const modal = document.getElementById('addCompanyModal');
  
  // Get the button that opens the modal
  const addBtn = document.querySelector('.content-actions .action-btn');
  
  // Get the <span> element that closes the modal
  const closeBtn = document.querySelector('.modal .close');
  
  // When the user clicks the button, open the modal
  addBtn.onclick = function() {
    modal.style.display = "block";
  }
  
  // When the user clicks on <span> (x), close the modal
  closeBtn.onclick = function() {
    closeModal();
  }
  
  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
    if (event.target == modal) {
      closeModal();
    }
  }
  
  // Function to close the modal
  function closeModal() {
    modal.style.display = "none";
    document.getElementById('addCompanyForm').reset();
  }
</script>
<?php
// Display success or error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<!-- Add this CSS to your stylesheet -->
<style>
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
</style>
</body>
</html>

<?php $conn->close(); ?>