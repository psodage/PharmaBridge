<?php 
session_start();
require_once '../../api/db.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../../signin.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Products - Pharmacy Management System</title>
  <link rel="stylesheet" href="../../css/home_company.css">
  <link rel="stylesheet" href="../../css/manage_products_company.css">

</head>
<body>
<?php

// Get company ID of logged in user
$company_id = $_SESSION['user_id']; // Assuming user_id is the company_id for company accounts

// Get pagination parameters
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 8; // Show 8 products per page in grid view
$offset = ($current_page - 1) * $items_per_page;

// Handle search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Build the base query
$query = "SELECT * FROM products WHERE company_id = ?";
$params = [$company_id];
$types = "i";

// Add search condition if provided
if (!empty($search)) {
    $query .= " AND (product_name LIKE ? OR generic_name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Add category filter if provided
if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

// Add stock filter if provided
if (!empty($stock_filter)) {
    if ($stock_filter == 'low') {
        $query .= " AND stock_quantity <= 10 AND stock_quantity > 0";
    } else if ($stock_filter == 'out') {
        $query .= " AND stock_quantity = 0";
    }
}

// Count total matching records for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*)", $query);
$stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_rows = mysqli_fetch_row($count_result)[0];
$total_pages = ceil($total_rows / $items_per_page);

// Add pagination to the main query
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Fetch products
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all categories for the filter dropdown
$category_query = "SELECT DISTINCT category FROM products WHERE company_id = ?";
$cat_stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($cat_stmt, "i", $company_id);
mysqli_stmt_execute($cat_stmt);
$cat_result = mysqli_stmt_get_result($cat_stmt);
$categories = [];
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row['category'];
}
?>

  
<div class="header">
    <div class="logo">
      <span class="logo-icon">💊</span> BridgeRx Supply Hub
    <div class="header-controls">
      <button class="header-btn" id="sidebarToggle" title="Toggle Sidebar">☰</button>
 
      
      <div class="account-info">
      <span>Welcome, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest'; ?></span>
      <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'M'; ?></div>
    </div>
    </div>
  </div>
  
  <div class="sidebar" id="sidebar">
    <ul class="menu">
      <li class="menu-item">
        <button class="menu-btn " data-page="../company.php">
          <span class="menu-icon">🏠</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">📦</span>
          <span class="menu-text">Products</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="add_products.php">Add New <br>Products</a>
          <a href="#" class="dropdown-item active" data-page="manage_products.php">Manage <br>Products</a>
          <a href="#" class="dropdown-item" data-page="view_products.php">View Product <br>Listings</a>
        </div>
      </li>
     
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🛒</span>
          <span class="menu-text">Orders</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../order/view_orders.php">View <br>Orders</a>
          <a href="#" class="dropdown-item" data-page="../order/update_order_status.php">Update Order <br>Status</a>
         
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Inquiries</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../view_inquires.php">View Inquiries</a>
          <a href="#" class="dropdown-item" data-page="../inquires.php">Respond to <br>Inquiries</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Settings</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../profile.php">Profile <br>Management</a>
        

        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🔍</span>
          <span class="menu-text">Support</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="../contact_admin.php">Contact Admin</a>
       
         
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
  

     
  </div>
      <!-- Search and Filters - Vibrant Styling -->
      <form method="GET" action="" class="search-filter">
        <div class="form-title">Search and Filter Products</div>
        
        <input type="text" name="search" placeholder="Search products by name, generic name or description..." value="<?php echo htmlspecialchars($search); ?>">
        
        <select name="category">
          <option value="">All Categories</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($category); ?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <select name="stock">
          <option value="">All Stock Levels</option>
          <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock (≤10)</option>
          <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
        </select>
        
        <div class="action-buttons">
          <button type="submit" class="btn-gradient">Apply Filters</button>
          <a href="manage_products.php" class="btn-clear">Reset</a>
          <a href="../add_products.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add New Product
          </a>
        </div>
      </form>
      
      <!-- Products Grid -->
      <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="product-grid">
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php
              // Check if product is expired or expiring soon
              $today = new DateTime();
              $expiry_date = new DateTime($row['expiry_date']);
              $diff = $today->diff($expiry_date);
              $days_until_expiry = $expiry_date > $today ? $diff->days : -$diff->days;
              
              $expired = $days_until_expiry < 0;
              $expiring_soon = $days_until_expiry >= 0 && $days_until_expiry <= 90; // 3 months
              
              // Stock level class
              $stock_class = '';
              if ($row['stock_quantity'] <= 0) {
                  $stock_class = 'out';
              } elseif ($row['stock_quantity'] <= 10) {
                  $stock_class = 'low';
              }
              
              // Expiry class
              $expiry_class = '';
              if ($expired) {
                  $expiry_class = 'expired';
              } elseif ($expiring_soon) {
                  $expiry_class = 'warning';
              }
            ?>
            <div class="product-card">
              <?php if (!empty($row['product_image'])): ?>
                <img src="../../uploads/products/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>" class="product-image">
              <?php else: ?>
                <div class="product-image-placeholder">No Image Available</div>
              <?php endif; ?>
              
              <div class="product-details">
                <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                <p><?php echo htmlspecialchars($row['generic_name']); ?> - <?php echo htmlspecialchars($row['category']); ?></p>
                
                <div class="product-meta">
                  <span class="product-price">$<?php echo htmlspecialchars($row['price']); ?></span>
                  <span class="product-stock <?php echo $stock_class; ?>">
                    <?php 
                      if ($row['stock_quantity'] <= 0) {
                          echo 'Out of Stock';
                      } elseif ($row['stock_quantity'] <= 10) {
                          echo 'Low Stock: ' . $row['stock_quantity'];
                      } else {
                          echo 'In Stock: ' . $row['stock_quantity'];
                      }
                    ?>
                  </span>
                </div>
                
                <div class="product-expiry <?php echo $expiry_class; ?>">
                  <?php 
                    if ($expired) {
                        echo 'Expired: ' . htmlspecialchars($row['expiry_date']);
                    } elseif ($expiring_soon) {
                        echo 'Expiring Soon: ' . htmlspecialchars($row['expiry_date']);
                    } else {
                        echo 'Expiry: ' . htmlspecialchars($row['expiry_date']);
                    }
                  ?>
                </div>
                
                <div class="product-actions">
                  <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">Edit</a>
                  <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $row['id']; ?>)" class="action-btn delete-btn">Delete</a>
                  <a href="javascript:void(0);" onclick="viewProductDetails(<?php echo $row['id']; ?>)" class="action-btn view-btn">View</a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="pagination">
            <?php if ($current_page > 1): ?>
              <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&stock=<?php echo urlencode($stock_filter); ?>">&laquo;</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&stock=<?php echo urlencode($stock_filter); ?>" <?php echo $i == $current_page ? 'class="active"' : ''; ?>>
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
              <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&stock=<?php echo urlencode($stock_filter); ?>">&raquo;</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        
      <?php else: ?>
        <div class="empty-state">
          <p>No products found matching your criteria.</p>
          <a href="../add_products.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add a New Product
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
    <!-- Add this HTML for the modal at the end of the page, just before the closing body tag -->
<div id="productModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <div id="modalContent">
      <!-- Product details will be loaded here -->
      <div class="loading-spinner">Loading...</div>
    </div>
  </div>
</div>

<!-- Add this CSS to your manage_product_company.css file -->
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
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s;
  }
  
  .modal-content {
    position: relative;
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    width: 80%;
    max-width: 800px;
    animation: slideIn 0.3s;
  }
  
  .close-modal {
    position: absolute;
    top: 15px;
    right: 20px;
    color: #888;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
  }
  
  .close-modal:hover {
    color: #333;
  }
  
  /* Product Detail Styles */
  .product-detail-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
  }
  
  .product-detail-image {
    flex: 0 0 300px;
    max-width: 300px;
  }
  
  .product-detail-image img {
    width: 100%;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  
  .product-detail-info {
    flex: 1;
    min-width: 300px;
  }
  
  .product-detail-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
  }
  
  .product-detail-header h2 {
    font-size: 24px;
    margin: 0 0 5px 0;
    color: #333;
  }
  
  .product-detail-header p {
    font-size: 16px;
    color: #666;
    margin: 0;
  }
  
  .product-detail-meta {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }
  
  .detail-item {
    padding: 12px;
    background: #f9f9f9;
    border-radius: 6px;
  }
  
  .detail-item .label {
    font-weight: 600;
    color: #555;
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
  }
  
  .detail-item .value {
    font-size: 16px;
    color: #333;
  }
  
  .detail-item .value.out, .detail-item .value.expired {
    color: #d9534f;
    font-weight: 600;
  }
  
  .detail-item .value.low, .detail-item .value.warning {
    color: #f0ad4e;
    font-weight: 600;
  }
  
  .product-description {
    margin-top: 20px;
    line-height: 1.6;
  }
  
  .loading-spinner {
    text-align: center;
    padding: 30px;
    color: #666;
    font-style: italic;
  }

</style>

<!-- Add this JavaScript at the end of your script section -->
<script>
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
  // Complete updated JavaScript for sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
  // Initialize sidebar toggle button
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
    });
  }
  
  // Initialize all menu buttons with dropdown functionality
  document.querySelectorAll('.menu-btn').forEach(button => {
    // Check if this button has a dropdown
    const hasDropdown = button.querySelector('.dropdown-indicator');
    
    if (hasDropdown) {
      button.addEventListener('click', function() {
        // Find the dropdown
        const dropdown = this.nextElementSibling;
        
        // Toggle the dropdown visibility
        if (dropdown.style.maxHeight) {
          dropdown.style.maxHeight = null;
          dropdown.style.opacity = "0";
          dropdown.style.visibility = "hidden";
          this.querySelector('.dropdown-indicator').textContent = "▼";
        } else {
          dropdown.style.maxHeight = dropdown.scrollHeight + "px";
          dropdown.style.opacity = "1";
          dropdown.style.visibility = "visible";
          this.querySelector('.dropdown-indicator').textContent = "▲";
        }
      });
    }
  });
  
  // Add logout function
  window.logout = function() {
    window.location.href = '../../logout.php';
  };
  
  // Make sure active menu items are visible
  document.querySelectorAll('.dropdown-item.active').forEach(item => {
    const dropdown = item.closest('.dropdown');
    if (dropdown) {
      dropdown.style.maxHeight = dropdown.scrollHeight + "px";
      dropdown.style.opacity = "1";
      dropdown.style.visibility = "visible";
      
      const button = dropdown.previousElementSibling;
      if (button && button.querySelector('.dropdown-indicator')) {
        button.querySelector('.dropdown-indicator').textContent = "▲";
      }
    }
  });
});

// Define toggleDropdown function for backward compatibility
function toggleDropdown(button) {
  // Find the dropdown
  const dropdown = button.nextElementSibling;
  
  // Toggle the dropdown visibility
  if (dropdown.style.maxHeight) {
    dropdown.style.maxHeight = null;
    dropdown.style.opacity = "0";
    dropdown.style.visibility = "hidden";
    button.querySelector('.dropdown-indicator').textContent = "▼";
  } else {
    dropdown.style.maxHeight = dropdown.scrollHeight + "px";
    dropdown.style.opacity = "1";
    dropdown.style.visibility = "visible";
    button.querySelector('.dropdown-indicator').textContent = "▲";
  }
}
  // Get the modal element
  const modal = document.getElementById('productModal');
  const modalContent = document.getElementById('modalContent');
  const closeBtn = document.querySelector('.close-modal');
  
  // Close the modal when clicking the close button
  closeBtn.onclick = function() {
    modal.style.display = "none";
  }
  
  // Close the modal when clicking outside of it
  window.onclick = function(event) {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }
  
  // Function to open the modal and load product details
  function viewProductDetails(productId) {
    // Show the modal
    modal.style.display = "block";
    modalContent.innerHTML = '<div class="loading-spinner">Loading product details...</div>';
    
    // Fetch product details using AJAX
    fetch('get_product_details.php?id=' + productId)
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        // Create product details HTML
        let stockClass = '';
        if (data.stock_quantity <= 0) {
          stockClass = 'out';
        } else if (data.stock_quantity <= 10) {
          stockClass = 'low';
        }
        
        // Check expiry status
        const today = new Date();
        const expiryDate = new Date(data.expiry_date);
        const daysDiff = Math.floor((expiryDate - today) / (1000 * 60 * 60 * 24));
        
        let expiryClass = '';
        let expiryText = 'Expires on ' + data.expiry_date;
        
        if (daysDiff < 0) {
          expiryClass = 'expired';
          expiryText = 'Expired on ' + data.expiry_date;
        } else if (daysDiff <= 90) {
          expiryClass = 'warning';
          expiryText = 'Expiring soon: ' + data.expiry_date;
        }
        
        // Format stock text
        let stockText = 'In Stock: ' + data.stock_quantity;
        if (data.stock_quantity <= 0) {
          stockText = 'Out of Stock';
        } else if (data.stock_quantity <= 10) {
          stockText = 'Low Stock: ' + data.stock_quantity;
        }
        
        // Build HTML for modal content
        let html = `
          <div class="product-detail-container">
            <div class="product-detail-image">
              ${data.product_image ? 
                `<img src="../../uploads/products/${data.product_image}" alt="${data.product_name}">` : 
                `<div class="product-image-placeholder">No Image Available</div>`
              }
            </div>
            <div class="product-detail-info">
              <div class="product-detail-header">
                <h2>${data.product_name}</h2>
                <p>${data.generic_name} - ${data.category}</p>
              </div>
              
              <div class="product-detail-meta">
                <div class="detail-item">
                  <span class="label">Price</span>
                  <span class="value">$${data.price}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Stock Level</span>
                  <span class="value ${stockClass}">${stockText}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Expiry</span>
                  <span class="value ${expiryClass}">${expiryText}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Product Code</span>
                  <span class="value">${data.batch_number || 'N/A'}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Manufacturer</span>
                  <span class="value">${data.manufacturer || 'N/A'}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Dosage</span>
                  <span class="value">${data.dosage_form || 'N/A'}</span>
                </div>
              </div>
              
              <div class="product-description">
                <h3>Description</h3>
                <p>${data.description || 'No description available.'}</p>
              </div>
              
              <div class="product-actions" style="margin-top: 20px;">
                <a href="edit_product.php?id=${data.id}" class="action-btn edit-btn">Edit Product</a>
                <button onclick="confirmDelete(${data.id})" class="action-btn delete-btn">Delete Product</button>
              </div>
            </div>
          </div>
        `;
        
        modalContent.innerHTML = html;
      })
      .catch(error => {
        modalContent.innerHTML = `
          <div style="text-align: center; padding: 30px;">
            <p>Error loading product details. Please try again.</p>
            <p>${error.message}</p>
          </div>
        `;
      });
  }
  
  // Confirm delete function
  function confirmDelete(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
      window.location.href = '../delete_product.php?id=' + productId;
    }
  }

  function toggleMenu() {
    const menu = document.getElementById('mainMenu');
    menu.classList.toggle('show');
  }
      
  // For demonstration purposes - toggle dropdown on mobile
  document.querySelectorAll('.menu-btn').forEach(item => {
    item.addEventListener('click', event => {
      if (window.innerWidth <= 768) {
        const menuItem = event.currentTarget.parentNode;
        menuItem.classList.toggle('active');
      }
      
      // Handle logout
      if (event.currentTarget.textContent.includes('Logout')) {
        window.location.href = '../../logout.php';
      }
    });
  });
  // Add this function to your existing JavaScript at the bottom of the page
function toggleDropdown(button) {
  // Find the dropdown element that is a sibling of the clicked button
  const dropdown = button.nextElementSibling;
  
  // Toggle the display of the dropdown
  if (dropdown.style.display === "block") {
    dropdown.style.display = "none";
    button.querySelector('.dropdown-indicator').textContent = "▼";
  } else {
    // First close all other dropdowns
    document.querySelectorAll('.dropdown').forEach(item => {
      if (item !== dropdown) {
        item.style.display = "none";
        const indicator = item.previousElementSibling.querySelector('.dropdown-indicator');
        if (indicator) indicator.textContent = "▼";
      }
    });
    
    // Then open the clicked dropdown
    dropdown.style.display = "block";
    button.querySelector('.dropdown-indicator').textContent = "▲";
  }
}

// Add this function for the sidebar toggle button
document.addEventListener('DOMContentLoaded', function() {
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
    });
  }
  
  // Add logout function
  window.logout = function() {
    window.location.href = '../../logout.php';
  }
});
</script>
</body>
</html>
