<?php
require '../db.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage-medical-stores.php");
    exit;
}

$id = (int)$_GET['id'];

// Fetch medical details
$query = "SELECT * FROM medical_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-medical-stores.php");
    exit;
}

$company = $result->fetch_assoc();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $conn->real_escape_string($_POST['status']);
    $companyId = (int)$_POST['company_id'];
    
    $updateQuery = "UPDATE medical_users SET approval_status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("si", $newStatus, $companyId);
    
    if ($updateStmt->execute()) {
        // Refresh medical data after update
        $company['approval_status'] = $newStatus;
        $statusMessage = "Status updated successfully!";
    } else {
        $errorMessage = "Error updating status: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Details - BridgeRx Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../../css/view_company.css">
    <style>
        /* Button styles for admin actions */
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

/* Specific styling for Back to List button */
.action-btn.back-btn {
    background-color: #6c757d;
}

.action-btn.back-btn:hover {
    background-color: #5a6268;
}

/* Specific styling for Update Status button */
.action-btn.update-btn {
    background-color: #28a745;
}

.action-btn.update-btn:hover {
    background-color: #218838;
}

/* Specific styling for Edit Details button */
.action-btn.edit-btn {
    background-color: #17a2b8;
}

.action-btn.edit-btn:hover {
    background-color: #138496;
}

/* Specific styling for Delete button */
.action-btn.delete-btn {
    background-color: #dc3545;
}

.action-btn.delete-btn:hover {
    background-color: #c82333;
}

/* Add space between buttons */
.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

/* Style for the status form */
.status-form {
    display: flex;
    align-items: center;
    gap: 15px;
}

.status-select {
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    flex: 1;
    max-width: 200px;
    font-size: 14px;
    background-color: #fff;
}

.status-select:focus {
    border-color: #4a6fdc;
    outline: none;
    box-shadow: 0 0 0 2px rgba(74, 111, 220, 0.25);
}
        /* Additional styles for this page */
        .company-details {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .detail-label {
            width: 200px;
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .status-controls {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .status-form {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .status-select {
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            flex: 1;
            max-width: 200px;
        }
        
        .license-document {
            margin-top: 20px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .document-preview {
            margin-top: 15px;
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .document-preview img {
            max-width: 100%;
            display: block;
        }
        
        .document-link {
            display: inline-block;
            margin-top: 10px;
            color: #0066cc;
            text-decoration: none;
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
            gap: 10px;
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
        <!-- Same sidebar as in manage-pharma-stores.php -->
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
                    <a href="manage-pharma-companies.php" class="dropdown-item ">View & Manage Pharmaceutical Companies</a>
                    <a href="manage-medical-stores.php" class="dropdown-item active">View & Manage Medical Stores</a>
                    <a href="manage-user-status.php" class="dropdown-item">Approve / Suspend / Remove Users</a>
                </div>
            </li>
            
            <!-- Other menu items (same as manage-pharma-stores.php) -->
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
                <h1 class="content-title">Medical Details</h1>
                <div class="content-actions">
                <button class="action-btn back-btn" onclick="window.location.href='manage-medical-stores.php'">Back to List</button>

                </div>
            </div>
            
            <?php if (isset($statusMessage)): ?>
                <div class="message success-message"><?php echo $statusMessage; ?></div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="message error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <div class="company-details">
                <h2><?php echo htmlspecialchars($company['name']); ?></h2>
                
                <div class="detail-row">
                    <div class="detail-label">Medical ID:</div>
                    <div class="detail-value">PHR<?php echo sprintf('%03d', $company['id']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($company['email']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">License Number:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($company['license_number']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Registration Date:</div>
                    <div class="detail-value"><?php echo date('F d, Y', strtotime($company['created_at'])); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Current Status:</div>
                    <div class="detail-value">
                        <?php 
                        $statusClass = '';
                        switch (strtolower($company['approval_status'])) {
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
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo ucfirst($company['approval_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="license-document">
                    <h3>License Document</h3>
                    <?php if (!empty($company['license_document'])): ?>
                        <div class="document-preview">
                            <?php 
                            $ext = pathinfo($company['license_document'], PATHINFO_EXTENSION);
                            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): 
                            ?>
                                <img src="admin/../../<?php echo $company['license_document']; ?>" alt="License Document">
                            <?php else: ?>
                                <p>Document file: <?php echo htmlspecialchars($company['license_document']); ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="admin/../../uploads/licenses/<?php echo $company['license_document']; ?>" class="document-link" target="_blank">View Full Document</a>
                    <?php else: ?>
                        <p>No document uploaded</p>
                    <?php endif; ?>
                </div>
                
                <div class="status-controls">
                    <h3>Update Status</h3>
                    <form method="post" class="status-form">
                        <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                        <select name="status" class="status-select">
                            <option value="pending" <?php echo ($company['approval_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo ($company['approval_status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="declined" <?php echo ($company['approval_status'] == 'declined') ? 'selected' : ''; ?>>Declined</option>
                        </select>
                        <button type="submit" name="update_status" class="action-btn update-btn">Update Status</button>
                    </form>
                </div>
                
                <div class="action-buttons">
                <button class="action-btn edit-btn" onclick="window.location.href='edit_medical.php?id=<?php echo $company['id']; ?>'">Edit Details</button>

                    <?php if ($company['approval_status'] == 'declined'): ?>
                        <button class="action-btn delete-btn" onclick="deleteCompany(<?php echo $company['id']; ?>)">Delete Store</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Same sidebar toggle and menu functions as manage-pharma-stores.php
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
        
        function deleteCompany(id) {
            if (confirm('Are you sure you want to delete this medical? This action cannot be undone.')) {
                // AJAX call to delete_medical.php
                fetch('delete_medical.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    window.location.href = 'manage-medical-stores.php';
                });
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>