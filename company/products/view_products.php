<?php 
session_start();
require_once '../../api/db.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../../../signin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Products - Pharmacy Management System</title>



<style>
  
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f0f2f5;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  position: fixed;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #3a7bd5;
  padding: 10px 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  width: 100%; /* Add this line to make it full width */
  top: 0; /* Add this to ensure it stays at the top */
  left: 0; /* Add this to ensure it starts from the left edge */
  z-index: 1000; /* Add this to ensure it stays above other elements */
  box-sizing: border-box; /* Add this to include padding in width calculation */
}

.logo {
  display: flex;
  align-items: center;
  color: white;
  font-size: 22px;
  font-weight: bold;
}

.logo-icon {
  margin-right: 10px;
  font-size: 26px;
}

.header-controls {
  display: flex;
  align-items: center;
}

.header-btn {
  background: none;
  border: none;
  color: white;
  margin-left: 15px;
  font-size: 20px;
  cursor: pointer;
  position: relative;
}

.account-info {
  display: flex;
  align-items: center;
  color: white;
  margin-left: 20px;
}

.account-icon {
  width: 38px;
  height: 38px;
  background-color: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: 15px;
  color: #3a7bd5;
  font-weight: bold;
}

.alert-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background-color: #e74c3c;
  color: white;
  border-radius: 50%;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.sidebar {
  position: fixed;
  top: 60px;
  left: 0;
  bottom: 0;
  width: 250px;
  background-color: #ffffff; /* Changed to white */
  color: #333333; /* Changed to dark gray/black */
  overflow-y: auto;
  transition: all 0.3s ease;
  z-index: 100;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05); /* Added subtle shadow for depth */
}

.sidebar-collapsed {
  width: 70px;
}

.menu {
  list-style-type: none;
  margin: 0;
  padding: 0;
}

.menu-item {
  position: relative;
  border-bottom: 1px solid #f0f0f0; /* Added subtle separator */
}

.menu-btn {
  background: none;
  border: none;
  color: #333333; /* Changed to dark gray/black */
  cursor: pointer;
  font-size: 15px;
  padding: 15px;
  text-align: left;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  width: 100%;
}

.menu-btn:hover {
  background-color: #f5f5f5; /* Light gray hover state */
  color: #3a7bd5; /* Teal color on hover to match header */
}

.menu-icon {
  margin-right: 15px;
  width: 20px;
  text-align: center;
  font-size: 18px;
  color: #3a7bd5; /* Teal color for icons to match header */
}

.menu-text {
  white-space: nowrap;
  overflow: hidden;
  font-weight: 500; /* Added medium weight for better readability */
}

.sidebar-collapsed .menu-text {
  display: none;
}

.dropdown-indicator {
  margin-left: auto;
  transition: transform 0.3s ease;
  color: #888888; /* Lighter color for the indicator */
}

.active {
  background-color: #e0f2f1; /* Very light teal background */
  color: #3a7bd5; /* Teal text color */
  border-left: 4px solid #3a7bd5; /* Added teal border indicator */
}

.active:hover {
  background-color: #e0f2f1; /* Keep consistent with active state */
}

.active .menu-icon {
  color: #3a7bd5; /* Ensure icon is teal in active state */
}

.dropdown {
  background-color: #f9f9f9; /* Slightly different white for dropdown */
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.3s ease;
}

.dropdown.show {
  max-height: 1000px;
}

.dropdown-item {
  color: #555555; /* Dark gray for dropdown items */
  display: block;
  padding: 12px 15px 12px 45px;
  text-decoration: none;
  transition: all 0.3s ease;
  font-size: 14px;
}

.dropdown-item:hover {
  background-color: #f0f0f0; /* Light gray hover */
  color: #3a7bd5; /* Teal text on hover */
}

.sidebar-collapsed .dropdown {
  position: absolute;
  left: 70px;
  top: 0;
  min-width: 200px;
  z-index: 1;
  max-height: none;
  display: none;
  border-radius: 0 4px 4px 0;
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.1); /* Added shadow for popout menu */
}

.sidebar-collapsed .menu-item:hover .dropdown {
  display: block;
}

.sidebar-collapsed .dropdown-item {
  padding: 12px 15px;
}

.content {
  margin-left: 250px;
  padding: 20px;
  transition: margin-left 0.3s ease;
  margin-top: 60px; /* Add this to prevent content from hiding under header */
}

.content-full {
  margin-left: 70px;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.content-title {
  color: #2c3e50;
  margin: 0;
  font-size: 24px;
}

.dashboard-stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background-color: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.stat-title {
  color: #7f8c8d;
  font-size: 16px;
  margin: 0;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.stat-users {
  background-color: #e6f7ff;
  color: #2980b9;
}

.stat-transactions {
  background-color: #e6fffb;
  color: #20b2aa;
}

.stat-alerts {
  background-color: #fff2e8;
  color: #e74c3c;
}

.stat-verifications {
  background-color: #fcf8e3;
  color: #f39c12;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #2c3e50;
  margin: 5px 0;
}

.stat-change {
  font-size: 14px;
}

.mobile-menu-toggle {
  display: none;
  background: none;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    width: 250px;
  }
  
  .sidebar.show {
    transform: translateX(0);
  }
  
  .content {
    margin-left: 0;
  }
  
  .mobile-menu-toggle {
    display: block;
  }
}
  body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f5f7fa;
  color: #333;
}

.container {
  max-width: 1600px;
  padding: 40px;
}

.content {
  height:auto;
  width: 1650px;
  margin-top:-38px;
  margin-left:-40px;
  max-width: 1700px;
  padding: 20px;
}

.page-header {

  margin-bottom: 30px;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 15px;
}

.page-header h1 {
 
  color: #2c3e50;
  font-size: 28px;
}

.page-header p {
  margin: 10px 0 0 0;
  color: #7f8c8d;
  font-size: 16px;
}

/* Search and Filter Form */
.search-filter {
  background-color: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
  margin-bottom: 30px;
  transition: all 0.3s ease;
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.search-filter input[type="text"],
.search-filter select {
  flex: 1;
  min-width: 250px;
  padding: 12px 15px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s ease;
  background-color: white;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
}

.search-filter input[type="text"]:focus,
.search-filter select:focus {
  border-color: #2196F3;
  box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2);
  outline: none;
}

.search-filter select {
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  padding-right: 40px;
}

.search-filter button,
.search-filter a {
  padding: 14px 28px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 16px;
  text-decoration: none;
  display: inline-block;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.search-filter button {
  background: linear-gradient(135deg, #66BB6A 0%, #43A047 100%);
  color: white;
}

.search-filter a {
  background: linear-gradient(135deg, #f2f2f2 0%, #e0e0e0 100%);
  color: #333;
}

.search-filter button:hover {
  background: linear-gradient(135deg, #43A047 0%, #388E3C 100%);
  box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.search-filter a:hover {
  background: linear-gradient(135deg, #e0e0e0 0%, #d1d1d1 100%);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

/* Products Table */
.table-container {
  background-color: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
  margin-top: 20px;
  overflow-x: auto;
}

.product-table {
  width: 98%;
  border-collapse: separate;
  border-spacing: 0;
}

.product-table th {
  background-color: #2196F3;
  color: white;
  padding: 15px;
  text-align: left;
  border-top: none;
}

.product-table th:first-child {
  border-top-left-radius: 8px;
}

.product-table th:last-child {
  border-top-right-radius: 8px;
}

.product-table td {
  padding: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.product-table tr:last-child td {
  border-bottom: none;
}

.product-image {
  max-width: 100px;
  max-height: 100px;
  object-fit: cover;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.pagination a {
  margin: 0 5px;
  padding: 10px 15px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  color: #333;
  text-decoration: none;
  transition: all 0.3s ease;
}

.pagination a.active {
  background-color: #2196F3;
  color: white;
  border-color: #2196F3;
}

.pagination a:hover:not(.active) {
  background-color: #f0f0f0;
}

/* No Products Found Message */
.table-container p {
  text-align: center;
  color: #7f8c8d;
  padding: 30px;
  background-color: #f9f9f9;
  border-radius: 12px;
  font-size: 16px;
}

/* Responsive Design */
@media screen and (max-width: 768px) {
  .search-filter {
      flex-direction: column;
      padding: 20px;
  }

  .search-filter input[type="text"],
  .search-filter select,
  .search-filter button,
  .search-filter a {
      width: 100%;
      margin-bottom: 15px;
  }

  .table-container {
      padding: 15px;
  }
}

/* Adjust content area to work with sidebar */
#content {
  margin-left: 250px;
  padding: 20px;
  transition: margin-left 0.3s ease;
}

#content.content-full {
  margin-left: 80px;
}

@media screen and (max-width: 768px) {
  #content {
    margin-left: 0;
  }
}
</style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="logo-icon">💊</span> BridgeRx Supply Hub
    </div>
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
        <button class="menu-btn" data-page="../company.php">
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
          <a href="#" class="dropdown-item" data-page="manage_products.php">Manage <br>Products</a>
          <a href="#" class="dropdown-item active" data-page="view_products.php">View Product <br>Listings</a>
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

<?php
// Get company ID of logged in user
$company_id = $_SESSION['user_id']; // Assuming user_id is the company_id for company accounts

// Get pagination parameters
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($current_page - 1) * $items_per_page;

// Handle search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$expiry_filter = isset($_GET['expiry']) ? $_GET['expiry'] : '';

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

// Add expiry filter if provided
if (!empty($expiry_filter)) {
    // Current date
    $current_date = date('Y-m-d');
    
    if ($expiry_filter == 'expired') {
        $query .= " AND expiry_date < ?";
        $params[] = $current_date;
        $types .= "s";
    } else if ($expiry_filter == 'expiring_soon') {
        // Products expiring in the next 3 months
        $three_months_later = date('Y-m-d', strtotime('+3 months'));
        $query .= " AND expiry_date BETWEEN ? AND ?";
        $params[] = $current_date;
        $params[] = $three_months_later;
        $types .= "ss";
    } else if ($expiry_filter == 'valid') {
        $query .= " AND expiry_date > ?";
        $params[] = $current_date;
        $types .= "s";
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

  <div id="content">
    <div class="container">
      <div class="content">
        <h1>View Products</h1>
        <p>Manage and view all your pharmaceutical products currently listed in the system.</p>
        
        <!-- Search and Filters -->
        <form method="GET" action="" class="search-filter">
          <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
          
          <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($category); ?>
              </option>
            <?php endforeach; ?>
          </select>
          
          <select name="expiry">
            <option value="">All Expiry Status</option>
            <option value="valid" <?php echo $expiry_filter == 'valid' ? 'selected' : ''; ?>>Valid</option>
            <option value="expiring_soon" <?php echo $expiry_filter == 'expiring_soon' ? 'selected' : ''; ?>>Expiring Soon (3 months)</option>
            <option value="expired" <?php echo $expiry_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
          </select>
          
          <button type="submit">Filter</button>
          <a href="view_products.php" style="padding: 8px; text-decoration: none; background-color: #f2f2f2; border: 1px solid #ddd; border-radius: 4px;">Clear</a>
        </form>
        
        <!-- Products Table -->
        <div class="table-container">
          <?php if (mysqli_num_rows($result) > 0): ?>
            <table class="product-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Product Name</th>
                  <th>Generic Name</th>
                  <th>Category</th>
                  <th>Batch Number</th>
                  <th>Manufacturer</th>
                  <th>Expiry Date</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Dosage Form</th>
                  <th>Strength</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <?php
                    // Check if product is expired
                    $expired = strtotime($row['expiry_date']) < strtotime('today');
                    $expiring_soon = strtotime($row['expiry_date']) < strtotime('+3 months') && !$expired;
                    $row_class = $expired ? 'style="background-color: #ffcccc;"' : ($expiring_soon ? 'style="background-color: #ffffcc;"' : '');
                  ?>
                  <tr <?php echo $row_class; ?>>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td>
                      <?php if (!empty($row['product_image'])): ?>
                        <img src="../../uploads/products/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Product Image" class="product-image">
                      <?php else: ?>
                        <span>No image</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['generic_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['manufacturer']); ?></td>
                    <td><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['price']); ?></td>
                    <td><?php echo htmlspecialchars($row['stock_quantity']); ?></td>
                    <td><?php echo htmlspecialchars($row['dosage_form']); ?></td>
                    <td><?php echo htmlspecialchars($row['strength']); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
              <div class="pagination">
                <?php if ($current_page > 1): ?>
                  <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&expiry=<?php echo urlencode($expiry_filter); ?>">&laquo;</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&expiry=<?php echo urlencode($expiry_filter); ?>" <?php echo $i == $current_page ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                  </a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                  <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&expiry=<?php echo urlencode($expiry_filter); ?>">&raquo;</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
          <?php else: ?>
            <p>No products found. <a href="products/add_products.php">Add a new product</a>.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
    
  <script>
    // On page load, initialize the dashboard
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
    
    // For demonstration purposes - toggle dropdown on mobile
    document.querySelectorAll('.menu-btn').forEach(item => {
      item.addEventListener('click', event => {
        if (window.innerWidth <= 768) {
          const menuItem = event.currentTarget.parentNode;
          menuItem.classList.toggle('active');
        }
      });
    });
    
    // Confirm delete function
    function confirmDelete(productId) {
      if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        window.location.href = 'delete_product.php?id=' + productId;
      }
    }
  </script>
</body>
</html>