<?php
require '../db.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage-pharma-companies.php");
    exit;
}

$id = (int)$_GET['id'];

// Fetch company details
$query = "SELECT * FROM company_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-pharma-companies.php");
    exit;
}

$company = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {
    // Get form data
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $license_number = $conn->real_escape_string($_POST['license_number']);
    
    // Update company information
    $updateQuery = "UPDATE company_users SET name = ?, email = ?, license_number = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssi", $name, $email, $license_number, $id);
    
    // Handle license document upload if a new one is provided
    if (!empty($_FILES['license_document']['name'])) {
        $uploadDir = "../uploads/licenses/";
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['license_document']['name']);
        $targetFile = $uploadDir . $fileName;
        $uploadOk = 1;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        // Check file size
        if ($_FILES['license_document']['size'] > 5000000) { // 5MB max
            $errorMessage = "Sorry, your file is too large. Maximum size is 5MB.";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if (!in_array($fileType, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $errorMessage = "Sorry, only PDF, JPG, JPEG & PNG files are allowed.";
            $uploadOk = 0;
        }
        
        // If everything is ok, try to upload file
        if ($uploadOk) {
            if (move_uploaded_file($_FILES['license_document']['tmp_name'], $targetFile)) {
                // Update the database with the new file path
                $filePath = "uploads/licenses/" . $fileName;
                $updateFileQuery = "UPDATE company_users SET license_document = ? WHERE id = ?";
                $updateFileStmt = $conn->prepare($updateFileQuery);
                $updateFileStmt->bind_param("si", $filePath, $id);
                $updateFileStmt->execute();
                
                // Delete the old file if it exists
                if (!empty($company['license_document']) && file_exists("../" . $company['license_document'])) {
                    unlink("../" . $company['license_document']);
                }
            } else {
                $errorMessage = "Sorry, there was an error uploading your file.";
            }
        }
    }
    
    if ($updateStmt->execute()) {
        // Refresh company data after update
        $successMessage = "Company information updated successfully!";
        
        // Refresh company data
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
    } else {
        $errorMessage = "Error updating company information: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company - BridgeRx Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../../css/view_company.css">
    <style>
        /* Form styles */
        .edit-form {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
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
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #4a6fdc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 111, 220, 0.25);
        }
        
        .form-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .current-file {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 13px;
        }
        
        .action-btn {
            background-color: #4a6fdc;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .action-btn:hover {
            background-color: #3a5cbc;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .action-btn:active {
            transform: translateY(0);
            box-shadow: none;
        }
        
        .action-btn.save-btn {
            background-color: #28a745;
        }

        .action-btn.save-btn:hover {
            background-color: #218838;
        }
        
        .action-btn.back-btn {
            background-color: #6c757d;
        }

        .action-btn.back-btn:hover {
            background-color: #5a6268;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
    </style>
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
                <button class="menu-btn" data-page="home.php">
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
                    <a href="manage-pharma-companies.php" class="dropdown-item active">View & Manage Pharmaceutical Companies</a>
                    <a href="manage-medical-stores.php" class="dropdown-item">View & Manage Medical Stores</a>
                    <a href="manage-user-status.php" class="dropdown-item">Approve / Suspend / Remove Users</a>
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
                    <a href="#" class="dropdown-item" data-page="flag-products.php">Flag Fake or Unauthorized Products</a>
                    <a href="#" class="dropdown-item" data-page="review-orders.php">Review Order Activities</a>
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
                <h1 class="content-title">Edit Company</h1>
                <div class="content-actions">
                    <button class="action-btn back-btn" onclick="window.location.href='view_company.php?id=<?php echo $id; ?>'">Back to Details</button>
                </div>
            </div>
            
            <?php if (isset($successMessage)): ?>
                <div class="message success-message"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="message error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <div class="edit-form">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">Company Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($company['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($company['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="license_number" class="form-label">License Number</label>
                        <input type="text" name="license_number" id="license_number" class="form-control" value="<?php echo htmlspecialchars($company['license_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="license_document" class="form-label">License Document</label>
                        <input type="file" name="license_document" id="license_document" class="form-control">
                        <div class="form-hint">Accepted formats: PDF, JPG, JPEG, PNG (Max size: 5MB)</div>
                        
                        <?php if (!empty($company['license_document'])): ?>
                            <div class="current-file">
                                Current document: <?php echo htmlspecialchars(basename($company['license_document'])); ?>
                                <a href="../<?php echo $company['license_document']; ?>" target="_blank">(View)</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="update_company" class="action-btn save-btn">Save Changes</button>
                        <button type="button" class="action-btn back-btn" onclick="window.location.href='view_company.php?id=<?php echo $id; ?>'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Main JavaScript for Admin Dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize event listeners
            initializeEventListeners();
        });
        
        function initializeEventListeners() {
            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.toggle('show');
                    } else {
                        sidebar.classList.toggle('sidebar-collapsed');
                        content.classList.toggle('content-full');
                    }
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('sidebar-collapsed');
                    content.classList.remove('content-full');
                    sidebar.classList.remove('show');
                }
            });
        }
        
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
        
        function logout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "../logout.php";
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>