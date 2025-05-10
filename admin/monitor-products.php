<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitor Listed Products - BridgeRx Admin</title>
  <link rel="stylesheet" href="../css/home_admin.css">
  <link rel="stylesheet" href="../css/admin.css">
  <style>
    /* Custom styles for Monitor Products page */
    .filter-section {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }
    
    .filter-form {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: flex-end;
    }
    
    .filter-group {
      flex: 1 1 200px;
    }
    
    .filter-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #555;
    }
    
    .filter-input {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ddd;
      font-size: 14px;
    }
    
    .filter-btn {
      background: #4b70dd;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }
    
    .filter-btn:hover {
      background: #3a5bb9;
    }
    
    .reset-btn {
      background: #f0f0f0;
      color: #555;
      border: 1px solid #ddd;
    }
    
    .reset-btn:hover {
      background: #e0e0e0;
    }
    
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    
    .product-card {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .product-image {
      height: 180px;
      background-color: #f9f9f9;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    
    .product-image img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
    
    .product-details {
      padding: 20px;
    }
    
    .product-name {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 5px;
      color: #333;
    }
    
    .product-generic {
      font-size: 15px;
      color: #666;
      margin-bottom: 10px;
    }
    
    .product-meta {
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
    }
    
    .product-category {
      background: #e6f2ff;
      padding: 4px 10px;
      border-radius: 15px;
      font-size: 13px;
      color: #4b70dd;
    }
    
    .product-stock {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 14px;
    }
    
    .stock-indicator {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }
    
    .in-stock {
      background-color: #27ae60;
    }
    
    .low-stock {
      background-color: #f39c12;
    }
    
    .out-stock {
      background-color: #e74c3c;
    }
    
    .product-price {
      font-size: 18px;
      font-weight: 600;
      color: #4b70dd;
      margin-bottom: 15px;
    }
    
    .product-expiry {
      display: flex;
      align-items: center;
      gap: 5px;
      color: #666;
      font-size: 14px;
      margin-bottom: 5px;
    }
    
    .expiry-warning {
      color: #e74c3c;
    }
    
    .product-company {
      font-size: 14px;
      color: #666;
      margin-bottom: 15px;
    }
    
    .product-actions {
      display: flex;
      gap: 10px;
    }
    
    .action-btn {
      flex: 1;
      padding: 8px 10px;
      border-radius: 6px;
      border: none;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      transition: background 0.3s;
    }
    
    .view-btn {
      background: #f0f0f0;
      color: #555;
    }
    
    .view-btn:hover {
      background: #e0e0e0;
    }
    
    .flag-btn {
      background: #ffe9e9;
      color: #e74c3c;
    }
    
    .flag-btn:hover {
      background: #ffd9d9;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 30px;
      gap: 5px;
    }
    
    .page-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #fff;
      border: 1px solid #ddd;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .page-btn:hover {
      background: #f0f0f0;
    }
    
    .page-btn.active {
      background: #4b70dd;
      color: white;
      border-color: #4b70dd;
    }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .stat-box {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    
    .stat-number {
      font-size: 24px;
      font-weight: 600;
      margin: 10px 0;
    }
    
    .stat-label {
      font-size: 14px;
      color: #666;
    }
    
    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
      font-size: 24px;
    }
    
    .stat-blue {
      background: #e6f2ff;
      color: #4b70dd;
    }
    
    .stat-green {
      background: #e6f9ed;
      color: #27ae60;
    }
    
    .stat-orange {
      background: #fff2e6;
      color: #f39c12;
    }
    
    .stat-red {
      background: #ffe9e9;
      color: #e74c3c;
    }
    
  /* Modal Styling Improvements */
.product-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background-color: #fff;
  border-radius: 8px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
  background-color: #f8f9fa;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  color: #333;
}

.close-modal {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: row;
  gap: 25px;
  max-height: calc(90vh - 130px);
}

.modal-image {
  flex: 0 0 250px;
  display: flex;
  align-items: flex-start;
}

.modal-image img {
  width: 100%;
  border-radius: 6px;
  object-fit: cover;
  border: 1px solid #eee;
}

.modal-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.modal-detail-row {
  display: flex;
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 8px;
}

.detail-label {
  flex: 0 0 150px;
  font-weight: 600;
  color: #555;
}

.detail-value {
  flex: 1;
  color: #333;
}

.expiry-warning {
  color: #d9534f;
  font-weight: 500;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 15px 20px;
  border-top: 1px solid #eee;
  gap: 10px;
  background-color: #f8f9fa;
}

.flag-form {
  margin-top: 15px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 6px;
}

.flag-form h3 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 1rem;
}

#flagComments {
  width: 100%;
  min-height: 80px;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  resize: vertical;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .modal-body {
    flex-direction: column;
  }
  
  .modal-image {
    flex: 0 0 auto;
    margin-bottom: 15px;
  }
  
  .modal-detail-row {
    flex-direction: column;
    gap: 4px;
  }
  
  .detail-label {
    flex: 0 0 auto;
  }
}
  </style>
</head>
<body>
  <!-- Header will be included from the admin template -->
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
        <button class="menu-btn" data-page="home.php">
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
          <a href="#" class="dropdown-item" data-page="user_management/manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item" data-page="user_management/manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="#" class="dropdown-item" data-page="user_management/manage-user-status.php">Approve / Suspend / Remove Users</a>
        </div>
      </li>
     
    
      <li class="menu-item">
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">📦</span>
          <span class="menu-text">Product & Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item active" data-page="monitor-products.php">Monitor Listed Products</a>
       
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
        <h1 class="content-title">Monitor Listed Products</h1>
      </div>
      
      <!-- Stats Overview -->
      <div class="stats-row">
        <?php
        // Include database connection
        require_once '../api/db.php';
        
        // Get total products count
        $sql_total = "SELECT COUNT(*) as total FROM products";
        $result_total = $conn->query($sql_total);
        $total_products = $result_total->fetch_assoc()['total'];
        
        // Get products with low stock
        $sql_low_stock = "SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity < 10";
        $result_low_stock = $conn->query($sql_low_stock);
        $low_stock_count = $result_low_stock->fetch_assoc()['low_stock'];
        
        // Get products near expiry (within 90 days)
        $sql_near_expiry = "SELECT COUNT(*) as near_expiry FROM products WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
        $result_near_expiry = $conn->query($sql_near_expiry);
        $near_expiry_count = $result_near_expiry->fetch_assoc()['near_expiry'];
        
        // Get flagged products count (checking if flagged column exists first)
        $sql_check_column = "SHOW COLUMNS FROM products LIKE 'flagged'";
        $result_check_column = $conn->query($sql_check_column);
        $flagged_count = 0;
        
        if ($result_check_column->num_rows > 0) {
            $sql_flagged = "SELECT COUNT(*) as flagged FROM products WHERE flagged = 1";
            $result_flagged = $conn->query($sql_flagged);
            $flagged_count = $result_flagged->fetch_assoc()['flagged'];
        }
        ?>
        
        <div class="stat-box">
          <div class="stat-icon stat-blue">📦</div>
          <div class="stat-number"><?php echo $total_products; ?></div>
          <div class="stat-label">Total Products</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-icon stat-green">✓</div>
          <div class="stat-number"><?php echo $total_products - $low_stock_count - $near_expiry_count - $flagged_count; ?></div>
          <div class="stat-label">Healthy Products</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-icon stat-orange">⚠️</div>
          <div class="stat-number"><?php echo $low_stock_count; ?></div>
          <div class="stat-label">Low Stock Products</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-icon stat-orange">⏱️</div>
          <div class="stat-number"><?php echo $near_expiry_count; ?></div>
          <div class="stat-label">Near Expiry</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-icon stat-red">🚩</div>
          <div class="stat-number"><?php echo $flagged_count; ?></div>
          <div class="stat-label">Flagged Products</div>
        </div>
      </div>
      
      <!-- Filter Section -->
      <div class="filter-section">
        <h2>Filter Products</h2>
        <form class="filter-form" method="GET">
          <div class="filter-group">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" class="filter-input" placeholder="Search by name..." value="<?php echo isset($_GET['product_name']) ? htmlspecialchars($_GET['product_name']) : ''; ?>">
          </div>
          
          <div class="filter-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="filter-input">
              <option value="">All Categories</option>
              <?php
              // Get unique categories from database
              $sql_categories = "SELECT DISTINCT category FROM products ORDER BY category";
              $result_categories = $conn->query($sql_categories);
              
              while ($category = $result_categories->fetch_assoc()) {
                $selected = (isset($_GET['category']) && $_GET['category'] == $category['category']) ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($category['category']) . "' {$selected}>" . htmlspecialchars($category['category']) . "</option>";
              }
              ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="company_id">Pharmaceutical Company</label>
            <select id="company_id" name="company_id" class="filter-input">
              <option value="">All Companies</option>
              <?php
              // Get companies from database
              $sql_companies = "SELECT id, name FROM company_users WHERE approval_status = 'approved' ORDER BY name";
              $result_companies = $conn->query($sql_companies);
              
              if ($result_companies) {
                while ($company = $result_companies->fetch_assoc()) {
                  $selected = (isset($_GET['company_id']) && $_GET['company_id'] == $company['id']) ? 'selected' : '';
                  echo "<option value='" . htmlspecialchars($company['id']) . "' {$selected}>" . htmlspecialchars($company['name']) . "</option>";
                }
              }
              ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="status">Stock Status</label>
            <select id="status" name="status" class="filter-input">
              <option value="">All Statuses</option>
              <option value="in_stock" <?php echo (isset($_GET['status']) && $_GET['status'] == 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
              <option value="low_stock" <?php echo (isset($_GET['status']) && $_GET['status'] == 'low_stock') ? 'selected' : ''; ?>>Low Stock</option>
              <option value="out_of_stock" <?php echo (isset($_GET['status']) && $_GET['status'] == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
              <option value="near_expiry" <?php echo (isset($_GET['status']) && $_GET['status'] == 'near_expiry') ? 'selected' : ''; ?>>Near Expiry</option>
              <option value="flagged" <?php echo (isset($_GET['status']) && $_GET['status'] == 'flagged') ? 'selected' : ''; ?>>Flagged</option>
            </select>
          </div>
          
          <div class="filter-group">
            <button type="submit" class="filter-btn">Apply Filters</button>
            <button type="reset" class="filter-btn reset-btn" onclick="window.location.href='monitor-products.php'">Reset</button>
          </div>
        </form>
      </div>
      
      <!-- Products Grid -->
      <div class="products-grid">
        <?php
        // Build the SQL query with filters
        $sql = "SELECT p.*, c.name as company_name 
                FROM products p 
                LEFT JOIN company_users c ON p.company_id = c.id 
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Add filters to the SQL query
        if (isset($_GET['product_name']) && !empty($_GET['product_name'])) {
          $sql .= " AND (p.product_name LIKE ? OR p.generic_name LIKE ?)";
          $search_term = "%" . $_GET['product_name'] . "%";
          $params[] = $search_term;
          $params[] = $search_term;
          $types .= "ss";
        }
        
        if (isset($_GET['category']) && !empty($_GET['category'])) {
          $sql .= " AND p.category = ?";
          $params[] = $_GET['category'];
          $types .= "s";
        }
        
        if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
          $sql .= " AND p.company_id = ?";
          $params[] = $_GET['company_id'];
          $types .= "s";
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
          switch ($_GET['status']) {
            case 'in_stock':
              $sql .= " AND p.stock_quantity > 10";
              break;
            case 'low_stock':
              $sql .= " AND p.stock_quantity > 0 AND p.stock_quantity <= 10";
              break;
            case 'out_of_stock':
              $sql .= " AND p.stock_quantity = 0";
              break;
            case 'near_expiry':
              $sql .= " AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
              break;
            case 'flagged':
              $sql .= " AND p.flagged = 1";
              break;
          }
        }
        
        // Add pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $items_per_page = 12;
        $offset = ($page - 1) * $items_per_page;
        
        // Get total items for pagination
        $count_sql = str_replace("SELECT p.*, c.name as company_name", "SELECT COUNT(*) as total", $sql);
        $stmt_count = $conn->prepare($count_sql);
        
        if (!empty($params)) {
          $stmt_count->bind_param($types, ...$params);
        }
        
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $total_items = $result_count->fetch_assoc()['total'];
        $total_pages = ceil($total_items / $items_per_page);
        
        // Get items for current page
        $sql .= " ORDER BY p.created_at DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $items_per_page;
        $types .= "ii"; // Add integer types for LIMIT and OFFSET
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
          $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
          while ($product = $result->fetch_assoc()) {
            // Determine stock status
            $stock_class = $product['stock_quantity'] > 10 ? 'in-stock' : ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-stock');
            $stock_text = $product['stock_quantity'] > 10 ? 'In Stock' : ($product['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
            
            // Check if product is near expiry
            $expiry_date = new DateTime($product['expiry_date']);
            $today = new DateTime();
            $days_until_expiry = $today->diff($expiry_date)->days;
            $is_near_expiry = $expiry_date > $today && $days_until_expiry <= 90;
            
            // Format dates
            $expiry_formatted = date('M d, Y', strtotime($product['expiry_date']));
            $manufacturing_formatted = date('M d, Y', strtotime($product['manufacturing_date']));
            
            // Image placeholder if no image is available
            $image_src = !empty($product['product_image']) ? "../uploads/products/" . htmlspecialchars($product['product_image']) : "../assets/images/product-placeholder.png";
        ?>
        <div class="product-card" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
          <div class="product-image">
            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
          </div>
          <div class="product-details">
            <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
            <p class="product-generic"><?php echo htmlspecialchars($product['generic_name']); ?></p>
            
            <div class="product-meta">
              <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
              <span class="product-stock">
                <span class="stock-indicator <?php echo $stock_class; ?>"></span>
                <?php echo $stock_text; ?> (<?php echo htmlspecialchars($product['stock_quantity']); ?>)
              </span>
            </div>
            
            <div class="product-price">$<?php echo number_format((float)$product['price'], 2); ?></div>
            
            <div class="product-expiry <?php echo $is_near_expiry ? 'expiry-warning' : ''; ?>">
              <span>Expires: <?php echo $expiry_formatted; ?></span>
              <?php if ($is_near_expiry): ?>
              <span>⚠️ Expires soon</span>
              <?php endif; ?>
            </div>
            
            <div class="product-company">
              <strong>Company:</strong> <?php echo htmlspecialchars($product['company_name']); ?>
            </div>
            
            <div class="product-actions">
              <button class="action-btn view-btn" onclick="viewProductDetails(<?php echo htmlspecialchars($product['id']); ?>)">
                <span>👁️</span> View Details
              </button>
              <button class="action-btn flag-btn" onclick="flagProduct(<?php echo htmlspecialchars($product['id']); ?>)">
                <span>🚩</span> Flag Product
              </button>
            </div>
          </div>
        </div>
        <?php
          }
        } else {
          echo "<p class='no-results'>No products found matching your criteria.</p>";
        }
        ?>
      </div>
      
      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['product_name']) ? '&product_name=' . urlencode($_GET['product_name']) : ''; ?><?php echo isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['company_id']) ? '&company_id=' . urlencode($_GET['company_id']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?>" class="page-btn">&lt;</a>
        <?php endif; ?>
        
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++) {
          $active_class = $i == $page ? 'active' : '';
          echo "<a href='?page={$i}" . 
               (isset($_GET['product_name']) ? '&product_name=' . urlencode($_GET['product_name']) : '') . 
               (isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : '') . 
               (isset($_GET['company_id']) ? '&company_id=' . urlencode($_GET['company_id']) : '') . 
               (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') . 
               "' class='page-btn {$active_class}'>{$i}</a>";
        }
        ?>
        
        <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['product_name']) ? '&product_name=' . urlencode($_GET['product_name']) : ''; ?><?php echo isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['company_id']) ? '&company_id=' . urlencode($_GET['company_id']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?>" class="page-btn">&gt;</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Product Details Modal -->
  <div class="product-modal" id="productModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Product Details</h2>
        <button class="close-modal" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- Content will be loaded dynamically -->
      </div>
      <div class="modal-footer">
        <button class="action-btn view-btn" onclick="closeModal()">Close</button>
        <button class="action-btn flag-btn" id="flagProductBtn">Flag Product</button>
      </div>
    </div>
  </div>
  
  <script>
    // Function to view product details
    function viewProductDetails(productId) {
      const modal = document.getElementById('productModal');
      const modalBody = document.getElementById('modalBody');
      const flagBtn = document.getElementById('flagProductBtn');
      
      // Fetch product details via AJAX
      fetch(`get_product_details.php?id=${encodeURIComponent(productId)}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(product => {
          // Format dates
          const expiryDate = new Date(product.expiry_date);
          const manufacturingDate = new Date(product.manufacturing_date);
          
          // Check if product is near expiry
          const today = new Date();
          const daysUntilExpiry = Math.floor((expiryDate - today) / (1000 * 60 * 60 * 24));
          const isNearExpiry = expiryDate > today && daysUntilExpiry <= 90;
          
          // Image source
          const imageSrc = product.product_image 
            ? `../uploads/products/${encodeURIComponent(product.product_image)}` 
            : "../assets/images/product-placeholder.png";
          
          // Build modal content with proper escaping
          let modalContent = `
            <div class="modal-image">
              <img src="${imageSrc}" alt="${product.product_name}">
            </div>
            <div class="modal-details">
              <div class="modal-detail-row">
                <div class="detail-label">Product Name</div>
                <div class="detail-value">${escapeHTML(product.product_name)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Generic Name</div>
                <div class="detail-value">${escapeHTML(product.generic_name)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Category</div>
                <div class="detail-value">${escapeHTML(product.category)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Description</div>
                <div class="detail-value">${escapeHTML(product.description || 'No description available')}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Price</div>
                <div class="detail-value">$${parseFloat(product.price).toFixed(2)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Current Stock</div>
                <div class="detail-value">${escapeHTML(product.stock_quantity.toString())} units</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Dosage Form</div>
                <div class="detail-value">${escapeHTML(product.dosage_form)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Strength</div>
                <div class="detail-value">${escapeHTML(product.strength)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Manufacturer</div>
                <div class="detail-value">${escapeHTML(product.manufacturer)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Batch Number</div>
                <div class="detail-value">${escapeHTML(product.batch_number)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Manufacturing Date</div>
                <div class="detail-value">${manufacturingDate.toLocaleDateString()}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Expiry Date</div>
                <div class="detail-value ${isNearExpiry ? 'expiry-warning' : ''}">${expiryDate.toLocaleDateString()} ${isNearExpiry ? '⚠️ Expires soon' : ''}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Company</div>
                <div class="detail-value">${escapeHTML(product.company_name)}</div>
              </div>
              <div class="modal-detail-row">
                <div class="detail-label">Added On</div>
                <div class="detail-value">${new Date(product.created_at).toLocaleDateString()}</div>
              </div>
              
              <div id="flagFormContainer" class="flag-form" style="display: none;">
                <h3>Flag this product</h3>
                <div class="modal-detail-row">
                  <div class="detail-label">Reason for flagging</div>
                  <select id="flagReason" class="filter-input">
                    <option value="counterfeit">Suspected Counterfeit</option>
                    <option value="quality">Quality Issues</option>
                    <option value="license">Licensing Issues</option>
                    <option value="pricing">Pricing Concerns</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="modal-detail-row">
                  <div class="detail-label">Additional Comments</div>
                  <textarea id="flagComments" placeholder="Please provide details about the issue..."></textarea>
                </div>
                <button class="filter-btn" onclick="submitFlagReport(${product.id})">Submit Report</button>
              </div>
            </div>
          `;
          
          modalBody.innerHTML = modalContent;
          
          // Setup flag button action
          flagBtn.onclick = function() {
            const flagForm = document.getElementById('flagFormContainer');
            flagForm.style.display = flagForm.style.display === 'none' ? 'block' : 'none';
          };
          
          // Display modal
          modal.style.display = 'flex';
        })
        .catch(error => {
          console.error('Error fetching product details:', error);
          alert('Failed to load product details. Please try again.');
        });
    }
    
    // Helper function to escape HTML
    function escapeHTML(str) {
      if (!str) return '';
      return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
    
    // Function to close modal
    function closeModal() {
      document.getElementById('productModal').style.display = 'none';
    }
    
    // Function to flag a product directly from the product card
    function flagProduct(productId) {
      viewProductDetails(productId);
      setTimeout(() => {
        document.getElementById('flagProductBtn').click();
      }, 500);
    }
    
    // Function to submit flag report
    function submitFlagReport(productId) {
      const reason = document.getElementById('flagReason').value;
      const comments = document.getElementById('flagComments').value;
      
      if (!comments.trim()) {
        alert('Please provide detailed comments about the issue.');
        return;
      }
      
      // Send flag report via AJAX
      fetch('flag_product.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${encodeURIComponent(productId)}&reason=${encodeURIComponent(reason)}&comments=${encodeURIComponent(comments)}`
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          alert('Product has been flagged successfully. Our team will review this report.');
          closeModal();
          window.location.reload(); // Refresh to update UI
        } else {
          alert('Failed to flag product: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error flagging product:', error);
        alert('An error occurred while flagging the product. Please try again.');
      });
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('productModal');
      if (event.target === modal) {
        closeModal();
      }
    };
    
    // Initialize sidebar toggle from admin template
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      const content = document.getElementById('content');
      
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
    });
    
    // Function to toggle dropdowns (from admin template)
    function toggleDropdown(button) {
      if (!button) return;
      
      const dropdown = button.nextElementSibling;
      if (!dropdown) return;
      
      const arrow = button.querySelector('.dropdown-indicator');
      const sidebar = document.getElementById('sidebar');
      
      if (sidebar && sidebar.classList.contains('sidebar-collapsed') && window.innerWidth > 768) {
        return; // Don't toggle dropdowns in collapsed mode on desktop
      }
      
      const dropdowns = document.querySelectorAll('.dropdown');
      dropdowns.forEach(function(item) {
        if (item !== dropdown && item.classList.contains('show')) {
          item.classList.remove('show');
          const prevArrow = item.previousElementSibling.querySelector('.dropdown-indicator');
          if (prevArrow) {
            prevArrow.style.transform = 'rotate(0deg)';
          }
        }
      });
      
      dropdown.classList.toggle('show');
      if (arrow) {
        arrow.style.transform = dropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
      }
    }
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
  </script></body>
  </html>