<?php
// Start session if not already started
session_start();

// Database connection
require_once '../api/db.php';

// Get company ID from session
$company_id = $_SESSION['user_id'];

// Check if an inquiry ID is provided
$inquiry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$inquiry = null;
$error_message = '';
$success_message = '';

// If an inquiry ID is provided, get the inquiry details
if ($inquiry_id > 0) {
    $query = "SELECT i.*, m.name as medical_name, m.email as medical_email 
              FROM inquiries i 
              JOIN medical_users m ON i.medical_id = m.id 
              WHERE i.id = ? AND i.supplier_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $inquiry_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $inquiry = $result->fetch_assoc();
    } else {
        $error_message = "Inquiry not found or you don't have permission to view it.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $reply = trim($_POST['reply']);
    $inquiry_id = (int)$_POST['inquiry_id'];
    
    // Validate input
    if (empty($reply)) {
        $error_message = "Reply cannot be empty.";
    } else {
        // Update the inquiry with the reply
        $query = "UPDATE inquiries 
                  SET reply = ?, reply_at = NOW(), status = 'replied' 
                  WHERE id = ? AND supplier_id = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $reply, $inquiry_id, $company_id);
        
        if ($stmt->execute()) {
            $success_message = "Your reply has been sent successfully.";
            
            // Refresh inquiry data
            $query = "SELECT i.*, m.name as medical_name, m.email as medical_email 
                      FROM inquiries i 
                      JOIN medical_users m ON i.medical_id = m.id 
                      WHERE i.id = ? AND i.supplier_id = ?";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $inquiry_id, $company_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $inquiry = $result->fetch_assoc();
        } else {
            $error_message = "Failed to send reply. Please try again.";
        }
    }
}

// Function to close an inquiry (used for the close button action)
if (isset($_GET['action']) && $_GET['action'] === 'close' && $inquiry_id > 0) {
    $query = "UPDATE inquiries SET status = 'closed' WHERE id = ? AND supplier_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $inquiry_id, $company_id);
    
    if ($stmt->execute()) {
        $success_message = "Inquiry has been closed successfully.";
        
        // Refresh inquiry data
        $query = "SELECT i.*, m.name as medical_name, m.email as medical_email 
                  FROM inquiries i 
                  JOIN medical_users m ON i.medical_id = m.id 
                  WHERE i.id = ? AND i.supplier_id = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $inquiry_id, $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $inquiry = $result->fetch_assoc();
    } else {
        $error_message = "Failed to close inquiry. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respond to Inquiries - BridgeRx Supply Hub</title>
    <link rel="stylesheet" href="../css/home_company.css">
    <style>
        .content {
            padding: 20px;
            margin-left: 250px; /* Adjust based on sidebar width */
            transition: margin-left 0.3s ease;
        }
        
        .content-full {
            margin-left: 60px;
        }
        
        .inquiry-details {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .inquiry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .inquiry-subject {
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #ffe082;
            color: #856404;
        }
        
        .status-replied {
            background-color: #c8e6c9;
            color: #155724;
        }
        
        .status-closed {
            background-color: #d6d8db;
            color: #383d41;
        }
        
        .inquiry-meta {
            margin-bottom: 15px;
            color: #666;
        }
        
        .inquiry-meta div {
            margin-bottom: 5px;
        }
        
        .inquiry-message {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .reply-form {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 150px;
            font-family: inherit;
            font-size: 1em;
        }
        
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        
        .btn-submit:hover {
            background-color: #45a049;
        }
        
        .btn-close {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-left: 10px;
        }
        
        .btn-close:hover {
            background-color: #5a6268;
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
        
        .buttons-container {
            display: flex;
            justify-content: flex-start;
            margin-top: 20px;
        }
        
        .reply-section {
            margin-top: 20px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }
        
        .reply-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .go-back {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50;
            text-decoration: none;
        }
        
        .go-back:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 15px;
            }
            
            .inquiry-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .status-badge {
                margin-top: 10px;
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
                <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'G'; ?></div>
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
                <button class="menu-btn active" onclick="toggleDropdown(this)">
                    <span class="menu-icon">💬</span>
                    <span class="menu-text">Inquiries</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown show">
                    <a href="#" class="dropdown-item" data-page="view_inquiries.php">View Inquiries</a>
                    <a href="#" class="dropdown-item active" data-page="inquires.php">Respond to <br>Inquiries</a>
                </div>
            </li>
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">⚙️</span>
                    <span class="menu-text">Settings</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="profile.php">Profile <br>Management</a>
       
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
    
    <div class="content" id="content">
        <div class="main-content">
            <a href="view_inquiries.php" class="go-back">← Back to All Inquiries</a>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($inquiry): ?>
                <div class="inquiry-details">
                    <div class="inquiry-header">
                        <div class="inquiry-subject"><?php echo htmlspecialchars($inquiry['subject']); ?></div>
                        <div class="status-badge status-<?php echo $inquiry['status']; ?>">
                            <?php echo ucfirst($inquiry['status']); ?>
                        </div>
                    </div>
                    
                    <div class="inquiry-meta">
                        <div><strong>From:</strong> <?php echo htmlspecialchars($inquiry['medical_name']); ?> (<?php echo htmlspecialchars($inquiry['medical_email']); ?>)</div>
                        <div><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($inquiry['created_at'])); ?></div>
                        <div><strong>Product ID:</strong> <?php echo $inquiry['supplier_id'] ? htmlspecialchars($inquiry['supplier_id']) : 'N/A'; ?></div>
                    </div>
                    
                    <div class="inquiry-message">
                        <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
                    </div>
                    
                    <?php if ($inquiry['reply']): ?>
                        <div class="reply-section">
                            <h3>Your Reply</h3>
                            <div class="inquiry-meta">
                                <div><strong>Replied on:</strong> <?php echo date('F j, Y, g:i a', strtotime($inquiry['reply_at'])); ?></div>
                            </div>
                            <div class="inquiry-message">
                                <?php echo nl2br(htmlspecialchars($inquiry['reply'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($inquiry['status'] !== 'closed'): ?>
                        <div class="buttons-container">
                            <?php if ($inquiry['status'] === 'pending'): ?>
                                <a href="?id=<?php echo $inquiry_id; ?>&action=close" class="btn-close" onclick="return confirm('Are you sure you want to close this inquiry without replying?');">Close Inquiry</a>
                            <?php elseif ($inquiry['status'] === 'replied'): ?>
                                <a href="?id=<?php echo $inquiry_id; ?>&action=close" class="btn-close" onclick="return confirm('Are you sure you want to close this inquiry?');">Close Inquiry</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($inquiry['status'] === 'pending'): ?>
                    <div class="reply-form">
                        <h2>Reply to Inquiry</h2>
                        <form method="POST" action="">
                            <input type="hidden" name="inquiry_id" value="<?php echo $inquiry_id; ?>">
                            
                            <div class="form-group">
                                <label for="reply">Your Reply:</label>
                                <textarea name="reply" id="reply" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="submit_reply" class="btn-submit">Send Reply</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h2>No Inquiry Selected</h2>
                    <p>Please select an inquiry from the list to view details.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Main JavaScript for Company Dashboard
        
        // On page load, initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize event listeners
            initializeEventListeners();
            
            // Auto-resize textarea
            const textarea = document.querySelector('textarea');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
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
    </script>
</body>
</html>