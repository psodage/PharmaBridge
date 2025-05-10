<?php 
session_start();
require_once('../api/db.php');

// Check if user is logged in and has the correct account type
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../../signin.php");
    exit();
}

// Get user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Initialize message variables
$success_message = "";
$error_message = "";

// Get user data from database
$user_data = [];
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM company_users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    } else {
        $error_message = "User data not found.";
    }
    $stmt->close();
}

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $license_number = trim($_POST['license_number']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $valid = true;
    
    if (empty($name) || empty($email) || empty($license_number)) {
        $error_message = "All fields marked with * are required.";
        $valid = false;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
        $valid = false;
    }
    
    // Check if email already exists (for a different user)
    if ($valid) {
        $stmt = $conn->prepare("SELECT id FROM company_users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This email is already in use by another account.";
            $valid = false;
        }
        $stmt->close();
    }
    
    // Check if license number already exists (for a different user)
    if ($valid) {
        $stmt = $conn->prepare("SELECT id FROM company'_users WHERE license_number = ? AND id != ?");
        $stmt->bind_param("si", $license_number, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This license number is already in use by another account.";
            $valid = false;
        }
        $stmt->close();
    }
    
    // Process license document if uploaded
    $license_document = $user_data['license_document']; // Keep existing by default
    if (isset($_FILES['license_document']) && $_FILES['license_document']['size'] > 0) {
        $target_dir = "uploads/licenses/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["license_document"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . $_FILES["license_document"]["name"];
        $target_file = $target_dir . $new_filename;
        
        // Check file type
        $allowed_types = array('pdf', 'jpg', 'jpeg', 'png');
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            $error_message = "Only PDF, JPG, JPEG & PNG files are allowed for license document.";
            $valid = false;
        }
        
        // Check file size (5MB max)
        if ($_FILES["license_document"]["size"] > 5000000) {
            $error_message = "License document file size should be less than 5MB.";
            $valid = false;
        }
        
        // Upload file if valid
        if ($valid && move_uploaded_file($_FILES["license_document"]["tmp_name"], $target_file)) {
            $license_document = $target_file;
        } else if ($_FILES["license_document"]["error"] != UPLOAD_ERR_NO_FILE) {
            $error_message = "Error uploading license document. Please try again.";
            $valid = false;
        }
    }
    
    // Update user information if validation passes
    if ($valid) {
        // Start with basic profile update
        $stmt = $conn->prepare("UPDATE company'_users SET name = ?, email = ?, license_number = ?, license_document = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $license_number, $license_document, $user_id);
        
        if ($stmt->execute()) {
            // Update session username
            $_SESSION['user_name'] = $name;
            
            $success_message = "Profile updated successfully.";
            
            // Refresh user data
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM company'_users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
        
        // Handle password change if requested
        if (!empty($current_password) && !empty($new_password)) {
            if ($new_password != $confirm_password) {
                $error_message = "New password and confirmation do not match.";
            } else {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM company'_users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
                
                if (password_verify($current_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE company'_users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Profile and password updated successfully.";
                    } else {
                        $error_message = "Error updating password: " . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Current password is incorrect.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Management - BridgeRx  Supply Hub</title>
  <link rel="stylesheet" href="../css/home_company.css">
  <style>
    :root {
  --primary-color: #4A6FDC;
  --light-gray: #f5f5f5;
  --sidebar-width: 250px;
}
    /* Profile page specific styles */
    .content-container {
 
     margin-top:40px;
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s;
    }
    
    .sidebar-collapsed + .content-container {
      margin-left: 70px;
    }
    
    @media (max-width: 768px) {
      .content-container {
        margin-left: 0;
      }
    }
    
    .profile-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 25px;
      max-width: 800px;
      margin: 0 auto;
    }
    
    .profile-header {
      margin-bottom: 25px;
      border-bottom: 1px solid #eee;
      padding-bottom: 15px;
    }
    
    .profile-title {
      font-size: 24px;
      font-weight: 600;
      color: #333;
      margin: 0;
    }
    
    .profile-subtitle {
      color: #666;
      margin-top: 5px;
    }
    
    .form-group {
      
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #444;
    }
    
    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    
    .form-section {
      margin-bottom: 30px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .btn-primary {
      background-color: #4A6FDC;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .btn-primary:hover {
      background-color: #3A5DC8;
    }
    
    .alert {
      padding: 15px;
      border-radius: 4px;
      margin-bottom: 20px;
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
    
    .required {
      color: red;
    }
    <style>
  /* Add these additional styles to align the form in landscape mode */
  .content-container {
    padding: 20px;
    margin-left: var(--sidebar-width);
    margin-top: 60px;
    background-color: var(--light-gray);
    min-height: calc(100vh - 60px);
  }
  
  .profile-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 25px 30px;
    max-width: 100%;
    margin: 0;
  }
  
  /* Form layout for landscape orientation */
  .form-section {
    margin-bottom: 30px;
  }
  
  /* Create a cleaner form layout */
  .form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
  }
  
  .form-column {
    flex: 1;
    padding: 0 15px;
    min-width: 250px;
  }
  
  /* Make inputs match the style in the image */
  .form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    height: 42px;
    box-sizing: border-box;
  }
  
  /* Adjust spacing for the form fields to match the image */
  .form-group {
    margin-bottom: 22px;
  }
  
  /* Fix the label styles */
  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #444;
    font-size: 0.9rem;
  }
  
  /* Submit button styling to match the image */
  .btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    margin-top: 15px;
  }
</style>
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="logo-icon">🏥</span> BridgeRx Supply Hub
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
                <button class="menu-btn" data-page="company.php">
                    <span class="menu-icon">🏠</span>
                    <span class="menu-text">Home</span>
                </button>
            </li>
            
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">📦</span>
                    <span class="menu-text">Products</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="products/add_products.php">Add New <br>Products</a>
                    <a href="#" class="dropdown-item" data-page="products/manage_products.php">Manage <br>Products</a>
                    <a href="#" class="dropdown-item" data-page="products/view_products.php">View Product <br>Listings</a>
                </div>
            </li>
           
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">🛒</span>
                    <span class="menu-text">Orders</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="order/view_orders.php">View <br>Orders</a>
                    <a href="#" class="dropdown-item" data-page="order/update_order_status.php">Update Order <br>Status</a>
                  
                </div>
            </li>
            <li class="menu-item">
                <button class="menu-btn " onclick="toggleDropdown(this)">
                    <span class="menu-icon">💬</span>
                    <span class="menu-text">Inquiries</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown show">
                    <a href="#" class="dropdown-item" data-page="view_inquiries.php">View Inquiries</a>
                    <a href="#" class="dropdown-item " data-page="inquires.php">Respond to <br>Inquiries</a>
                </div>
            </li>
            <li class="menu-item">
                <button class="menu-btn active" onclick="toggleDropdown(this)">
                    <span class="menu-icon">⚙️</span>
                    <span class="menu-text">Settings</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item active" data-page="profile.php">Profile <br>Management</a>
           
                </div>
            </li>
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">🔍</span>
                    <span class="menu-text">Support</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="contact_admin.php">Contact Admin</a>
                    
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
    
  
  <div class="content-container" id="content">
    <div class="profile-card">
      <div class="profile-header">
        <h1 class="profile-title">Profile Management</h1>
        <p class="profile-subtitle">Update your account information and preferences</p>
      </div>
      
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
          <?php echo $success_message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      
      <form action="profile.php" method="post" enctype="multipart/form-data">
  <div class="form-section">
    <h3 class="section-title">Account Information</h3>
    
    <div class="form-row">
      <div class="form-column">
        <div class="form-group">
          <label for="name">Medical Facility Name <span class="required">*</span></label>
          <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
          <label for="email">Email Address <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
          <label for="license_number">License Number <span class="required">*</span></label>
          <input type="text" id="license_number" name="license_number" class="form-control" value="<?php echo htmlspecialchars($user_data['license_number'] ?? ''); ?>" required>
        </div>
      </div>
      
      <div class="form-column">
        <div class="form-group">
          <label for="license_document">License Document (PDF, JPG, JPEG, PNG; Max 5MB)</label>
          <input type="file" id="license_document" name="license_document" class="form-control">
          <?php if (!empty($user_data['license_document'])): ?>
            <p class="file-info">Current document: <?php echo basename($user_data['license_document']); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div class="form-section">
    <h3 class="section-title">Change Password</h3>
    <p>Leave blank if you don't want to change your password</p>
    
    <div class="form-row">
      <div class="form-column">
        <div class="form-group">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" class="form-control">
        </div>
      </div>
      
      <div class="form-column">
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control">
        </div>
      </div>
    </div>
  </div>
  
  <div class="form-group">
    <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
  </div>
</form>
    </div>
  </div>
  
  <script>
    // Toggle sidebar
    document.addEventListener('DOMContentLoaded', function() {
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
      
      // Handle menu buttons and dropdown items clicks
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
    });
    
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