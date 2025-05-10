<?php 
session_start();
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
  <title>Add New Product - BridgeRx Supply Hub</title>
  <link rel="stylesheet" href="../../css/add_produt_company.css">
  <link rel="stylesheet" href="../../css/home_company.css">
  <style>
    .container {
      margin-top:-100px;
 margin-right: 700px;
 margin-left:-250px;
  
}

/* Ensure product form uses available space */
.product-form {
margin-left:-20px;
  width: 1550px;
  margin: 0;
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
        <div class="dropdown show">
          <a href="#" class="dropdown-item active" data-page="add_products.php">Add New <br>Products</a>
          <a href="#" class="dropdown-item" data-page="manage_products.php">Manage <br>Products</a>
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
  
  <div class="content" id="content">
  <?php
  $company_id = $_SESSION['user_id'];
  $success_message = "";
  $error_message = "";

  // Process form submission
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      // Database connection
      require_once "../../api/db.php";
      
      // Get form data and sanitize
      $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
      $generic_name = mysqli_real_escape_string($conn, $_POST['generic_name']);
      $category = mysqli_real_escape_string($conn, $_POST['category']);
      $description = mysqli_real_escape_string($conn, $_POST['description']);
      $batch_number = mysqli_real_escape_string($conn, $_POST['batch_number']);
      $manufacturer = mysqli_real_escape_string($conn, $_POST['manufacturer']);
      $manufacturing_date = $_POST['manufacturing_date'];
      $expiry_date = $_POST['expiry_date'];
      $price = floatval($_POST['price']);
      $stock_quantity = intval($_POST['stock_quantity']);
      $dosage_form = mysqli_real_escape_string($conn, $_POST['dosage_form']);
      $strength = mysqli_real_escape_string($conn, $_POST['strength']);
    
      // Image upload handling
      $product_image = null;
      if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
          $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
          $filename = $_FILES["product_image"]["name"];
          $filetype = $_FILES["product_image"]["type"];
          $filesize = $_FILES["product_image"]["size"];
      
          // Verify file extension
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          if(!array_key_exists($ext, $allowed)) {
              $error_message = "Error: Please select a valid file format.";
          }
      
          // Verify file size - 5MB maximum
          $maxsize = 5 * 1024 * 1024;
          if($filesize > $maxsize) {
              $error_message = "Error: File size is larger than the allowed limit.";
          }
      
          // Verify MIME type of the file
          if(in_array($filetype, $allowed)) {
              // Generate unique file name
              $new_filename = uniqid() . "." . $ext;
              $target_dir = "../../uploads/products/";
              
              // Create directory if it doesn't exist
              if (!file_exists($target_dir)) {
                  mkdir($target_dir, 0777, true);
              }
              
              $target_file = $target_dir . $new_filename;
              
              // Move file to designated folder
              if(move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                  $product_image = $new_filename;
              } else {
                  $error_message = "Error: There was a problem uploading your file. Please try again.";
              }
          } else {
              $error_message = "Error: There was a problem with the file type. Please try again.";
          }
      }
      
      // If no errors, proceed with database insertion
      if(empty($error_message)) {
        $sql = "INSERT INTO products (company_id, product_name, generic_name, category, description, batch_number, 
        manufacturer, manufacturing_date, expiry_date, price, stock_quantity, dosage_form, strength, product_image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssssssdisss", $company_id, $product_name, $generic_name, $category, $description, $batch_number, $manufacturer, $manufacturing_date, $expiry_date, $price, $stock_quantity, $dosage_form, $strength, $product_image);
          
        if(mysqli_stmt_execute($stmt)) {
            $success_message = "Product added successfully!";
            // Clear form data on success
            $_POST = array();
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
      }
  }
  ?>
    
    <div class="container">
      <div class="content">
        <h1>Add New Product</h1>
        <p>Enter the details of the new pharmaceutical product you want to add to the system.</p>
        
        <?php if(!empty($success_message)): ?>
          <div class="alert alert-success">
            <strong>Success!</strong> <?php echo $success_message; ?>
          </div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
          <div class="alert alert-danger">
            <strong>Error!</strong> <?php echo $error_message; ?>
          </div>
        <?php endif; ?>
        
        <div class="product-form">
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
            
            <div class="form-section">
              <div class="section-title">Basic Information</div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="product_name" class="required-field">Product Name</label>
                  <input type="text" id="product_name" name="product_name" required value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" placeholder="Enter product name">
                </div>
                
                <div class="form-group">
                  <label for="generic_name" class="required-field">Generic Name</label>
                  <input type="text" id="generic_name" name="generic_name" required value="<?php echo isset($_POST['generic_name']) ? htmlspecialchars($_POST['generic_name']) : ''; ?>" placeholder="Enter generic name">
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="category" class="required-field">Category</label>
                  <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Analgesics" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Analgesics') ? 'selected' : ''; ?>>Analgesics</option>
                    <option value="Antibiotics" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Antibiotics') ? 'selected' : ''; ?>>Antibiotics</option>
                    <option value="Antidepressants" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Antidepressants') ? 'selected' : ''; ?>>Antidepressants</option>
                    <option value="Antidiabetics" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Antidiabetics') ? 'selected' : ''; ?>>Antidiabetics</option>
                    <option value="Antihistamines" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Antihistamines') ? 'selected' : ''; ?>>Antihistamines</option>
                    <option value="Antihypertensives" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Antihypertensives') ? 'selected' : ''; ?>>Antihypertensives</option>
                    <option value="Cardiovascular" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Cardiovascular') ? 'selected' : ''; ?>>Cardiovascular</option>
                    <option value="Dermatological" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Dermatological') ? 'selected' : ''; ?>>Dermatological</option>
                    <option value="Nutritional" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Nutritional') ? 'selected' : ''; ?>>Nutritional</option>
                    <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="dosage_form" class="required-field">Dosage Form</label>
                  <select id="dosage_form" name="dosage_form" required>
                    <option value="">Select Dosage Form</option>
                    <option value="Tablets" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Tablets') ? 'selected' : ''; ?>>Tablets</option>
                    <option value="Capsules" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Capsules') ? 'selected' : ''; ?>>Capsules</option>
                    <option value="Syrup" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Syrup') ? 'selected' : ''; ?>>Syrup</option>
                    <option value="Injection" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Injection') ? 'selected' : ''; ?>>Injection</option>
                    <option value="Topical" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Topical') ? 'selected' : ''; ?>>Topical</option>
                    <option value="Drops" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Drops') ? 'selected' : ''; ?>>Drops</option>
                    <option value="Inhaler" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Inhaler') ? 'selected' : ''; ?>>Inhaler</option>
                    <option value="Other" <?php echo (isset($_POST['dosage_form']) && $_POST['dosage_form'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Enter detailed product description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
              </div>
            </div>
            
            <div class="form-section">
              <div class="section-title">Manufacturing Details</div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="batch_number" class="required-field">Batch Number</label>
                  <input type="text" id="batch_number" name="batch_number" required value="<?php echo isset($_POST['batch_number']) ? htmlspecialchars($_POST['batch_number']) : ''; ?>" placeholder="Enter batch number">
                </div>
                
                <div class="form-group">
                  <label for="manufacturer" class="required-field">Manufacturer</label>
                  <input type="text" id="manufacturer" name="manufacturer" required value="<?php echo isset($_POST['manufacturer']) ? htmlspecialchars($_POST['manufacturer']) : ''; ?>" placeholder="Enter manufacturer name">
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="manufacturing_date" class="required-field">Manufacturing Date</label>
                  <input type="date" id="manufacturing_date" name="manufacturing_date" required value="<?php echo isset($_POST['manufacturing_date']) ? htmlspecialchars($_POST['manufacturing_date']) : ''; ?>">
                </div>
                
                <div class="form-group">
                  <label for="expiry_date" class="required-field">Expiry Date</label>
                  <input type="date" id="expiry_date" name="expiry_date" required value="<?php echo isset($_POST['expiry_date']) ? htmlspecialchars($_POST['expiry_date']) : ''; ?>">
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <div class="section-title">Product Specifications & Inventory</div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="strength">Strength (e.g., 500mg)</label>
                  <input type="text" id="strength" name="strength" value="<?php echo isset($_POST['strength']) ? htmlspecialchars($_POST['strength']) : ''; ?>" placeholder="Enter strength of the product">
                </div>
                
                <div class="form-group">
                  <label for="price" class="required-field">Price Per Unit (USD)</label>
                  <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" placeholder="Enter price per unit">
                </div>
              </div>
              
              <div class="form-group">
                <label for="stock_quantity" class="required-field">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="1" required value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : ''; ?>" placeholder="Enter available stock quantity">
              </div>
            </div>
            
            <div class="form-section">
              <div class="section-title">Product Image</div>
              
              <div class="form-group">
                <label for="product_image">Upload Product Image</label>
                <p style="color: #666; font-size: 14px; margin-top: 0;">Acceptable formats: JPG, JPEG, PNG (Max: 5MB)</p>
                <div class="file-input-wrapper">
                  <div class="file-input-button">Choose File</div>
                  <input type="file" id="product_image" name="product_image" accept="image/png, image/jpeg, image/jpg" onchange="previewImage(this)">
                </div>
                <div class="image-preview" id="imagePreview">
                  <img src="#" alt="Product Image Preview">
                </div>
              </div>
            </div>
            
            <button type="submit" class="submit-btn">Add Product</button>
          </form>
        </div>
      </div>
    </div>
  </div>
    
  <script>
    // Form validation
    document.querySelector('form').addEventListener('submit', function(event) {
      const manufacturingDate = new Date(document.getElementById('manufacturing_date').value);
      const expiryDate = new Date(document.getElementById('expiry_date').value);
      
      if (expiryDate <= manufacturingDate) {
        alert('Expiry date must be later than manufacturing date');
        event.preventDefault();
      }
    });
    
    // Image preview functionality
    function previewImage(input) {
      const preview = document.getElementById('imagePreview');
      const previewImg = preview.querySelector('img');
      const fileInputButton = document.querySelector('.file-input-button');
      
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          preview.style.display = 'block';
          fileInputButton.textContent = 'Change Image';
        }
        
        reader.readAsDataURL(input.files[0]);
      } else {
        previewImg.src = '#';
        preview.style.display = 'none';
        fileInputButton.textContent = 'Choose File';
      }
    }
    
    // Main JavaScript for Dashboard
    
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
        window.location.href = "../../logout.php";
      }
    }
  </script>
</body>
</html>