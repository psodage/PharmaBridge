<?php 
session_start();
require_once '../../api/db.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../../signin.php");
    exit();
}

// Get company ID of logged in user
$company_id = $_SESSION['user_id']; // Assuming user_id is the company_id for company accounts

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Verify this product belongs to the logged-in company
$check_query = "SELECT * FROM products WHERE id = ? AND company_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $product_id, $company_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

// If no product found or doesn't belong to this company, redirect
if (mysqli_num_rows($result) == 0) {
    header("Location: manage_products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Handle form submission
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $product_name = $_POST['product_name'];
    $generic_name = $_POST['generic_name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $expiry_date = $_POST['expiry_date'];
    
    // Validate form data
    if (empty($product_name) || empty($generic_name) || empty($category) || empty($description) || 
        empty($price) || !isset($stock_quantity) || empty($expiry_date)) {
        $error = "All fields are required";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Price must be a positive number";
    } elseif (!is_numeric($stock_quantity) || $stock_quantity < 0) {
        $error = "Stock quantity must be a non-negative number";
    } else {
        // Handle image upload if a new one is provided
        $product_image = $product['product_image']; // Keep existing image by default
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['product_image']['type'], $allowed_types)) {
                $error = "Only JPG, JPEG and PNG images are allowed";
            } elseif ($_FILES['product_image']['size'] > $max_size) {
                $error = "Image size should not exceed 5MB";
            } else {
                $upload_dir = "../../uploads/products/";
                $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('product_') . '.' . $file_extension;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $new_filename)) {
                    // Delete old image if exists and not the default
                    if (!empty($product['product_image']) && file_exists($upload_dir . $product['product_image'])) {
                        unlink($upload_dir . $product['product_image']);
                    }
                    
                    $product_image = $new_filename;
                } else {
                    $error = "Failed to upload image. Please try again.";
                }
            }
        }
        
        // If no errors, update product in database
        if (empty($error)) {
            $update_query = "UPDATE products SET 
                product_name = ?, 
                generic_name = ?, 
                category = ?, 
                description = ?, 
                price = ?, 
                stock_quantity = ?, 
                expiry_date = ?, 
                product_image = ? 
                WHERE id = ? AND company_id = ?";
                
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssssddssii", 
                $product_name, 
                $generic_name, 
                $category, 
                $description, 
                $price, 
                $stock_quantity, 
                $expiry_date, 
                $product_image, 
                $product_id, 
                $company_id
            );
            
            if (mysqli_stmt_execute($update_stmt)) {
                $message = "Product updated successfully!";
                
                // Refresh product data
                mysqli_stmt_execute($check_stmt);
                $result = mysqli_stmt_get_result($check_stmt);
                $product = mysqli_fetch_assoc($result);
            } else {
                $error = "Failed to update product. Please try again.";
            }
        }
    }
}

// Fetch all categories for dropdown
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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Product - Pharmacy Management System</title>
  <link rel="stylesheet" href="../../css/manage_product_company.css">
  <style>
    a[href="manage_products.php"] {
  display: inline-block;
  padding: 10px 16px;
  background-color:rgb(255, 255, 255);
  color: #333;
  text-decoration: none;
  border-radius: 4px;
  font-weight: 600;
  margin-bottom: 16px;

  transition: all 0.3s ease;
}

a[href="manage_products.php"]:hover {
  background-color: #e5e5e5;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

a[href="manage_products.php"]::before {
  content: "←";
  margin-right: 8px;
}
    /* Additional styles for edit product page */
    .edit-form {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
      padding: 24px;
      margin-bottom: 24px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }
    
    .form-control {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    
    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }
    
    .form-row {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
    }
    
    .form-row .form-group {
      flex: 1;
      margin-bottom: 0;
    }
    
    .alert {
      padding: 12px 16px;
      border-radius: 4px;
      margin-bottom: 24px;
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
    
    .current-image {
      max-width: 200px;
      max-height: 200px;
      margin-top: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 4px;
    }
    
    .file-upload {
      margin-top: 8px;
    }
    
    .action-buttons {
      display: flex;
      gap: 12px;
      margin-top: 20px;
    }
    
    .btn-submit {
    background: linear-gradient(135deg, #66BB6A 0%, #43A047 100%);
    color: white;
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    display: block;
    width: fit-content;
    margin: 30px auto 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .btn-submit:hover {
    background: linear-gradient(135deg, #43A047 0%, #388E3C 100%);
    box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
  }
    
    .btn-cancel {
      background: #f1f1f1;
      color: #333;
      border: 1px solid #ddd;
      padding: 12px 24px;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .btn-cancel:hover {
      background: #e5e5e5;
    }
    .file-upload {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

/* Hide the default file input appearance */
#product_image {
  width: 0.1px;
  height: 0.1px;
  opacity: 0;
  overflow: hidden;
  position: absolute;
  z-index: -1;
}

/* Style the label that will act as our button */
#product_image + label {
  font-size: 16px;
  font-weight: 600;
  color: white;
  background-color: #3498db;
  display: inline-block;
  padding: 12px 24px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

/* Add hover effect */
#product_image + label:hover {
  background-color: #2980b9;
}

/* Add focus styling for accessibility */
#product_image:focus + label {
  outline: 2px solid #0078d7;
  outline-offset: 2px;
}

/* Style for the file name display (optional) */
.file-name {
  margin-left: 10px;
  font-size: 14px;
}
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="logo-icon">💊</span> BridgeRx Supply Hub
    </div>
    <div class="account-info">
      <span>Welcome, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest'; ?></span>
      <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'G'; ?></div>
    </div>
  </div>
    
  <div class="navbar">
    <button class="mobile-toggle" onclick="toggleMenu()">☰</button>
    <ul class="menu" id="mainMenu">
      <li class="menu-item">
        <button class="menu-btn">
          <span class="menu-icon">🏠</span> Home
        </button>
      </li>
      <li class="menu-item">
        <button class="menu-btn active">
          <span class="menu-icon">📦</span> Products
        </button>
        <div class="dropdown">
          <a href="add_products.php" class="dropdown-item">Add New Product</a>
          <a href="manage_products.php" class="dropdown-item active">Manage Products</a>
          <a href="view_products.php" class="dropdown-item">View Product Listings</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn">
          <span class="menu-icon">🛒</span> Orders
        </button>
        <div class="dropdown">
          <a href="../order/view_orders.php" class="dropdown-item">View Orders</a>
          <a href="../order/orders.php" class="dropdown-item">Update Order Status</a>
          
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn">
          <span class="menu-icon">💬</span> Inquiries
        </button>
        <div class="dropdown">
          <a href="../view_inquires.php" class="dropdown-item">View Inquiries</a>
          <a href="../inquires.php" class="dropdown-item">Respond to Inquiries</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn">
          <span class="menu-icon">⚙️</span> Settings
        </button>
        <div class="dropdown">
          <a href="../profile.php" class="dropdown-item">Profile Management</a>
          <a href="../payment.php" class="dropdown-item">Payment & Billing Details</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn">
          <span class="menu-icon">🔍</span> Support
        </button>
        <div class="dropdown">
          <a href="../contact_admin.php" class="dropdown-item">Contact Admin</a>
          
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn">
          <span class="menu-icon">🚪</span> Logout
        </button>
      </li>
    </ul>
  </div>
    
  <div class="container">
    <div class="content">
      <div class="page-header">
        <h1>Edit Product</h1>
        <p>Update the details of your pharmaceutical product.</p>
  
      </div>
      
      <!-- Success/Error Messages -->
      <?php if (!empty($message)): ?>
        <div class="alert alert-success">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>
      
      <!-- Edit Product Form -->
      <form method="POST" action="" enctype="multipart/form-data" class="edit-form">
        <div class="form-row">
          <div class="form-group">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
          </div>
          
          <div class="form-group">
            <label for="generic_name">Generic Name</label>
            <input type="text" id="generic_name" name="generic_name" class="form-control" value="<?php echo htmlspecialchars($product['generic_name']); ?>" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="form-control" required>
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
            
            <div id="new_category_container" style="display: none; margin-top: 10px;">
              <input type="text" id="new_category" name="new_category" class="form-control" placeholder="Enter new category">
            </div>
          </div>
          
          <div class="form-group">
            <label for="price">Price ($)</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
          </div>
          
          <div class="form-group">
            <label for="expiry_date">Expiry Date</label>
            <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="<?php echo htmlspecialchars($product['expiry_date']); ?>" required>
          </div>
        </div>
        
        <div class="form-group">
          <label for="description">Product Description</label>
          <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        
        <div class="form-group">
          <label for="product_image">Product Image</label>
          <?php if (!empty($product['product_image'])): ?>
            <div>
              <p>Current Image:</p>
              <img src="../../uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product Image" class="current-image">
            </div>
          <?php endif; ?>
          <div class="file-upload">
          <input type="file" id="product_image" name="product_image" accept="image/jpeg, image/png, image/jpg">
<label for="product_image">Choose Product Image</label>
<span class="file-name"></span>
          </div>
        </div>
        
        <div class="action-buttons">
          <button type="submit" class="btn-submit">Update Product</button>
         
        </div>
      </form>
    </div>
  </div>
    
  <script>
    document.getElementById('product_image').addEventListener('change', function(e) {
  // Get the file name
  let fileName = e.target.files[0].name;
  // Display file name next to the button
  document.querySelector('.file-name').textContent = fileName;
});
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
          window.location.href = '../logout.php';
        }
      });
    });
    
    // Show/hide new category input based on selection
    document.getElementById('category').addEventListener('change', function() {
      const newCategoryContainer = document.getElementById('new_category_container');
      if (this.value === 'other') {
        newCategoryContainer.style.display = 'block';
        document.getElementById('new_category').setAttribute('required', 'required');
      } else {
        newCategoryContainer.style.display = 'none';
        document.getElementById('new_category').removeAttribute('required');
      }
    });
    
    // Expiry date validation - ensure it's a future date
    document.getElementById('expiry_date').addEventListener('change', function() {
      const selectedDate = new Date(this.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Reset time part for proper comparison
      
      if (selectedDate < today) {
        alert('Warning: You are setting an expiry date that has already passed.');
      }
    });
  </script>
</body>
</html>