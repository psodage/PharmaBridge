<?php
// Start session if not already started
session_start();

// Check if admin is logged in


// Database connection
require_once("db_connect.php");

// Get admin ID from session
$admin_id = $_SESSION['admin'];

// Initialize message variables
$success_message = "";
$error_message = "";

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Sanitize inputs
    $first_name = mysqli_real_escape_string($conn, $_POST['firstName']);
    $last_name = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Update admin profile
    $update_query = "UPDATE admins SET 
                    first_name = '$first_name',
                    last_name = '$last_name',
                    email = '$email',
                    phone = '$phone'
                    WHERE admin_id = $admin_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile: " . mysqli_error($conn);
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['currentPassword'];
    $new_password = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];
    
    // Get current password from database
    $password_query = "SELECT password FROM admins WHERE admin_id = $admin_id";
    $password_result = mysqli_query($conn, $password_query);
    $admin_data = mysqli_fetch_assoc($password_result);
    
    // Verify current password
    if (password_verify($current_password, $admin_data['password'])) {
        // Check if new passwords match
        if ($new_password === $confirm_password) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $password_update = "UPDATE admins SET password = '$hashed_password' WHERE admin_id = $admin_id";
            if (mysqli_query($conn, $password_update)) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password: " . mysqli_error($conn);
            }
        } else {
            $error_message = "New passwords do not match!";
        }
    } else {
        $error_message = "Current password is incorrect!";
    }
}

// Update 2FA settings
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_2fa'])) {
    $twofa_option = mysqli_real_escape_string($conn, $_POST['2faOption']);
    
    $twofa_update = "UPDATE admins SET two_factor_auth = '$twofa_option' WHERE admin_id = $admin_id";
    
    if (mysqli_query($conn, $twofa_update)) {
        $success_message = "Two-factor authentication settings updated!";
    } else {
        $error_message = "Error updating 2FA settings: " . mysqli_error($conn);
    }
}

// Handle profile photo upload
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (in_array($_FILES['profile_photo']['type'], $allowed_types) && $_FILES['profile_photo']['size'] <= $max_size) {
        $upload_dir = "uploads/admin_photos/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $new_filename = $admin_id . "_" . time() . "_" . basename($_FILES['profile_photo']['name']);
        $target_file = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
            // Update database with new photo path
            $photo_update = "UPDATE admins SET profile_photo = '$target_file' WHERE admin_id = $admin_id";
            
            if (mysqli_query($conn, $photo_update)) {
                $success_message = "Profile photo updated successfully!";
            } else {
                $error_message = "Error updating profile photo in database: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Error uploading profile photo.";
        }
    } else {
        $error_message = "Invalid file. Please upload a JPG, PNG, or GIF file under 2MB.";
    }
}

// Fetch admin information
$query = "SELECT * FROM admins WHERE admin_id = $admin_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $admin = mysqli_fetch_assoc($result);
} else {
    $error_message = "Admin not found!";
    // Redirect to login if admin not found
    header("Location: ../logout.php");
    exit();
}

// Fetch recent activity logs
$activity_query = "SELECT * FROM admin_activity_logs 
                  WHERE admin_id = $admin_id 
                  ORDER BY activity_timestamp DESC 
                  LIMIT 5";
$activity_result = mysqli_query($conn, $activity_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile Management - Pharmacy System</title>
    <link rel="stylesheet" href="../css/home_admin.css">
    <style>
        /* Additional styles for success/error messages */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
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
</head>
<body class="admin-panel">
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
        <span><?php echo htmlspecialchars($admin['first_name']); ?></span>
        <div class="account-icon"><?php echo substr($admin['first_name'], 0, 1); ?></div>
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
        <a href="#" class="dropdown-item " data-page="user_management/manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item " data-page="user_management/manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="#" class="dropdown-item" data-page="user_management/manage-user-status.php">Approve / Suspend / Remove Users</a>
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
          <a href="#" class="dropdown-item active" data-page="admin-profile.php">Admin Profile Management</a>
  
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
    <div class="container">
        <div class="page-header">
            <h1>Admin Profile Management</h1>
            <p>View and update your admin profile information</p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-image">
                        <?php if (!empty($admin['profile_photo']) && file_exists($admin['profile_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($admin['profile_photo']); ?>" alt="Admin Avatar">
                        <?php else: ?>
                            <img src="images/admin-avatar.png" alt="Admin Avatar">
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data">
                            <input type="file" name="profile_photo" id="profile_photo" style="display: none;">
                            <button type="button" class="btn small change-photo" onclick="document.getElementById('profile_photo').click();">Change Photo</button>
                            <button type="submit" id="upload_btn" style="display: none;">Upload</button>
                        </form>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h2>
                        <p class="role"><?php echo htmlspecialchars($admin['role']); ?></p>
                        <p class="status">Active since: <?php echo date('M d, Y', strtotime($admin['created_at'])); ?></p>
                        <p class="last-login">Last login: <?php echo date('M d, Y \a\t h:i A', strtotime($admin['last_login'])); ?></p>
                    </div>
                </div>
                
                <div class="profile-body">
                    <form class="profile-form" method="post" action="">
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($admin['first_name']); ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($admin['last_name']); ?>" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" class="form-control">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn primary">Save Personal Info</button>
                            </div>
                        </div>
                    </form>
                        
                    <form class="profile-form" method="post" action="">
                        <div class="form-section">
                            <h3>Account Security</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <input type="password" id="currentPassword" name="currentPassword" placeholder="Enter current password" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" class="form-control">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn primary">Change Password</button>
                            </div>
                        </div>
                    </form>
                    
                    <form class="profile-form" method="post" action="">
                        <div class="form-section">
                            <h3>Two-Factor Authentication</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="2faOption">Two-Factor Authentication</label>
                                    <select id="2faOption" name="2faOption" class="form-control">
                                        <option value="sms" <?php echo ($admin['two_factor_auth'] == 'sms') ? 'selected' : ''; ?>>SMS Verification</option>
                                        <option value="email" <?php echo ($admin['two_factor_auth'] == 'email') ? 'selected' : ''; ?>>Email Verification</option>
                                        <option value="app" <?php echo ($admin['two_factor_auth'] == 'app') ? 'selected' : ''; ?>>Authentication App</option>
                                        <option value="none" <?php echo ($admin['two_factor_auth'] == 'none') ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_2fa" class="btn primary">Update 2FA Settings</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card activity-log">
                <div class="card-header">
                    <h2>Recent Activity</h2>
                </div>
                <div class="card-body">
                    <ul class="activity-list">
                        <?php if (mysqli_num_rows($activity_result) > 0): ?>
                            <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                                <li class="activity-item">
                                    <span class="activity-time"><?php echo date('M d, h:i A', strtotime($activity['activity_timestamp'])); ?></span>
                                    <span class="activity-icon <?php echo $activity['activity_type']; ?>">
                                        <?php 
                                        // Display different icons based on activity type
                                        $icon = '⚙️'; // Default icon
                                        switch ($activity['activity_type']) {
                                            case 'login':
                                                $icon = '🔑';
                                                break;
                                            case 'logout':
                                                $icon = '🚪';
                                                break;
                                            case 'edit':
                                                $icon = '✏️';
                                                break;
                                            case 'approve':
                                                $icon = '✅';
                                                break;
                                            case 'export':
                                                $icon = '📤';
                                                break;
                                            case 'settings':
                                                $icon = '⚙️';
                                                break;
                                        }
                                        echo $icon;
                                        ?>
                                    </span>
                                    <span class="activity-description"><?php echo htmlspecialchars($activity['activity_description']); ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="activity-item">
                                <span class="activity-description">No recent activity logged.</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="view-all">
                        <a href="view-all-activity.php" class="view-all-link">View All Activity</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Initialize event listeners
        initializeEventListeners();
        
        // Auto-submit form when file is selected
        document.getElementById('profile_photo').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('upload_btn').click();
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
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
          // Log the activity before logging out
          const xhttp = new XMLHttpRequest();
          xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
              window.location.href = "../logout.php";
            }
          };
          xhttp.open("POST", "log_activity.php", true);
          xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xhttp.send("activity_type=logout&description=User logged out");
        }
      }
    </script>
</body>
</html>